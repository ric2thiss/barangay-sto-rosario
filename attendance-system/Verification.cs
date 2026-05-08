using DPUruNet;
using System;
using System.Collections.Generic;
using System.Net.Http;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;
using System.Diagnostics;

namespace UareUSampleCSharp
{
    public partial class Verification : Form
    {
        // ⚠️ CONFIGURATION: Base URL of the attendance system
        // Change this if your folder name or domain is different
        // Example: "http://localhost/attendance" or "http://localhost/attendance-system"
        private const string BASE_URL = "http://localhost/attendance-system";

        public Form_Main _sender;

        private const int PROBABILITY_ONE = 0x7fffffff;
        private Fmd firstFinger;
        private int count;

        public Verification()
        {
            InitializeComponent();
        }

        private void Verification_Load(object sender, EventArgs e)
        {
            txtVerify.Text = string.Empty;
            firstFinger = null;
            count = 0;

            SendMessage(Action.SendMessage, "Place a finger on the reader.");

            if (!_sender.OpenReader() || !_sender.StartCaptureAsync(this.OnCaptured))
                this.Close();
        }

        // 🔹 Fetch fingerprint templates from Laravel
        private async Task<List<(string employeeId, Fmd template)>> FetchTemplatesAsync()
        {
            var client = new HttpClient();
            // OLD ENDPOINT (backward compatible): http://localhost/attendance-system/api/services.php?resource=templates
            // NEW ENDPOINT: http://localhost/attendance-system/api/templates/index.php
            var response = await client.GetAsync(BASE_URL + "/api/templates/index.php");
            response.EnsureSuccessStatusCode();

            var json = await response.Content.ReadAsStringAsync();
            var parsed = JArray.Parse(json);

            var templates = new List<(string, Fmd)>();

            foreach (var item in parsed)
            {
                string employeeId = item["employee_id"].ToString();
                string base64Template = item["template"].ToString();

                try
                {
                    string xmlTemplate = Encoding.UTF8.GetString(Convert.FromBase64String(base64Template));
                    Fmd fmdTemplate = Fmd.DeserializeXml(xmlTemplate);
                    templates.Add((employeeId, fmdTemplate));
                }
                catch (Exception ex)
                {
                    SendMessage(Action.SendMessage, $"⚠️ Failed to deserialize template for {employeeId}: {ex.Message}");
                }
            }

            return templates;
        }

        // 🔹 Send verification result to Laravel
        private async Task SendVerificationAsync(string employeeId)
        {
            try
            {
                var client = new HttpClient();

                var payload = new
                {
                    employee_id = employeeId,
                    status = "verified",
                    device_id = "KIOSK-01",
                    timestamp = DateTime.UtcNow.ToString("o")
                };

                var json = JsonConvert.SerializeObject(payload);
                var content = new StringContent(json, Encoding.UTF8, "application/json");
                //http://localhost:8000/api/biometric/verify
                var response = await client.PostAsync(BASE_URL + "/biometricVerification.php", content);
                var responseString = await response.Content.ReadAsStringAsync();


                //SendMessage(Action.SendMessage, "📡 Sent verification to Laravel: " + responseString);
            }
            catch (Exception ex)
            {
                MessageBox.Show("Failed to send verification: " + ex.Message, "Network Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private async void OnCaptured(CaptureResult captureResult)
        {
            try
            {
                if (!_sender.CheckCaptureResult(captureResult))
                    return;

                SendMessage(Action.SendMessage, "A finger was captured.");

                var resultConversion = FeatureExtraction.CreateFmdFromFid(captureResult.Data, Constants.Formats.Fmd.ANSI);
                if (resultConversion.ResultCode != Constants.ResultCode.DP_SUCCESS)
                {
                    _sender.Reset = true;
                    MessageBox.Show("Failed to extract features from fingerprint.", "Extraction Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                if (count == 0)
                {
                    firstFinger = resultConversion.Data;
                    count++;
                    SendMessage(Action.SendMessage, "Now place the same or a different finger on the reader.");
                }
                else if (count == 1)
                {
                    var secondFinger = resultConversion.Data;
                    SendMessage(Action.SendMessage, "Verifying against registered templates...");

                    var templates = await FetchTemplatesAsync();
                    bool matched = false;

                    foreach (var (employeeId, storedTemplate) in templates)
                    {
                        var compareResult = Comparison.Compare(secondFinger, 0, storedTemplate, 0);

                        if (compareResult.ResultCode == Constants.ResultCode.DP_SUCCESS &&
                            compareResult.Score < (PROBABILITY_ONE / 10000)) // threshold
                        {
                            string message = $"✅ Match found!\nEmployee ID: {employeeId}";
                            MessageBox.Show(message, "Verification Success", MessageBoxButtons.OK, MessageBoxIcon.Information);

                            // 🔹 Step 1: Send secure POST to PHP verify.php
                            var client = new HttpClient();
                            var payload = new
                            {
                                employee_id = employeeId,
                                status = "verified",
                                token = "MY_SECRET_KEY" // shared secret
                            };
                            var json = JsonConvert.SerializeObject(payload);
                            var content = new StringContent(json, Encoding.UTF8, "application/json");

                            var response = await client.PostAsync(BASE_URL + "/verify.php", content);
                            var confirmToken = await response.Content.ReadAsStringAsync();
                            confirmToken = confirmToken.Trim();

                            // 🔹 Step 2: Open confirmation page in browser with token
                            Process.Start(new ProcessStartInfo
                            {
                                FileName = $"{BASE_URL}/verify.php?confirm={confirmToken}",
                                UseShellExecute = true
                            });


                            // 🔹 Step 3: Notify Laravel too
                            await SendVerificationAsync(employeeId);

                            matched = true;
                            break;
                        }
                    }

                    if (!matched)
                    {
                        MessageBox.Show("❌ No match found.", "Verification Failed", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    }

                    count = 0;
                    SendMessage(Action.SendMessage, "Place a finger on the reader.");
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error: " + ex.Message, "Exception", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void btnBack_Click(object sender, EventArgs e)
        {
            this.Close();
        }

        private void Verification_Closed(object sender, EventArgs e)
        {
            _sender.CancelCaptureAndCloseReader(this.OnCaptured);
        }

        #region SendMessage
        private enum Action
        {
            SendMessage
        }

        private delegate void SendMessageCallback(Action action, string payload);

        private void SendMessage(Action action, string payload)
        {
            try
            {
                if (txtVerify.InvokeRequired)
                {
                    SendMessageCallback d = SendMessage;
                    this.Invoke(d, new object[] { action, payload });
                }
                else
                {
                    if (action == Action.SendMessage)
                    {
                        txtVerify.Text += payload + "\r\n\r\n";
                        txtVerify.SelectionStart = txtVerify.TextLength;
                        txtVerify.ScrollToCaret();
                    }
                }
            }
            catch { }
        }
        #endregion
    }
}
