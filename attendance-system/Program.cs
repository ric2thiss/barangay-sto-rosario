

using System;
using System.Windows.Forms;

namespace UareUSampleCSharp
{
    static class Program
    {
        [MTAThread]
        static void Main(string[] args)
        {
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




