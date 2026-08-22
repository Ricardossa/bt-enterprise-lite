<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

// Apenas Administradores podem ver o financeiro
Auth::protegerAPI('ADMIN');

try {
    $inicio = $_GET['inicio'] ?? date('Y-m-d');
    $fim = $_GET['fim'] ?? date('Y-m-d');
    $params = [$inicio, $fim];

    // 1. Resumo Global
    $resumo = Database::fetch("
        SELECT
            COUNT(*) as total_atendimentos,
            SUM(valor_total) as receita_bruta,
            SUM(CASE WHEN pagamento_status = 'PAGO' THEN valor_total ELSE 0 END) as receita_paga,
            SUM(CASE WHEN pagamento_status = 'PENDENTE' THEN valor_total ELSE 0 END) as receita_pendente
        FROM senhas
        WHERE status = 'FINALIZADA'
        AND date(created_at) BETWEEN ? AND ?
    ", $params);

    // 2. Performance por Barbeiro (v3.4.0: Adicionado Pendente)
    $porBarbeiro = Database::fetchAll("
        SELECT
            o.nome as barbeiro,
            COUNT(s.id) as total_servicos,
            SUM(s.valor_total) as total_gerado,
            SUM(CASE WHEN s.pagamento_status = 'PAGO' THEN s.valor_total ELSE 0 END) as total_pago,
            SUM(CASE WHEN s.pagamento_status = 'PENDENTE' THEN s.valor_total ELSE 0 END) as total_pendente
        FROM operadores o
        LEFT JOIN senhas s ON s.operador_id = o.id
            AND s.status = 'FINALIZADA'
            AND date(s.created_at) BETWEEN ? AND ?
        WHERE o.nivel = 'OPERADOR'
        GROUP BY o.id, o.nome
        ORDER BY total_gerado DESC
    ", $params);

    // 3. Detalhamento de Serviços (Quais combos mais saíram)
    $servicosDestaque = Database::fetchAll("
        SELECT
            servicos_desc as descricao,
            COUNT(*) as quantidade,
            SUM(valor_total) as subtotal
        FROM senhas
        WHERE status = 'FINALIZADA'
        AND date(created_at) BETWEEN ? AND ?
        AND servicos_desc IS NOT NULL AND servicos_desc != ''
        GROUP BY servicos_desc
        ORDER BY quantidade DESC
        LIMIT 10
    ", $params);

    // 4. Lista de Auditoria (Serviços Finalizados mas não pagos)
    $pendencias = Database::fetchAll("
        SELECT
            s.id,
            s.uuid,
            s.nome_cliente,
            s.valor_total,
            s.finalizada_em,
            o.nome as barbeiro_nome,
            s.servicos_desc
        FROM senhas s
        JOIN operadores o ON o.id = s.operador_id
        WHERE s.status = 'FINALIZADA'
        AND s.pagamento_status = 'PENDENTE'
        AND date(s.created_at) BETWEEN ? AND ?
        ORDER BY s.finalizada_em DESC
    ", $params);

    echo json_encode([
        'success' => true,
        'data' => [
            'resumo' => [
                'total_atendimentos' => (int)($resumo['total_atendimentos'] ?? 0),
                'receita_bruta' => (float)($resumo['receita_bruta'] ?? 0),
                'receita_paga' => (float)($resumo['receita_paga'] ?? 0),
                'receita_pendente' => (float)($resumo['receita_pendente'] ?? 0)
            ],
            'barbeiros' => $porBarbeiro ?: [],
            'servicos' => $servicosDestaque ?: [],
            'pendencias' => $pendencias ?: []
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
