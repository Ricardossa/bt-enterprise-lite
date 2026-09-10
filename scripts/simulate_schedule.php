<?php
/**
 * BT Queue - Simulador de Agendamento (Google Calendar Mock)
 */
$url = "http://enterprise.brandaotech.com.br/api/v1/external/schedule.php";
$token = "CEF3504AC887599D03C07639D5C90D63";

$agendados = [
    ["nome" => "MARCOS OLIVEIRA", "hora" => date('Y-m-d 09:00:00')],
    ["nome" => "ANA JULIA SANTOS", "hora" => date('Y-m-d 10:30:00')],
    ["nome" => "RICARDO BRANDAO JR", "hora" => date('Y-m-d 11:15:00')]
];

foreach ($agendados as $agd) {
    echo "📅 Enviando agendamento: {$agd['nome']} às {$agd['hora']}...\n";

    $payload = json_encode([
        "nome_paciente" => $agd['nome'],
        "data_hora" => $agd['hora'],
        "servico_id" => 1
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Integration-Token: ' . $token
    ]);

    $res = curl_exec($ch);
    curl_close($ch);
    echo "✅ Resposta: $res\n";
}
?>
