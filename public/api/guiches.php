<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BTQueue\Core\Auth;
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

try {

    Auth::iniciar();

    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($metodo === 'PUT') {
        $metodo = 'POST';
    }

    if ($metodo === 'POST' || $metodo === 'DELETE') {
        Auth::protegerAPI('ADMIN');
    }

    $tenantId = Auth::tenantId();

    if ($tenantId <= 0) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Tenant não identificado.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if ($metodo === 'GET') {

        $guiches = Database::fetchAll(
            "SELECT id, tenant_id, codigo, nome, icone, cor, ativo
             FROM guiches
             WHERE tenant_id = ?
               AND ativo = 1
             ORDER BY codigo ASC",
            [$tenantId]
        );

        // Garante que o retorno seja sempre uma lista, mesmo que vazia
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            $guiches ?: [],
            JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );

        exit;
    }

    if ($metodo === 'POST') {

        $dados = json_decode(
            file_get_contents('php://input'),
            true
        ) ?? $_POST;

        $codigo = trim((string)($dados['codigo'] ?? ''));
        $nome   = trim((string)($dados['nome'] ?? ''));
        $icone  = trim((string)($dados['icone'] ?? '⚙️'));
        $cor    = trim((string)($dados['cor'] ?? '#1565C0'));

        $id = isset($dados['id']) && $dados['id'] !== ''
            ? (int)$dados['id']
            : null;

        if ($codigo === '' || $nome === '') {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Código e Nome são obrigatórios.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $registroExistente = Database::fetch(
            "SELECT id, ativo
             FROM guiches
             WHERE tenant_id = ?
               AND codigo = ?
             LIMIT 1",
            [$tenantId, $codigo]
        );

        // ========================================================
        // NOVO GUICHÊ
        // ========================================================

        if ($id === null) {

            if ($registroExistente) {

                Database::execute(
                    "UPDATE guiches
                     SET nome = ?,
                         icone = ?,
                         cor = ?,
                         ativo = 1,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?
                       AND tenant_id = ?",
                    [
                        $nome,
                        $icone,
                        $cor,
                        (int)$registroExistente['id'],
                        $tenantId
                    ]
                );

            } else {

                Database::execute(
                    "INSERT INTO guiches
                        (
                            tenant_id,
                            codigo,
                            nome,
                            icone,
                            cor,
                            ativo,
                            created_at,
                            updated_at
                        )
                     VALUES
                        (?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                    [
                        $tenantId,
                        $codigo,
                        $nome,
                        $icone,
                        $cor
                    ]
                );
            }

        } else {

            // ====================================================
            // EDITAR GUICHÊ
            // ====================================================

            if (
                $registroExistente &&
                (int)$registroExistente['id'] !== $id
            ) {

                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' => 'Este código já está sendo utilizado por outro local.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            Database::execute(
                "UPDATE guiches
                 SET codigo = ?,
                     nome = ?,
                     icone = ?,
                     cor = ?,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?
                   AND tenant_id = ?",
                [
                    $codigo,
                    $nome,
                    $icone,
                    $cor,
                    $id,
                    $tenantId
                ]
            );
        }

        echo json_encode([
            'success' => true,
            'message' => 'Local físico salvo com sucesso.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // ============================================================
    // DELETE
    // ============================================================

    if ($metodo === 'DELETE') {

        $dados = json_decode(
            file_get_contents('php://input'),
            true
        ) ?? [];

        $id = (int)($dados['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'ID inválido.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        Database::execute(
            "UPDATE guiches
             SET ativo = 0,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = ?
               AND tenant_id = ?",
            [
                $id,
                $tenantId
            ]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Local físico excluído com sucesso.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido.'
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {

    error_log((string)$e);

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erro interno ao processar a requisição de locais físicos.'
    ], JSON_UNESCAPED_UNICODE);
}
