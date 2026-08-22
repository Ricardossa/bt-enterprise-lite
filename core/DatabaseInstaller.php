<?php

declare(strict_types=1);

namespace BTQueue\Core;

use PDO;
use Exception;

/**
 * Responsável pela instalação limpa do banco de dados (Schema + Seeds).
 */
final class DatabaseInstaller
{
    public function install(): array
    {
        $results = [];

        try {
            // [PROTEÇÃO] Nunca delete um banco que já possui dados vitais
            if (Database::exists()) {
                $hasUuid = Database::fetch("SELECT installation_uuid FROM system_info LIMIT 1");

                if ($hasUuid) {
                    $results[] = "ℹ️ Banco de dados preservado (Instalação ativa detectada no MariaDB).";
                    return ['success' => true, 'message' => 'Estrutura preservada.', 'details' => $results];
                }
            }

            // 1. Conecta ao banco
            $db = Database::getInstance();

            // 2. Executa o Schema
            $schemaFile = dirname(__DIR__) . '/database/schema.sql';
            if (!file_exists($schemaFile)) throw new Exception("Arquivo schema.sql não encontrado.");

            $schemaSql = file_get_contents($schemaFile);
            $db->exec($schemaSql);
            $results[] = "✅ Estrutura de tabelas criada.";

            // 3. Executa os Seeds
            $seedsFile = dirname(__DIR__) . '/database/seeds.sql';
            if (!file_exists($seedsFile)) throw new Exception("Arquivo seeds.sql não encontrado.");

            $seedsSql = file_get_contents($seedsFile);
            $db->exec($seedsSql);
            $results[] = "✅ Dados iniciais configurados.";

            // Compatibilidade também para templates gerados antes do Diamond.
            DiamondActivationService::ensureLicenseColumns();

            // 4. Garante que a URL da Master está sempre configurada no banco inicial
            Database::execute(
                "INSERT IGNORE INTO configuracoes (chave, valor, tipo, descricao) VALUES (?, ?, 'STRING', ?)",
                [
                    'master_url',
                    'http://api.brandaotech.com.br:8080/api/v1/sync.php',
                    'URL de sincronização com a Platform Master'
                ]
            );
            $results[] = "✅ URL da Master garantida no banco.";

            // 5. Gera Identidade da Instalação (UUID Permanente)
            $uuid = $this->generateUuid();
            Database::execute(
                "INSERT IGNORE INTO system_info (installation_uuid, versao, build, hostname, php_version) VALUES (?, ?, ?, ?, ?)",
                [
                    $uuid,
                    Config::get('app.version', '4.0.0'),
                    date('Ymd.His'),
                    gethostname(),
                    PHP_VERSION
                ]
            );
            $results[] = "✅ Identidade gerada: $uuid";

            $migrationResult = (new Migration())->run();
            if (!$migrationResult['success']) {
                throw new Exception($migrationResult['message']);
            }
            $results[] = "✅ Migrations executadas.";

            return [
                'success' => true,
                'message' => 'Banco de dados instalado com sucesso.',
                'details' => $results,
                'uuid' => $uuid
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro na instalação do banco: ' . $e->getMessage()
            ];
        }
    }

    private function generateUuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
