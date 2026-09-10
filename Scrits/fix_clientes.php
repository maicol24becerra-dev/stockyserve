<?php
/**
 * Script de reparación: inserta en tabla `cliente` todos los usuarios
 * con rol=4 (Cliente) que no tengan fila en esa tabla todavía.
 */
$pdo = new PDO('mysql:host=127.0.0.1;port=3320;dbname=bdstockyserve', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("
    SELECT u.id_usuario, u.nombre
    FROM usuario u
    LEFT JOIN cliente c ON c.id_usuario = u.id_usuario
    WHERE u.id_rol = 4
      AND c.id_cliente IS NULL
");
$faltantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($faltantes)) {
    echo "Todos los clientes ya tienen fila en la tabla cliente. Nada que hacer." . PHP_EOL;
    exit;
}

$insert = $pdo->prepare("INSERT INTO cliente (id_usuario) VALUES (?)");
foreach ($faltantes as $u) {
    $insert->execute([$u['id_usuario']]);
    echo "Insertado: id_usuario={$u['id_usuario']} nombre={$u['nombre']}" . PHP_EOL;
}
echo PHP_EOL . "Listo. " . count($faltantes) . " cliente(s) reparado(s)." . PHP_EOL;
