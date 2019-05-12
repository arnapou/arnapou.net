Option Explicit
On Error Resume Next
Dim oFS, Erreur, FicLog, SRC, DST, DELETE_FOLDERS, LOG_FILE
Set oFS = WScript.CreateObject("Scripting.FileSystemObject")
Erreur = ""
DELETE_FOLDERS = True
LOG_FILE = ""

If WScript.Arguments.Count < 2 Then 
	Erreur = "Not enough arguments"
End If
If WScript.Arguments.Count > 4 Then 
	Erreur = "Too much arguments"
End If
If WScript.Arguments.Count >= 2 Then 
	SRC = WScript.Arguments(0)
	DST = WScript.Arguments(1)
End If
If WScript.Arguments.Count >= 3 Then 
	If LCase(WScript.Arguments(2)) = "0"     Then DELETE_FOLDERS = False
	If LCase(WScript.Arguments(2)) = "no"    Then DELETE_FOLDERS = False
	If LCase(WScript.Arguments(2)) = "non"   Then DELETE_FOLDERS = False
	If LCase(WScript.Arguments(2)) = "false" Then DELETE_FOLDERS = False
End If
If WScript.Arguments.Count >= 4 Then 
	LOG_FILE = WScript.Arguments(3)
End If

If Erreur <> "" Then
	WScript.Echo "Error: " & Erreur & vbCrLf & vbCrLf & _
	             "Usage: cscript.exe backup_copy.vbs <Source> <Dest> [Delete] [Log]" & vbCrLf & _
	             "       - Source : source folder" & vbCrLf & _
	             "       - Dest   : destination folder" & vbCrLf & _
	             "       - Delete : whether to delete backup old files (default TRUE)" & vbCrLf & _
	             "       - Log    : log file (default : no log)" & vbCrLf
Else
	If LOG_FILE <> "" Then Set FicLog = oFS.OpenTextFile(LOG_FILE, 2, True)
	Backup SRC, DST
	If LOG_FILE <> "" Then FicLog.Close
End If

''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
Sub Backup(Fsrc, Fdst)
	Dim oFile, oFile2, oFolder
	On Error Resume Next
	If oFS.FolderExists(Fsrc) Then
		' create folder
		If Not(oFS.FolderExists(Fdst)) Then
			oFS.CreateFolder Fdst
			LogWrite "Create Folder " & Fdst
		End If
		' copy
		For Each oFile In oFS.GetFolder(Fsrc).Files
			If oFS.FileExists(AddSlash(Fdst) & oFile.Name) Then
				Set oFile2 = oFS.GetFile(AddSlash(Fdst) & oFile.Name)
				If oFile.Size <> oFile2.Size OR oFile.DateLastModified > oFile2.DateLastModified Then
					LogWrite "Copy File     " & AddSlash(Fdst) & oFile.Name
					oFile.Copy AddSlash(Fdst) & oFile.Name, True
				End If
			Else 
				LogWrite "Copy File     " & AddSlash(Fdst) & oFile.Name
				oFile.Copy AddSlash(Fdst) & oFile.Name, True
			End If
		Next
		For Each oFolder In oFS.GetFolder(Fsrc).SubFolders
			Backup AddSlash(Fsrc) & oFolder.Name, AddSlash(Fdst) & oFolder.Name
		Next
		' delete
		If DELETE_FOLDERS Then
			For Each oFile In oFS.GetFolder(Fdst).Files
				If Not(oFS.FileExists(AddSlash(Fsrc) & oFile.Name)) Then
					LogWrite "Delete File   " & oFile.Path
					oFS.DeleteFile oFile.Path
				End If
			Next
			For Each oFolder In oFS.GetFolder(Fdst).SubFolders
				If Not(oFS.FolderExists(AddSlash(Fsrc) & oFolder.Name)) Then
					LogWrite "Delete Folder " & oFolder.Path
					CleanFolder oFolder.Path
					oFolder.Delete True
				End If
			Next
		End If
	End If
End Sub
''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
Sub CleanFolder(sFolder)
	Dim oFile, oFolder
	On Error Resume Next
	For Each oFile In oFS.GetFolder(sFolder).Files
		oFile.Delete True
	Next
	For Each oFolder In oFS.GetFolder(sFolder).SubFolders
		CleanFolder oFolder.Path
		oFolder.Delete True
	Next
End Sub
''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
Sub LogWrite(line)
	On Error Resume Next
	If LOG_FILE <> "" Then FicLog.WriteLine GetDateNow() & "  " & line
End Sub
''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
Function GetDateNow() 
	Dim y, m, d, h, i, s
	y = DatePart("yyyy", Now)
	m = DatePart("m", Now)
	d = DatePart("d", Now)
	h = DatePart("h", Now)
	i = DatePart("n", Now)
	s = DatePart("s", Now)
	If m < 10 Then m = "0" & m
	If d < 10 Then d = "0" & d
	If h < 10 Then h = "0" & h
	If i < 10 Then i = "0" & i
	If s < 10 Then s = "0" & s
	GetDateNow = y & "-" & m & "-" & d & " " & h & ":" & i & ":" & s
End Function
''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
Function AddSlash(Path) 
	AddSlash = Path
	While Right(AddSlash, 1) = "\"
		AddSlash = Left(AddSlash, Len(AddSlash)-1)
	Wend
	AddSlash = AddSlash & "\"
End Function
