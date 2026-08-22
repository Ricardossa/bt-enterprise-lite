<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

try {
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Compatibilidade REST:
    // reutiliza a lógica do POST para operações PUT.
    if ($metodo === 'PUT') {
        $metodo = 'POST';
    }

    if ($metodo === 'POST' || $metodo === 'DELETE') {
        \BTQueue\Core\Auth::protegerAPI('ADMIN');
    }

    // =========================================================================
    // CASO A: REQUISIÇÃO GET - LISTAR OS LOCAIS FÍSICOS (GUICHÊS) ATIVOS
    // =========================================================================
    if ($metodo === 'GET') {
        // Busca apenas registros onde ativo = 1 para popular a tabela do admin
        $guiches = Database::fetchAll("SELECT * FROM guiches WHERE ativo = 1 ORDER BY codigo ASC");
        
        echo json_encode($guiches, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // =========================================================================
    // CASO B: REQUISIÇÃO POST - SALVAR / UPSERT INTELIGENTE
    // =========================================================================
    if ($metodo === 'POST') {
        $dados = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $codigo = trim((string)($dados['codigo'] ?? ''));
        $nome   = trim((string)($dados['nome'] ?? ''));
        $icone  = trim((string)($dados['icone'] ?? '⚙️'));
        $cor    = trim((string)($dados['cor'] ?? '#1565C0'));
        $id     = isset($dados['id']) && $dados['id'] !== '' ? (int)$dados['id'] : null;

        if ($codigo === '' || $nome === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Código e Nome são obrigatórios.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Procura registro global com o mesmo código (ativo ou inativo)
        $registroExistente = Database::fetch(
            "SELECT id, ativo FROM guiches WHERE codigo = ? LIMIT 1",
            [$codigo]
        );

        if ($id === null) {
            // --- NOVO REGISTRO (UPSERT TRATADO) ---
            if ($registroExistente) {
                // Se já existia desativado, reativa e atualiza os dados
                Database::execute(
                    "UPDATE guiches 
                     SET nome = ?, icone = ?, cor = ?, ativo = 1, updated_at = datetime('now', 'localtime') 
                     WHERE codigo = ?",
                    [$nome, $icone, $cor, $codigo]
                );
            } else {
                // Inserção limpa tradicional
                Database::execute(
                    "INSERT INTO guiches (codigo, nome, icone, cor, ativo, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, 1, datetime('now', 'localtime'), datetime('now', 'localtime'))",
                    [$codigo, $nome, $icone, $cor]
                );
            }
        } else {
            // --- EDIÇÃO DE REGISTRO EXISTENTE ---
            if ($registroExistente && (int)$registroExistente['id'] !== $id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Este código já está sendo utilizado por outro local.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            Database::execute(
                "UPDATE guiches 
                 SET codigo = ?, nome = ?, icone = ?, cor = ?, updated_at = datetime('now', 'localtime') 
                 WHERE id = ?",
                [$codigo, $nome, $icone, $cor, $id]
            );
        }

        echo json_encode(['success' => true, 'message' => 'Local físico salvo com sucesso!'], JSON_UNESCAPED_UNICODE);
        exit;
    }


    // =========================================================================
    // CASO C: REQUISIÇÃO DELETE - EXCLUSÃO LÓGICA
    // =========================================================================
    if ($metodo === 'DELETE') {

        $dados = json_decode(file_get_contents('php://input'), true) ?? [];

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
                 updated_at = datetime('now', 'localtime')
             WHERE id = ?",
            [$id]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Local físico excluído com sucesso.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // Se não for nem GET nem POST, barra por segurança
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    error_log((string)$e);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno ao processar a requisição de locais físicos.'
    ], JSON_UNESCAPED_UNICODE);
}
