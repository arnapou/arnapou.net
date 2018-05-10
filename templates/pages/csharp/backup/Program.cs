using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.IO;

namespace backup_copy
{
    class Program
    {
        public TextWriter LOG = null;
        public bool DeleteFolders = true;

        static void Main(string[] args)
        {
            Program p = new Program();
            string Erreur = "";
            string SRC = "";
            string DST = "";

            if (args.Length < 2)
            {
                Erreur = "Not enough arguments";
            }
            if (args.Length > 4)
            {
                Erreur = "Too much arguments";
            }
            if (args.Length >= 2)
            {
                SRC = args[0];
                DST = args[1];
            }
            if (args.Length >= 3)
            {
                if (args[2].ToLower() == "0") { p.DeleteFolders = false; }
                if (args[2].ToLower() == "no") { p.DeleteFolders = false; }
                if (args[2].ToLower() == "non") { p.DeleteFolders = false; }
                if (args[2].ToLower() == "false") { p.DeleteFolders = false; }
            }
            if (args.Length >= 4)
            {
                try
                {
                    p.LOG = new StreamWriter(args[3]);
                }
                catch (Exception)
                {
                    Erreur = "Failed log file creation";
                }
            }
            if (Erreur != "")
            {
                Console.WriteLine("Error: " + Erreur + "\n\n" +
                     "Usage: backup_copy.exe <Source> <Dest> [Delete] [Log]\n" +
                     "       - Source : source folder\n" +
                     "       - Dest   : destination folder\n" +
                     "       - Delete : whether to delete backup old files (default TRUE)\n" +
                     "       - Log    : log file (default : no log)\n"
                );
            }
            else
            {
                SRC = SRC.TrimEnd('\\');
                DST = DST.TrimEnd('\\');
                p.Backup(SRC, DST);
                if (p.LOG != null)
                {
                    p.LOG.Close();
                }
            }
        }

        public void Backup(string SRC, string DST)
        {
            if (Directory.Exists(SRC))
            {
                if (!Directory.Exists(DST))
                {
                    try
                    {
                        Directory.CreateDirectory(DST);
                        this.LogWrite("Create Folder " + DST);
                    }
                    catch (Exception)
                    {
                        this.LogWrite("Error Create Directory " + DST);
                    }
                }
                // files
                try
                {
                    foreach (string file in Directory.GetFiles(SRC))
                    {
                        string basename = Path.GetFileName(file);
                        if (File.Exists(DST + "\\" + basename))
                        {
                            if (this.FileUpdated(SRC + "\\" + basename, DST + "\\" + basename))
                            {
                                try
                                {
                                    File.Copy(SRC + "\\" + basename, DST + "\\" + basename, true);
                                    this.LogWrite("Copy File     " + DST + "\\" + basename);
                                }
                                catch (Exception)
                                {
                                    this.LogWrite("Error Copying File to " + DST + "\\" + basename);
                                }
                            }
                        }
                        else
                        {
                            try
                            {
                                File.Copy(SRC + "\\" + basename, DST + "\\" + basename, true);
                                this.LogWrite("Copy File     " + DST + "\\" + basename);
                            }
                            catch (Exception)
                            {
                                this.LogWrite("Error Copying File to " + DST + "\\" + basename);
                            }
                        }
                    }
                }
                catch (Exception e)
                {
                    this.LogWrite(e.Message);
                }
                // folders
                try {
                    foreach (string folder in Directory.GetDirectories(SRC))
                    {
                        string basename = Path.GetFileName(folder);
                        this.Backup(SRC + "\\" + basename, DST + "\\" + basename);
                    }
                }
                catch (Exception e)
                {
                    this.LogWrite(e.Message);
                }
                // delete
                if (this.DeleteFolders)
                {
                    try
                    {
                        foreach (string file in Directory.GetFiles(DST))
                        {
                            string basename = Path.GetFileName(file);
                            if (!File.Exists(SRC + "\\" + basename))
                            {
                                try
                                {
                                    File.Delete(file);
                                    this.LogWrite("Delete File   " + file);
                                }
                                catch (Exception)
                                {
                                    this.LogWrite("Error Deleting File " + file);
                                }
                            }
                        }
                    }
                    catch (Exception e)
                    {
                        this.LogWrite(e.Message);
                    }
                    try
                    {
                        foreach (string folder in Directory.GetDirectories(DST))
                        {
                            string basename = Path.GetFileName(folder);
                            if (!Directory.Exists(SRC + "\\" + basename))
                            {
                                try
                                {
                                    Directory.Delete(folder, true);
                                    this.LogWrite("Delete Folder " + folder);
                                }
                                catch (Exception)
                                {
                                    this.LogWrite("Error Deleting Folder " + folder);
                                }
                            }
                        }
                    }
                    catch (Exception e)
                    {
                        this.LogWrite(e.Message);
                    }
                }
            }
        }

        protected bool FileUpdated(string SRC, string DST)
        {
            try
            {
                FileInfo SRCi = new FileInfo(SRC);
                FileInfo DSTi = new FileInfo(DST);
                if (SRCi.Length != DSTi.Length || SRCi.LastWriteTime > DSTi.LastWriteTime)
                {
                    return true;
                }
            }
            catch (Exception e)
            {
                this.LogWrite(e.Message + "\r\nSRC = " + SRC + "\r\nDST = " + DST);
            }
            return false;
        }

        protected void LogWrite(string line)
        {
            if (this.LOG != null)
            {
                LOG.WriteLine(this.GetDateNow() + line);
            }
        }

        protected string GetDateNow()
        {
            DateTime d = DateTime.Now;
            string s = d.Year.ToString() + "-";
            if (d.Month < 10) { s += "0"; }
            s += d.Month.ToString() + "-";
            if (d.Day < 10) { s += "0"; }
            s += d.Day.ToString() + " ";
            if (d.Hour < 10) { s += "0"; }
            s += d.Hour.ToString() + ":";
            if (d.Minute < 10) { s += "0"; }
            s += d.Minute.ToString() + ":";
            if (d.Second < 10) { s += "0"; }
            s += d.Second.ToString() + " ";
            return s;
        }
    }
}
