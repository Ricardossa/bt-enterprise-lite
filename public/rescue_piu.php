<?php
require_once __DIR__ . '/../bootstrap.php';
$path = 'Y:/bt-integration/storage/integration.db';
if (!file_exists($path)) die("Arquivo não encontrado.");

try {
    $db = new PDO("sqlite:$path");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    echo "<h1>🕵️ Resgate de Dados (Piu e João)</h1>";

    // Tenta listar tabelas
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Tabelas encontradas: " . implode(', ', $tables) . "</h3>";

    if (in_array('operadores', $tables)) {
        $ops = $db->query("SELECT * FROM operadores")->fetchAll();
        echo "<h4>Operadores:</h4><pre>" . json_encode($ops, JSON_PRETTY_PRINT) . "</pre>";
    }

    if (in_array('promocoes', $tables)) {
        $promos = $db->query("SELECT * FROM promocoes")->fetchAll();
        echo "<h4>Promoções:</h4><pre>" . json_encode($promos, JSON_PRETTY_PRINT) . "</pre>";
    }

    echo "<br><a href='migrar_bkp.php?source=" . urlencode($path) . "' style='background:green; color:#fff; padding:15px; text-decoration:none; font-weight:bold;'>MIGRAR TUDO DAQUI AGORA</a>";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
