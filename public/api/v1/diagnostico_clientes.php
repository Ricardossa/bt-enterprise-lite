<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

try {
    $tenant = Auth::getCurrentTenant();
    $tenantId = (int)($tenant['id'] ?? 0);

    $clientes = Database::fetchAll("SELECT id, uuid, tenant_id, nome, whatsapp, created_at FROM clientes");
    $fidelidade_saldo = Database::fetchAll("SELECT * FROM fidelidade_saldo");
    $fidelidade_config = Database::fetchAll("SELECT * FROM fidelidade_config");
    $senhas = Database::fetchAll("SELECT id, tenant_id, cliente_uuid, nome_cliente, status, created_at FROM senhas ORDER BY id DESC LIMIT 20");

    echo json_encode([
        'success' => true,
        'current_tenant' => $tenant,
        'diagnostico' => [
            'total_clientes_banco' => count($clientes),
            'clientes' => $clientes,
            'fidelidade_saldo' => $fidelidade_saldo,
            'fidelidade_config' => $fidelidade_config,
            'ultimas_senhas' => $senhas
        ]
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
