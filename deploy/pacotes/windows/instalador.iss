; Instalador do Check Planilha para o notebook da igreja (Windows).
;
; Antes de compilar, monte a pasta build\windows no Linux:
;   deploy/pacotes/windows/preparar-binarios-windows.sh --vendor <pasta vendor sem dev>
; Ela traz o PHP 8.4, o MySQL 8.4, o php.ini, os certificados e o sistema.
;
; ESQUELETO: ainda não foi compilado nem testado. Falta compilar com o Inno Setup 6 numa
; máquina Windows e testar a instalação e o lancador.ps1 de verdade.

#define NomeDoApp "Check Planilha Igreja"
#define VersaoDoApp "0.1.0"
#define PastaDeBuild "..\..\..\build\windows"

[Setup]
AppId={{BA854EC5-E7D2-4817-8FE1-1909BC77D4FA}
AppName={#NomeDoApp}
AppVersion={#VersaoDoApp}
DefaultDirName={autopf}\CheckPlanilhaIgreja
DefaultGroupName={#NomeDoApp}
OutputDir={#PastaDeBuild}
OutputBaseFilename=CheckPlanilhaIgreja-{#VersaoDoApp}-setup
Compression=lzma2
SolidCompression=yes
PrivilegesRequired=admin
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible

[Languages]
Name: "brazilianportuguese"; MessagesFile: "compiler:Languages\BrazilianPortuguese.isl"

[Files]
Source: "{#PastaDeBuild}\app\*"; DestDir: "{app}\app"; Flags: recursesubdirs createallsubdirs ignoreversion
Source: "{#PastaDeBuild}\php\*"; DestDir: "{app}\php"; Flags: recursesubdirs createallsubdirs ignoreversion
Source: "{#PastaDeBuild}\mysql\*"; DestDir: "{app}\mysql"; Flags: recursesubdirs createallsubdirs ignoreversion
Source: "lancador.ps1"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
Name: "{group}\{#NomeDoApp}"; Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\lancador.ps1"""; WorkingDir: "{app}"
Name: "{autodesktop}\{#NomeDoApp}"; Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\lancador.ps1"""; WorkingDir: "{app}"
