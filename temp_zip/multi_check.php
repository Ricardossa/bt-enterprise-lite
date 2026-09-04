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
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    $uuids = $dados['uuids'] ?? [];

    if (!is_array($uuids) || empty($uuids)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    // --- DETECÇÃO DE COLUNAS (v5.6.9) ---
    $resInfo = Database::getInstance()->query("PRAGMA table_info(senhas)");
    $cols = array_column($resInfo->fetchAll(PDO::FETCH_ASSOC), 'name');
    $campoCodigo = in_array('codigo', $cols) ? 's.codigo' : 's.senha';
    $hasAgendamento = in_array('data_agendamento', $cols);

    $placeholders = implode(',', array_fill(0, count($uuids), '?'));
    $sql = "SELECT s.id, $campoCodigo as senha, s.status, s.guiche_id, s.servico_id, s.cliente_uuid,
                   sv.nome as servico_nome, sv.icone as servico_icone, sv.cor as servico_cor, sv.tempo_medio,
                   g.nome as guiche_nome, s.created_at
            FROM senhas s
            LEFT JOIN servicos sv ON sv.id = s.servico_id
            LEFT JOIN guiches g ON g.id = s.guiche_id
            WHERE s.cliente_uuid IN ($placeholders)
            AND s.created_at > datetime('now', '-18 hours')";

    $rows = Database::fetchAll($sql, $uuids);

    $results = [];
    foreach ($rows as $s) {
        $status = strtoupper($s['status']);
        $pessoas = 0;
        $mensagem = '';

        if ($status === 'AGUARDANDO' || $status === 'CONGELADA') {
            $posicaoRes = Database::fetch("
                SELECT COUNT(*) AS total FROM senhas
                WHERE status IN ('AGUARDANDO', 'CONGELADA')
                  AND servico_id = ? AND id < ?
            ", [(int)$s['servico_id'], (int)$s['id']]);
            $pessoas = (int)($posicaoRes['total'] ?? 0);

            if ($status === 'CONGELADA') {
                $mensagem = '❄️ Sua vez está reservada. Aguardando outro atendimento.';
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
            'servico_id' => (int)$s['servico_id'], // v2.4.1: Vital para ocultação de botões
            'senha' => $s['senha'],
            'status' => $status,
            'servico' => $s['servico_nome'] ?? 'Atendimento',
            'icone' => $s['servico_icone'] ?: '📋',
            'cor' => $s['servico_cor'] ?: '#1565C0',
            'guiche' => $s['guiche_nome'] ?: '--',
            'posicao' => ($status === 'AGUARDANDO') ? $pessoas : '--',
            'tempo_estimado' => ($status === 'AGUARDANDO') ? ($pessoas * (int)($s['tempo_medio'] ?? 10)) : 0,
            'mensagem' => $mensagem
        ];
    }
    echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Sincronizando...']);
}
?>
