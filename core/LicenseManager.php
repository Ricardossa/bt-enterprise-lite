<?php

declare(strict_types=1);

namespace BTQueue\Core;

use BTQueue\Core\Database;
use Exception;

/**
 * Gestor da Autoridade Local de Licenças.
 */
final class LicenseManager
{
    /**
     * Obtém a licença ativa local.
     */
    public function getActiveLicense(): ?array
    {
        return Database::fetch("SELECT * FROM licencas LIMIT 1");
    }

    /**
     * Obtém os dados de identidade da licença, independente do status.
     * Necessário para manter o canal de sincronização aberto.
     */
    public function getLicenseIdentity(): ?array
    {
        return Database::fetch("SELECT uuid, token FROM licencas LIMIT 1");
    }

    /**
     * Verifica se o sistema está bloqueado localmente.
     * RIGOROSO: Só permite acesso se o status for exatamente 'ATIVA' e estiver dentro do prazo.
     * Funciona 100% offline se a licença gravada for válida.
     */
    public function isBlocked(): bool
    {
        $licenca = Database::fetch("SELECT * FROM licencas LIMIT 1");
        if (!$licenca) return true;

        // 1. Bloqueio por Status (Se não estiver ATIVA, bloqueia tudo: SUSPENSA, CANCELADA, BLOQUEADA)
        if (strtoupper($licenca['status']) !== 'ATIVA') {
            return true;
        }

        // 2. Bloqueio por Tempo (Data de Validade + Carência Offline)
        $hoje = date('Y-m-d H:i:s');
        $validade = $licenca['validade'];

        // Aumentamos a carência offline para 15 dias para dar mais estabilidade ao cliente
        $carencia = (int)($licenca['offline_dias'] ?? 15);

        $dataLimite = date('Y-m-d H:i:s', strtotime($validade . " + $carencia days"));

        return $hoje > $dataLimite;
    }

    /**
     * Atualiza os dados da licença com as diretrizes da Master.
     */
    public function updateLicense(array $licenseData, string $uuid, string $token): void
    {
        $licenca = Database::fetch("SELECT id FROM licencas LIMIT 1");

        if ($licenca !== null) {
            Database::execute(
                "UPDATE licencas SET
                    status = ?,
                    validade = ?,
                    uuid = ?,
                    token = ?,
                    ultima_validacao = CURRENT_TIMESTAMP
                WHERE id = ?",
                [
                    strtoupper((string)$licenseData['status']),
                    (string)$licenseData['expires'],
                    $uuid,
                    $token,
                    $licenca['id']
                ]
            );
        } else {
            Database::execute(
                "INSERT INTO licencas (cliente_id, chave, uuid, token, status, validade, ultima_validacao)
                 VALUES (1, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)",
                [
                    $this->generateLicenseKey(),
                    $uuid,
                    $token,
                    strtoupper($licenseData['status']),
                    $licenseData['expires']
                ]
            );
        }
    }

    private function generateLicenseKey(): string
    {
        return strtoupper(bin2hex(random_bytes(6)));
    }
}
