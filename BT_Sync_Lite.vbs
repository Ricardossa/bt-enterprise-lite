Set WshShell = CreateObject("WScript.Shell")
Do
    ' Dispara o pulso de sincronizacao a cada 60 segundos
    WshShell.Run "runtime\php\php.exe public\pulse.php", 0, True
    WScript.Sleep 60000
Loop
