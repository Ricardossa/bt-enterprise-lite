<?php
declare(strict_types=1);
namespace BTQueue\Core;
use BTQueue\Core\Database;

final class LicenseManager {
    public function getActiveLicense(): ?array {
        $tenantId = Auth::tenantId();
        if ($tenantId <= 0) return null;
        return Database::fetch("SELECT * FROM licencas WHERE tenant_id = ? LIMIT 1", [$tenantId]);
    }
    public function getLicenseIdentity(): ?array {
        $tenantId = Auth::tenantId();
        return Database::fetch("SELECT uuid, token FROM licencas WHERE tenant_id = ? LIMIT 1", [$tenantId]);
    }
    public function isBlocked(): bool {
        $tenantId = Auth::tenantId();
        if ($tenantId <= 0) return false; // Permite setup inicial
        $licenca = $this->getActiveLicense();
        if (!$licenca) return false;
        if (strtoupper((string)$licenca['status']) !== 'ATIVA') return true;
        $hoje = time();
        $validade = strtotime((string)$licenca['validade']);
        $carencia = (int)($licenca['offline_dias'] ?? 15);
        return $hoje > ($validade + ($carencia * 86400));
    }
    public function updateLicense(array $licenseData, string $uuid, string $token): void {
        $tenantId = Auth::tenantId();
        if ($tenantId <= 0) return;
        $licenca = Database::fetch("SELECT id FROM licencas WHERE tenant_id = ? LIMIT 1", [$tenantId]);
        if ($licenca !== null) {
            Database::execute("UPDATE licencas SET status = ?, validade = ?, uuid = ?, token = ?, ultima_validacao = CURRENT_TIMESTAMP WHERE id = ?", [strtoupper((string)$licenseData['status']), (string)$licenseData['expires'], $uuid, $token, $licenca['id']]);
        } else {
            Database::execute("INSERT INTO licencas (tenant_id, cliente_id, chave, uuid, token, status, validade, ultima_validacao) VALUES (?, 1, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)", [$tenantId, strtoupper(bin2hex(random_bytes(6))), $uuid, $token, strtoupper((string)$licenseData['status']), $licenseData['expires']]);
        }
    }
}
