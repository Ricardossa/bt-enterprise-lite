<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

Auth::protegerAPI();

header('Content-Type: application/json; charset=utf-8');

try {
    $dados = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = isset($dados['id']) ? (int)$dados['id'] : 0;

    if ($id <= 0) {
        throw new Exception("ID da senha inválido.");
    }

    $tenantId = Auth::tenantId();
    if ($tenantId <= 0) throw new Exception("Não autorizado.");

    $senha = Database::fetch("SELECT * FROM senhas WHERE id = ? AND tenant_id = ? LIMIT 1", [$id, $tenantId]);

    if (!$senha) {
        throw new Exception("Senha não encontrada.");
    }

    // [RECHAMAR] - Atualiza o timestamp (v6.8.5: Unificação de Relógio via PHP)
    $agora = date('Y-m-d H:i:s');
    Database::execute(
        "UPDATE senhas SET chamada_em = ? WHERE id = ? AND tenant_id = ?",
        [$agora, $id, $tenantId]
    );

    // Resolve o código do guichê
    $guiche = Database::fetch("SELECT codigo FROM guiches WHERE id = ? AND tenant_id = ? LIMIT 1", [$senha['guiche_id'], $tenantId]);
    $guicheCodigo = $guiche ? $guiche['codigo'] : '01';

    // Dispara novamente o evento de chamada na fila de sincronização (MasterSync)
    Database::execute(
        "INSERT INTO sync_queue (tenant_id, evento, entidade, referencia_id, payload, sincronizado) VALUES (?, ?, ?, ?, ?, 0)",
        [
            $tenantId,
            'CHAMAR',
            'senha',
            $id,
            json_encode([
                  'senha' => !empty($senha['nome_cliente']) ? $senha['nome_cliente'] : ($senha['codigo'] ?? $senha['senha']),
                  'codigo' => $senha['codigo'] ?? $senha['senha'],
                  'nome_cliente' => $senha['nome_cliente'] ?? null,
                  'guiche' => $guicheCodigo
              ], JSON_UNESCAPED_UNICODE)
        ]
    );

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
