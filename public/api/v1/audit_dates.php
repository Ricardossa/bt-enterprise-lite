<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json');

try {
    $tenantId = Auth::tenantId();

    $stats = Database::fetchAll("
        SELECT
            status,
            COUNT(*) as qtd,
            MIN(created_at) as primeira,
            MAX(created_at) as ultima,
            SUM(CASE WHEN finalizada_em IS NULL THEN 1 ELSE 0 END) as sem_data_finalizacao
        FROM senhas
        WHERE tenant_id = ?
        GROUP BY status
    ", [$tenantId]);

    $exemplos = Database::fetchAll("
        SELECT id, status, created_at, finalizada_em, valor_total
        FROM senhas
        WHERE tenant_id = ?
        ORDER BY id DESC LIMIT 10
    ", [$tenantId]);

    echo json_encode([
        'tenant_id' => $tenantId,
        'resumo_por_status' => $stats,
        'ultimas_senhas' => $exemplos
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
