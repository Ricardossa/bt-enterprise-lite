@echo off
setlocal
pushd "%~dp0"
title BT Queue Lite - Gerenciador
color 0b

echo ===================================================
echo    🚀 BT QUEUE LITE - INICIANDO SISTEMA
echo ===================================================

:: 1. Inicia o PHP Invisível (Kernel)
tasklist /fi "windowtitle eq BT Queue Lite - PHP" | findstr /i "php.exe" > nul
if %errorlevel% neq 0 (
    echo [ ] Ativando motores...
    start /min wscript.exe "BT_Kernel_Lite.vbs"
)

:: 2. Inicia o Sincronismo Invisível
tasklist /fi "imagename eq wscript.exe" | findstr /i "BT_Sync_Lite.vbs" > nul
if %errorlevel% neq 0 (
    echo [ ] Ativando sincronismo inteligente...
    start /min wscript.exe "BT_Sync_Lite.vbs"
)

echo ✅ SISTEMA PRONTO!
echo [ ] Abrindo Painel de Atendimento...
timeout /t 2 > nul
powershell -Command "Start-Process 'http://localhost:8120/index.php'"

exit
