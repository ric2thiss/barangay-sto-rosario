using DPUruNet;
using System;
using System.Collections.Generic;
using System.Net.Http;
using System.Net.WebSockets;
using System.Text;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Forms;
using Newtonsoft.Json.Linq;

namespace UareUSampleCSharp
{
    public class ServerResponse
    {
        public string message { get; set; }
    }

    public partial class Identification : Form
    {
        // ⚠️ CONFIGURATION: Base URL of the attendance system
        // Change this if your folder name or domain is different
        // Example: "http://localhost/attendance" or "http://localhost/attendance-system"
        private const string BASE_URL = "http://localhost/attendance-system";

        public Form_Main _sender;
        private Fmd anyFinger;
        private int count;
        private ClientWebSocket wsClient; // ✅ Added WebSocket client

        public Identification()
        {
            InitializeComponent();
        }

        private async void Identification_Load(object sender, EventArgs e)
        {
            txtIdentify.Text = string.Empty;
            anyFinger = null;
            count = 0;

            SendMessage(Action.SendMessage, "Place any finger on the reader.");

            if (!_sender.OpenReader() || !_sender.StartCaptureAsync(OnCaptured))
            {
                this.Close();
            }

            // await ConnectWebSocketAsync(); // ✅ Connect to WebSocket server
        }

