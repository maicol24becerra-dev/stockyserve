<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3320;dbname=bdstockyserve', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$cols = $pdo->query("SHOW COLUMNS FROM plato")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('inicio_oferta', $cols)) {
    $pdo->exec("ALTER TABLE plato ADD COLUMN inicio_oferta DATETIME DEFAULT NULL AFTER en_oferta");
    echo "Columna inicio_oferta agregada." . PHP_EOL;
} else {
    echo "Ya existe." . PHP_EOL;
}
if (!in_array('es_platillo_dia', $cols)) {
    $pdo->exec("ALTER TABLE plato ADD COLUMN es_platillo_dia TINYINT(1) DEFAULT 0 AFTER etiqueta_oferta");
    echo "Columna es_platillo_dia agregada." . PHP_EOL;
} else {
    echo "Ya existe." . PHP_EOL;
}
echo "Listo." . PHP_EOL;
