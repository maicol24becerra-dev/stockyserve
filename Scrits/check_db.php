<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3320;dbname=bdstockyserve', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Simular exactamente lo que hace el dashboard de Maicol
// Maicol: id_usuario=1
$id_usuario = 1;

// Paso 1: buscar id_cliente
$stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE id_usuario=? LIMIT 1");
$stmt->execute([$id_usuario]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$id_cliente = $row ? (int)$row['id_cliente'] : 0;
echo "id_usuario=$id_usuario  =>  id_cliente=$id_cliente" . PHP_EOL;

// Paso 2: buscar pedidos
$stmt2 = $pdo->prepare("
    SELECT p.*
    FROM pedido p
    WHERE p.id_cliente = ?
       OR p.id_usuario = ?
    ORDER BY p.fecha DESC
");
$stmt2->execute([$id_cliente, $id_usuario]);
$pedidos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "Pedidos encontrados: " . count($pedidos) . PHP_EOL;
foreach ($pedidos as $p)
    echo "  id_pedido={$p['id_pedido']}  estado={$p['estado']}  id_cliente={$p['id_cliente']}  id_usuario={$p['id_usuario']}" . PHP_EOL;
