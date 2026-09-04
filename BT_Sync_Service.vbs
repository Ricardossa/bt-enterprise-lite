Set WshShell = CreateObject("WScript.Shell")
strPath = Left(WScript.ScriptFullName, InStrRev(WScript.ScriptFullName, "\"))
phpExe = strPath & "runtime\php\php.exe"
pulseScript = strPath & "public\pulse.php"

Do
    ' Executa o script de pulso via CLI de forma oculta
    WshShell.Run """" & phpExe & """ """ & pulseScript & """", 0, True

    WScript.Sleep 60000 ' Pulsa a cada 1 minuto (60.000 ms) para resposta rápida
Loop
