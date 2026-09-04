<?php
declare(strict_types=1);

// We need to bypass the bootstrap's exception handler or at least not let it crash our CLI output
// But bootstrap is required for the autoloader and config.
require_once __DIR__ . '/bootstrap.php';

use BTQueue\Core\Database;
use BTQueue\Core\Config;

// Get connection details to create our own PDO to avoid bootstrap's instance if needed
// Actually, let's just use the existing one but very carefully.

try {
    $db = Database::getInstance();

    // Check bt_enterprise_saas
    $dbname = 'bt_enterprise_saas';
    $tables = ['configuracoes', 'tenants', 'system_info', 'licencas'];

    foreach ($tables as $table) {
        $cols = $db->query("DESCRIBE `$dbname`.`$table`")->fetchAll(PDO::FETCH_ASSOC);
        $textCols = [];
        foreach ($cols as $col) {
            $type = strtolower($col['Type']);
            if (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
                $textCols[] = "`" . $col['Field'] . "`";
            }
        }
        if (empty($textCols)) continue;

        $where = implode(" LIKE '%:8080%' OR ", $textCols) . " LIKE '%:8080%'";
        $sql = "SELECT * FROM `$dbname`.`$table` WHERE $where";
        $results = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($results)) {
            echo "MATCH in $dbname.$table:\n";
            print_r($results);
        }
    }

    // Check bt_platform if it exists
    $dbname = 'bt_platform';
    try {
        $tables = $db->query("SHOW TABLES FROM `$dbname`")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $cols = $db->query("DESCRIBE `$dbname`.`$table`")->fetchAll(PDO::FETCH_ASSOC);
            $textCols = [];
            foreach ($cols as $col) {
                $type = strtolower($col['Type']);
                if (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
                    $textCols[] = "`" . $col['Field'] . "`";
                }
            }
            if (empty($textCols)) continue;

            $where = implode(" LIKE '%:8080%' OR ", $textCols) . " LIKE '%:8080%'";
            $sql = "SELECT * FROM `$dbname`.`$table` WHERE $where";
            $results = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($results)) {
                echo "MATCH in $dbname.$table:\n";
                print_r($results);
            }
        }
    } catch (Exception $e) {
        echo "Error checking bt_platform: " . $e->getMessage() . "\n";
    }

} catch (Throwable $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
