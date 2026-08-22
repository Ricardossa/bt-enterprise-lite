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
    private string $dbPath;
    private string $tempDir;

    public function __construct()
    {
        $this->dbPath = dirname(__DIR__, 2) . '/database/banco.db';
        $this->tempDir = dirname(__DIR__, 2) . '/cache/';
    }

    /**
     * Executa o ciclo de backup e envio para a Master
     */
    public function run(): array
    {
        try {
            // 1. Gera o arquivo ZIP
            $zipPath = $this->compress();

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

            // 4. Envia via cURL
            $res = $this->send($endpoint, $identidade['uuid'], $identidade['token'], $zipPath);

            // 5. Limpeza
            @unlink($zipPath);

            return $res;

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function compress(): string
    {
        $zipName = 'backup_' . date('Ymd_His') . '.zip';
        $zipPath = $this->tempDir . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Exception("Não foi possível criar o arquivo ZIP temporário.");
        }

        // Adiciona o banco de dados (usando apenas o nome 'banco.db' dentro do zip)
        $zip->addFile($this->dbPath, 'banco.db');
        $zip->close();

        return $zipPath;
    }

    private function send(string $url, string $uuid, string $token, string $filePath): array
    {
        $ch = curl_init($url);

        $cfile = new \CURLFile($filePath, 'application/zip', 'backup.zip');

        $postData = [
            'uuid' => $uuid,
            'token' => $token,
            'backup' => $cfile
        ];

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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
