<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Auth;

Auth::protegerAPI('ADMIN');

header('Content-Type: application/json; charset=utf-8');

$pasta = __DIR__ . '/../uploads/promocoes/';

if (!is_dir($pasta)) {
    mkdir($pasta, 0755, true);
}

if (!isset($_FILES['imagem'])) {

    echo json_encode([
        'success' => false,
        'message' => 'Nenhuma imagem enviada.'
    ]);

    exit;

}

$tmp = $_FILES['imagem']['tmp_name'];

$ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));

$permitidas = ['jpg','jpeg','png','webp'];

if (!in_array($ext, $permitidas)) {

    echo json_encode([
        'success' => false,
        'message' => 'Formato inválido.'
    ]);

    exit;

}

$arquivo = strtoupper(bin2hex(random_bytes(4))) . '.' . $ext;

$destino = $pasta . $arquivo;

if (move_uploaded_file($tmp, $destino)) {

    echo json_encode([
        'success' => true,
        'arquivo' => $arquivo
    ]);

} else {

    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar imagem.'
    ]);

}
