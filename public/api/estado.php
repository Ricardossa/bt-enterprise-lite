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

    // [LITE v3.9.2] Ajuste para Mural Global (TV Smart)
    if ($guicheId > 0) {
        $forceOp = null;
        $usuario = Auth::operador();
        if ($usuario && $usuario['nivel'] === 'OPERADOR') {
            $forceOp = (int)$usuario['id'];
        }
        $estado = $queue->estado($servicoId, $guicheId, $forceOp);
    } elseif (isset($_GET['guiche'])) {
        $estado = $queue->estado($_GET['guiche']);
    } else {
        // MODO MURAL: Sem guichê definido, chama o estado global
        $estado = $queue->estado(null);
    }

    // Carrega rótulo personalizado
    $config = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'label_cliente' AND tenant_id = ? LIMIT 1", [$tenantId]);
    $estado['label_cliente'] = $config['valor'] ?? 'Paciente';

    // [LITE v2.7.4] Mantém a agenda rica do profissional se ela já foi processada
    if (!isset($estado['agendados']) || empty($estado['agendados'])) {
        $estado['agendados'] = $queue->getAgendados($servicoId > 0 ? $servicoId : null);
    }

    $estado['estatisticas'] = $queue->estatisticas();

    // [LITE v3.6.3] Cálculo de Ganhos Individual + Comissão
    $estado['comissao_percentual'] = 50.00;
    if (isset($estado['operador_id']) && $estado['operador_id'] > 0) {
        $opCom = Database::fetch("SELECT comissao FROM operadores WHERE id = ?", [$estado['operador_id']]);
        $perc = (float)($opCom['comissao'] ?? 50.00);
        $estado['comissao_percentual'] = $perc;

        $valorBruto = (float)($estado['ganhos_hoje'] ?? 0);
        $estado['ganhos_hoje'] = ($valorBruto * $perc) / 100;
    }

    // [LITE v3.9.3] Histórico Automático via Service (Não sobrescrever manualmente)
    if (!isset($estado['historico'])) {
        $estado['historico'] = $queue->getHistoricoChamadas(5);
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
