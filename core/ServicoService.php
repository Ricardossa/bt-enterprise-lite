<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Throwable;

class ServicoService
{
    public function listar(): array
    {
        $tenantId = Auth::tenantId();
        return Database::fetchAll(
            "SELECT id, codigo, nome, slug, prefixo, icone, cor, ordem, tempo_medio, preco, created_at, updated_at
             FROM servicos
             WHERE tenant_id = ? AND ativo = 1
             ORDER BY ordem ASC, nome ASC",
            [$tenantId]
        );
    }

    public function buscar(int $id): ?array
    {
        $tenantId = Auth::tenantId();
        return Database::fetch(
            "SELECT id, codigo, nome, slug, prefixo, icone, cor, ordem, tempo_medio, preco, ativo, created_at, updated_at
             FROM servicos
             WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );
    }

    public function adicionar(
        string $codigo,
        string $nome,
        string $slug,
        string $prefixo,
        string $icone,
        string $cor,
        int $ordem,
        int $tempo_medio,
        float $preco = 0 // v1.1.0-LITE
    ): array {
        try {
            $tenantId = Auth::tenantId();
            $existeCodigo = Database::fetch("SELECT id FROM servicos WHERE codigo = ? AND tenant_id = ?", [$codigo, $tenantId]);
            if ($existeCodigo) {
                return ['success' => false, 'message' => 'DUPLICATE_CODE'];
            }

            $existeSlug = Database::fetch("SELECT id FROM servicos WHERE slug = ? AND tenant_id = ?", [$slug, $tenantId]);
            if ($existeSlug) {
                return ['success' => false, 'message' => 'DUPLICATE_SLUG'];
            }

            Database::execute(
                "INSERT INTO servicos (tenant_id, codigo, nome, slug, prefixo, icone, cor, ordem, tempo_medio, preco)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$tenantId, $codigo, $nome, $slug, $prefixo, $icone, $cor, $ordem, $tempo_medio, $preco]
            );

            return ['success' => true];

        } catch (Throwable $e) {
            Logger::error($e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function editar(
        int $id,
        string $codigo,
        string $nome,
        string $slug,
        string $prefixo,
        string $icone,
        string $cor,
        int $ordem,
        int $tempo_medio,
        float $preco = 0 // v1.1.0-LITE
    ): array {
        try {
            $tenantId = Auth::tenantId();
            $existeCodigo = Database::fetch("SELECT id FROM servicos WHERE codigo = ? AND tenant_id = ? AND id != ?", [$codigo, $tenantId, $id]);
            if ($existeCodigo) {
                return ['success' => false, 'message' => 'DUPLICATE_CODE'];
            }

            $existeSlug = Database::fetch("SELECT id FROM servicos WHERE slug = ? AND tenant_id = ? AND id != ?", [$slug, $tenantId, $id]);
            if ($existeSlug) {
                return ['success' => false, 'message' => 'DUPLICATE_SLUG'];
            }

            Database::execute(
                "UPDATE servicos
                 SET codigo = ?, nome = ?, slug = ?, prefixo = ?, icone = ?, cor = ?, ordem = ?, tempo_medio = ?, preco = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ? AND tenant_id = ?",
                [$codigo, $nome, $slug, $prefixo, $icone, $cor, $ordem, $tempo_medio, $preco, $id, $tenantId]
            );

            return ['success' => true];

        } catch (Throwable $e) {
            Logger::error("Erro ao editar serviço (ID $id): " . $e->getMessage());
            return ['success' => false, 'message' => 'INTERNAL_ERROR'];
        }
    }

    public function excluir(int $id): array
    {
        try {
            $tenantId = Auth::tenantId();
            Database::execute(
                "UPDATE servicos 
                 SET ativo = 0, updated_at = CURRENT_TIMESTAMP 
                 WHERE id = ? AND tenant_id = ?",
                [$id, $tenantId]
            );
            return ['success' => true];
        } catch (Throwable $e) {
            Logger::error("Erro ao excluir serviço (ID $id): " . $e->getMessage());
            return ['success' => false, 'message' => 'INTERNAL_ERROR'];
        }
    }
}
