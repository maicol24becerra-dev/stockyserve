<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3320;dbname=bdstockyserve', 'root', '');
foreach ($pdo->query('SHOW COLUMNS FROM plato') as $r)
    echo $r['Field'] . ' — ' . $r['Type'] . PHP_EOL;
