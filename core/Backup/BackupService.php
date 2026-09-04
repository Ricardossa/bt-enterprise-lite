<?php
declare(strict_types=1);

namespace BTQueue\Core\Backup;

use BTQueue\Core\Database;
use BTQueue\Core\Config;
use ZipArchive;
use Exception;

/**
 * Serviço de Backup Proativo - Enterprise
 */
final class BackupService
{
    private string $tempDir;

    public function __construct()
    {
        $this->tempDir = dirname(__DIR__, 2) . '/cache/';
    }

    /**
     * Executa o ciclo de backup MariaDB e envio para a Master
     */
    public function run(): array
    {
        try {
            // 1. Gera o arquivo de Dump (.sql.gz)
            $backupFile = $this->generateDump();

            // 2. Obtém identidade
            $identidade = Database::fetch("SELECT uuid, token FROM licencas LIMIT 1");
            if (!$identidade) {
                throw new Exception("Sistema não ativado. Backup cancelado.");
            }

            // 3. Obtém URL da Master
            $urlConfig = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'master_url' LIMIT 1");
            $masterUrl = $urlConfig ? $urlConfig['valor'] : '';

            if (empty($masterUrl)) {
                throw new Exception("URL da Master não configurada.");
            }

            // Transforma URL de sync em URL de backup
            $endpoint = str_replace('sync.php', 'backup_receiver.php', $masterUrl);

            // 4. Calcula Hash de Integridade
            $hash = hash_file('sha256', $backupFile);

            // 5. Envia via cURL
            $res = $this->send($endpoint, $identidade['uuid'], $identidade['token'], $backupFile, $hash);

            // 6. Limpeza
            @unlink($backupFile);

            return $res;

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function generateDump(): string
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $dbCfg  = $config['database'];

        $filename = 'bt_backup_' . date('Ymd_His') . '.sql.gz';
        $filePath = $this->tempDir . $filename;

        // [v2.6.1] Detecção dinâmica do mysqldump no ambiente Lite
        $baseDir = dirname(__DIR__, 2);
        $mysqldump = $baseDir . '/runtime/mariadb/bin/mysqldump.exe';

        if (!file_exists($mysqldump)) {
            $mysqldump = 'mysqldump';
        }

        // Criar arquivo temporário para o SQL puro
        $tempSql = $this->tempDir . 'db_dump_' . time() . '.sql';

        $cmd = sprintf(
            '"%s" -h %s -u %s -p"%s" --single-transaction --routines --triggers %s > "%s"',
            $mysqldump,
            $dbCfg['host'],
            $dbCfg['username'],
            $dbCfg['password'],
            $dbCfg['dbname'],
            $tempSql
        );

        exec($cmd, $output, $resultCode);

        if ($resultCode !== 0 || !file_exists($tempSql) || filesize($tempSql) < 100) {
            if (file_exists($tempSql)) @unlink($tempSql);
            throw new Exception("Falha ao gerar dump do banco MariaDB. Verifique o utilitário mysqldump.");
        }

        // Comprimir usando ZLIB do PHP (Independente de utilitários externos como gzip)
        $fp = fopen($tempSql, 'rb');
        $zp = gzopen($filePath, 'wb9');
        if (!$zp) {
            fclose($fp);
            throw new Exception("Falha ao criar arquivo comprimido GZ.");
        }

        while (!feof($fp)) {
            gzwrite($zp, fread($fp, 65536));
        }
        gzclose($zp);
        fclose($fp);

        // Remover SQL temporário
        @unlink($tempSql);

        if (!file_exists($filePath) || filesize($filePath) < 100) {
            throw new Exception("Falha na integridade do arquivo comprimido.");
        }

        return $filePath;
    }

    private function send(string $url, string $uuid, string $token, string $filePath, string $hash): array
    {
        $ch = curl_init($url);

        $cfile = new \CURLFile($filePath, 'application/x-gzip', 'backup.sql.gz');

        $postData = [
            'uuid' => $uuid,
            'token' => $token,
            'hash' => $hash, // Enviando o selo de integridade
            'backup' => $cfile
        ];

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Aumentado para 60s
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("Falha na conexão com a Master.");
        }

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new Exception($data['message'] ?? "Erro HTTP $httpCode na Master.");
        }

        return $data;
    }
}
