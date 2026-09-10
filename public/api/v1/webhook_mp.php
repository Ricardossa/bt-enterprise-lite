<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\PaymentService;
use BTQueue\Core\Logger;

/**
 * Webhook de Notificacao do Mercado Pago (v1.1.0-LITE)
 */

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action'])) {
    http_response_code(200); // Mercado Pago exige 200
    exit;
}

if ($data['action'] === 'payment.updated') {
    $paymentId = $data['data']['id'] ?? '';
    if ($paymentId) {
        try {
            $paymentService = new PaymentService();
            $statusRes = $paymentService->checkPaymentStatus((string)$paymentId);

            if ($statusRes['success'] && $statusRes['status'] === 'approved') {
                // Localiza a senha vinculada a este pagamento
                // [LITE v4.1.0] Blindagem SaaS: Garante que a atualizaÃ§Ã£o respeite o tenant da senha
                $senha = Database::fetch("SELECT id, codigo, status, tenant_id FROM senhas WHERE pagamento_id = ? LIMIT 1", [$paymentId]);

                if ($senha && $senha['status'] === 'AGENDADO') {
                    Database::execute("UPDATE senhas SET pagamento_status = 'PAGO', status = 'CONFIRMADO' WHERE id = ? AND tenant_id = ?", [$senha['id'], $senha['tenant_id']]);
                    Logger::info("Pagamento Aprovado: Senha {$senha['codigo']} confirmada automaticamente para Unidade {$senha['tenant_id']}.");
                }
            }
        } catch (Throwable $e) {
            Logger::error("Erro no Webhook MP: " . $e->getMessage());
        }
    }
}

http_response_code(200);
echo json_encode(["status" => "ok"]);
