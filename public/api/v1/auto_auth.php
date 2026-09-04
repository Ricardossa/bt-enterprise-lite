<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;
use BTQueue\Core\Logger;

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

Logger::info(">>> API V1 AUTO_AUTH CHAMADA: IP " . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));

try {
    // [MODO ULTRA-DIRETO v1.2.0]
    Auth::iniciar();

    $operador = Database::fetch("SELECT o.*, g.nome AS guiche_nome, s.nome AS servico_nome
                                 FROM operadores o
                                 LEFT JOIN guiches g ON g.id = o.guiche_id
                                 LEFT JOIN servicos s ON s.id = o.servico_id
                                 WHERE o.ativo = 1 ORDER BY o.nivel DESC LIMIT 1");

    if (!$operador) {
        throw new Exception("Nenhum operador cadastrado.");
    }

    $_SESSION['operador'] = [
        'id'            => (int)$operador['id'],
        'nome'          => $operador['nome'],
        'login'         => $operador['login'],
        'nivel'         => strtoupper($operador['nivel'] ?? 'OPERADOR'),
        'guiche_id'     => $operador['guiche_id'],
        'servico_id'    => $operador['servico_id'],
        'guiche_nome'   => $operador['guiche_nome'],
        'servico_nome'  => $operador['servico_nome']
    ];

    // [v2.2.0] Busca Logo da Unidade
    $tenantId = Auth::tenantId();
    $logoRow = Database::fetch("SELECT valor FROM configuracoes WHERE tenant_id = ? AND chave = 'logo_url' LIMIT 1", [$tenantId]);
    $unitLogo = $logoRow['valor'] ?? '';

    echo json_encode([
        'success' => true,
        'operador' => $_SESSION['operador'],
        'unit_logo' => $unitLogo
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    Logger::error("ERRO NO AUTO_AUTH: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
