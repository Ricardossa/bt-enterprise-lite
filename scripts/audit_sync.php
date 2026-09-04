<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

function getConfigs($tenantId) {
    try {
        $rows = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE tenant_id = ?", [$tenantId]);
        $configs = [];
        foreach ($rows as $row) {
            $configs[$row['chave']] = $row['valor'];
        }
        return $configs;
    } catch (Exception $e) {
        return [];
    }
}

$lite = getConfigs(1);
$parada = getConfigs(30);

echo "Keys in Lite (ID 1):\n";
print_r(array_keys($lite));
echo "\nTotal keys in Lite: " . count($lite) . "\n";
echo "Total keys in Parada: " . count($parada) . "\n";

echo "\n1. Comparacao de Configuracoes:\n";
$allKeys = array_unique(array_merge(array_keys($lite), array_keys($parada)));
sort($allKeys);

foreach ($allKeys as $key) {
    if (isset($lite[$key]) && !isset($parada[$key])) {
        echo "[MISSING in Parada] Key '{$key}' exists in 'lite' but is missing in 'paradaobrigatoriavilas'\n";
    } elseif (isset($parada[$key]) && !isset($lite[$key])) {
        echo "[MISSING in Lite] Key '{$key}' exists in 'paradaobrigatoriavilas' but is missing in 'lite'\n";
    } elseif (isset($lite[$key]) && isset($parada[$key]) && $lite[$key] !== $parada[$key]) {
        echo "[DIFFERENT] Key '{$key}': Lite='{$lite[$key]}', Parada='{$parada[$key]}'\n";
    }
}

echo "\n2. Verificacao de logo_url e promo_logo para ID 30:\n";
echo "logo_url: " . ($parada['logo_url'] ?? 'NOT SET') . "\n";
echo "promo_logo: " . ($parada['promo_logo'] ?? 'NOT SET') . "\n";

echo "\n3. Verificacao de clube_planos e fidelidade_config para ID 30:\n";
$tables = ['clube_planos', 'fidelidade_config'];
foreach ($tables as $table) {
    try {
        $exists = Database::fetchAll("SHOW TABLES LIKE '$table'");
        if (empty($exists)) {
            echo "Tabela '$table' NAO EXISTE no banco de dados.\n";
            continue;
        }
        $count = Database::fetch("SELECT count(*) as total FROM $table WHERE tenant_id = 30");
        echo "Registros em $table (ID 30): " . ($count['total'] ?? 0) . "\n";
    } catch (Exception $e) {
        echo "Erro ao ler $table: " . $e->getMessage() . "\n";
    }
}
