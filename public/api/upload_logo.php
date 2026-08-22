<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Auth;

Auth::protegerAPI('ADMIN');
header('Content-Type: application/json; charset=utf-8');

$tipo = $_POST['tipo'] ?? 'admin';
$id = $_POST['id'] ?? '';

if (!isset($_FILES['logo'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhuma imagem enviada.']);
    exit;
}

$tenantId = Auth::tenantId();
$dirDestino = __DIR__ . '/../uploads/tenants/' . $tenantId . '/';

if (!is_dir($dirDestino)) {
    mkdir($dirDestino, 0775, true);
}

// Mapeamento de arquivos por tipo de identidade
if ($tipo === 'operador' && !empty($id)) {
    $nomeArquivo = 'operador_' . $id . '.png';
} else {
    $arquivos = [
        'admin'    => 'logo.png',
        'mobile'   => 'logo_mobile.png',
        'campaign' => 'logo_campanha.png'
    ];
    $nomeArquivo = $arquivos[$tipo] ?? 'logo.png';
}

$destino = $dirDestino . $nomeArquivo;

if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) {
    echo json_encode(['success' => true, 'arquivo' => 'uploads/tenants/' . $tenantId . '/' . $nomeArquivo]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar a imagem em disco.']);
}
