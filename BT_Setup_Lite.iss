#define MyAppName "BT Queue Lite"
#define MyAppVersion "4.0.0"
#define MyAppPublisher "Brandão Tech"
#define MyAppURL "http://brandaotech.com.br"
#define MyAppExeName "Ligar_Lite.bat"

[Setup]
AppId={{B0E4B4B2-8A73-4C62-9D4B-000000000002}}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
DefaultDirName={commonpf64}\BrandaoTech\BTQueueLite
DefaultGroupName={#MyAppName}
AllowNoIcons=yes
ArchitecturesInstallIn64BitMode=x64
ArchitecturesAllowed=x64
SetupIconFile=favicon.ico
PrivilegesRequired=admin
OutputDir=output
OutputBaseFilename=BTQueueLite_Setup_v4.0
Compression=lzma
SolidCompression=yes
WizardStyle=modern

[Languages]
Name: "brazilianportuguese"; MessagesFile: "compiler:Languages\BrazilianPortuguese.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked

[Files]
; 1. Runtime PHP e MariaDB (VITAL)
Source: "runtime\php\*"; DestDir: "{app}\runtime\php"; Flags: ignoreversion recursesubdirs createallsubdirs
Source: "runtime\mariadb\*"; DestDir: "{app}\runtime\mariadb"; Flags: ignoreversion recursesubdirs createallsubdirs

; 2. Motores de Servico (WinSW)
Source: "service\*"; DestDir: "{app}\service"; Flags: ignoreversion recursesubdirs createallsubdirs

; 3. Aplicacao Lite Limpa
Source: "*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: "runtime\*;service\*;database\*.db;logs\*;cache\*;BT_Setup_*.iss;.git\*;output\*;backup_*\*;*.zip"

; 4. Arquivos de Configuracao (Nao sobrescreve se ja existir)
Source: "config\config.php"; DestDir: "{app}\config"; Flags: ignoreversion onlyifdoesntexist

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; IconFilename: "{app}\favicon.ico"; WorkingDir: "{app}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: desktopicon; IconFilename: "{app}\favicon.ico"; WorkingDir: "{app}"

[Run]
; Instala o Microsoft Visual C++ Redistributable (Silencioso)
Filename: "{tmp}\vc_redist.x64.exe"; Parameters: "/install /quiet /norestart"; StatusMsg: "Instalando componentes de sistema (Microsoft Visual C++)..."; Check: not IsVCInstalled

; Inicializa o Banco de Dados MariaDB (Apenas se a pasta data não existir)
Filename: "{app}\runtime\mariadb\bin\mysql_install_db.exe"; Parameters: "--datadir=""{app}\runtime\mariadb\data"""; StatusMsg: "Inicializando Banco de Dados..."; Flags: runhidden

; Instala e Inicia o MariaDB como Servico (Silencioso)
Filename: "{app}\runtime\mariadb\bin\mysqld.exe"; Parameters: "--install BT_LITE_DB"; Flags: runhidden
Filename: "net.exe"; Parameters: "start BT_LITE_DB"; Flags: runhidden

; Registra e inicia os serviços do Servidor Web e Impressora
Filename: "{app}\service\BTQueueLiteServer.exe"; Parameters: "install"; Flags: runhidden
Filename: "{app}\service\BTQueueLitePrinter.exe"; Parameters: "install"; Flags: runhidden
Filename: "{app}\service\BTQueueLiteServer.exe"; Parameters: "start"; Flags: runhidden
Filename: "{app}\service\BTQueueLitePrinter.exe"; Parameters: "start"; Flags: runhidden

; Abre o navegador no Setup
Filename: "{app}\{#MyAppExeName}"; Description: "Lançar BT Queue Lite"; Flags: postinstall skipifsilent

[UninstallRun]
; Limpeza na desinstalacao
Filename: "net.exe"; Parameters: "stop BT_LITE_DB"; Flags: runhidden
Filename: "{app}\runtime\mariadb\bin\mysqld.exe"; Parameters: "--remove BT_LITE_DB"; Flags: runhidden
Filename: "{app}\service\BTQueueLiteServer.exe"; Parameters: "stop"; Flags: runhidden
Filename: "{app}\service\BTQueueLiteServer.exe"; Parameters: "uninstall"; Flags: runhidden

[Dirs]
Name: "{app}\database"; Permissions: users-full
Name: "{app}\public\uploads"; Permissions: users-full
Name: "{app}\logs"; Permissions: users-full

[Code]
function IsVCInstalled: Boolean;
begin
  Result := RegKeyExists(HKEY_LOCAL_MACHINE, 'SOFTWARE\Microsoft\VisualStudio\14.0\VC\Runtimes\x64');
end;
