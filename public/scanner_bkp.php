<?php
declare(strict_types=1);

$bases = [
    'Original Extraído' => dirname(__DIR__) . '/bt-enterprise-lite/database/banco.db',
    'Extraído .db.db'    => dirname(__DIR__) . '/bt-enterprise-lite/database/banco.db.db',
    'Banco Template'    => dirname(__DIR__) . '/bt-enterprise-lite/database/banco_template.db',
    'Integração DB'     => 'Y:/bt-integration/storage/integration.db',
    'Enterprise Full'   => 'Y:/bt-enterprise/database/banco.db'
];

echo "<h1>🕵️ Scanner de Identidade Brandão Tech (V2)</h1>";

foreach ($bases as $nome => $path) {
    echo "<h3>Analisando: $nome ($path) ...</h3>";

    if (!file_exists($path)) {
        echo "<p style='color:red;'>ARQUIVO NÃO ENCONTRADO</p>";
        continue;
    }

    try {
        $db = new PDO("sqlite:$path");
        // Tenta buscar em várias tabelas comuns
        $found = false;
        $tables = ['operadores', 'users', 'usuarios'];

        foreach($tables as $table) {
            try {
                $stmt = $db->query("SELECT * FROM $table WHERE nome LIKE '%Piu%' OR nome LIKE '%João%' OR nome LIKE '%Ricardo%'");
                $ops = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($ops)) {
                    echo "<p style='color:green; font-weight:bold;'>✅ ENCONTRADO na tabela [$table]!</p>";
                    foreach($ops as $o) echo " - " . ($o['nome'] ?? $o['login'] ?? 'Sem Nome') . "<br>";
                    echo "<a href='migrar_bkp.php?source=" . urlencode($path) . "&table=$table' style='background:green; color:#fff; padding:5px 10px; text-decoration:none;'>MIGRAR ESTE BANCO</a>";
                    $found = true;
                    break;
                }
            } catch(Exception $e) {}
        }

        if (!$found) echo "<p style='color:gray;'>Nenhum dado relevante encontrado.</p>";

    } catch (Exception $e) {
        echo "<p style='color:orange;'>Erro ao ler: " . $e->getMessage() . "</p>";
    }
}
