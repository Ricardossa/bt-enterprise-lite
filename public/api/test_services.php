<?php
header('Content-Type: text/plain');
$targets = [
    'Master (HTTPS)' => 'api.brandaotech.com.br',
    'Full (8090)' => '192.168.100.245:8090',
    'Lite (8120)' => '192.168.100.245:8120',
    'Print (8001)' => '192.168.100.245:8001'
];

foreach ($targets as $name => $url) {
    echo "Testando $name ($url)... ";
    $fp = @fsockopen('192.168.100.245', (int)explode(':', $url)[1], $errno, $errstr, 1);
    if ($fp) {
        echo "✅ ONLINE\n";
        fclose($fp);
    } else {
        echo "❌ OFFLINE ($errstr)\n";
    }
}
