<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\ServicoService;

$tenantId = 1;
$servicos = Database::fetchAll("SELECT id, nome, preco, promo_ativa, promo_desconto, promo_dias FROM servicos WHERE tenant_id = ? AND ativo = 1", [$tenantId]);

$res = [];
foreach ($servicos as $s) {
    $calc = ServicoService::getPrecoVigente((int)$s['id'], (int)$tenantId);
    $res[] = [
        'nome' => $s['nome'],
        'db_preco' => $s['preco'],
        'db_promo_ativa' => $s['promo_ativa'],
        'db_promo_dias' => $s['promo_dias'],
        'calc_preco' => $calc['preco'],
        'calc_is_promo' => $calc['is_promo'],
        'server_day' => date('w'),
        'server_time' => date('Y-m-d H:i:s')
    ];
}

header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT);
