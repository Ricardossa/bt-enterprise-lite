<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BTQueue\Core\QueueService;
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

Auth::iniciar();
$queue = new QueueService();

$servicoId = isset($_GET['servico_id']) ? (int)$_GET['servico_id'] : 0;
$guicheId  = isset($_GET['guiche_id']) ? (int)$_GET['guiche_id'] : 0;

try {

    // [SaaS] Resolve o Tenant Autenticado
    $tenantId = Auth::tenantId();
    if ($tenantId <= 0) {
        throw new Exception("Sessão ou unidade não identificada.");
    }

    // [LITE v2.7.6] Aceita chamadas de estado focadas apenas na Cadeira (Guichê)
    if ($guicheId > 0) {

        $forceOp = null;
        $usuario = Auth::operador();

        // Se for um profissional logado, força a visão dele
        if ($usuario && $usuario['nivel'] === 'OPERADOR') {
            $forceOp = (int)$usuario['id'];
        }

        $estado = $queue->estado($servicoId, $guicheId, $forceOp);

    } else {

        $guiche = $_GET['guiche'] ?? '01';
        $estado = $queue->estado($guiche);

    }

    // Carrega rótulo personalizado
    $config = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'label_cliente' AND tenant_id = ? LIMIT 1", [$tenantId]);
    $estado['label_cliente'] = $config['valor'] ?? 'Paciente';

    // [LITE v2.7.4] Mantém a agenda rica do profissional se ela já foi processada
    if (!isset($estado['agendados']) || empty($estado['agendados'])) {
        $estado['agendados'] = $queue->getAgendados($servicoId > 0 ? $servicoId : null);
    }

    $estado['estatisticas'] = $queue->estatisticas();

    // [LITE v3.6.1] Cálculo de Ganhos Líquidos em silêncio (Sem exibir porcentagem)
    if (isset($estado['operador_id']) && $estado['operador_id'] > 0) {
        $opCom = Database::fetch("SELECT comissao FROM operadores WHERE id = ?", [$estado['operador_id']]);
        $perc = (float)($opCom['comissao'] ?? 50.00);
        $estado['ganhos_hoje'] = ($estado['ganhos_hoje'] * $perc) / 100;
    }

    // [LITE SaaS] Busca Histórico Seguro e Filtrado
    try {
        // v2.9.0: Usa o NOME do cliente no histórico se for um agendamento
        $sqlHist = "SELECT s.id,
                           CASE
                            WHEN s.nome_cliente IS NOT NULL AND s.nome_cliente != '' AND (s.codigo = 'AGD' OR s.tipo_atendimento = 'AGENDAMENTO')
                            THEN s.nome_cliente
                            ELSE s.codigo
                           END as senha,
                           g.nome as guiche_nome, s.chamada_em, s.nome_cliente,
                           CASE WHEN s.nome_cliente IS NOT NULL AND s.nome_cliente != '' THEN 1 ELSE 0 END as is_hospital
                    FROM senhas s
                    LEFT JOIN guiches g ON g.id = s.guiche_id
                    WHERE s.status IN ('CHAMANDO', 'FINALIZADA')
                    AND s.tenant_id = ?
                    AND DATE(s.created_at) = CURDATE()";

        // Se for o painel de um profissional, filtra o histórico dele
        if (isset($estado['operador_id']) && $estado['operador_id'] > 0) {
            $sqlHist .= " AND s.operador_id = " . (int)$estado['operador_id'];
        }

        $sqlHist .= " ORDER BY s.chamada_em DESC, s.id DESC LIMIT 5";
        $estado['historico'] = Database::fetchAll($sqlHist, [$tenantId]);

        foreach ($estado['historico'] as &$item) {
            if (!empty($item['nome_cliente'])) {
                $item['is_hospital'] = true;
                $item['senha'] = $item['nome_cliente'];
            }
        }
        unset($item);

    } catch (Exception $eh) {
        $estado['historico'] = [];
    }

    echo json_encode([
        'success' => true,
        'data' => $estado
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}
