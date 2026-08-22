<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

Auth::protegerAPI('ADMIN');

try {
    $tenantId = Auth::tenantId();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $config = Database::fetch("SELECT * FROM fidelidade_config WHERE tenant_id = ? LIMIT 1", [$tenantId]);

        if (!$config) {
            // Cria padrão se não existir
            Database::execute("INSERT INTO fidelidade_config (tenant_id, meta_pontos, premio_desc, ativo) VALUES (?, 10, 'Corte Grátis', 1)", [$tenantId]);
            $config = Database::fetch("SELECT * FROM fidelidade_config WHERE tenant_id = ? LIMIT 1", [$tenantId]);
        }

        echo json_encode(['success' => true, 'data' => $config]);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $meta = (int)($input['meta_pontos'] ?? 10);
        $premio = trim($input['premio_desc'] ?? 'Corte Grátis');
        $ativo = (int)($input['ativo'] ?? 1);

        // v3.5.1: Usa REPLACE para garantir que a config existe ou é atualizada
        Database::execute(
            "REPLACE INTO fidelidade_config (tenant_id, meta_pontos, premio_desc, ativo) VALUES (?, ?, ?, ?)",
            [$tenantId, $meta, $premio, $ativo]
        );

        echo json_encode(['success' => true, 'message' => 'Configurações de fidelidade salvas!']);
        exit;
    }

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
