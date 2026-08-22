<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Throwable;

class GuicheService
{

    public function listar(): array
    {
        $tenantId = Auth::tenantId();
        return Database::fetchAll(
            "SELECT *
             FROM guiches
             WHERE tenant_id = ? AND ativo=1
             ORDER BY codigo",
            [$tenantId]
        );
    }

    public function buscar(int $id): ?array
    {
        $tenantId = Auth::tenantId();
        return Database::fetch(
            "SELECT *
             FROM guiches
             WHERE id=? AND tenant_id = ?",
            [$id, $tenantId]
        );
    }

    public function adicionar(
        string $codigo,
        string $nome,
        string $icone='',
        string $cor='#1565C0'
    ): array
    {
        $tenantId = Auth::tenantId();
        $existe = Database::fetch(
            "SELECT id
             FROM guiches
             WHERE codigo=? AND tenant_id = ?",
            [$codigo, $tenantId]
        );

        if($existe){
            return [
                'success'=>false,
                'message'=>'Já existe um guichê com este código.'
            ];
        }

        Database::execute(
            "INSERT INTO guiches (tenant_id, codigo, nome, icone, cor) VALUES (?,?,?,?,?)",
            [$tenantId, $codigo, $nome, $icone, $cor]
        );

        return ['success'=>true];
    }

    public function editar(
        int $id,
        string $codigo,
        string $nome,
        string $icone,
        string $cor
    ): array
    {
        $tenantId = Auth::tenantId();
        Database::execute(
            "UPDATE guiches SET codigo=?, nome=?, icone=?, cor=?, updated_at=CURRENT_TIMESTAMP WHERE id=? AND tenant_id = ?",
            [$codigo, $nome, $icone, $cor, $id, $tenantId]
        );

        return ['success'=>true];
    }

    public function excluir(int $id): array
    {
        $tenantId = Auth::tenantId();
        Database::execute(
            "UPDATE guiches SET ativo = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        return ['success' => true];
    }

}
