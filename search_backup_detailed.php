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

    if (strpos($line, 'paradaobrigatoria') !== false || strpos($line, '4aa0051b-2af1-44a4-a361-ea063c1aaf02') !== false) {
        echo "TABLE: $currentTable | LINE: " . trim($line) . "\n";
    }
}
gzclose($handle);
