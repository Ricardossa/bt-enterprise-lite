<?php
$db = new PDO('sqlite:Y:/bt-enterprise/database/banco.db');
$res = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
foreach($res as $r) {
    echo $r['name'] . "\n";
}
