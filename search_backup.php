<?php
$file = 'Y:/bt-platform/storage/backups/internal/master_db_20260901_040002.sql.gz';
$handle = gzopen($file, 'r');
if (!$handle) die("Could not open file\n");

$searchTerms = ['paradaobrigatoria', 'paradaobrigatoriavilas'];
$found = [];

while (!gzeof($handle)) {
    $line = gzgets($handle, 4096);
    foreach ($searchTerms as $term) {
        if (strpos($line, $term) !== false) {
            echo "FOUND '$term': " . trim($line) . "\n";
        }
    }
}
gzclose($handle);
