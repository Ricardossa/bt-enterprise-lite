<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

Auth::protegerAPI('ADMIN');

try {
    $tenantId = Auth::tenantId();
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception("Dados inválidos.");
    }

    $nome = trim((string)($input['nome'] ?? ''));
    $whatsapp = trim((string)($input['whatsapp'] ?? ''));
    $instagram = trim((string)($input['instagram'] ?? ''));

    if (!$nome) throw new Exception("Nome da barbearia é obrigatório.");

    // 1. Atualiza nome na tabela mestre de tenants
    Database::execute("UPDATE tenants SET nome = ? WHERE id = ?", [$nome, $tenantId]);

    // 2. Atualiza configurações de contato
    Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor, tipo) VALUES (?, 'empresa', ?, 'STRING')", [$tenantId, $nome]);
    Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor, tipo) VALUES (?, 'whatsapp', ?, 'STRING')", [$tenantId, $whatsapp]);
    Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor, tipo) VALUES (?, 'instagram', ?, 'STRING')", [$tenantId, $instagram]);

    echo json_encode(['success' => true, 'message' => 'Perfil da unidade atualizado!']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
