<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: text/plain');

echo "--- BT SCHEDULER DIAGNOSTICS ---\n";

try {
    echo "1. Database Path: " . (dirname(__DIR__, 2) . '/database/banco.db') . "\n";
    echo "2. Writable? " . (is_writable(dirname(__DIR__, 2) . '/database/banco.db') ? "YES" : "NO") . "\n";

    echo "\n3. Services:\n";
    $servs = Database::fetchAll("SELECT id, nome FROM servicos");
    print_r($servs);

    echo "\n4. Rules (agenda_regras):\n";
    $rules = Database::fetchAll("SELECT * FROM agenda_regras");
    print_r($rules);

    echo "\n5. Active Appointments for today:\n";
    $hoje = date('Y-m-d');
    $agd = Database::fetchAll("SELECT id, nome_cliente, data_agendamento FROM senhas WHERE status = 'AGENDADO' AND date(data_agendamento) = ?", [$hoje]);
    print_r($agd);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
