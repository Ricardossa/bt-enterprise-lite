<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;

/**
 * Serviço de Registro de Atividades Locais (Enterprise).
 */
final class ActivityService
{
    public static function log(
        string $tipo,
        string $categoria,
        string $mensagem,
        array $metadata = [],
        ?string $usuario = null
    ): void {
        try {
            self::ensureTableExists();

            $tenantId = Auth::tenantId();
            if ($tenantId <= 0) return;

            Database::execute(
                "INSERT INTO atividades (tenant_id, tipo, categoria, mensagem, metadata, usuario) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $tenantId,
                    $tipo,
                    $categoria,
                    $mensagem,
                    !empty($metadata) ? json_encode($metadata) : null,
                    $usuario
                ]
            );
        } catch (Exception $e) {
            // Falha silenciosa
            error_log("Erro ao registrar atividade local: " . $e->getMessage());
        }
    }

    private static function ensureTableExists(): void
    {
        static $checked = false;
        if ($checked) return;

        // Versão MariaDB SaaS
        $sql = "CREATE TABLE IF NOT EXISTS atividades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            tipo VARCHAR(50) DEFAULT 'INFO',
            categoria VARCHAR(50) DEFAULT 'SYSTEM',
            mensagem TEXT NOT NULL,
            usuario VARCHAR(100) NULL,
            metadata TEXT NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_atividades_tenant (tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        try {
            Database::execute($sql);

            // [AUTO-FIX] Garante que a coluna tenant_id existe se a tabela foi criada antes da migração SaaS
            $columns = Database::getTableColumns('atividades');
            if (!empty($columns) && !in_array('tenant_id', $columns)) {
                Database::execute("ALTER TABLE atividades ADD COLUMN tenant_id INT NOT NULL DEFAULT 1 AFTER id");
            }
        } catch (Exception $e) {}

        $checked = true;
    }

    public static function getRecent(int $limit = 10): array
    {
        try {
            self::ensureTableExists();
            $tenantId = Auth::tenantId();

            // MariaDB LIMIT não aceita placeholders em algumas versões/drivers com prepare desativado
            // Como $limit é castado para int, é seguro concatenar aqui.
            // Retorna data_criacao para compatibilidade total com o dashboard.php
            return Database::fetchAll(
                "SELECT id, tipo, categoria, mensagem, usuario, metadata, data_criacao
                 FROM atividades
                 WHERE tenant_id = ?
                 ORDER BY id DESC LIMIT " . (int)$limit,
                [$tenantId]
            );
        } catch (Exception $e) {
            return [];
        }
    }
}
