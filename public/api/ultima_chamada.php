<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();

    // 1. Busca a ULTIMA chamada global realizada no sistema
    $ultimaChamada = Database::fetch(
        "SELECT s.senha, sv.nome as servico, g.nome as local
         FROM senhas s
         JOIN servicos sv ON s.servico_id = sv.id
         LEFT JOIN guiches g ON s.guiche_id = g.id
         WHERE s.status = 'CHAMANDO'
         ORDER BY s.chamada_em DESC, s.id DESC
         LIMIT 1"
    );

    // 2. Busca as últimas 5 chamadas para montar o histórico/atendimento lateral da TV
    $historico = Database::fetchAll(
        "SELECT s.senha, g.nome as local
         FROM senhas s
         LEFT JOIN guiches g ON s.guiche_id = g.id
         WHERE s.status IN ('CHAMANDO', 'FINALIZADA')
         ORDER BY s.chamada_em DESC, s.id DESC
         LIMIT 5"
    );

    // 3. Resposta limpa, estruturada e ideal para consumo público da TV
    echo json_encode([
        'success' => true,
        'data' => [
            'chamando' => $ultimaChamada ? [
                'senha'  => $ultimaChamada['senha'],
                'servico' => $ultimaChamada['servico'],
                'local'   => $ultimaChamada['local'] ?? 'Guichê Geral'
            ] : null,
            'historico' => array_map(function($item) {
                return [
                    'senha' => $item['senha'],
                    'local' => $item['local'] ?? 'Geral'
                ];
            }, $historico ?? [])
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    error_log((string)$e);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno ao processar dados de transmissão pública.'
    ], JSON_UNESCAPED_UNICODE);
}
