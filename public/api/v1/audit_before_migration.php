<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
header('Content-Type: application/json');
$hoje = date('Y-m-d');
$res = Database::fetchAll("SELECT tenant_id, COUNT(*) as total FROM senhas WHERE (DATE(data_agendamento) >= ? OR DATE(created_at) = ?) AND status IN ('AGENDADO', 'PRESENTE', 'AGUARDANDO', 'CHAMANDO') GROUP BY tenant_id", [$hoje, $hoje]);
echo json_encode($res, JSON_PRETTY_PRINT);
