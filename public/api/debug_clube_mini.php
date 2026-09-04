<?php
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;
header('Content-Type: application/json');
try {
    $tenantId = Auth::tenantId();
    $assinaturas = Database::fetchAll("SELECT a.id, a.cliente_id, a.cortes_restantes, a.data_fim, a.status FROM clube_assinaturas a WHERE a.tenant_id = ?", [$tenantId]);
    echo json_encode($assinaturas);
} catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
