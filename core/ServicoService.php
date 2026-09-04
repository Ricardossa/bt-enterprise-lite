<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Throwable;

class ServicoService
{
    public function listar(): array
    {
        $tenantId = Auth::tenantId();
        $rows = Database::fetchAll(
            "SELECT id, codigo, nome, slug, prefixo, icone, cor, ordem, tempo_medio, preco,
                    promo_ativa, promo_desconto, promo_dias, created_at, updated_at
             FROM servicos
             WHERE tenant_id = ? AND ativo = 1
             ORDER BY ordem ASC, nome ASC",
            [$tenantId]
        );

        foreach ($rows as &$r) {
            $calc = self::getPrecoVigente((int)$r['id'], (int)$tenantId);
            $r['current_price'] = $calc['preco'];
            $r['is_promo_today'] = $calc['is_promo'];
        }
        return $rows;
    }

    public function buscar(int $id): ?array
    {
        $tenantId = Auth::tenantId();
        $r = Database::fetch(
            "SELECT id, codigo, nome, slug, prefixo, icone, cor, ordem, tempo_medio, preco,
                    promo_ativa, promo_desconto, promo_dias, ativo, created_at, updated_at
             FROM servicos
             WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if ($r) {
            $calc = self::getPrecoVigente((int)$r['id'], (int)$tenantId);
            $r['current_price'] = $calc['preco'];
            $r['is_promo_today'] = $calc['is_promo'];
        }
        return $r;
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
        float $preco = 0,
        int $promo_ativa = 0,
        float $promo_desconto = 20.00,
        string $promo_dias = '[1,2,3]'
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
                "INSERT INTO servicos (tenant_id, codigo, nome, slug, prefixo, icone, cor, ordem, tempo_medio, preco, promo_ativa, promo_desconto, promo_dias)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$tenantId, $codigo, $nome, $slug, $prefixo, $icone, $cor, $ordem, $tempo_medio, $preco, $promo_ativa, $promo_desconto, $promo_dias]
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
        float $preco = 0,
        int $promo_ativa = 0,
        float $promo_desconto = 20.00,
        string $promo_dias = '[1,2,3]'
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
                 SET codigo = ?, nome = ?, slug = ?, prefixo = ?, icone = ?, cor = ?, ordem = ?, tempo_medio = ?, preco = ?,
                     promo_ativa = ?, promo_desconto = ?, promo_dias = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ? AND tenant_id = ?",
                [$codigo, $nome, $slug, $prefixo, $icone, $cor, $ordem, $tempo_medio, $preco, $promo_ativa, $promo_desconto, $promo_dias, $id, $tenantId]
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
            Logger::error("Erro ao excluir serviÃ§o (ID $id): " . $e->getMessage());
            return ['success' => false, 'message' => 'INTERNAL_ERROR'];
        }
    }

    /**
     * [LITE v4.2.1] Calcula o preço real do serviço para uma data específica (Fonte da Verdade)
     */
    public static function getPrecoVigente(int $id, int $tenantId, ?string $dataAlvo = null): array
    {
        $s = Database::fetch("SELECT preco, promo_ativa, promo_desconto, promo_dias FROM servicos WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        if (!$s) return ['preco' => 0, 'is_promo' => false, 'original' => 0];

        $precoOriginal = (float)$s['preco'];
        $precoFinal = $precoOriginal;
        $isPromo = false;

        if (($s['promo_ativa'] ?? 0) == 1) {
            // Se não informou data, usa HOJE. Se informou (agendamento), usa a data do agendamento.
            $diaSemana = (int)date('w', $dataAlvo ? strtotime($dataAlvo) : time());
            $diasPromo = json_decode($s['promo_dias'] ?? '[]', true);

            if (in_array($diaSemana, $diasPromo)) {
                $desconto = (float)($s['promo_desconto'] ?? 20.00);
                $precoFinal = $precoOriginal * (1 - ($desconto / 100));
                $isPromo = true;
            }
        }

        return [
            'preco' => (float)$precoFinal,
            'is_promo' => $isPromo,
            'original' => $precoOriginal,
            'desconto_perc' => (float)($s['promo_desconto'] ?? 0)
        ];
    }
}
