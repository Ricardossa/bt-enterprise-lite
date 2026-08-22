<?php

declare(strict_types=1);

namespace BTQueue\Core;

/**
 * Motor de Mensageria WhatsApp - BT Zap Mestre
 * Responsável por disparar notificações automáticas para clientes.
 */
final class WhatsAppService
{
    /**
     * Envia uma mensagem de texto simples via Gateway configurado.
     */
    public static function send(string $numero, string $mensagem): bool
    {
        try {
            $configs = self::getConfigs();

            if (($configs['whatsapp_enabled'] ?? '0') !== '1') {
                return false;
            }

            $url = $configs['whatsapp_api_url'] ?? '';
            $token = $configs['whatsapp_api_token'] ?? '';

            if (empty($url) || empty($token)) {
                Logger::warning("WhatsApp habilitado mas URL/Token ausentes.");
                return false;
            }

            // Limpa o número para o padrão internacional (sem +, apenas números)
            $numeroLimpo = preg_replace('/\D/', '', $numero);
            if (strlen($numeroLimpo) < 10) return false;

            // Suporte para Evolution API (Default)
            $payload = json_encode([
                'number' => $numeroLimpo,
                'text' => $mensagem
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $token // Padrão Evolution
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return true;
            }

            Logger::error("Falha ao enviar WhatsApp (HTTP $httpCode): " . $response);
            return false;

        } catch (\Throwable $e) {
            Logger::error("Erro no WhatsAppService: " . $e->getMessage());
            return false;
        }
    }

    private static function getConfigs(): array
    {
        $tenantId = Auth::tenantId();
        $rows = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE tenant_id = ? AND (chave LIKE 'whatsapp_%' OR chave = 'empresa')", [$tenantId]);
        $cfg = [];
        foreach ($rows as $r) {
            $cfg[$r['chave']] = $r['valor'];
        }
        return $cfg;
    }
}
