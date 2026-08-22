<?php

declare(strict_types=1);

namespace BTQueue\Core\Integration;

use BTQueue\Core\Database;
use BTQueue\Core\ActivityService;
use Exception;
use Throwable;

/**
 * Motor de Integração Hospitalar - BT Integration Hub
 * Responsável por converter requisições externas de ERPs em chamadas na TV.
 */
final class IntegrationHubController
{
    /**
     * Registra e Chamar uma senha externa (ex: pelo nome do paciente)
     */
    public function externalCall(array $payload): array
    {
        try {
            $paciente = strtoupper(trim((string)($payload['nome_paciente'] ?? '')));
            $local = strtoupper(trim((string)($payload['local'] ?? 'Geral')));
            $profissional = strtoupper(trim((string)($payload['profissional'] ?? '')));
            $prioridade = strtoupper(trim((string)($payload['prioridade'] ?? 'NORMAL')));

            if (empty($paciente)) {
                throw new Exception("O nome do paciente é obrigatório para integração.");
            }

            Database::begin();

            $uuid = bin2hex(random_bytes(16));
            $agora = date('Y-m-d H:i:s');

            // 1. Registra a "Senha Virtual" no banco (sem número, apenas nome)
            Database::execute(
                "INSERT INTO senhas (
                    uuid, cliente_uuid, codigo, numero, prefixo,
                    nome_cliente, atendente_nome, atendente,
                    tipo_atendimento, status, emitida_em, chamada_em, created_at
                ) VALUES (
                    ?, 'HIS-INTEGRATION', 'HIS', 0, 'H',
                    ?, ?, ?,
                    ?, 'CHAMANDO', ?, ?, ?
                )",
                [
                    $uuid,
                    $paciente, $profissional ?: $local, $profissional ?: $local,
                    $prioridade, $agora, $agora, $agora
                ]
            );

            $id = Database::lastInsertId();

            // 2. Dispara evento para a TV e Mobile
            Database::execute(
                "INSERT INTO sync_queue (evento, entidade, referencia_id, payload, sincronizado) VALUES (?, ?, ?, ?, 0)",
                [
                    'CHAMAR',
                    'senha',
                    $id,
                    json_encode([
                        'senha' => $paciente, // Na TV Hospitalar, a "senha" é o nome
                        'guiche' => $local,   // O "guichê" é a sala/consultório
                        'profissional' => $profissional,
                        'is_hospital' => true,
                        'tipo_atendimento' => $prioridade
                    ], JSON_UNESCAPED_UNICODE)
                ]
            );

            ActivityService::log('INFO', 'INTEGRATION', "Chamada externa HIS: $paciente -> $local", [], 'IntegrationHub');

            Database::commit();

            // Carrega rótulo personalizado para a mensagem de retorno
            $config = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'label_cliente' LIMIT 1");
            $label = $config['valor'] ?? 'Paciente';

            return [
                'success' => true,
                'message' => "$label chamado com sucesso na TV.",
                'job_id' => $id,
                'paciente' => $paciente
            ];

        } catch (Throwable $e) {
            Database::rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
