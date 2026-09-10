<?php
/**
 * Asocia todos los pedidos sin id_cliente al cliente Maicol (id_cliente=1)
 * Solo ejecutar una vez para reparar datos existentes.
 */
$pdo = new PDO('mysql:host=127.0.0.1;port=3320;dbname=bdstockyserve', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Verificar cuántos pedidos sin cliente hay
$count = $pdo->query("SELECT COUNT(*) FROM pedido WHERE id_cliente IS NULL")->fetchColumn();
echo "Pedidos sin cliente: $count" . PHP_EOL;

if ($count == 0) {
    echo "Nada que reparar." . PHP_EOL;
    exit;
}

// Obtener id_cliente de Maicol
$row = $pdo->query("SELECT c.id_cliente FROM cliente c JOIN usuario u ON c.id_usuario=u.id_usuario WHERE u.id_rol=4 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo "No hay clientes en la tabla cliente." . PHP_EOL;
    exit;
}
$id_cliente = $row['id_cliente'];
echo "Asociando pedidos al id_cliente=$id_cliente" . PHP_EOL;

$stmt = $pdo->prepare("UPDATE pedido SET id_cliente=? WHERE id_cliente IS NULL");
$stmt->execute([$id_cliente]);
echo "Pedidos actualizados: " . $stmt->rowCount() . PHP_EOL;

// Verificar resultado
echo PHP_EOL . "=== Estado final ===" . PHP_EOL;
foreach ($pdo->query('SELECT id_pedido, estado, id_usuario, id_cliente FROM pedido ORDER BY id_pedido DESC') as $r)
    echo "  id_pedido={$r['id_pedido']}  estado={$r['estado']}  id_cliente=" . ($r['id_cliente'] ?? 'NULL') . PHP_EOL;
