#!/bin/bash
# 🚀 BRANDÃO TECH - LINUX APPLIANCE INSTALLER v2.0 (Diamond Standard)
# Este script padroniza o sistema para produção industrial.

echo "🏗️ Padronizando o Appliance Brandão Tech..."

APP_PATH="/var/www/html/btqueue"

# 1. Ajuste de Permissões Industriais
chown -R www-data:www-data $APP_PATH
chmod -R 755 $APP_PATH
chmod -R 775 $APP_PATH/database
chmod -R 775 $APP_PATH/logs
chmod -R 775 $APP_PATH/public/uploads

# 2. Reset de Estado (Modo Cliente Novo)
rm -f $APP_PATH/database/.installed
cp $APP_PATH/database/banco_template.db $APP_PATH/database/banco.db
chown www-data:www-data $APP_PATH/database/banco.db
chmod 775 $APP_PATH/database/banco.db

# 3. Reinicia Serviços
systemctl restart apache2
systemctl restart bt-sync 2>/dev/null

echo "✅ PADRONIZAÇÃO CONCLUÍDA!"
echo "👉 Acesse: http://$(hostname -I | awk '{print $1}')/"