        private async void OnCaptured(CaptureResult captureResult)
        {
            try
            {
                if (!_sender.CheckCaptureResult(captureResult)) return;

                SendMessage(Action.SendMessage, "A finger was captured.");

                var resultConversion = FeatureExtraction.CreateFmdFromFid(captureResult.Data, Constants.Formats.Fmd.ANSI);
                if (resultConversion.ResultCode != Constants.ResultCode.DP_SUCCESS)
                {
                    _sender.Reset = true;
                    throw new Exception(resultConversion.ResultCode.ToString());
                }

                anyFinger = resultConversion.Data;

                using (var client = new HttpClient())
                {
                    // OLD ENDPOINT (backward compatible): http://localhost/attendance-system/api/services.php?resource=templates
                    // NEW ENDPOINT: http://localhost/attendance-system/api/templates/index.php
                    var response = await client.GetAsync(BASE_URL + "/api/templates/index.php");
                    var jArray = JArray.Parse(await response.Content.ReadAsStringAsync());

                    var storedTemplates = new List<StoredTemplate>();
                    foreach (var jToken in jArray)
                    {
                        storedTemplates.Add(new StoredTemplate
                        {
                            employee_id = jToken["employee_id"]?.ToString(),
                            template = jToken["template"]?.ToString()
                        });
                    }

                    bool matchFound = false;
                    SendMessage(Action.SendMessage, $"Current PC time: {DateTime.Now:HH:mm:ss}");

                    foreach (var item in storedTemplates)
                    {
                        try
                        {
                            byte[] storedBytes = Convert.FromBase64String(item.template);
                            string storedXml = System.Text.Encoding.UTF8.GetString(storedBytes);
                            Fmd storedFmd = Fmd.DeserializeXml(storedXml);

                            var compareResult = Comparison.Compare(anyFinger, 0, storedFmd, 0);

                            if (compareResult.ResultCode == Constants.ResultCode.DP_SUCCESS &&
                                compareResult.Score < (0x7fffffff / 100000))
                            {
                                // Step 1: Determine active time window
                                // OLD ENDPOINT (backward compatible): http://localhost/attendance-system/api/services.php?resource=attendance-windows
                                // NEW ENDPOINT: http://localhost/attendance-system/api/attendance/windows.php
                                var timeWindowResponse = await client.GetAsync(BASE_URL + "/api/attendance/windows.php");
                                var windows = JArray.Parse(JObject.Parse(await timeWindowResponse.Content.ReadAsStringAsync())["windows"].ToString());

                                DateTime now = DateTime.Now;
                                string currentWindow = null;

                                foreach (var win in windows)
                                {
                                    var start = TimeSpan.Parse(win["start"].ToString());
                                    var end = TimeSpan.Parse(win["end"].ToString());

                                    if (now.TimeOfDay >= start && now.TimeOfDay <= end)
                                    {
                                        currentWindow = win["label"].ToString();
                                        break;
                                    }
                                }

                                if (currentWindow == null)
                                {
                                    await SendErrorMessageAsync("No active attendance window found. Please try again later.", "no_active_window");
                                    return;
                                }

                                var attendanceContent = new FormUrlEncodedContent(new Dictionary<string, string>
                                {
                                    { "employee_id", item.employee_id },
                                    { "window", currentWindow }
                                });

                                // OLD ENDPOINT (backward compatible): http://localhost/attendance-system/api/services.php?resource=attendances
                                // NEW ENDPOINT: http://localhost/attendance-system/api/attendance/index.php
                                var logResponse = await client.PostAsync(BASE_URL + "/api/attendance/index.php", attendanceContent);
                                string logResult = await logResponse.Content.ReadAsStringAsync();

                                if (logResponse.StatusCode == System.Net.HttpStatusCode.Conflict)
                                {
                                    var conflictJson = JObject.Parse(logResult);
                                    var errorMsg = conflictJson["error"]?.ToString() ?? $"Attendance already logged for Employee ID: {item.employee_id}.";
                                    await SendErrorMessageAsync(errorMsg, "already_logged");
                                }
                                else if (logResponse.IsSuccessStatusCode)
                                {
                                    var logJson = JObject.Parse(logResult);
                                    var message = logJson["message"]?.ToString() ?? "Attendance logged successfully.";
                                    
                                    // Connect to WebSocket
                                    await ConnectWebSocketAsync();
                                    
                                    if (wsClient != null && wsClient.State == WebSocketState.Open)
                                    {
                                        // Fetch all attendance data to get the latest with employee/resident info
                                        // OLD ENDPOINT (backward compatible): http://localhost/attendance-system/api/services.php?resource=attendances
                                        // NEW ENDPOINT: http://localhost/attendance-system/api/attendance/index.php
                                        var attendanceDataResponse = await client.GetAsync(BASE_URL + "/api/attendance/index.php");
                                        
                                        if (attendanceDataResponse.IsSuccessStatusCode)
                                        {
                                            string attendanceDataJson = await attendanceDataResponse.Content.ReadAsStringAsync();
                                            var attendanceData = JObject.Parse(attendanceDataJson);
                                            
                                            // Send the full attendance data via WebSocket
                                            var websocketMessage = new JObject
                                            {
                                                ["type"] = "attendance_update",
                                                ["data"] = attendanceData
                                            };
                                            
                                            var jsonBytes = Encoding.UTF8.GetBytes(websocketMessage.ToString());
                                            await wsClient.SendAsync(new ArraySegment<byte>(jsonBytes), WebSocketMessageType.Text, true, CancellationToken.None);
                                            SendMessage(Action.SendMessage, $"✅ Attendance data sent to WebSocket: {item.employee_id}");
                                        }
                                        else
                                        {
                                            SendMessage(Action.SendMessage, $"⚠️ Failed to fetch attendance data: {attendanceDataResponse.StatusCode}");
                                        }
                                    }
                                    //MessageBox.Show(message, $"✅ Match Found - {item.employee_id}", MessageBoxButtons.OK, MessageBoxIcon.Information);
                                }
                                else
                                {
                                    var errorJson = JObject.Parse(logResult);
                                    var errorMsg = errorJson["error"]?.ToString() ?? $"Server error: {logResponse.StatusCode}";
                                    await SendErrorMessageAsync(errorMsg, "server_error");
                                }

                                matchFound = true;
                                break;
                            }
                        }
                        catch (Exception ex)
                        {
                            SendMessage(Action.SendMessage, $"⚠️ Failed to process template for {item.employee_id}: {ex.Message}");
                        }
                    }

                    if (!matchFound)
                    {
                        await SendErrorMessageAsync("No match found. Please try again.", "no_match");
                    }
                }

                SendMessage(Action.SendMessage, "Place another finger on the reader to match again.");
            }
            catch (Exception ex)
            {
                await SendErrorMessageAsync($"Error: {ex.Message}", "exception");
            }
        }

        private void btnBack_Click(object sender, EventArgs e)
        {
            this.Close();
        }

