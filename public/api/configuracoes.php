<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

Auth::protegerAPI('ADMIN');

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $tenantId = Auth::tenantId();

    if ($method === 'GET') {
        $linhas = Database::fetchAll(
            "SELECT chave, valor FROM configuracoes WHERE tenant_id = ?",
            [$tenantId]
        );

        $config = [];
        foreach ($linhas as $linha) {
            $config[$linha['chave']] = $linha['valor'];
        }

        echo json_encode(['success' => true, 'data' => $config]);
        exit;
    }

    if ($method === 'POST') {
        $dados = json_decode(file_get_contents('php://input'), true);

        if (!$dados || !is_array($dados)) {
            throw new Exception('Dados inválidos.');
        }

        foreach ($dados as $chave => $valor) {
            Database::execute(
                "REPLACE INTO configuracoes (tenant_id, chave, valor, tipo) VALUES (?, ?, ?, 'STRING')",
                [$tenantId, $chave, (string)$valor]
            );
        }

        echo json_encode([
            'success' => true,
            'message' => 'Configurações salvas com sucesso.'
        ]);

        exit;
    }

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido.'
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
