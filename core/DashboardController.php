<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;

class DashboardController
{
    /**
     * Retorna estatísticas rápidas do dia.
     */
    public function getStats(): array
    {
        $queue = new QueueService();
        return $queue->getStatsPorPeriodo();
    }

    /**
     * Verifica a saúde técnica do sistema local.
     */
    public function getHealth(): array
    {
        // [v3.5.0] Busca Licença filtrada por Tenant para modo SaaS VPS
        $tenantId = Auth::tenantId();
        $licenca = Database::fetch("SELECT status FROM licencas WHERE tenant_id = ? LIMIT 1", [$tenantId]);
        $isBlocked = ($licenca && strtoupper((string)$licenca['status']) !== 'ATIVA');

        // --- CHECAGEM DA IMPRESSORA LOCAL (Porta 8001) ---
        $printStatus = 'Offline';
        $printColor = 'var(--danger)';

        $connection = @fsockopen('127.0.0.1', 8001, $errno, $errstr, 0.5);
        if ($connection) {
            $printStatus = 'Online';
            $printColor = 'var(--success)';
            fclose($connection);
        }

        return [
            [
                'nome' => 'Banco de Dados',
                'status' => 'Conectado',
                'color' => 'var(--success)'
            ],
            [
                'nome' => 'Impressora',
                'status' => $printStatus,
                'color' => $printColor
            ],
            [
                'nome' => 'Licença',
                'status' => $isBlocked ? 'Bloqueada' : 'Válida',
                'color' => $isBlocked ? 'var(--danger)' : 'var(--success)'
            ]
        ];
    }

    /**
     * Garante que o motor de atividades está registrando o tempo atual.
     */
    public function ensureActivityIsAlive(): void
    {
        // Placeholder para futuras checagens de processo
    }
}
