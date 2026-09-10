<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3320;dbname=bdstockyserve', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Agregar columnas solo si no existen
$cols = $pdo->query("SHOW COLUMNS FROM plato")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('en_oferta', $cols)) {
    $pdo->exec("ALTER TABLE plato ADD COLUMN en_oferta TINYINT(1) DEFAULT 0 AFTER disponibilidad");
    echo "Columna en_oferta agregada." . PHP_EOL;
}
if (!in_array('precio_oferta', $cols)) {
    $pdo->exec("ALTER TABLE plato ADD COLUMN precio_oferta DECIMAL(10,2) DEFAULT NULL AFTER en_oferta");
    echo "Columna precio_oferta agregada." . PHP_EOL;
}
if (!in_array('fin_oferta', $cols)) {
    $pdo->exec("ALTER TABLE plato ADD COLUMN fin_oferta DATETIME DEFAULT NULL AFTER precio_oferta");
    echo "Columna fin_oferta agregada." . PHP_EOL;
}
if (!in_array('etiqueta_oferta', $cols)) {
    $pdo->exec("ALTER TABLE plato ADD COLUMN etiqueta_oferta VARCHAR(60) DEFAULT NULL AFTER fin_oferta");
    echo "Columna etiqueta_oferta agregada." . PHP_EOL;
}
echo "Listo." . PHP_EOL;
