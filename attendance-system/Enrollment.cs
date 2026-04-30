using DPUruNet;
using Newtonsoft.Json;
using System;
using System.Collections.Generic;
using System.Net.Http;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace UareUSampleCSharp
{
    public partial class Enrollment : Form
    {
        // ⚠️ CONFIGURATION: Base URL of the attendance system
        // Change this if your folder name or domain is different
        // Example: "http://localhost/attendance" or "http://localhost/attendance-system"
        private const string BASE_URL = "http://localhost/attendance-system";

        public Form_Main _sender;

        List<Fmd> preenrollmentFmds;
        int count;

        private string _employeeId;
        private string _residentId;
        private Reader _reader;

        public Enrollment()
        {
            InitializeComponent();
            AutoSelectReader();
        }

        public Enrollment(string employeeId) : this(employeeId, null)
        {
        }

        public Enrollment(string employeeId, string residentId) : this()
        {
            if (!string.IsNullOrWhiteSpace(employeeId))
            {
                _employeeId = employeeId;
            }
            if (!string.IsNullOrWhiteSpace(residentId))
            {
                _residentId = residentId;
            }
        }

        private void AutoSelectReader()
        {
            try
            {
                var readers = ReaderCollection.GetReaders();
                if (readers != null && readers.Count > 0)
                {
                    _reader = readers[0]; // pick the first reader
                    SendMessage(Action.SendMessage, $"Auto-selected reader: {_reader.Description.Name}");
                }
                else
                {
                    MessageBox.Show("No fingerprint reader detected.", "Reader Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    this.Close();
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("Failed to auto-select reader: " + ex.Message, "Reader Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                this.Close();
            }
        }

        private Reader _directReader;

        private void Enrollment_Load(object sender, EventArgs e)
        {
            try
            {
                if (txtEnroll != null)
                {
                    txtEnroll.Text = string.Empty;
                }

                preenrollmentFmds = new List<Fmd>();
                count = 0;

                SendMessage(Action.SendMessage, "Place a finger on the reader.");

                if (_sender != null)
                {
                    // Normal flow (launched from Form_Main)
                    if (!_sender.OpenReader())
                    {
                        MessageBox.Show("Failed to open reader (Form_Main flow).");
                        this.Close();
                        return;
                    }

                    if (!_sender.StartCaptureAsync(this.OnCaptured))
                    {
                        MessageBox.Show("Failed to start capture (Form_Main flow).");
                        this.Close();
                        return;
                    }
                }
                else
                {
                    // Direct flow (launched via registry)
                    var readers = ReaderCollection.GetReaders();
                    if (readers == null || readers.Count == 0)
                    {
                        MessageBox.Show("No fingerprint reader detected.");
                        this.Close();
                        return;
                    }

                    _directReader = readers[0];
                    var result = _directReader.Open(DPUruNet.Constants.CapturePriority.DP_PRIORITY_COOPERATIVE);

                    if (result != DPUruNet.Constants.ResultCode.DP_SUCCESS)
                    {
                        MessageBox.Show("Failed to open reader. Result: " + result);
                        this.Close();
                        return;
                    }

                    if (_directReader.Capabilities == null ||
                        _directReader.Capabilities.Resolutions == null ||
                        _directReader.Capabilities.Resolutions.Length == 0)
                    {
                        MessageBox.Show("Reader capabilities not available.");
                        this.Close();
                        return;
                    }

                    _directReader.On_Captured += OnCaptured;

                    var captureResult = _directReader.CaptureAsync(
                        DPUruNet.Constants.Formats.Fid.ANSI,
                        DPUruNet.Constants.CaptureProcessing.DP_IMG_PROC_DEFAULT,
                        _directReader.Capabilities.Resolutions[0]
                    );

                    if (captureResult != DPUruNet.Constants.ResultCode.DP_SUCCESS)
                    {
                        MessageBox.Show("Failed to start capture. Error: " + captureResult);
                        this.Close();
                        return;
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error in Enrollment_Load: " + ex.ToString(),
                    "Initialization Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                this.Close();
            }
        }

        private async Task SendEnrollToApiAsync(string templateBase64)
        {
            // Determine which ID to use (employee_id takes precedence for backward compatibility)
            bool isEmployee = !string.IsNullOrWhiteSpace(_employeeId);
            bool isResident = !string.IsNullOrWhiteSpace(_residentId);
            
            if (!isEmployee && !isResident)
            {
                MessageBox.Show("⚠️ Enrollment cancelled: No ID provided from browser.",
                    "Cancelled", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            object data;
            string successUrl;
            
            if (isEmployee)
            {
                data = new
                {
                    employee_id = _employeeId,
                    template = templateBase64
                };
                successUrl = BASE_URL + "/biometric-success.php?employee_id=" + _employeeId;
            }
            else
            {
                data = new
                {
                    resident_id = _residentId,
                    template = templateBase64
                };
                successUrl = BASE_URL + "/biometric-success.php?resident_id=" + _residentId;
            }

            var json = JsonConvert.SerializeObject(data);
            var content = new StringContent(json, Encoding.UTF8, "application/json");

            using (var client = new HttpClient())
            {
                try
                {
                    //PHP endpoint = http://localhost/attendance-system/enroll.php
                    //Laravel Endpoint = http://127.0.0.1:8000/api/fingerprint/enroll

                    var response = await client.PostAsync(BASE_URL + "/enroll.php", content);

                    if (response.IsSuccessStatusCode)
                    {
                        MessageBox.Show("✅ Fingerprint enrolled successfully.", "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                        System.Diagnostics.Process.Start(successUrl);
                    }
                    else
                    {
                        string responseBody = await response.Content.ReadAsStringAsync();
                        MessageBox.Show(" Fingerprint already enrolled.", "Failed", MessageBoxButtons.OK, MessageBoxIcon.Information);
                        SendMessage(Action.SendMessage, $"❌ Enrollment failed. Status: {response.StatusCode}\n{responseBody}");
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"❌ Error sending to server: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private async void OnCaptured(CaptureResult captureResult)
        {
            try
            {
                if (_sender != null && !_sender.CheckCaptureResult(captureResult)) return;

                count++;

                var resultConversion = FeatureExtraction.CreateFmdFromFid(captureResult.Data, Constants.Formats.Fmd.ANSI);

                SendMessage(Action.SendMessage, $"A finger was captured.\r\nCount: {count}");

                if (resultConversion.ResultCode != Constants.ResultCode.DP_SUCCESS)
                {
                    if (_sender != null) _sender.Reset = true;
                    throw new Exception(resultConversion.ResultCode.ToString());
                }

                preenrollmentFmds.Add(resultConversion.Data);

                if (count >= 4)
                {
                    var resultEnrollment = DPUruNet.Enrollment.CreateEnrollmentFmd(Constants.Formats.Fmd.ANSI, preenrollmentFmds);

                    if (resultEnrollment.ResultCode == Constants.ResultCode.DP_SUCCESS)
                    {
                        SendMessage(Action.SendMessage, "✅ An enrollment FMD was successfully created.");

                        string xmlString = Fmd.SerializeXml(resultEnrollment.Data);
                        byte[] xmlBytes = Encoding.UTF8.GetBytes(xmlString);
                        string fmdBase64 = Convert.ToBase64String(xmlBytes);

                        bool isDuplicate = await CheckIfFingerprintAlreadyExistsAsync(resultEnrollment.Data);
                        if (isDuplicate)
                        {
                            MessageBox.Show("❌ This fingerprint is already registered. Enrollment cancelled.", "Duplicate", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                            preenrollmentFmds.Clear();
                            count = 0;
                            return;
                        }

                        if (string.IsNullOrWhiteSpace(_employeeId) && string.IsNullOrWhiteSpace(_residentId))
                        {
                            MessageBox.Show("⚠️ Enrollment cancelled: No ID provided from browser.",
                                "Cancelled", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                            return;
                        }

                        await SendEnrollToApiAsync(fmdBase64);

                        preenrollmentFmds.Clear();
                        count = 0;
                        return;
                    }
                    else if (resultEnrollment.ResultCode == Constants.ResultCode.DP_ENROLLMENT_INVALID_SET)
                    {
                        MessageBox.Show("Enrollment was unsuccessful. Please try again.", "Enrollment Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                        SendMessage(Action.SendMessage, "Place a finger on the reader.");
                        preenrollmentFmds.Clear();
                        count = 0;
                        return;
                    }
                }

                SendMessage(Action.SendMessage, "Now place the same finger on the reader.");
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error: " + ex.Message, "Exception", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private async Task<bool> CheckIfFingerprintAlreadyExistsAsync(Fmd newFmd)
        {
            using (var client = new HttpClient())
            {
                try
                {
                    // OLD ENDPOINT (backward compatible): http://localhost/attendance-system/api/services.php?resource=templates
                    // NEW ENDPOINT: http://localhost/attendance-system/api/templates/index.php
                    // Laravel endpoint = http://127.0.0.1:8000/api/templates

                    var response = await client.GetAsync(BASE_URL + "/api/templates/index.php");
                    response.EnsureSuccessStatusCode();

                    var responseBody = await response.Content.ReadAsStringAsync();
                    var templates = JsonConvert.DeserializeObject<List<TemplateDto>>(responseBody);

                    List<Fmd> existingFmds = new List<Fmd>();

                    foreach (var tpl in templates)
                    {
                        try
                        {
                            byte[] xmlBytes = Convert.FromBase64String(tpl.template);
                            string xml = Encoding.UTF8.GetString(xmlBytes);
                            Fmd existingFmd = Fmd.DeserializeXml(xml);
                            existingFmds.Add(existingFmd);
                        }
                        catch
                        {
                            SendMessage(Action.SendMessage, $"⚠️ Failed to process one template: {tpl.employee_id}");
                        }
                    }

                    if (existingFmds.Count > 0)
                    {
                        IdentifyResult identifyResult = Comparison.Identify(newFmd, 0, existingFmds.ToArray(), 36000, 1);
                        if (identifyResult.ResultCode == Constants.ResultCode.DP_SUCCESS && identifyResult.Indexes.Length > 0)
                        {
                            string matchedId = templates[identifyResult.Indexes[0][0]].employee_id;
                            SendMessage(Action.SendMessage, $"❗This fingerprint already belongs to employee: {matchedId}");
                            return true;
                        }
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show("Failed to check existing fingerprints: " + ex.Message, "Check Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }

            return false;
        }

        public class TemplateDto
        {
            public string employee_id { get; set; }
            public string template { get; set; }
        }

        private void btnBack_Click(object sender, EventArgs e)
        {
            this.Close();
        }

        private void Enrollment_Closed(object sender, EventArgs e)
        {
            _sender?.CancelCaptureAndCloseReader(this.OnCaptured);
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
                if (this.txtEnroll.InvokeRequired)
                {
                    SendMessageCallback d = new SendMessageCallback(SendMessage);
                    this.Invoke(d, new object[] { action, payload });
                }
                else
                {
                    switch (action)
                    {
                        case Action.SendMessage:
                            txtEnroll.Text += payload + "\r\n\r\n";
                            txtEnroll.SelectionStart = txtEnroll.TextLength;
                            txtEnroll.ScrollToCaret();
                            break;
                    }
                }
            }
            catch (Exception)
            {
                // Ignore safely
            }
        }
        #endregion
    }
}

