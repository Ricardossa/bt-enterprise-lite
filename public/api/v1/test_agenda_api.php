<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'servicos_por_barbeiro';
$_GET['operador_id'] = 0;
// Simulate request for Tenant 30
$_SERVER['HTTP_HOST'] = 'paradaobrigatoriavilas.brandaotech.com.br';
require 'agenda.php';
