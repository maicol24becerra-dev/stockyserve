<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3320;dbname=bdstockyserve', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$cols = $pdo->query("SHOW COLUMNS FROM pedido")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('es_urgente', $cols)) {
    $pdo->exec("ALTER TABLE pedido ADD COLUMN es_urgente TINYINT(1) DEFAULT 0 AFTER estado");
    echo "Columna es_urgente agregada." . PHP_EOL;
} else { echo "es_urgente ya existe." . PHP_EOL; }

if (!in_array('hora_entrega', $cols)) {
    $pdo->exec("ALTER TABLE pedido ADD COLUMN hora_entrega TIME DEFAULT NULL AFTER es_urgente");
    echo "Columna hora_entrega agregada." . PHP_EOL;
} else { echo "hora_entrega ya existe." . PHP_EOL; }

if (!in_array('notas_pedido', $cols)) {
    $pdo->exec("ALTER TABLE pedido ADD COLUMN notas_pedido VARCHAR(200) DEFAULT NULL AFTER hora_entrega");
    echo "Columna notas_pedido agregada." . PHP_EOL;
} else { echo "notas_pedido ya existe." . PHP_EOL; }

echo "Listo." . PHP_EOL;
