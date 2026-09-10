<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

Auth::protegerAPI();

try {
    $tenantId = Auth::tenantId();
    $operador = Auth::operador();
    $id = (int)$operador['id'];

    $input = json_decode(file_get_contents('php://input'), true);
    $status = strtoupper(trim((string)($input['status'] ?? 'ONLINE')));

    if (!in_array($status, ['ONLINE', 'BREAK', 'OFFLINE'])) {
        throw new Exception("Status inválido.");
    }

    Database::execute(
        "UPDATE operadores SET status = ? WHERE id = ? AND tenant_id = ?",
        [$status, $id, $tenantId]
    );

    echo json_encode(['success' => true, 'status' => $status]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
