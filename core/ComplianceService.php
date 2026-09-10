<?php

declare(strict_types=1);

namespace BTQueue\Core;

/**
 * Módulo de Compliance Industrial - BT Guardian
 * Responsável por vigiar agendamentos e aplicar punições automáticas.
 */
final class ComplianceService
{
    /**
     * Executa o radar de abandonos.
     * Marca como 'FALTOU' quem não fez check-in em até 15 minutos.
     */
    public function runRadar(): void
    {
        try {
            $tenantId = Auth::tenantId();
            if ($tenantId <= 0) return;

            $configs = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE tenant_id = ? AND chave LIKE 'radar_%'", [$tenantId]);
            $cfg = [];
            foreach ($configs as $c) { $cfg[$c['chave']] = $c['valor']; }

            // Se o radar estiver desligado, não faz nada (v6.9.0)
            if (($cfg['radar_enabled'] ?? '0') !== '1') {
                return;
            }

            $tolerancia = (int)($cfg['radar_tolerance'] ?? 15);

            // [LITE SaaS] Busca agendamentos expirados (MariaDB Syntax)
            $expirados = Database::fetchAll(
                "SELECT * FROM senhas
                 WHERE status = 'AGENDADO'
                 AND tenant_id = ?
                 AND data_agendamento < DATE_SUB(NOW(), INTERVAL $tolerancia MINUTE)
                 AND DATE(data_agendamento) = CURDATE()",
                [$tenantId]
            );

            foreach ($expirados as $s) {
                $this->processarFalta($s);
            }
        } catch (\Throwable $e) {
            Logger::error("Erro no Radar de Abandonados: " . $e->getMessage());
        }
    }

    private function processarFalta(array $senha): void
    {
        $tenantId = (int)$senha['tenant_id'];

        // 1. Marca como FALTOU
        Database::execute(
            "UPDATE senhas SET status = 'FALTOU', updated_at = NOW() WHERE id = ?",
            [$senha['id']]
        );

        Logger::info("📍 FALTA REGISTRADA: {$senha['nome_cliente']} não compareceu ao horário das " . date('H:i', strtotime($senha['data_agendamento'])));

        // 2. Conta reincidências nos últimos 90 dias
        $faltas = Database::fetch(
            "SELECT COUNT(*) as total FROM senhas
             WHERE tenant_id = ? AND nome_cliente = ?
             AND status = 'FALTOU'
             AND created_at > DATE_SUB(NOW(), INTERVAL 90 DAY)",
            [$tenantId, $senha['nome_cliente']]
        );

        $totalFaltas = (int)($faltas['total'] ?? 0);

        // 3. Aplica suspensão gradual
        if ($totalFaltas >= 1) {
            $dias = 14;
            if ($totalFaltas == 2) $dias = 30;
            if ($totalFaltas >= 3) $dias = 60;

            $dataFim = date('Y-m-d', strtotime("+$dias days"));
            $motivo = "Suspensão automática: $totalFaltas falta(s) sem aviso prévio nos últimos 90 dias.";

            Database::execute(
                "REPLACE INTO agenda_suspensoes (tenant_id, identificador, motivo, data_fim) VALUES (?, ?, ?, ?)",
                [$tenantId, $senha['nome_cliente'], $motivo, $dataFim]
            );

            // Tenta suspender pelo WhatsApp também se disponível
            if (!empty($senha['whatsapp'])) {
                $whatsappLimpo = preg_replace('/\D/', '', $senha['whatsapp']);
                Database::execute(
                    "REPLACE INTO agenda_suspensoes (tenant_id, identificador, motivo, data_fim) VALUES (?, ?, ?, ?)",
                    [$tenantId, $whatsappLimpo, $motivo, $dataFim]
                );
            }

            Logger::warning("🚨 SUSPENSÃO APLICADA: {$senha['nome_cliente']} suspenso por $dias dias.");
        }
    }
}
