<?php
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json');

try {
    $tenantId = Auth::tenantId();
    $planos = Database::fetchAll("SELECT * FROM clube_planos WHERE tenant_id = ?", [$tenantId]);
    $assinaturas = Database::fetchAll("SELECT a.*, c.nome as cliente_nome, p.nome as plano_nome
                                      FROM clube_assinaturas a
                                      JOIN clientes c ON c.id = a.cliente_id
                                      JOIN clube_planos p ON p.id = a.plano_id
                                      WHERE a.tenant_id = ?", [$tenantId]);

    echo json_encode([
        'tenant_id' => $tenantId,
        'planos' => $planos,
        'assinaturas' => $assinaturas
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
