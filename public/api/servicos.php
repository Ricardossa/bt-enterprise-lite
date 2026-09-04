<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BTQueue\Core\ServicoService;

header('Content-Type: application/json; charset=utf-8');

$service = new ServicoService();

$method = $_SERVER['REQUEST_METHOD'];

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

try {

    if ($method !== 'GET') {
        \BTQueue\Core\Auth::protegerAPI('ADMIN');
    }

    switch ($method) {

        case 'GET':

            if (isset($_GET['id'])) {

                $resultado = $service->buscar((int)$_GET['id']);

                echo json_encode([
                    'success' => true,
                    'data' => $resultado
                ], JSON_UNESCAPED_UNICODE);

            } else {

                echo json_encode([
                    'success' => true,
                    'data' => $service->listar()
                ], JSON_UNESCAPED_UNICODE);

            }

            break;

        case 'POST':

            $retorno = $service->adicionar(
                trim($input['codigo'] ?? ''),
                trim($input['nome'] ?? ''),
                trim($input['slug'] ?? ''),
                trim($input['prefixo'] ?? ''),
                trim($input['icone'] ?? ''),
                trim($input['cor'] ?? ''),
                (int)($input['ordem'] ?? 0),
                (int)($input['tempo_medio'] ?? 0),
                (float)($input['preco'] ?? 0),
                (int)($input['promo_ativa'] ?? 0),
                (float)($input['promo_desconto'] ?? 20.00),
                (string)($input['promo_dias'] ?? '[1,2,3]')
            );

            echo json_encode(
                $retorno,
                JSON_UNESCAPED_UNICODE
            );

            break;

        case 'PUT':

            $retorno = $service->editar(
                (int)($input['id'] ?? 0),
                trim($input['codigo'] ?? ''),
                trim($input['nome'] ?? ''),
                trim($input['slug'] ?? ''),
                trim($input['prefixo'] ?? ''),
                trim($input['icone'] ?? ''),
                trim($input['cor'] ?? ''),
                (int)($input['ordem'] ?? 0),
                (int)($input['tempo_medio'] ?? 0),
                (float)($input['preco'] ?? 0),
                (int)($input['promo_ativa'] ?? 0),
                (float)($input['promo_desconto'] ?? 20.00),
                (string)($input['promo_dias'] ?? '[1,2,3]')
            );

            echo json_encode(
                $retorno,
                JSON_UNESCAPED_UNICODE
            );

            break;

        case 'DELETE':

            $retorno = $service->excluir(
                (int)($input['id'] ?? 0)
            );

            echo json_encode(
                $retorno,
                JSON_UNESCAPED_UNICODE
            );

            break;

        default:

            http_response_code(405);

            echo json_encode([
                'success' => false,
                'message' => 'Método não permitido.'
            ], JSON_UNESCAPED_UNICODE);

            break;
    }

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}