<?php
/**
 * 🛰️ BT Queue Enterprise - MariaDB Connection Test
 * Valida a conexão SaaS usando as configurações do sistema.
 */
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Config;

$col_green = "\033[0;32m";
$col_red = "\033[0;31m";
$col_blue = "\033[0;34m";
$col_reset = "\033[0m";

echo "=====================================================\n";
echo "       🛰️ TESTE DE CONEXÃO MARIADB SAAS\n";
echo "=====================================================\n\n";

$dbConfig = Config::get('database');
echo "Host: " . $dbConfig['host'] . "\n";
echo "Banco: " . $dbConfig['dbname'] . "\n";
echo "Usuário: " . $dbConfig['username'] . "\n\n";

try {
    echo "Iniciando tentativa de conexão... ";
    $db = Database::getInstance();
    echo "{$col_green}✔ CONECTADO!{$col_reset}\n\n";

    // 1. Verificar Versão
    $version = $db->query("SELECT VERSION()")->fetchColumn();
    echo "{$col_blue}🔹 Versão do Servidor:{$col_reset} $version\n";

    // 2. Verificar Tabelas Críticas
    echo "{$col_blue}🔹 Verificando Tabelas:{$col_reset}\n";
    $tables = ['system_info', 'licencas', 'operadores', 'configuracoes'];
    foreach ($tables as $table) {
        $res = $db->query("SHOW TABLES LIKE '$table'")->rowCount();
        $status = $res > 0 ? "{$col_green}[OK]{$col_reset}" : "{$col_red}[AUSENTE]{$col_reset}";
        printf("  - %-15s %s\n", $table, $status);
    }

    // 3. Verificar Registro de Instalação
    $uuid = Database::fetch("SELECT installation_uuid FROM system_info LIMIT 1");
    if ($uuid) {
        echo "\n{$col_green}✅ Instalação Identificada:{$col_reset} " . $uuid['installation_uuid'] . "\n";
    } else {
        echo "\n{$col_red}⚠ Nenhuma instalação encontrada na tabela system_info.{$col_reset}\n";
    }

} catch (Exception $e) {
    echo "{$col_red}✖ ERRO CRÍTICO:{$col_reset} " . $e->getMessage() . "\n";
    echo "\n{$col_blue}Dica:{$col_reset} Verifique se o usuário possui permissões GRANT e se o bind-address do MariaDB permite conexões de " . gethostname() . ".\n";
}

echo "\n=====================================================\n";
