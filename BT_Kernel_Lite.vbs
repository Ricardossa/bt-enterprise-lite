Set WshShell = CreateObject("WScript.Shell")
' Inicia o servidor PHP da Lite na porta 8120 de forma invisivel
WshShell.Run "runtime\php\php.exe -S localhost:8120 -t public", 0, False
