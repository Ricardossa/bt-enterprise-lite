Set WshShell = CreateObject("WScript.Shell")
strPath = Left(WScript.ScriptFullName, InStrRev(WScript.ScriptFullName, "\"))

' 1. Inicia o Watchdog (que liga o PHP e cuida dele)
WshShell.Run "wscript.exe """ & strPath & "BT_Watchdog.vbs""", 0, False

' 2. Inicia o Serviço de Sincronia de Fundo
WshShell.Run "wscript.exe """ & strPath & "BT_Sync_Service.vbs""", 0, False

' 3. Aguarda um pouco e abre o Painel no navegador
WScript.Sleep 3000
WshShell.Run "http://localhost:8090/index.php"
