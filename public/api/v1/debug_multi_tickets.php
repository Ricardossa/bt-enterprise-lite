<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "🔍 AUDITORIA DE MULTI-TICKETS (CLIENTE)...\n\n";

    // 1. Verifica colunas
    $resInfo = Database::getTableColumns("senhas");
    $cols = array_column($resInfo, 'name');
    echo "Colunas presentes: " . implode(', ', $cols) . "\n\n";

    // 2. Busca senhas ativas para teste
    $senhas = Database::fetchAll("SELECT id, codigo, status, cliente_uuid FROM senhas WHERE status != 'FINALIZADA' ORDER BY id DESC LIMIT 5");
    echo "Senhas Ativas no Banco:\n";
    print_r($senhas);

    if (empty($senhas)) {
        echo "\n⚠️ Nenhuma senha ativa encontrada para teste.";
    } else {
        // 3. Simula o comportamento da API multi_check
        $uuids = array_column($senhas, 'cliente_uuid');
        $placeholders = implode(',', array_fill(0, count($uuids), '?'));

        $sql = "SELECT id, status FROM senhas WHERE cliente_uuid IN ($placeholders)";
        $rows = Database::fetchAll($sql, $uuids);
        echo "\nTeste de Query Multi-Check:\n";
        echo "UUIDs enviados: " . count($uuids) . "\n";
        echo "Resultados: " . count($rows) . "\n";
    }

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage();
}
?>
