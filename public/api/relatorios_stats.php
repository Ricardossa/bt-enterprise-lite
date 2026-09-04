<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

// Trava de segurança: Apenas ADMIN vê relatórios
Auth::protegerAPI('ADMIN');

try {
    $inicio = $_GET['inicio'] ?? date('Y-m-d');
    $fim = $_GET['fim'] ?? date('Y-m-d');
    $params = [$inicio, $fim];

    // 1. Ranking de Atendimentos por Operador
    $rankingOperadores = Database::fetchAll("
        SELECT
            atendente as nome,
            COUNT(*) as total,
            AVG(TIMESTAMPDIFF(MINUTE, chamada_em, finalizada_em)) as tempo_medio_atendimento
        FROM senhas
        WHERE status = 'FINALIZADA' AND atendente IS NOT NULL
        AND tenant_id = ?
        AND date(created_at) BETWEEN ? AND ?
        GROUP BY atendente
        ORDER BY total DESC
    ", array_merge([Auth::tenantId()], $params));

    // 2. Tempo Médio de Espera por Serviço
    $esperaPorServico = Database::fetchAll("
        SELECT
            s.nome,
            AVG(TIMESTAMPDIFF(MINUTE, sen.emitida_em, sen.chamada_em)) as tempo_espera
        FROM senhas sen
        JOIN servicos s ON s.id = sen.servico_id
        WHERE sen.status IN ('CHAMANDO', 'FINALIZADA', 'ATENDIMENTO')
        AND sen.chamada_em IS NOT NULL
        AND sen.tenant_id = ?
        AND s.tenant_id = ?
        AND date(sen.created_at) BETWEEN ? AND ?
        GROUP BY s.id
    ", array_merge([Auth::tenantId(), Auth::tenantId()], $params));

    // 3. Resumo Global e Origem
    $resumo = Database::fetch("
        SELECT
            COUNT(*) as total_emitidas,
            SUM(CASE WHEN cliente_uuid IN ('GOOGLE-CALENDAR', 'NATIVO') THEN 1 ELSE 0 END) as total_agendados,
            SUM(CASE WHEN cliente_uuid NOT IN ('GOOGLE-CALENDAR', 'NATIVO') THEN 1 ELSE 0 END) as total_presencial,
            SUM(CASE WHEN tipo_atendimento = 'PRIORITARIO' THEN 1 ELSE 0 END) as total_prioritarias,
            SUM(CASE WHEN COALESCE(tipo_atendimento, 'NORMAL') = 'NORMAL' THEN 1 ELSE 0 END) as total_normais,
            AVG(TIMESTAMPDIFF(MINUTE, emitida_em, chamada_em)) as espera_global
        FROM senhas
        WHERE tenant_id = ?
        AND date(created_at) BETWEEN ? AND ?
    ", array_merge([Auth::tenantId()], $params));

    // 5. Situação atual da fila, separada por serviço e tipo de atendimento
    $filaAtual = Database::fetchAll("
        SELECT
            s.nome,
            COUNT(*) as aguardando,
            SUM(CASE WHEN sen.tipo_atendimento = 'PRIORITARIO' THEN 1 ELSE 0 END) as prioritarias,
            SUM(CASE WHEN COALESCE(sen.tipo_atendimento, 'NORMAL') = 'NORMAL' THEN 1 ELSE 0 END) as normais
        FROM senhas sen
        JOIN servicos s ON s.id = sen.servico_id
        WHERE sen.status IN ('AGUARDANDO', 'CONGELADA')
        AND sen.tenant_id = ?
        AND sen.created_at > DATE_SUB(NOW(), INTERVAL 18 HOUR)
        GROUP BY s.id, s.nome
        ORDER BY aguardando DESC, s.nome ASC
    ", [Auth::tenantId()]);

    // 4. Movimento por Hora (Picos)
    $movimentoHora = Database::fetchAll("
        SELECT
            DATE_FORMAT(emitida_em, '%H:00') as hora,
            COUNT(*) as total
        FROM senhas
        WHERE tenant_id = ?
        AND date(created_at) BETWEEN ? AND ?
        GROUP BY hora
        ORDER BY hora ASC
    ", array_merge([Auth::tenantId()], $params));

    echo json_encode([
        'success' => true,
        'data' => [
            'ranking' => $rankingOperadores ?: [],
            'espera_servico' => $esperaPorServico ?: [],
            'resumo' => [
                'total_emitidas' => (int)($resumo['total_emitidas'] ?? 0),
                'total_agendados' => (int)($resumo['total_agendados'] ?? 0),
                'total_presencial' => (int)($resumo['total_presencial'] ?? 0),
                'total_prioritarias' => (int)($resumo['total_prioritarias'] ?? 0),
                'total_normais' => (int)($resumo['total_normais'] ?? 0),
                'espera_global' => (float)($resumo['espera_global'] ?? 0)
            ],
            'picos' => $movimentoHora ?: [],
            'fila_atual' => $filaAtual ?: []
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
