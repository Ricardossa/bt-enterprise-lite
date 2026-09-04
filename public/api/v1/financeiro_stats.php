<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

// SeguranÃ§a: Bloqueia qualquer acesso nÃ£o autenticado
Auth::iniciar();
if (!Auth::autenticado()) {
    http_response_code(401);
    exit;
}

try {
    $inicio = $_GET['inicio'] ?? date('Y-m-d');
    $fim = $_GET['fim'] ?? date('Y-m-d');
    $tenantId = Auth::tenantId();

    // AÃ‡ÃƒO: Pagar ComissÃ£o (v5.2.0)
    $action = $_GET['action'] ?? '';
    if ($action === 'pagar_comissao' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        Auth::protegerAPI('ADMIN'); // Somente Admin paga
        $input = json_decode(file_get_contents('php://input'), true);
        $operadorId = (int)($input['operador_id'] ?? 0);
        $valor = (float)($input['valor'] ?? 0);
        $dataInicio = $input['data_inicio'] ?? date('Y-m-d');
        $dataFim = $input['data_fim'] ?? date('Y-m-d');
        $pagoPor = Auth::operador()['id'] ?? 0;

        if ($operadorId > 0 && $valor > 0) {
            Database::execute("
                INSERT INTO financeiro_acertos (tenant_id, operador_id, valor, data_inicio, data_fim, pago_por)
                VALUES (?, ?, ?, ?, ?, ?)
            ", [$tenantId, $operadorId, $valor, $dataInicio, $dataFim, $pagoPor]);

            echo json_encode(['success' => true]);
            exit;
        }
        throw new Exception("Dados inválidos para pagamento.");
    }

    $params = [$inicio, $fim, $tenantId];

    // 1. Resumo Global
    $resumo = Database::fetch("
        SELECT
            COUNT(*) as total_atendimentos,
            IFNULL(SUM(valor_total), 0) as receita_bruta,
            IFNULL(SUM(CASE WHEN pagamento_status = 'PAGO' THEN valor_total ELSE 0 END), 0) as receita_paga,
            IFNULL(SUM(CASE WHEN pagamento_status = 'PENDENTE' THEN valor_total ELSE 0 END), 0) as receita_pendente,
            COUNT(CASE WHEN pagamento_status = 'ISENTO' THEN 1 END) as total_vip
        FROM senhas
        WHERE status = 'FINALIZADA'
        AND DATE(finalizada_em) BETWEEN ? AND ?
        AND tenant_id = ?
    ", $params);

    // 2. Performance por Barbeiro (v3.6.7: VIP Stats)
    $porBarbeiro = Database::fetchAll("
        SELECT
            o.id,
            o.nome as barbeiro,
            o.comissao,
            COUNT(s.id) as total_servicos,
            COUNT(CASE WHEN s.pagamento_status = 'ISENTO' THEN 1 END) as total_vip,
            IFNULL(SUM(s.valor_total), 0) as total_gerado,
            IFNULL(SUM(CASE WHEN s.pagamento_status = 'PAGO' THEN s.valor_total ELSE 0 END), 0) as total_pago,
            IFNULL(SUM(CASE WHEN s.pagamento_status = 'PENDENTE' THEN s.valor_total ELSE 0 END), 0) as total_pendente
        FROM operadores o
        LEFT JOIN senhas s ON s.operador_id = o.id
            AND s.status = 'FINALIZADA'
            AND DATE(s.finalizada_em) BETWEEN ? AND ?
        WHERE o.nivel = 'OPERADOR'
        AND o.tenant_id = ?
        GROUP BY o.id, o.nome, o.comissao
        ORDER BY total_gerado DESC
    ", $params);

    $totalAPagar = 0;
    foreach ($porBarbeiro as &$b) {
        $perc = (float)($b['comissao'] ?? 50.00);
        $apagar = ((float)$b['total_gerado'] * $perc) / 100;
        $b['total_a_pagar'] = $apagar;
        $totalAPagar += $apagar;
    }
    unset($b);

    // 3. Detalhamento de Serviços (Quais combos mais saíram)
    $servicosDestaque = Database::fetchAll("
        SELECT
            servicos_desc as descricao,
            COUNT(*) as quantidade,
            SUM(valor_total) as subtotal
        FROM senhas
        WHERE status = 'FINALIZADA'
        AND DATE(finalizada_em) BETWEEN ? AND ?
        AND tenant_id = ?
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
            s.is_promo,
            s.finalizada_em,
            o.nome as barbeiro_nome,
            s.servicos_desc
        FROM senhas s
        JOIN operadores o ON o.id = s.operador_id
        WHERE s.status = 'FINALIZADA'
        AND s.pagamento_status = 'PENDENTE'
        AND DATE(s.finalizada_em) BETWEEN ? AND ?
        AND s.tenant_id = ?
        ORDER BY s.finalizada_em DESC
    ", $params);

    // 5. Histórico de Pagamentos (Últimos 10) - Protegido contra tabela inexistente
    $historico = [];
    try {
        $historico = Database::fetchAll("
            SELECT
                a.*,
                o.nome as barbeiro_nome,
                admin.nome as admin_nome
            FROM financeiro_acertos a
            JOIN operadores o ON o.id = a.operador_id
            JOIN operadores admin ON admin.id = a.pago_por
            WHERE a.tenant_id = ?
            ORDER BY a.data_pagamento DESC
            LIMIT 10
        ", [$tenantId]);
    } catch (Throwable $e_table) {
        // Tabela ainda não existe ou erro na consulta
    }

    // 6. Pagamentos para o Barbeiro Logado (v3.1.0)
    $pagamentosBarbeiro = [];
    $usuario = Auth::operador();
    if ($usuario && $usuario['nivel'] === 'OPERADOR') {
        try {
            $pagamentosBarbeiro = Database::fetchAll("
                SELECT * FROM financeiro_acertos
                WHERE operador_id = ? AND tenant_id = ?
                ORDER BY data_pagamento DESC LIMIT 10
            ", [$usuario['id'], $tenantId]);
        } catch (Throwable $e_table_op) {}
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'resumo' => [
                'total_atendimentos' => (int)($resumo['total_atendimentos'] ?? 0),
                'receita_bruta' => (float)($resumo['receita_bruta'] ?? 0),
                'receita_paga' => (float)($resumo['receita_paga'] ?? 0),
                'receita_pendente' => (float)($resumo['receita_pendente'] ?? 0),
                'total_a_pagar' => (float)$totalAPagar,
                'total_vip' => (int)($resumo['total_vip'] ?? 0)
            ],
            'barbeiros' => Auth::isAdmin() ? $porBarbeiro : [],
            'servicos' => Auth::isAdmin() ? $servicosDestaque : [],
            'pendencias' => Auth::isAdmin() ? $pendencias : [],
            'historico' => Auth::isAdmin() ? $historico : [],
            'pagamentos' => $pagamentosBarbeiro
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
