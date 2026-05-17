

using System;
using System.IO;
using System.Windows.Forms;

namespace UareUSampleCSharp
{
    public static class AppConfig
    {
        public static string BaseUrl { get; private set; } = "http://localhost/attendance-system";
        public static string WebSocketUrl { get; private set; } = "ws://localhost:8081";

        public static void LoadEnv()
        {
            try
            {
                string envPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, ".env");
                if (!File.Exists(envPath))
                {
                    // Try to go up one directory if running in bin/Debug
                    envPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "..", "..", ".env");
                }

                if (File.Exists(envPath))
                {
                    string host = "localhost";
                    string port = "8081";

                    foreach (var line in File.ReadAllLines(envPath))
                    {
                        var trimmed = line.Trim();
                        if (string.IsNullOrEmpty(trimmed) || trimmed.StartsWith("#")) continue;

                        int eqIndex = trimmed.IndexOf('=');
                        if (eqIndex > 0)
                        {
                            string key = trimmed.Substring(0, eqIndex).Trim();
                            string val = trimmed.Substring(eqIndex + 1).Trim(' ', '"', '\'');

                            if (key == "BASE_URL" && !string.IsNullOrEmpty(val)) BaseUrl = val;
                            else if (key == "WEBSOCKET_HOST" && !string.IsNullOrEmpty(val)) host = val;
                            else if (key == "WEBSOCKET_PORT" && !string.IsNullOrEmpty(val)) port = val;
                        }
                    }

                    WebSocketUrl = $"ws://{host}:{port}";
                }
            }
            catch { }
        }
    }

    static class Program
    {
        [MTAThread]
        static void Main(string[] args)
        {
            AppConfig.LoadEnv();
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);

            // Always create Form_Main (manages the reader)
            var mainForm = new Form_Main();

            if (args.Length > 0 && args[0].StartsWith("biometrics://"))
            {
                string employeeId = null;
                string residentId = null;
                string action = null;

                try
                {
                    var uri = new Uri(args[0]);
                    action = uri.Host; // "enroll" or "identify"

                    if (!string.IsNullOrEmpty(uri.Query))
                    {
                        var queryParams = uri.Query.TrimStart('?').Split('&');
                        foreach (var param in queryParams)
                        {
                            var parts = param.Split('=');
                            if (parts.Length == 2)
                            {
                                if (parts[0] == "employee_id")
                                {
                                    employeeId = Uri.UnescapeDataString(parts[1]);
                                }
                                else if (parts[0] == "resident_id")
                                {
                                    residentId = Uri.UnescapeDataString(parts[1]);
                                }
                            }
                        }
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show("Invalid biometrics URL: " + ex.Message, "Error",
                        MessageBoxButtons.OK, MessageBoxIcon.Error);
                }

                // Decide which form to launch
                Form formToRun = null;
                if (action == "enroll")
                {
                    formToRun = new Enrollment(employeeId, residentId) { _sender = mainForm };
                }
                else if (action == "identify")
                {
                    formToRun = new Identification() { _sender = mainForm };
                }
                else if (action == "verify")
                {
                    formToRun = new Verification() { _sender = mainForm };
                }

                if (formToRun != null)
                    Application.Run(formToRun);
                else
                    Application.Run(mainForm);
            }
            else
            {
                // Normal app startup
                Application.Run(mainForm);
            }
        }
    }
}




