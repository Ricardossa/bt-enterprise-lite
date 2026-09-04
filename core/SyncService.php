<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;

/**
 * SyncService - Versão Ultra-Compatível MasterSync (v3.3.0)
 * Baseado na lógica da Enterprise Full para garantir reconhecimento na Master.
 */
final class SyncService
{
    public function synchronize(): array
    {
        $enabled = Config::get('sync.enabled', true);
        if (!$enabled) return ['success' => true, 'message' => 'Sincronização desativada.'];

        // 1. Busca Identidade no Banco
        $uuidRow = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'uuid' LIMIT 1");
        $tokenRow = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'token' LIMIT 1");
        $urlRow = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'master_url' LIMIT 1");

        $uuid = $uuidRow['valor'] ?? '';
        $token = $tokenRow['valor'] ?? '';
        $masterUrl = $urlRow['valor'] ?? Config::get('sync.endpoint');

        if (empty($uuid) || empty($token) || empty($masterUrl)) {
            return ['success' => false, 'message' => 'Credenciais incompletas no banco local.'];
        }

        // 2. Prepara Endpoint
        $endpoint = $masterUrl;
        if (!str_contains($endpoint, '/api/v1/sync.php')) {
            $endpoint = rtrim($endpoint, '/') . '/api/v1/sync.php';
        }

        try {
            // [ESTRUTURA IDENTICA À FULL]
            $payload = [
                'uuid' => $uuid,
                'token' => $token,
                'produto' => 'BT_QUEUE_ENTERPRISE_LITE',
                'versao' => Config::get('app.version', '7.7.0'),
                'device_uuid' => gethostname(),
                'device' => [
                    'os' => PHP_OS,
                    'php' => PHP_VERSION,
                    'time' => date('Y-m-d H:i:s'),
                    'hardware_id' => SecurityService::getHardwareId()
                ]
            ];

            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-BT-PRODUCT: BT_QUEUE_ENTERPRISE_LITE',
                'X-BT-UUID: ' . $uuid,
                'X-BT-TENANT-ID: ' . $uuid
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                $licenseManager = new LicenseManager();

                // Captura erro de identificação (Status ERROR ou code NOT_FOUND)
                $resStatus = $responseData['status'] ?? $responseData['data']['status'] ?? 'OK';
                $resCode = $responseData['code'] ?? $responseData['data']['code'] ?? '';

                if ($resStatus === 'ERROR' || $resCode === 'INSTALLATION_NOT_FOUND') {
                    // Auto-bloqueio se a Master não reconhecer
                    $licenseManager->updateLicense(['status' => 'SUSPENSA', 'expires' => date('Y-m-d')], $uuid, $token);
                    return ['success' => false, 'message' => 'Instalação não encontrada na Master.'];
                }

                // Processa atualização de licença
                $sync = $responseData['sync'] ?? $responseData['data']['sync'] ?? null;
                if ($sync && isset($sync['license'])) {
                    $licenseManager->updateLicense($sync['license'], $uuid, $token);
                    return ['success' => true, 'message' => 'Sincronizado.', 'status' => $sync['license']['status']];
                }

                return ['success' => true, 'message' => 'Heartbeat ok.'];
            }

            return ['success' => false, 'message' => "HTTP $httpCode"];

        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
