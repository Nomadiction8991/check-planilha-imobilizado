# Compila o instalador do Windows com o Inno Setup 6.7.4 (série 6.5 ou mais nova) e o Wine, num contêiner Linux.
# Serve só para compilar: os .exe do sistema e o instalador final ainda precisam de teste numa máquina Windows.
# Uso: deploy/pacotes/windows/compilar/compilar-instalador.sh
FROM debian:bookworm-slim
ENV DEBIAN_FRONTEND=noninteractive
RUN dpkg --add-architecture i386 \
 && apt-get update -qq \
 && apt-get install -y -qq --no-install-recommends wine wine32 xvfb xauth curl file ca-certificates \
 && rm -rf /var/lib/apt/lists/*
RUN useradd -m -u 1000 cp \
 && mkdir -p /opt/wineprefix \
 && curl -fsSL -o /opt/innosetup.exe https://github.com/jrsoftware/issrc/releases/download/is-6_7_4/innosetup-6.7.4.exe \
 && file /opt/innosetup.exe | grep -q 'PE32'
ENV WINEPREFIX=/opt/wineprefix WINEDEBUG=-all
# Instala o Inno Setup no prefixo do Wine, em modo silencioso, e confere se o compilador (ISCC.exe) está lá.
# O prefixo pertence ao usuário cp (uid 1000), que é quem compila: o Wine recusa prefixo de outro dono.
RUN xvfb-run -a sh -c 'wine wineboot -i >/tmp/wineboot.log 2>&1; wine /opt/innosetup.exe /VERYSILENT /SUPPRESSMSGBOXES /NORESTART /SP- /LOG=/tmp/inno-instalacao.log; wineserver -w; true' \
 && find /opt/wineprefix/drive_c -name ISCC.exe | head -n 1 | grep -q 'ISCC.exe' \
 && chown -R 1000:1000 /opt/wineprefix
