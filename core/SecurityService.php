<?php

declare(strict_types=1);

namespace BTQueue\Core;

/**
 * Motor de Segurança Industrial - BT Diamond Guard
 * Responsável por gerar a identidade única do hardware e validar assinaturas.
 */
final class SecurityService
{
    /**
     * Gera o HWID (Hardware ID) único desta máquina.
     * Combina Nome do PC, Versão do SO e um Segredo Interno.
     */
    public static function getHardwareId(): string
    {
        // Coleta dados do ambiente
        $pcName = gethostname();
        $os = php_uname('s') . php_uname('v') . php_uname('m');

        // Em Windows, tentamos pegar o ID do processador via WMIC de forma silenciosa
        $cpuId = '';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            try {
                $cpuId = (string)shell_exec('wmic cpu get processorid /format:list');
                $cpuId = trim(str_replace('ProcessorId=', '', $cpuId));
            } catch (\Exception $e) { $cpuId = 'WIN-GENERIC'; }
        }

        // Cria a "Digital" única
        return hash('sha256', $pcName . $os . $cpuId . 'BRANDAO_TECH_SECRET_2026');
    }

    /**
     * Gera uma assinatura digital para os dados da licença.
     * Impede que alguém altere o banco de dados manualmente.
     */
    public static function signData(array $data, string $token): string
    {
        ksort($data); // Garante que a ordem não mude o hash
        $raw = json_encode($data) . $token . self::getHardwareId();
        return hash_hmac('sha256', $raw, 'BT_DIAMOND_KEY_8877');
    }

    /**
     * Valida se a assinatura do banco ainda é legítima.
     */
    public static function validateIntegrity(array $storedLicense): bool
    {
        if (!isset($storedLicense['assinatura'])) return false;

        $checkData = [
            'uuid' => $storedLicense['uuid'],
            'status' => $storedLicense['status'],
            'validade' => $storedLicense['validade']
        ];

        $recalculated = self::signData($checkData, $storedLicense['token']);
        return hash_equals($recalculated, $storedLicense['assinatura']);
    }
}
