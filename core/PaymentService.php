<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;
use Throwable;

/**
 * BT QUEUE ENTERPRISE LITE - PAYMENT ENGINE (v1.2.0-SaaS)
 * Integracao Nativa com Mercado Pago (PIX) e PIX Manual.
 */
class PaymentService
{
    private string $accessToken;
    private string $staticKey;
    private string $strategy;
    private bool $testMode;

    public function __construct()
    {
        $tenantId = Auth::tenantId();
        $config = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE chave IN ('mercadopago_token', 'mercadopago_test_mode', 'pix_chave_estatica', 'payment_strategy') AND tenant_id = ?", [$tenantId]);
        $cfg = [];
        foreach ($config as $c) { $cfg[$c['chave']] = $c['valor']; }

        $this->accessToken = $cfg['mercadopago_token'] ?? '';
        $this->staticKey = $cfg['pix_chave_estatica'] ?? '';
        $this->strategy = $cfg['payment_strategy'] ?? 'manual';

        // v3.5.3: Fallback Automático para Manual se o Mercado Pago estiver sem token
        if ($this->strategy === 'mercadopago' && empty($this->accessToken)) {
            $this->strategy = 'manual';
        }

        $this->testMode = ($cfg['mercadopago_test_mode'] ?? '0') === '1';
    }

    /**
     * Cria um pagamento PIX baseado na estratégia configurada.
     */
    public function createPixPayment(float $amount, string $description, string $email, string $externalReference): array
    {
        if ($this->strategy === 'disabled') {
            return ['success' => false, 'message' => 'Cobrança desativada.'];
        }

        if ($this->testMode) {
            return [
                'success' => true,
                'id' => 'TEST_' . time(),
                'qr_code' => '00020101021243001600000000000000005204000053039865802BR5913BRANDAO_TECH6007SALVADOR62070503***6304E22D',
                'qr_code_base64' => 'iVBORw0KGgoAAAANSUhEUgAAAMgAAADIEAIAAACXidVDAAAABmBMVEUAAAD///+l2Z/dAAAC4UlEQVR4nO3buW7CQBRG4REpUrBAsv8rUqRIUfA6XGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf8pXGzMBWfIOf+T7wV8AfXh9X8AAAAASUVORK5CYII=',
                'status' => 'pending',
                'mode' => 'test'
            ];
        }

        // MODO MANUAL (Chave Estática)
        if ($this->strategy === 'manual') {
            if (!empty($this->staticKey)) {
                $payload = $this->generateStaticPayload($amount, $description, $externalReference);
                return [
                    'success' => true,
                    'id' => 'STATIC_' . time(),
                    'qr_code' => $payload,
                    'qr_code_base64' => null,
                    'status' => 'pending',
                    'mode' => 'static'
                ];
            }
            throw new Exception("Chave PIX manual não configurada.");
        }

        // MODO MERCADO PAGO (Automático)
        if (empty($this->accessToken)) {
            throw new Exception("Configuração de pagamento Mercado Pago ausente.");
        }

        $url = "https://api.mercadopago.com/v1/payments";

        $payload = [
            "transaction_amount" => $amount,
            "description" => $description,
            "payment_method_id" => "pix",
            "external_reference" => $externalReference,
            "payer" => [
                "email" => $email
            ]
        ];

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->accessToken,
                "X-Idempotency-Key: " . uniqid()
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode((string)$response, true);

            if ($httpCode !== 201) {
                $errorMsg = $data['message'] ?? "Erro ao criar pagamento no Mercado Pago (HTTP $httpCode)";
                throw new Exception($errorMsg);
            }

            return [
                'success' => true,
                'id' => (string)$data['id'],
                'qr_code' => $data['point_of_interaction']['transaction_data']['qr_code'],
                'qr_code_base64' => $data['point_of_interaction']['transaction_data']['qr_code_base64'],
                'status' => $data['status'],
                'mode' => 'mercadopago'
            ];

        } catch (Throwable $e) {
            Logger::error("Falha ao criar PIX: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verifica o status de um pagamento.
     */
    public function checkPaymentStatus(string $paymentId): array
    {
        if (strpos($paymentId, 'STATIC_') === 0) {
            return ['success' => true, 'status' => 'pending', 'status_detail' => 'static_manual'];
        }

        $url = "https://api.mercadopago.com/v1/payments/" . $paymentId;

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $this->accessToken
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode((string)$response, true);

            return [
                'success' => true,
                'status' => $data['status'] ?? 'unknown',
                'status_detail' => $data['status_detail'] ?? ''
            ];

        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * [LITE v2.8.7] Gerador de Payload Pix EMV (Static)
     */
    private function generateStaticPayload(float $amount, string $description, string $txid): string
    {
        $key = trim($this->staticKey);

        // v2.8.7: Tratamento de Chave Telefone (Obrigatorio +55 no BR Code para ser reconhecido)
        if (ctype_digit($key) && (strlen($key) === 10 || strlen($key) === 11)) {
            $key = "+55" . $key;
        }

        // Formata o valor
        $amountStr = number_format($amount, 2, '.', '');

        $payload = [
            '00' => '01', // Payload Format Indicator
            '01' => '11', // Point of Initiation Method (11 = estatico)
            '26' => [    // Merchant Account Information
                '00' => 'br.gov.bcb.pix',
                '01' => $key
            ],
            '52' => '0000', // Merchant Category Code
            '53' => '986',  // Transaction Currency (BRL)
            '54' => $amountStr, // Transaction Amount
            '58' => 'BR',   // Country Code
            '59' => 'BRANDAO TECH LITE', // Merchant Name
            '60' => 'BRASILIA', // Merchant City
            '62' => [    // Additional Data Field Template
                '05' => '***' // TXID estatico padrao para evitar erros de remocao
            ]
        ];

        $out = "";
        foreach ($payload as $id => $value) {
            if (is_array($value)) {
                $sub = "";
                foreach ($value as $sid => $sv) {
                    $sub .= $sid . str_pad((string)strlen($sv), 2, '0', STR_PAD_LEFT) . $sv;
                }
                $out .= $id . str_pad((string)strlen($sub), 2, '0', STR_PAD_LEFT) . $sub;
            } else {
                $out .= $id . str_pad((string)strlen($value), 2, '0', STR_PAD_LEFT) . $value;
            }
        }

        $out .= "6304"; // ID do CRC e tamanho (04)
        $out .= $this->crc16($out);

        return $out;
    }

    private function crc16($data): string
    {
        $res = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $res ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($res & 0x8000) $res = ($res << 1) ^ 0x1021;
                else $res <<= 1;
            }
        }
        return strtoupper(str_pad(dechex($res & 0xFFFF), 4, '0', STR_PAD_LEFT));
    }
}
