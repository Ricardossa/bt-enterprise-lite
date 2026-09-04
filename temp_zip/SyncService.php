<?php

declare(strict_types=1);

namespace BTQueue\Core\MasterSync;

use BTQueue\Core\Config;
use BTQueue\Core\Logger;
use BTQueue\Core\Database;
use Exception;

/**
 * Orquestrador Central da Sincronização MasterSync.
 */
final class SyncService
{
    private Client $client;
    private LicenseManager $license;
    private QueueSender $queue;
    private CommandDispatcher $dispatcher;

    public function __construct()
    {
        // Tenta buscar a URL da Master na tabela de configurações local
        $urlConfig = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'master_url' LIMIT 1");
        $url = $urlConfig ? $urlConfig['valor'] : Config::get('sync.endpoint');

        // Fallback final caso tudo falhe
        if (!$url) {
            $url = 'http://api.brandaotech.com.br:8080/api/v1/sync.php';
        }

        $this->client = new Client($url);
        $this->license = new LicenseManager();
        $this->queue = new QueueSender();
        $this->dispatcher = new CommandDispatcher();
    }

    /**
     * Executa o Ciclo de Sincronização de 9 Passos.
     */
    public function synchronize(): array
    {
        try {
            // 1. Obtém Identidade da Licença (mesmo se bloqueado)
            $licencaLocal = $this->license->getLicenseIdentity();
            $uuid = $licencaLocal['uuid'] ?? '';
            $token = $licencaLocal['token'] ?? '';

            if (empty($uuid) || empty($token)) {
                return ['success' => false, 'message' => 'Provisionamento ausente (UUID/Token).'];
            }

            // --- RADAR DE COMPLIANCE DIAMOND (v6.5) ---
            try {
                $compliance = new \BTQueue\Core\ComplianceService();
                $compliance->runRadar();

                // --- AUTO-FINALIZADOR DIÁRIO (v7.5.1 Diamond) ---
                // Finaliza senhas de dias anteriores para evitar "fantasmas" no celular do cliente
                Database::execute("UPDATE senhas SET status = 'FINALIZADA', finalizada_em = CURRENT_TIMESTAMP WHERE status IN ('AGUARDANDO', 'CHAMANDO', 'CONGELADA') AND created_at < datetime('now', '-18 hours')");
            } catch (\Throwable $e) {}

            // 2. Prepara Pulse (Heartbeat + Fila Pendente)
            $pendentes = $this->queue->getPendingItems();

            $payload = [
                'uuid' => $uuid,
                'token' => $token,
                'produto' => 'BT_QUEUE_ENTERPRISE',
                'versao' => defined('BT_VERSION') ? BT_VERSION : '4.0.0',
                'device_uuid' => gethostname(), // Identificador da VM/Máquina
                'sync_queue' => $pendentes,
                'device' => [
                    'fabricante' => 'Windows PC',
                    'modelo' => gethostname(),
                    'android' => PHP_OS,
                    'os' => PHP_OS,
                    'php' => PHP_VERSION,
                    'time' => date('Y-m-d H:i:s')
                ]
            ];

            // 3. Envia Pulse e Recebe Resposta
            $response = $this->client->post($payload);

            if ($response['status'] !== 'OK') {
                $this->queue->markProcessed(array_column($pendentes, 'id'), false);
                return ['success' => false, 'message' => $response['message'] ?? 'Erro desconhecido na Master.'];
            }

            $syncPackage = $response['sync'];

            // 4. Atualiza Tabela Licencas (Diretrizes e Features da Master)
            $this->license->updateLicense($syncPackage['license'], $uuid, $token, $syncPackage['features'] ?? []);

            // 5. Processa Comandos
            if (!empty($syncPackage['commands'])) {
                $this->dispatcher->dispatch($syncPackage['commands']);
            }

            // 6. Marca Sincronizados (Outbox)
            $this->queue->markProcessed(array_column($pendentes, 'id'), true);

            // 7. Grava Log Operacional
            Logger::info("Sincronização MasterSync concluída. ID: " . ($syncPackage['id'] ?? 'N/A'));

            return [
                'success' => true,
                'message' => 'Sincronização realizada com sucesso.',
                'sync_id' => $syncPackage['id'] ?? null
            ];

        } catch (Exception $e) {
            Logger::error("Falha no Ciclo MasterSync: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
