; Instalador do Check Planilha para o notebook da igreja (Windows).
;
; Antes de compilar, monte a pasta build\windows no Linux:
;   deploy/pacotes/windows/preparar-binarios-windows.sh --vendor <pasta vendor sem dev>
; Ela traz o PHP 8.4, o MySQL 8.4, o Visual C++ 2022, o php.ini, os certificados e o sistema.
;
; COMPILADO no Linux com o Inno Setup 6.7.4 (deploy/pacotes/windows/compilar). AINDA NÃO TESTADO
; numa máquina Windows. O lancador.ps1 foi validado só em PowerShell 7 no Linux (ver
; deploy/pacotes/windows/validacao/validar-powershell.sh). Falta instalar, rodar o lançador e
; testar a desinstalação de verdade.

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

[Dirs]
; O programa fica em Program Files, onde o usuário comum só lê. O Laravel grava logs, cache e sessões
; nestas pastas, então elas precisam liberar a escrita para os usuários do computador.
Name: "{app}\app\storage"; Permissions: users-modify
Name: "{app}\app\bootstrap\cache"; Permissions: users-modify

[Files]
Source: "{#PastaDeBuild}\app\*"; DestDir: "{app}\app"; Flags: recursesubdirs createallsubdirs ignoreversion
Source: "{#PastaDeBuild}\php\*"; DestDir: "{app}\php"; Flags: recursesubdirs createallsubdirs ignoreversion
Source: "{#PastaDeBuild}\mysql\*"; DestDir: "{app}\mysql"; Flags: recursesubdirs createallsubdirs ignoreversion
Source: "lancador.ps1"; DestDir: "{app}"; Flags: ignoreversion
; Redistribuível do Visual C++ 2022 (x64). Copiado para a pasta temporária e apagado depois.
Source: "{#PastaDeBuild}\vc_redist.x64.exe"; DestDir: "{tmp}"; Flags: deleteafterinstall; Check: VcRedistNeedsInstall

[Icons]
Name: "{group}\{#NomeDoApp}"; Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\lancador.ps1"""; WorkingDir: "{app}"
Name: "{autodesktop}\{#NomeDoApp}"; Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\lancador.ps1"""; WorkingDir: "{app}"

[Run]
; O PHP e o MySQL não abrem sem o Visual C++ 2022. A etapa só roda se a versão do computador for antiga.
Filename: "{tmp}\vc_redist.x64.exe"; Parameters: "/install /quiet /norestart"; StatusMsg: "Instalando componentes do Visual C++ 2022 para o PHP e o MySQL..."; Flags: waituntilterminated; Check: VcRedistNeedsInstall

[Code]
// Diz se o Visual C++ 2022 (versão 14.44 ou mais nova) ainda precisa ser instalado.
// Lê o registro de 64 bits, onde o instalador do Visual C++ grava a versão.
function VcRedistNeedsInstall: Boolean;
var
  Chave: String;
  Instalado, Minor: Cardinal;
begin
  Chave := 'SOFTWARE\Microsoft\VisualStudio\14.0\VC\Runtimes\x64';
  Result := True;
  if RegQueryDWordValue(HKLM64, Chave, 'Installed', Instalado) and (Instalado = 1) then
    if RegQueryDWordValue(HKLM64, Chave, 'Minor', Minor) and (Minor >= 44) then
      Result := False;
end;
