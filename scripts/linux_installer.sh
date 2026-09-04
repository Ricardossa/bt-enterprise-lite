#!/usr/bin/env bash
# BT Queue Enterprise - instalador oficial do Appliance Linux.
set -Eeuo pipefail

APP_DIR='/var/www/html/btqueue'
SITE_FILE='/etc/apache2/sites-available/btqueue.conf'
SERVICE_FILE='/etc/systemd/system/bt-sync.service'
MODE="${1:-}"
PACKAGE_URL="${2:-}"
PACKAGE_FILE="${3:-/tmp/btqueue-package.zip}"
ARCHIVE_INDEX=''

fail() { echo "ERRO: $*" >&2; exit 1; }
info() { echo "==> $*"; }
require_root() { [ "${EUID}" -eq 0 ] || fail 'Execute como root.'; }
require_executable() {
    local executable="$1"
    local package="$2"
    [ -x "$executable" ] || fail "Executável obrigatório ausente: $executable. Instale/repare o pacote $package."
}

validate_apache_tools() {
    require_executable /usr/bin/install coreutils
    require_executable /usr/bin/find findutils
    require_executable /usr/bin/chmod coreutils
    require_executable /usr/bin/cat coreutils
    require_executable /usr/bin/systemctl systemd
    require_executable /usr/sbin/a2ensite apache2
    require_executable /usr/sbin/a2dissite apache2
    require_executable /usr/sbin/a2enmod apache2
    require_executable /usr/sbin/apache2ctl apache2
}

validate_runtime_tools() {
    validate_apache_tools
    require_executable /usr/bin/curl curl
    require_executable /usr/bin/unzip unzip
    require_executable /usr/bin/php php-cli
    require_executable /usr/bin/cp coreutils
}

php_has_module() {
    local expected="$1"
    local module
    local modules

    modules="$(/usr/bin/php -m)" || fail 'Não foi possível consultar os módulos do PHP.'
    while IFS= read -r module; do
        [[ "$module" == "$expected" ]] && return 0
    done <<< "$modules"

    return 1
}

usage() {
    cat <<'EOF'
Uso:
  linux_installer.sh new <URL-ou-arquivo-FULL> [arquivo-temporario]
  linux_installer.sh update <URL-ou-arquivo-OTA> [arquivo-temporario]
  linux_installer.sh repair

NEW aceita somente o FULL. UPDATE aceita somente OTA. REPAIR não baixa nem
extrai pacotes e nunca altera banco ou config.
EOF
}

fetch_package() {
    local source="$1"
    if [ -f "$source" ]; then
        /usr/bin/cp -- "$source" "$PACKAGE_FILE"
    else
        /usr/bin/curl --fail --location --proto '=https,http' --tlsv1.2 --progress-bar "$source" -o "$PACKAGE_FILE"
    fi
    /usr/bin/unzip -tq "$PACKAGE_FILE" >/dev/null || fail 'ZIP inválido.'
    ARCHIVE_INDEX="$(/usr/bin/unzip -Z1 "$PACKAGE_FILE")" || fail 'Não foi possível listar o conteúdo do ZIP.'
}

archive_has() {
    local expected="$1"
    local entry

    while IFS= read -r entry; do
        [[ "$entry" == "$expected" ]] && return 0
    done <<< "$ARCHIVE_INDEX"

    return 1
}

archive_matches() {
    local pattern="$1"
    local entry

    while IFS= read -r entry; do
        [[ "$entry" =~ $pattern ]] && return 0
    done <<< "$ARCHIVE_INDEX"

    return 1
}

validate_full() {
    archive_has 'bootstrap.php' || fail 'FULL sem bootstrap.php.'
    archive_has 'config/config.php' || fail 'FULL sem configuração inicial.'
    archive_has 'scripts/linux_installer.sh' || fail 'FULL sem instalador Linux.'
    archive_has 'install/apache.conf' || fail 'FULL sem configuração Apache.'
    if archive_matches '(^|/)(database/.*\.db|temp_client\.db|\.git/|99_quarentena/|logs/|cache/|uploads/|runtime/|service/)|\.(old|bak|bkp|exe|bat|vbs)$'; then
        fail 'FULL contém dado persistente, resíduo ou componente Windows proibido.'
    fi
}

