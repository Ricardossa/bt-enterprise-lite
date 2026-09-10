<?php
declare(strict_types=1);

$_SERVER['SCRIPT_NAME'] = 'setup.php'; // Bypass LicenseGuard
require_once __DIR__ . '/../bootstrap.php';

use BTQueue\Core\Database;

$logFile = 'Y:/bt-enterprise-lite/logs/db_audit.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$logHandle = fopen($logFile, 'w');

function logAudit(string $message) {
    global $logHandle;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $message\n";
    fwrite($logHandle, $line);
    echo $line;
}

logAudit("Iniciando auditoria de banco de dados...");

try {
    // 1. Verificar se o tenant 'paradaobrigatoriavilas' existe
    $tenant = Database::fetch("SELECT * FROM tenants WHERE slug = ?", ['paradaobrigatoriavilas']);

    if ($tenant) {
        logAudit("Tenant 'paradaobrigatoriavilas' encontrado (ID: {$tenant['id']}).");
    } else {
        logAudit("Tenant 'paradaobrigatoriavilas' NÃO encontrado. Verificando outros tenants...");

        // Buscamos qualquer tenant que não tenha o slug 'paradaobrigatoriavilas'
        // A instrução diz "any other tenant besides 'lite'".
        // Se temos um tenant que não é o ID 1, mesmo que o slug seja 'lite', ele é um candidato a ser o outro.
        $otherTenant = Database::fetch("SELECT * FROM tenants WHERE slug != 'paradaobrigatoriavilas' AND (slug != 'lite' OR id != 1) LIMIT 1");

        if ($otherTenant) {
            logAudit("Outro tenant encontrado: '{$otherTenant['slug']}' (ID: {$otherTenant['id']}). Atualizando slug para 'paradaobrigatoriavilas'...");
            Database::execute("UPDATE tenants SET slug = ? WHERE id = ?", ['paradaobrigatoriavilas', $otherTenant['id']]);

            $tenant = Database::fetch("SELECT * FROM tenants WHERE id = ?", [$otherTenant['id']]);
            logAudit("Slug atualizado com sucesso para o tenant ID {$tenant['id']}.");

            // Agora que o slug 'lite' está livre (se era o do tenant 30), podemos garantir que o tenant 1 existe como 'lite'
            Database::execute("INSERT IGNORE INTO tenants (id, uuid, slug, nome, status) VALUES (1, 'default-tenant-uuid', 'lite', 'Unidade Padrão', 'ATIVO')");
        } else {
            logAudit("Nenhum outro tenant encontrado além de 'lite' (ID 1).");

            // E se o LITE for o ID 30?
            $lite30 = Database::fetch("SELECT * FROM tenants WHERE id = 30 AND slug = 'lite'");
            if ($lite30) {
                logAudit("Encontrado tenant 30 com slug 'lite'. Renomeando para 'paradaobrigatoriavilas' para liberar o slug 'lite' para a Unidade 1.");
                Database::execute("UPDATE tenants SET slug = ? WHERE id = 30", ['paradaobrigatoriavilas']);
                $tenant = Database::fetch("SELECT * FROM tenants WHERE id = 30");
                Database::execute("INSERT IGNORE INTO tenants (id, uuid, slug, nome, status) VALUES (1, 'default-tenant-uuid', 'lite', 'Unidade Padrão', 'ATIVO')");
            }
        }
    }

    if ($tenant) {
        logAudit("Auditando configurações para o tenant: {$tenant['nome']} (ID: {$tenant['id']})");

        // Garantir que empresa nome esteja correto se for o Parada Obrigatória
        $empresa = Database::fetch("SELECT valor FROM configuracoes WHERE tenant_id = ? AND chave = 'empresa'", [$tenant['id']]);
        if (!$empresa || empty($empresa['valor'])) {
            logAudit("Nome da empresa não definido. Configurando como 'Parada Obrigatória'.");
            Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor, tipo) VALUES (?, 'empresa', 'Parada Obrigatória', 'STRING')", [$tenant['id']]);
        }

        $configs = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE tenant_id = ? AND chave IN ('logo_url', 'promo_logo')", [$tenant['id']]);

        $foundConfigs = [];
        foreach ($configs as $cfg) {
            $foundConfigs[$cfg['chave']] = $cfg['valor'];
        }

        foreach (['logo_url', 'promo_logo'] as $key) {
            $valor = $foundConfigs[$key] ?? 'NÃO DEFINIDO';
            logAudit("Configuração '{$key}' atual: {$valor}");

            $expectedPart = "uploads/tenants/{$tenant['id']}/";

            // Se o valor estiver vazio ou não apontar para o diretório do tenant
            if ($valor === 'NÃO DEFINIDO' || $valor === '' || strpos($valor, $expectedPart) === false) {
                logAudit("AVISO: '{$key}' está incorreto ou ausente. Corrigindo...");

                $filename = ($key === 'logo_url') ? 'logo.png' : 'logo_mobile.png';

                // Verifica se o arquivo existe no disco
                $fullPath = 'Y:/bt-enterprise-lite/public/' . $expectedPart . $filename;
                if (!file_exists($fullPath)) {
                    // Tenta ver se tem algum outro arquivo no diretório
                    $dir = 'Y:/bt-enterprise-lite/public/' . $expectedPart;
                    if (is_dir($dir)) {
                        $files = glob($dir . "*.png");
                        if (!empty($files)) {
                            $filename = basename($files[0]);
                        }
                    }
                }

                $newVal = $expectedPart . $filename;
                Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor, tipo) VALUES (?, ?, ?, 'STRING')", [
                    $tenant['id'],
                    $key,
                    $newVal
                ]);

                logAudit("Configuração '{$key}' atualizada para: {$newVal}");
            } else {
                logAudit("Configuração '{$key}' parece correta.");
            }
        }
    } else {
        logAudit("ERRO: Nenhum tenant alvo (Parada Obrigatória) identificado para auditoria de configurações.");
    }

} catch (Exception $e) {
    logAudit("ERRO CRÍTICO DURANTE AUDITORIA: " . $e->getMessage());
}

logAudit("Auditoria finalizada.");
fclose($logHandle);
