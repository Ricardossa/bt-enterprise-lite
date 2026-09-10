<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: *");

try {
    Auth::iniciar();

    // 1. Resolve o Tenant (Vital para SaaS)
    $tenant = Auth::getCurrentTenant();
    if (!$tenant) {
        throw new Exception("Unidade não identificada via subdomínio.");
    }

    $tenantId = (int)$tenant['id'];
    $_SESSION['tenant_id'] = $tenantId;
    $_SESSION['tenant_slug'] = $tenant['slug'];
    $_SESSION['tenant_uuid'] = $tenant['uuid'];

    // 2. Resolve o Operador filtrando pelo Tenant
    $opId = isset($_GET['op_id']) ? (int)$_GET['op_id'] : 0;
    $pass = isset($_GET['pass']) ? (string)$_GET['pass'] : '';

    if ($opId > 0) {
        $operador = Database::fetch("SELECT o.* FROM operadores o WHERE o.id = ? AND o.tenant_id = ? AND o.ativo = 1 LIMIT 1", [$opId, $tenantId]);
    } else {
        // Bloqueia fallback automático por segurança (Exige ID e Senha específicos)
        throw new Exception("ID do barbeiro necessário.");
    }

    if (!$operador) {
        throw new Exception("Nenhum barbeiro ativo encontrado nesta unidade.");
    }

    // [v3.0.0] Validação de Segurança Mandatória
    if (empty($pass)) {
        throw new Exception("Senha não informada.");
    }

    if (!password_verify($pass, $operador['senha'])) {
        // Suporte temporário para senhas em texto plano na migração
        if ($operador['senha'] !== $pass) {
            throw new Exception("Senha incorreta.");
        }
    }

    $_SESSION['operador'] = [
        'id' => (int)$operador['id'],
        'nome' => $operador['nome'],
        'login' => $operador['login'],
        'nivel' => $operador['nivel'] ?? 'OPERADOR',
        'guiche_id' => (int)$operador['guiche_id'],
        'tenant_id' => $tenantId,
        'tenant_uuid' => $tenant['uuid']
    ];

    // [v2.2.0] Busca Logo da Unidade
    $logoRow = Database::fetch("SELECT valor FROM configuracoes WHERE tenant_id = ? AND chave = 'logo_url' LIMIT 1", [$tenantId]);
    $unitLogo = $logoRow['valor'] ?? '';

    echo json_encode([
        'success' => true,
        'operador' => $_SESSION['operador'],
        'tenant' => [
            'id' => $tenantId,
            'slug' => $tenant['slug'],
            'uuid' => $tenant['uuid'],
            'logo' => $unitLogo
        ],
        'debug' => "Autenticado como: " . $operador['nome'] . " (ID: " . $operador['id'] . ")"
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
