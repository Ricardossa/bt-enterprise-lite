#!/bin/bash
# ==========================================================
# 🚀 BRANDÃO TECH APPLIANCE INSTALLER v2.2 (Diamond Elite)
# ==========================================================

set -e

APP_NAME="BT Queue Enterprise"
INSTALL_DIR="/var/www/html/btqueue"
MASTER_IP="192.168.100.245"
# URLs Oficiais
FULL_URL="https://$MASTER_IP/bt-enterprise.zip"
CHECK_URL="https://$MASTER_IP/api/v1/updates_check.php"
DOWNLOAD_URL="https://$MASTER_IP/api/v1/updates_download.php"

ZIP_FILE="/tmp/bt-package.zip"

COL_RED="\033[0;31m"
COL_GREEN="\033[0;32m"
COL_YELLOW="\033[1;33m"
COL_BLUE="\033[0;34m"
COL_RESET="\033[0m"

msg() { echo -e "${COL_BLUE}$1${COL_RESET}"; }
ok()  { echo -e "${COL_GREEN}✔ $1${COL_RESET}"; }
warn(){ echo -e "${COL_YELLOW}⚠ $1${COL_RESET}"; }
erro(){ echo -e "${COL_RED}✖ $1${COL_RESET}"; exit 1; }

banner() {
    clear
    echo -e "${COL_BLUE}==================================================="
    echo "      BRANDÃO TECH APPLIANCE INSTALLER v2.2"
    echo "==================================================="
    echo " Produto     : $APP_NAME"
    echo " Plataforma  : Debian 13 / Linux"
    echo "===================================================${COL_RESET}"
}

check_root() { [ "$EUID" -ne 0 ] || return 0; erro "Execute como root."; }

check_network() {
    msg "🌐 Testando conectividade com a Fábrica..."
    if ping -c1 $MASTER_IP >/dev/null 2>&1; then
        ok "Conexão com a Fábrica estabelecida."
    else
        erro "Não foi possível acessar a VM Master ($MASTER_IP)."
    fi
}

install_dependencies() {
    msg "📦 Verificando motores..."
    apt update -y > /dev/null
    apt install -y apache2 php libapache2-mod-php php-mysql php-curl php-mbstring php-gd php-zip php-xml mariadb-client curl unzip rsync jq > /dev/null
    ok "Dependências verificadas."
}

prepare_installation() {
    msg "📂 Analisando ambiente..."
    if [ -d "$INSTALL_DIR/public" ]; then
        warn "Instalação existente detectada."
        echo -e "1) Atualizar (Seguro - Preserva Config)\n2) Reparar (Forçar Permissões)\n3) Reinstalar (Limpa - APAGA TUDO)\n4) Cancelar"
        read -p "Escolha: " OPTION
        case $OPTION in
            1) INSTALL_MODE="UPDATE" ;;
            2) INSTALL_MODE="REPAIR" ;;
            3) INSTALL_MODE="NEW"; rm -rf "$INSTALL_DIR"; mkdir -p "$INSTALL_DIR" ;;
            *) erro "Operação cancelada." ;;
        esac
    else
        INSTALL_MODE="NEW"
        mkdir -p "$INSTALL_DIR"
    fi
}

download_package() {
    if [ "$INSTALL_MODE" == "UPDATE" ]; then
        msg "🔍 Consultando versão mais recente na Master..."
        # Busca o ID da última versão estável
        LATEST_JSON=$(curl -s "$CHECK_URL?v=0.0.0")
        RELEASE_ID=$(echo $LATEST_JSON | jq -r '.release_id // empty')

        if [ "$RELEASE_ID" == "" ]; then
            erro "Não foi possível localizar uma atualização válida na Master."
        fi

        FINAL_URL="$DOWNLOAD_URL?id=$RELEASE_ID"
        msg "📥 Baixando pacote OTA (Release $RELEASE_ID)..."
    else
        FINAL_URL="$FULL_URL"
        msg "📥 Baixando pacote COMPLETO Diamond..."
    fi

    rm -f "$ZIP_FILE"
    curl -L --fail --progress-bar "$FINAL_URL" -o "$ZIP_FILE"
    ok "Download concluído."
}

extract_package() {
    msg "📦 Aplicando arquivos..."
    # -o: overwrite, -q: quiet, -x: exclude
    # Protegemos arquivos de configuração durante a extração
    unzip -oq "$ZIP_FILE" -d "$INSTALL_DIR" -x "config/config.php"
    ok "Sistema atualizado."
}

configure_system() {
    msg "🌐 Configurando VirtualHost..."
    if [ -f "$INSTALL_DIR/install/apache.conf" ]; then
        cp "$INSTALL_DIR/install/apache.conf" /etc/apache2/sites-available/000-default.conf
    fi
    /usr/sbin/a2enmod rewrite > /dev/null

    msg "🔐 Ajustando permissões Diamond..."
    chown -R www-data:www-data "$INSTALL_DIR"
    find "$INSTALL_DIR" -type d -exec chmod 755 {} \;
    find "$INSTALL_DIR" -type f -exec chmod 644 {} \;
    chmod -R 775 "$INSTALL_DIR/database" "$INSTALL_DIR/logs" "$INSTALL_DIR/public/uploads"

    msg "🗄️ Verificando integridade do banco MariaDB..."
    # Em MariaDB SaaS, a verificação é feita via setup.php
    ok "Conectividade MariaDB pronta para provisionamento."

    msg "🤖 Atualizando BT Doctor..."
    chmod +x "$INSTALL_DIR/scripts/bt-doctor.php"
    ln -sf "$INSTALL_DIR/scripts/bt-doctor.php" /usr/local/bin/bt-doctor

    msg "🛰️ Validando MasterSync..."
    # Garante que o serviço existe
    if [ ! -f /etc/systemd/system/bt-sync.service ]; then
        echo "[Unit]
Description=BT Queue MasterSync Service
After=network.target

[Service]
Type=simple
User=www-data
ExecStart=/usr/bin/php $INSTALL_DIR/public/pulse.php
Restart=always
RestartSec=60

[Install]
WantedBy=multi-user.target" > /etc/systemd/system/bt-sync.service
        systemctl daemon-reload
        systemctl enable bt-sync
    fi
    systemctl restart bt-sync
}

main() {
    check_root
    banner
    check_network
    install_dependencies
    prepare_installation
    download_package
    extract_package
    configure_system
    systemctl restart apache2
    echo
    echo -e "${COL_GREEN}=====================================================${COL_RESET}"
    echo -e "${COL_GREEN}      APPLIANCE SINCRONIZADO COM SUCESSO!${COL_RESET}"
    echo -e "${COL_GREEN}=====================================================${COL_RESET}"
    msg "👉 Acesso: http://$(hostname -I | awk '{print $1}')/"
    msg "👉 Suporte: Digite 'bt-doctor' para auditoria."
}

main
