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

    // 2. Resolve o Operador filtrando pelo Tenant
    $opId = isset($_GET['op_id']) ? (int)$_GET['op_id'] : 0;

    if ($opId > 0) {
        $operador = Database::fetch("SELECT o.* FROM operadores o WHERE o.id = ? AND o.tenant_id = ? AND o.ativo = 1 LIMIT 1", [$opId, $tenantId]);
    } else {
        // Fallback: Pega o primeiro Ricardo ou o primeiro ativo desta unidade
        $operador = Database::fetch("SELECT o.* FROM operadores o WHERE o.tenant_id = ? AND o.ativo = 1 ORDER BY (CASE WHEN o.nome LIKE '%RICARDO%' THEN 1 ELSE 2 END), o.id ASC LIMIT 1", [$tenantId]);
    }

    if (!$operador) {
        throw new Exception("Nenhum barbeiro ativo encontrado nesta unidade.");
    }

    $_SESSION['operador'] = [
        'id' => (int)$operador['id'],
        'nome' => $operador['nome'],
        'login' => $operador['login'],
        'nivel' => $operador['nivel'] ?? 'OPERADOR',
        'tenant_id' => $tenantId
    ];

    echo json_encode([
        'success' => true,
        'operador' => $_SESSION['operador'],
        'debug' => "Autenticado como: " . $operador['nome'] . " (ID: " . $operador['id'] . ")"
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
