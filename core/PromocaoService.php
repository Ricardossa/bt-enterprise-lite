<?php
declare(strict_types=1);

namespace BTQueue\Core;

class PromocaoService
{
    public function listar(): array
    {
        $tenantId = Auth::tenantId();
        return Database::fetchAll(
            "SELECT *
             FROM promocoes
             WHERE tenant_id = ?
             ORDER BY ordem, titulo",
            [$tenantId]
        );
    }

    public function buscar(int $id): ?array
    {
        $tenantId = Auth::tenantId();
        return Database::fetch(
            "SELECT *
             FROM promocoes
             WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );
    }

    public function adicionar(
        string $titulo,
        string $descricao,
        string $preco = '',
        string $imagem = '',
        int $ordem = 0
    ): array
    {
        $tenantId = Auth::tenantId();
        Database::execute(
            "INSERT INTO promocoes
            (
                tenant_id,
                titulo,
                descricao,
                preco,
                imagem,
                ordem
            )
            VALUES
            (
                ?,?,?,?,?,?
            )",
            [
                $tenantId,
                $titulo,
                $descricao,
                $preco,
                $imagem,
                $ordem
            ]
        );

        return [
            'success' => true
        ];
    }

    public function editar(
        int $id,
        string $titulo,
        string $descricao,
        string $preco,
        string $imagem,
        int $ordem
    ): array
    {
        $tenantId = Auth::tenantId();
        Database::execute(
            "UPDATE promocoes
             SET
                titulo=?,
                descricao=?,
                preco=?,
                imagem=?,
                ordem=?,
                updated_at=NOW()
             WHERE id=? AND tenant_id = ?",
            [
                $titulo,
                $descricao,
                $preco,
                $imagem,
                $ordem,
                $id,
                $tenantId
            ]
        );

        return [
            'success' => true
        ];
    }

    public function excluir(int $id): array
    {
        $tenantId = Auth::tenantId();
        Database::execute(
            "DELETE FROM promocoes
             WHERE id=? AND tenant_id = ?",
            [
                $id,
                $tenantId
            ]
        );

        return [
            'success' => true
        ];
    }
}
