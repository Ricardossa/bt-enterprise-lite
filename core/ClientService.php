<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;
use Throwable;

/**
 * Motor de Gestão de Clientes e Fidelidade SaaS
 * Responsável por identidades permanentes e pontuação.
 */
final class ClientService
{
    public function buscarPorUuid(string $uuid, int $tenantId): ?array
    {
        return Database::fetch(
            "SELECT * FROM clientes WHERE uuid = ? AND tenant_id = ? LIMIT 1",
            [$uuid, $tenantId]
        );
    }

    public function buscarPorWhatsapp(string $whatsapp, int $tenantId): ?array
    {
        $limpo = preg_replace('/\D/', '', $whatsapp);
        return Database::fetch(
            "SELECT * FROM clientes WHERE whatsapp = ? AND tenant_id = ? LIMIT 1",
            [$limpo, $tenantId]
        );
    }

    public function registrar(array $dados, int $tenantId): array
    {
        $nome = strtoupper(trim($dados['nome'] ?? ''));
        $whatsapp = preg_replace('/\D/', '', $dados['whatsapp'] ?? '');
        $email = strtolower(trim($dados['email'] ?? ''));
        $nascimento = !empty($dados['data_nascimento']) ? $dados['data_nascimento'] : null;

        if (empty($nome) || empty($whatsapp)) {
            throw new Exception("Nome e WhatsApp são obrigatórios para o perfil.");
        }

        // Verifica se já existe esse Zap no tenant
        $existente = $this->buscarPorWhatsapp($whatsapp, $tenantId);
        if ($existente) {
            return [
                'success' => true,
                'modo' => 'recuperado',
                'cliente' => $existente
            ];
        }

        $uuid = bin2hex(random_bytes(16));

        Database::execute(
            "INSERT INTO clientes (uuid, tenant_id, nome, whatsapp, email, data_nascimento)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$uuid, $tenantId, $nome, $whatsapp, $email, $nascimento]
        );

        return [
            'success' => true,
            'modo' => 'criado',
            'cliente' => $this->buscarPorUuid($uuid, $tenantId)
        ];
    }

    public function atualizar(int $id, array $dados, int $tenantId): bool
    {
        $nome = strtoupper(trim($dados['nome'] ?? ''));
        $whatsapp = preg_replace('/\D/', '', $dados['whatsapp'] ?? '');
        $email = strtolower(trim($dados['email'] ?? ''));
        $nascimento = !empty($dados['data_nascimento']) ? $dados['data_nascimento'] : null;

        if (empty($nome) || empty($whatsapp)) {
            throw new Exception("Nome e WhatsApp são obrigatórios.");
        }

        return Database::execute(
            "UPDATE clientes SET nome = ?, whatsapp = ?, email = ?, data_nascimento = ?
             WHERE id = ? AND tenant_id = ?",
            [$nome, $whatsapp, $email, $nascimento, $id, $tenantId]
        );
    }

    public function excluir(int $id, int $tenantId): bool
    {
        // 1. Limpa histórico e saldo (Opcional - pode-se manter para auditoria)
        Database::execute("DELETE FROM fidelidade_saldo WHERE cliente_id = ? AND tenant_id = ?", [$id, $tenantId]);

        // 2. Remove o cliente
        return Database::execute("DELETE FROM clientes WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
    }

    public function getSaldo(int $clienteId, int $tenantId): int
    {
        $res = Database::fetch(
            "SELECT saldo_pontos FROM fidelidade_saldo WHERE cliente_id = ? AND tenant_id = ? LIMIT 1",
            [$clienteId, $tenantId]
        );
        return (int)($res['saldo_pontos'] ?? 0);
    }

    public function getConfig(int $tenantId): array
    {
        $config = Database::fetch(
            "SELECT meta_pontos, premio_desc, ativo FROM fidelidade_config WHERE tenant_id = ? LIMIT 1",
            [$tenantId]
        );

        if (!$config) {
            return [
                'meta_pontos' => 10,
                'premio_desc' => 'Corte Grátis',
                'ativo' => 1
            ];
        }

        return [
            'meta_pontos' => (int)$config['meta_pontos'],
            'premio_desc' => $config['premio_desc'],
            'ativo' => (int)$config['ativo']
        ];
    }

    public function creditarPonto(int $clienteId, int $tenantId, int $referenciaId, string $descricao = 'Atendimento concluído'): bool
    {
        try {
            Database::begin();

            // 1. Registra no Histórico (Auditoria)
            Database::execute(
                "INSERT INTO fidelidade_historico (tenant_id, cliente_id, tipo, pontos, descricao, referencia_id)
                 VALUES (?, ?, 'CREDITO', 1, ?, ?)",
                [$tenantId, $clienteId, $descricao, $referenciaId]
            );

            // 2. Atualiza Saldo Acumulado
            Database::execute(
                "INSERT INTO fidelidade_saldo (tenant_id, cliente_id, saldo_pontos)
                 VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE saldo_pontos = saldo_pontos + 1",
                [$tenantId, $clienteId]
            );

            Database::commit();
            return true;
        } catch (Throwable $e) {
            Database::rollback();
            return false;
        }
    }
}
