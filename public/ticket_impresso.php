<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$senha = $_GET['senha'] ?? '---';
$servico = $_GET['servico'] ?? 'Atendimento';
$empresa = $_GET['empresa'] ?? 'BT Queue';
$data = date('d/m/Y H:i:s');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Atendimento</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 80mm; /* Largura padrão de impressora térmica */
            margin: 0;
            padding: 5mm;
            text-align: center;
            color: #000;
            background: #fff;
        }

        .header { border-bottom: 1pt dashed #000; padding-bottom: 5mm; margin-bottom: 5mm; }
        .empresa { font-size: 14pt; font-weight: bold; margin-bottom: 2mm; text-transform: uppercase; }

        .ticket-box { padding: 5mm 0; }
        .label { font-size: 10pt; text-transform: uppercase; margin-bottom: 2mm; }
        .senha { font-size: 48pt; font-weight: 900; margin: 2mm 0; }
        .servico { font-size: 12pt; font-weight: bold; text-transform: uppercase; }

        .footer { border-top: 1pt dashed #000; padding-top: 5mm; margin-top: 5mm; font-size: 9pt; }
        .data { margin-bottom: 2mm; }

        @media print {
            @page { margin: 0; }
            body { padding: 5mm; }
        }
    </style>
</head>
<body onload="window.print(); setTimeout(() => window.close(), 500);">

    <div class="header">
        <div class="empresa"><?= htmlspecialchars($empresa) ?></div>
        <div class="label">SISTEMA DE SENHAS</div>
    </div>

    <div class="ticket-box">
        <div class="label">SUA SENHA É:</div>
        <div class="senha"><?= htmlspecialchars($senha) ?></div>
        <div class="servico"><?= htmlspecialchars($servico) ?></div>
    </div>

    <div class="footer">
        <div class="data"><?= $data ?></div>
        <div>Aguarde ser chamado no painel.</div>
    </div>

</body>
</html>
