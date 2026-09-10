<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
header('Content-Type: application/json');
try {
    $hoje = date('Y-m-d');
    $agendados = Database::fetch("SELECT COUNT(*) as total FROM senhas WHERE tenant_id = 35 AND status = 'AGENDADO' AND DATE(data_agendamento) = ?", [$hoje]);
    $presentes = Database::fetch("SELECT COUNT(*) as total FROM senhas WHERE tenant_id = 35 AND status = 'PRESENTE' AND DATE(data_agendamento) = ?", [$hoje]);
    $finalizados = Database::fetch("SELECT COUNT(*) as total FROM senhas WHERE tenant_id = 35 AND status = 'FINALIZADA' AND DATE(data_agendamento) = ?", [$hoje]);

    echo json_encode([
        'unidade' => 'Parada Obrigatória (Local)',
        'agendados' => $agendados['total'] ?? 0,
        'checkin_realizado' => $presentes['total'] ?? 0,
        'ja_atendidos' => $finalizados['total'] ?? 0,
        'hora_servidor' => date('H:i:s')
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
