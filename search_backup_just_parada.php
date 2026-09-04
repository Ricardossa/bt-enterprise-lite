<?php
$file = 'Y:/bt-platform/storage/backups/internal/master_db_20260901_040002.sql.gz';
$handle = gzopen($file, 'r');
if (!$handle) die("Could not open file\n");

$currentTable = '';
while (!gzeof($handle)) {
    $line = gzgets($handle, 16384);
    if (preg_match('/INSERT INTO `([^`]+)`/', $line, $matches)) {
        $currentTable = $matches[1];
    }

    if ($currentTable === 'instalacoes' && strpos($line, 'paradaobrigatoria') !== false) {
        echo "TABLE: instalacoes | LINE: " . trim($line) . "\n";
    }
}
gzclose($handle);
