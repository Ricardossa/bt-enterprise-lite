<?php

declare(strict_types=1);

namespace BTQueue\Core;

use RuntimeException;

/** Centraliza a criação e a renovação do selo Diamond. */
final class DiamondActivationService
{
    public static function ensureLicenseColumns(): void
    {
        $columns = Database::getTableColumns('licencas');
        if (!in_array('hardware_id', $columns, true)) {
            Database::execute('ALTER TABLE licencas ADD COLUMN hardware_id TEXT');
        }
        if (!in_array('assinatura', $columns, true)) {
            Database::execute('ALTER TABLE licencas ADD COLUMN assinatura TEXT');
        }
    }

    /** @return array{hardware_id:string, assinatura:string} */
    public static function sealLicense(int $licenseId): array
    {
        self::ensureLicenseColumns();
        $license = Database::fetch('SELECT * FROM licencas WHERE id = ?', [$licenseId]);
        if (!$license) {
            throw new RuntimeException('Licença não encontrada para ativação Diamond.');
        }

        $hardwareId = SecurityService::getHardwareId();
        $signature = SecurityService::signData([
            'uuid' => (string) $license['uuid'],
            'status' => (string) $license['status'],
            'validade' => (string) $license['validade'],
        ], (string) $license['token']);

        Database::execute(
            'UPDATE licencas SET hardware_id = ?, assinatura = ? WHERE id = ?',
            [$hardwareId, $signature, $licenseId]
        );

        $sealed = Database::fetch('SELECT * FROM licencas WHERE id = ?', [$licenseId]);
        if (!$sealed || !SecurityService::validateIntegrity($sealed)) {
            throw new RuntimeException('Falha na validação final do selo Diamond.');
        }

        return ['hardware_id' => $hardwareId, 'assinatura' => $signature];
    }
}
