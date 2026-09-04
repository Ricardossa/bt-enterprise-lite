<?php
/**
 * 🏥 BT Queue Enterprise - Doctor
 * Auditoria completa de saúde do sistema.
 */
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\SecurityService;

$col_green = "\033[0;32m";
$col_red = "\033[0;31m";
$col_reset = "\033[0m";

echo "=====================================================\n";
echo "        🏥 DIAGNÓSTICO BRANDÃO TECH\n";
echo "=====================================================\n\n";

function check(string $label, bool $status, string $extra = '') {
    global $col_green, $col_red, $col_reset;
    $icon = $status ? "{$col_green}✔ [OK]{$col_reset}" : "{$col_red}✖ [ERRO]{$col_reset}";
    printf("%-25s %s %s\n", $label, $icon, $extra);
}

// 1. Requisitos de Motor
check("Apache / PHP", defined('PHP_VERSION'));
check("PDO MariaDB", extension_loaded('pdo_mysql'));
check("CURL", extension_loaded('curl'));

// 2. Banco e Permissões
try {
    $dbExists = Database::exists();
    check("Conexão MariaDB", $dbExists);
} catch (Exception $e) {
    check("Conexão MariaDB", false, $e->getMessage());
}
check("Permissão Pasta DB", is_writable(dirname(__DIR__) . '/database'));

// 3. Integridade Diamond
try {
    $lic = Database::fetch("SELECT status, uuid FROM licencas LIMIT 1");
    check("UUID Identidade", !empty($lic['uuid'] ?? ''), $lic['uuid'] ?? '');
    check("Licença Ativa", ($lic['status'] ?? '') === 'ATIVA', $lic['status'] ?? '');

    $admin = Database::fetch("SELECT id FROM operadores WHERE nivel = 'ADMIN' LIMIT 1");
    check("Administrador", !empty($admin));

    $hwid = SecurityService::getHardwareId();
    check("Hardware Selado", !empty($hwid), substr($hwid, 0, 15) . '...');

} catch (Exception $e) {
    check("Integridade Banco", false, $e->getMessage());
}

echo "\n=====================================================\n";
?>