        private async void Identification_Closed(object sender, EventArgs e)
        {
            _sender.CancelCaptureAndCloseReader(OnCaptured);
            
            // Close WebSocket connection if open
            if (wsClient != null && wsClient.State == WebSocketState.Open)
            {
                try
                {
                    await wsClient.CloseAsync(WebSocketCloseStatus.NormalClosure, "Form closing", CancellationToken.None);
                }
                catch { }
                wsClient.Dispose();
                wsClient = null;
            }
        }

        #region WebSocket Client Connection
        private async Task ConnectWebSocketAsync()
        {
            try
            {
                // Reuse existing connection if already connected
                if (wsClient != null && wsClient.State == WebSocketState.Open)
                {
                    SendMessage(Action.SendMessage, "✅ WebSocket already connected.");
                    return;
                }

                // Close existing connection if in a non-open state
                if (wsClient != null && wsClient.State != WebSocketState.Closed)
                {
                    try
                    {
                        await wsClient.CloseAsync(WebSocketCloseStatus.NormalClosure, "Reconnecting", CancellationToken.None);
                    }
                    catch { }
                    wsClient.Dispose();
                }

                wsClient = new ClientWebSocket();
                await wsClient.ConnectAsync(new Uri("ws://localhost:8081"), CancellationToken.None);
                SendMessage(Action.SendMessage, "✅ Connected to WebSocket server.");

                // Optional: listen to incoming messages (not required but useful for debugging)
                // _ = Task.Run(async () =>
                // {
                //     var buffer = new byte[1024];
                //     while (wsClient != null && wsClient.State == WebSocketState.Open)
                //     {
                //         try
                //         {
                //             var result = await wsClient.ReceiveAsync(new ArraySegment<byte>(buffer), CancellationToken.None);
                //             if (result.MessageType == WebSocketMessageType.Text)
                //             {
                //                 string msg = Encoding.UTF8.GetString(buffer, 0, result.Count);
                //                 SendMessage(Action.SendMessage, $"📩 From server: {msg}");
                //             }
                //         }
                //         catch (Exception ex)
                //         {
                //             SendMessage(Action.SendMessage, $"⚠️ WebSocket receive error: {ex.Message}");
                //             break;
                //         }
                //     }
                // });
            }
            catch (Exception ex)
            {
                SendMessage(Action.SendMessage, $"⚠️ WebSocket connection failed: {ex.Message}");
            }
        }

        // Helper function to send error messages via WebSocket
        private async Task SendErrorMessageAsync(string errorMessage, string errorType = "error")
        {
            try
            {
                // Ensure WebSocket is connected
                await ConnectWebSocketAsync();
                
                if (wsClient != null && wsClient.State == WebSocketState.Open)
                {
                    var errorMessageObj = new JObject
                    {
                        ["type"] = "attendance_error",
                        ["error_type"] = errorType,
                        ["message"] = errorMessage,
                        ["timestamp"] = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss")
                    };
                    
                    var jsonBytes = Encoding.UTF8.GetBytes(errorMessageObj.ToString());
                    await wsClient.SendAsync(new ArraySegment<byte>(jsonBytes), WebSocketMessageType.Text, true, CancellationToken.None);
                    SendMessage(Action.SendMessage, $"⚠️ Error sent to WebSocket: {errorMessage}");
                }
            }
            catch (Exception ex)
            {
                SendMessage(Action.SendMessage, $"⚠️ Failed to send error via WebSocket: {ex.Message}");
            }
        }
        #endregion

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
                if (txtIdentify.InvokeRequired)
                {
                    var d = new SendMessageCallback(SendMessage);
                    Invoke(d, new object[] { action, payload });
                }
                else
                {
                    if (action == Action.SendMessage)
                    {
                        txtIdentify.Text += payload + "\r\n\r\n";
                        txtIdentify.SelectionStart = txtIdentify.TextLength;
                        txtIdentify.ScrollToCaret();
                    }
                }
            }
            catch { }
        }
        #endregion
    }

    public class StoredTemplate
    {
        public string employee_id { get; set; }
        public string template { get; set; }
    }
}