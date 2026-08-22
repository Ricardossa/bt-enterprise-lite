<?php
declare(strict_types=1);

namespace BTQueue\Core;

use PDO;
use Exception;

/**
 * Motor de Evolução de Banco de Dados (Migrations).
 */
class Migration
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();

        // Garante que a tabela de controle suporte a nova coluna checksum
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS migrations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    arquivo TEXT UNIQUE,
                    checksum TEXT,
                    executado_em DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Adiciona a coluna checksum se não existir (Migração manual            // Garante coluna checksum (Migração interna SaaS Safe)
            $columns = Database::getTableColumns('migrations');
            if (!empty($columns) && !in_array('checksum', $columns)) {
                $this->db->exec("ALTER TABLE migrations ADD COLUMN checksum VARCHAR(64)");
            }
        } catch (Exception $e) {}
    }

    public function run(): array
    {
        $results = [];
        $dir = dirname(__DIR__) . '/database/migrations';

        if (!is_dir($dir)) {
            return ['success' => false, 'message' => "Diretório de migrations não encontrado."];
        }

        $arquivos = glob($dir . '/*.sql');
        sort($arquivos);

        foreach ($arquivos as $arquivo) {
            $nome = basename($arquivo);
            $checksum = md5_file($arquivo);

            $stmt = $this->db->prepare("SELECT * FROM migrations WHERE arquivo = ?");
            $stmt->execute([$nome]);
            $row = $stmt->fetch();

            if ($row) {
                // Se o arquivo mudou, mas já foi executado, avisamos (Integridade)
                if ($row['checksum'] && $row['checksum'] !== $checksum) {
                    $results[] = "⚠️ Atenção: O arquivo $nome foi alterado após a execução.";
                }
                continue;
            }

            try {
                $sql = file_get_contents($arquivo);
                if (trim($sql) === '') {
                    // Arquivos vazios são apenas marcados como feitos
                    $stmt = $this->db->prepare("INSERT INTO migrations (arquivo, checksum) VALUES (?, ?)");
                    $stmt->execute([$nome, $checksum]);
                    continue;
                }

                $this->db->exec($sql);

                $stmt = $this->db->prepare("INSERT INTO migrations (arquivo, checksum) VALUES (?, ?)");
                $stmt->execute([$nome, $checksum]);

                $results[] = "✅ Atualizado: $nome";
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => "Erro crítico ao processar $nome: " . $e->getMessage(),
                    'details' => $results
                ];
            }
        }

        return [
            'success' => true,
            'message' => empty($results) ? 'Nenhuma atualização pendente.' : 'Sistema atualizado com sucesso.',
            'details' => $results
        ];
    }
}
