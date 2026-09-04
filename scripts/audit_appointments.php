<?php

require_once __DIR__ . '/../bootstrap.php';

use BTQueue\Core\Database;

$today = '2026-08-29';
$logFile = 'Y:/bt-enterprise-lite/logs/appointments_audit.log';

if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

logMessage("--- AUDIT START FOR $today ---");

try {
    // 1. List all appointments (status 'AGENDADO' or 'PRESENTE') for today
    $sqlToday = "SELECT id, tenant_id, nome_cliente, status, data_agendamento, created_at
                 FROM senhas
                 WHERE (status = 'AGENDADO' OR status = 'PRESENTE')
                 AND (DATE(data_agendamento) = :today1 OR DATE(created_at) = :today2)";

    $todayAppts = Database::fetchAll($sqlToday, ['today1' => $today, 'today2' => $today]);

    logMessage("Appointments for today ($today) with status AGENDADO/PRESENTE: " . count($todayAppts));
    foreach ($todayAppts as $appt) {
        logMessage("ID: {$appt['id']} | Tenant: {$appt['tenant_id']} | Status: {$appt['status']} | Client: {$appt['nome_cliente']}");
    }

    // 2. Look for ANY appointment with 'Parada Obrigatória' in tenant 1 and move to 30
    // The user said "Specifically, look for any appointment...", which might mean beyond today.
    $sqlMisplaced = "SELECT id, tenant_id, nome_cliente, status FROM senhas
                     WHERE tenant_id = 1 AND nome_cliente LIKE :name";
    $misplaced = Database::fetchAll($sqlMisplaced, ['name' => '%Parada Obrigatória%']);

    if (count($misplaced) > 0) {
        logMessage("Found " . count($misplaced) . " misplaced appointments in Tenant 1 belonging to 'Parada Obrigatória'.");
        foreach ($misplaced as $m) {
            logMessage(">> MOVING APPOINTMENT {$m['id']} (Client: {$m['nome_cliente']}) to Tenant 30");
            Database::execute("UPDATE senhas SET tenant_id = 30 WHERE id = :id", ['id' => $m['id']]);
        }
    } else {
        logMessage("No misplaced 'Parada Obrigatória' appointments found in Tenant 1.");
    }

    // 3. Check for operators like "Ysmael" in tenant 1 and move to 30
    $sqlOp = "SELECT id, nome, tenant_id FROM operadores WHERE tenant_id = 1 AND (nome LIKE :n1 OR login LIKE :n2)";
    $ops = Database::fetchAll($sqlOp, ['n1' => '%Ysmael%', 'n2' => '%Ysmael%']);

    if (count($ops) > 0) {
        logMessage("Found " . count($ops) . " operators in Tenant 1 that should be in Tenant 30.");
        foreach ($ops as $op) {
            logMessage(">> MOVING OPERATOR {$op['nome']} (ID: {$op['id']}) to Tenant 30");
            Database::execute("UPDATE operadores SET tenant_id = 30 WHERE id = :id", ['id' => $op['id']]);
        }
    } else {
        logMessage("No operators matching 'Ysmael' found in Tenant 1.");
    }

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
}

logMessage("--- AUDIT END ---");
echo "Audit completed. Results written to $logFile" . PHP_EOL;
