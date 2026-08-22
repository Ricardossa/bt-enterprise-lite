<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

try {
    $tenantId = Auth::tenantId();
    // Corrigido: usando created_at em vez de criada_em
    $senhas = Database::fetchAll("SELECT id, codigo, status, tenant_id, nome_cliente, created_at, finalizada_em FROM senhas WHERE DATE(created_at) = CURDATE() AND tenant_id = ?", [$tenantId]);

    header('Content-Type: application/json');
    echo json_encode([
        'tenant_id' => $tenantId,
        'total_hoje' => count($senhas),
        'dados' => $senhas
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Erro na auditoria: " . $e->getMessage();
}
