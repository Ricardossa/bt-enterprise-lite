<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;
try {
    $tables = Database::fetchAll("SHOW TABLES");
    foreach($tables as $t) {
        $table = current($t);
        $cols = Database::getTableColumns($table);
        foreach($cols as $colName) {
            if ($colName == 'operador_id') {
                $count = Database::fetch("SELECT COUNT(*) as total FROM $table WHERE operador_id = 1")['total'];
                if ($count > 0) {
                    echo "Found $count orphans in $table. Moving to ID 2...\n";
                    Database::execute("UPDATE $table SET operador_id = 2 WHERE operador_id = 1");
                }
            }
        }
    }
    echo "Integridade restaurada para ID 2.";
} catch(Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
