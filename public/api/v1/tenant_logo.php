<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

Auth::protegerAPI('ADMIN');

try {
    $tenantId = Auth::tenantId();

    if (!isset($_FILES['logo'])) {
        throw new Exception("Nenhum arquivo enviado.");
    }

    $uploadDir = dirname(__DIR__, 2) . "/uploads/tenants/{$tenantId}/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $dest = $uploadDir . "logo.png";

    if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
        // Salva nas configurações para fácil acesso
        $logoUrl = "uploads/tenants/{$tenantId}/logo.png";
        Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor, tipo) VALUES (?, 'logo_url', ?, 'STRING')", [$tenantId, $logoUrl]);

        echo json_encode(['success' => true, 'message' => 'Logo atualizada!']);
    } else {
        throw new Exception("Falha ao mover arquivo.");
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