validate_ota() {
    archive_has 'bootstrap.php' || fail 'OTA sem bootstrap.php.'
    if archive_matches '^(database/|config/config\.php$|public/uploads/|cache/|logs/)|\.(exe|bat|vbs)$'; then
        fail 'OTA tenta substituir dados persistentes ou inclui componente Windows.'
    fi
}

install_dependencies() {
    info 'Instalando dependências Apache/PHP/MariaDB.'
    require_executable /usr/bin/apt-get apt
    /usr/bin/apt-get update -y
    DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get install -y apache2 libapache2-mod-php php-cli php-mysql php-curl php-mbstring php-gd php-zip php-xml mariadb-client curl unzip
    validate_runtime_tools
    php_has_module pdo_mysql || fail 'Extensão pdo_mysql indisponível; instale o pacote php-mysql.'
    php_has_module curl || fail 'Extensão curl indisponível; instale o pacote php-curl.'
}

prepare_runtime() {
    /usr/bin/install -d -o www-data -g www-data -m 2775 \
        "$APP_DIR/database" "$APP_DIR/logs" "$APP_DIR/cache" \
        "$APP_DIR/cache/updates" "$APP_DIR/cache/backups" "$APP_DIR/cache/tickets" \
        "$APP_DIR/public/uploads" "$APP_DIR/public/uploads/promocoes"
    /usr/bin/find "$APP_DIR" -type d -exec /usr/bin/chmod 755 {} +
    /usr/bin/find "$APP_DIR" -type f -exec /usr/bin/chmod 644 {} +
    /usr/bin/install -d -o www-data -g www-data -m 2775 \
        "$APP_DIR/database" "$APP_DIR/logs" "$APP_DIR/cache" \
        "$APP_DIR/cache/updates" "$APP_DIR/cache/backups" "$APP_DIR/cache/tickets" \
        "$APP_DIR/public/uploads" "$APP_DIR/public/uploads/promocoes"
}

configure_apache() {
    /usr/bin/install -m 644 "$APP_DIR/install/apache.conf" "$SITE_FILE"
    /usr/sbin/a2dissite 000-default >/dev/null 2>&1 || true
    /usr/sbin/a2ensite btqueue >/dev/null
    /usr/sbin/a2enmod rewrite >/dev/null
    /usr/sbin/apache2ctl configtest
}

configure_sync_service() {
    /usr/bin/cat > "$SERVICE_FILE" <<EOF
[Unit]
Description=BT Queue MasterSync Service
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/php $APP_DIR/public/pulse.php
Restart=always
RestartSec=60

[Install]
WantedBy=multi-user.target
EOF
    /usr/bin/systemctl daemon-reload
    /usr/bin/systemctl enable bt-sync.service
}

new_install() {
    [ -n "$PACKAGE_URL" ] || fail 'Informe a URL ou arquivo do FULL.'
    install_dependencies
    fetch_package "$PACKAGE_URL"
    validate_full
    [ ! -e "$APP_DIR" ] || fail "O destino $APP_DIR já existe; use repair ou remova-o conscientemente antes de uma instalação nova."
    /usr/bin/install -d -m 755 "$APP_DIR"
    /usr/bin/unzip -q "$PACKAGE_FILE" -d "$APP_DIR"
    prepare_runtime
    configure_apache
    configure_sync_service
    /usr/bin/systemctl restart apache2
    info 'Base instalada. Acesse /setup.php para provisionar MasterSync, admin e Diamond automaticamente.'
}

update_install() {
    [ -n "$PACKAGE_URL" ] || fail 'Informe a URL ou arquivo OTA.'
    [ -d "$APP_DIR" ] || fail 'Instalação inexistente para update.'
    install_dependencies
    fetch_package "$PACKAGE_URL"
    validate_ota
    /usr/bin/unzip -oq "$PACKAGE_FILE" -d "$APP_DIR"
    prepare_runtime
    /usr/bin/systemctl restart apache2
    /usr/bin/systemctl try-restart bt-sync.service || true
}

repair_install() {
    [ -d "$APP_DIR" ] || fail 'Instalação inexistente para reparo.'
    validate_apache_tools
    prepare_runtime
    [ -f "$APP_DIR/install/apache.conf" ] && configure_apache
    /usr/bin/systemctl restart apache2
    /usr/bin/systemctl try-restart bt-sync.service || true
}

require_root
case "$MODE" in
    new) new_install ;;
    update) update_install ;;
    repair) repair_install ;;
    *) usage; exit 2 ;;
esac
