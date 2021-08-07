using System;
using System.Text;
using System.Diagnostics;

class Program
{
    public static void Main (string[] args)
    {
        if (args.Length == 2)
        {
            Process process = new Process();

            process.StartInfo.FileName = args[0];
            process.StartInfo.Arguments = Encoding.UTF8.GetString(Convert.FromBase64String(args[1]));
            process.StartInfo.CreateNoWindow = true;
            process.StartInfo.WindowStyle = ProcessWindowStyle.Hidden;
            
            process.Start();
        }
    }
}
