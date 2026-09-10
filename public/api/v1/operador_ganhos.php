<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

// Barbeiros podem ver seus próprios ganhos
Auth::protegerAPI();

try {
    $op = Auth::operador();
    $opId = (int)($op['id'] ?? 0);
    $tenantId = Auth::tenantId();

    if ($opId <= 0 || $tenantId <= 0) {
        throw new Exception("Operador ou Unidade não identificada.");
    }

    $inicio = $_GET['inicio'] ?? date('Y-m-d');
    $fim = $_GET['fim'] ?? date('Y-m-d');
    $params = [$opId, $tenantId, $inicio, $fim];

    // 1. Busca Comissão do Operador
    $opData = Database::fetch("SELECT comissao FROM operadores WHERE id = ? AND tenant_id = ?", [$opId, $tenantId]);
    $perc = (float)($opData['comissao'] ?? 50.00);

    // 2. Resumo de Ganhos no Período
    $resumo = Database::fetch("
        SELECT
            COUNT(*) as total_servicos,
            IFNULL(SUM(valor_total), 0) as total_bruto
        FROM senhas
        WHERE status = 'FINALIZADA'
        AND operador_id = ?
        AND tenant_id = ?
        AND DATE(finalizada_em) BETWEEN ? AND ?
    ", $params);

    $ganhoReal = ((float)$resumo['total_bruto'] * $perc) / 100;

    // 3. Histórico Detalhado
    $historico = Database::fetchAll("
        SELECT
            id,
            codigo as senha,
            nome_cliente,
            valor_total,
            chamada_em,
            servicos_desc
        FROM senhas
        WHERE status = 'FINALIZADA'
        AND operador_id = ?
        AND tenant_id = ?
        AND DATE(finalizada_em) BETWEEN ? AND ?
        ORDER BY chamada_em DESC
    ", $params);

    foreach ($historico as &$h) {
        $h['ganho_liquido'] = ((float)$h['valor_total'] * $perc) / 100;
        $h['senha'] = !empty($h['nome_cliente']) ? $h['nome_cliente'] : $h['senha'];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'total_servicos' => (int)$resumo['total_servicos'],
            'ganho_real' => (float)$ganhoReal,
            'comissao_percentual' => $perc,
            'historico' => $historico
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
