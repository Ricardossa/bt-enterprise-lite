<?php

declare(strict_types=1);

namespace BTQueue\Core;

use PDO;
use Exception;

/**
 * Responsável pela instalação limpa do banco de dados (Schema + Provisionamento Dinâmico).
 * [DIAMOND v8.5] SaaS-Ready: Não cria mais o tenant "lite" por padrão.
 */
final class DatabaseInstaller
{
    /**
     * Instala apenas a estrutura de tabelas (Schema).
     */
    public function installSchema(): array
    {
        try {
            $db = Database::getInstance();
            $schemaFile = dirname(__DIR__) . '/database/schema.sql';
            if (!file_exists($schemaFile)) throw new Exception("Arquivo schema.sql não encontrado.");

            $schemaSql = file_get_contents($schemaFile);
            $db->exec($schemaSql);

            return ['success' => true, 'message' => 'Estrutura de tabelas criada com sucesso.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao criar schema: ' . $e->getMessage()];
        }
    }

    /**
     * Provisiona uma unidade específica no banco de dados.
     * Substitui o antigo seeds.sql por um provisionamento controlado por Tenant.
     */
    public function provisionTenant(int $tenantId, string $uuid, string $name, string $slug): array
    {
        try {
            // 1. Cria o Tenant
            Database::execute(
                "INSERT IGNORE INTO tenants (id, uuid, slug, nome, status) VALUES (?, ?, ?, ?, 'ATIVO')",
                [$tenantId, $uuid, $slug, $name]
            );

            // 2. Configurações Iniciais do Tenant
            $configs = [
                'master_url' => 'https://api.brandaotech.com.br/api/v1/sync.php',
                'app_name' => 'BT Queue Enterprise - ' . $name,
                'offline_limit_days' => '7',
                'uuid' => $uuid
            ];

            foreach ($configs as $key => $val) {
                Database::execute(
                    "INSERT IGNORE INTO configuracoes (tenant_id, chave, valor, tipo) VALUES (?, ?, ?, 'STRING')",
                    [$tenantId, $key, $val]
                );
            }

            // 3. Serviço e Guichê Padrão para esta Unidade
            Database::execute(
                "INSERT IGNORE INTO servicos (tenant_id, codigo, nome, slug, prefixo, icone, cor, ordem) VALUES (?, '1', 'Atendimento Geral', 'atendimento-geral', 'A', '📋', '#1565C0', 1)",
                [$tenantId]
            );
            $servicoId = Database::lastInsertId();

            Database::execute(
                "INSERT IGNORE INTO guiches (tenant_id, codigo, nome, icone, cor) VALUES (?, '01', 'Mesa 01', '⚙️', '#1565C0')",
                [$tenantId]
            );
            $guicheId = Database::lastInsertId();

            // Vínculo
            Database::execute("INSERT IGNORE INTO guiche_servicos (guiche_id, servico_id) VALUES (?, ?)", [(int)$guicheId, (int)$servicoId]);

            // 4. System Info (Identidade da Instalação)
            Database::execute(
                "INSERT IGNORE INTO system_info (tenant_id, installation_uuid, versao, build, hostname, php_version) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $tenantId,
                    $uuid,
                    Config::get('app.version', '4.0.0'),
                    date('Ymd.His'),
                    gethostname(),
                    PHP_VERSION
                ]
            );

            return ['success' => true, 'message' => "Unidade $name provisionada com sucesso."];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro no provisionamento: ' . $e->getMessage()];
        }
    }

    /**
     * @deprecated Use installSchema + provisionTenant
     */
    public function install(): array
    {
        return $this->installSchema();
    }
}
