<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

$backupFile = dirname(__DIR__) . '/database/backup_fidelidade_' . date('Ymd_His') . '.sql';

try {
    $tables = ['tenants', 'clientes', 'fidelidade_config', 'fidelidade_saldo', 'fidelidade_historico', 'senhas', 'configuracoes'];
    $sqlDump = "";

    foreach ($tables as $table) {
        $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
        $createTable = Database::fetch("SHOW CREATE TABLE `$table`")['Create Table'];
        $sqlDump .= $createTable . ";\n\n";

        $rows = Database::fetchAll("SELECT * FROM `$table`");
        foreach ($rows as $row) {
            $keys = array_keys($row);
            $values = array_map(function($val) {
                if ($val === null) return 'NULL';
                return "'" . addslashes((string)$val) . "'";
            }, array_values($row));

            $sqlDump .= "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $values) . ");\n";
        }
        $sqlDump .= "\n";
    }

    file_put_contents($backupFile, $sqlDump);
    echo "<h1>✅ Backup realizado com sucesso!</h1>";
    echo "<p>Arquivo salvo em: $backupFile</p>";
} catch (Exception $e) {
    echo "<h1>❌ Erro no backup:</h1> " . $e->getMessage();
}
