<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
$platos = $db->query("SELECT id_plato, nombre, imagen FROM plato")->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Platos en BD:</h3>";
foreach ($platos as $p) {
    $url = UPLOADS_URL . $p['imagen'];
    $existe = file_exists(UPLOADS_PATH . $p['imagen']) ? '✅ existe' : '❌ NO existe';
    echo "<p><b>{$p['nombre']}</b> → imagen: <code>{$p['imagen']}</code><br>";
    echo "URL: <a href='$url' target='_blank'>$url</a><br>";
    echo "Archivo físico: $existe</p><hr>";
}
echo "<h3>Archivos en uploads/:</h3><pre>";
$files = scandir(UPLOADS_PATH);
foreach ($files as $f) {
    if ($f !== '.' && $f !== '..') echo $f . "\n";
}
echo "</pre>";
