<?php
declare(strict_types=1);
/**
 * BT Queue - Multi-Ticket Check API (DIAMOND SHIELD v5.6.9)
 * Blindado contra banco desatualizado.
 */
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
use PDO;

header('Content-Type: application/json; charset=utf-8');

try {
    // [SaaS] Resolve o Tenant Autenticado
    $tenantId = \BTQueue\Core\Auth::tenantId();
    if ($tenantId <= 0) {
        throw new Exception("Unidade não identificada.");
    }

    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    $uuids = $dados['uuids'] ?? [];

    if (!is_array($uuids) || empty($uuids)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    // --- DETECÇÃO DE COLUNAS (SaaS Safe) ---
    $cols = Database::getTableColumns('senhas');
    $campoCodigo = in_array('codigo', $cols) ? 's.codigo' : 's.senha';

    // [LITE v3.3.7] Carrega regra de liberação
    $releaseMode = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'booking_release_mode' AND tenant_id = ? LIMIT 1", [$tenantId])['valor'] ?? 'immediate';

    $placeholders = implode(',', array_fill(0, count($uuids), '?'));

    $sql = "SELECT s.id, $campoCodigo as senha, s.status, s.guiche_id, s.servico_id, s.operador_id, s.cliente_uuid, s.uuid as ticket_uuid,
                   sv.nome as servico_nome, sv.icone as servico_icone, sv.cor as servico_cor, sv.tempo_medio,
                   g.nome as guiche_nome, s.created_at, s.pagamento_status, s.valor_total,
                   s.cancel_token, s.data_agendamento
            FROM senhas s
            LEFT JOIN servicos sv ON sv.id = s.servico_id
            LEFT JOIN guiches g ON g.id = s.guiche_id
            WHERE (s.cliente_uuid IN ($placeholders) OR s.uuid IN ($placeholders))
            AND s.tenant_id = ?
            AND (
                s.status IN ('AGUARDANDO', 'CHAMANDO', 'PRESENTE', 'AGENDADO', 'CONGELADA')
                OR (s.status = 'FINALIZADA' AND s.finalizada_em >= DATE_SUB(NOW(), INTERVAL 2 MINUTE))
            )
            AND (
                DATE(s.created_at) = CURDATE()
                OR DATE(s.data_agendamento) = CURDATE()
            )";

    // Duplica os UUIDs para os dois conjuntos de placeholders (cliente_uuid e uuid)
    $params = array_merge($uuids, $uuids, [(int)$tenantId]);

    $rows = Database::fetchAll($sql, $params);

    $results = [];
    foreach ($rows as $s) {
        $status = strtoupper($s['status']);
        $pessoas = 0;
        $mensagem = '';

        if ($status === 'AGUARDANDO' || $status === 'CONGELADA' || $status === 'PRESENTE' || $status === 'AGENDADO') {
            $posicaoRes = Database::fetch("
                SELECT COUNT(*) AS total FROM senhas
                WHERE status IN ('AGUARDANDO', 'CONGELADA', 'PRESENTE', 'AGENDADO')
                  AND tenant_id = ?
                  AND operador_id = ? AND id < ?
            ", [$tenantId, (int)$s['operador_id'], (int)$s['id']]);
            $pessoas = (int)($posicaoRes['total'] ?? 0);

            if ($status === 'CONGELADA') {
                $mensagem = '❄️ Sua vez está reservada. Aguardando outro atendimento.';
            } elseif ($status === 'AGENDADO') {
                if ($releaseMode === 'after_payment' && $s['pagamento_status'] !== 'PAGO' && (float)$s['valor_total'] > 0) {
                    $mensagem = '⏳ Aguardando confirmação do PIX para liberar sua posição.';
                } else {
                    $mensagem = '📅 Horário reservado. Faça o check-in ao chegar na loja.';
                }
            } else {
                $mensagem = ($pessoas === 0) ? '🟢 Você é o próximo da fila!' : "Há $pessoas pessoa(s) à sua frente.";
            }
        } elseif ($status === 'CHAMANDO') {
            $mensagem = '🔔 SUA VEZ! Dirija-se ao local indicado.';
        } else {
            $mensagem = '✅ Atendimento concluído. Obrigado!';
        }

        $results[] = [
            'id' => $s['id'],
            'cliente_uuid' => $s['cliente_uuid'],
            'servico_id' => (int)$s['servico_id'],
            'senha' => !empty($s['nome_cliente']) && ($s['senha'] === 'AGD' || strlen($s['senha']) <= 3) ? $s['nome_cliente'] : $s['senha'],
            'status' => $status,
            'servico' => $s['servico_nome'] ?? 'Atendimento',
            'icone' => $s['servico_icone'] ?: '📋',
            'cor' => $s['servico_cor'] ?: '#1565C0',
            'guiche' => $s['guiche_nome'] ?: '--',
            'posicao' => ($status === 'AGUARDANDO') ? $pessoas : '--',
            'tempo_estimado' => ($status === 'AGUARDANDO') ? ($pessoas * (int)($s['tempo_medio'] ?? 10)) : 0,
            'mensagem' => $mensagem,
            'pagamento_status' => $s['pagamento_status'] ?? 'ISENTO',
            'cancel_token' => $s['cancel_token'],
            'data_agendamento' => $s['data_agendamento']
        ];
    }
    echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Sincronizando...',
        'debug' => $e->getMessage()
    ]);
}
