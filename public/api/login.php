<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Método não permitido.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $dados = json_decode(file_get_contents('php://input'), true);

    if (!$dados) {
        $dados = $_POST;
    }

    $login = trim((string)($dados['login'] ?? ''));
    $senha = (string)($dados['senha'] ?? '');

    if ($login === '' || $senha === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Informe login e senha.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        if (!Auth::login($login, $senha)) {
            throw new Exception("Login ou senha inválidos.");
        }
    } catch (Throwable $e) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso.',
        'operador' => Auth::operador()
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}
