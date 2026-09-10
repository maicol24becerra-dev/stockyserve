<?php
session_start();
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'mesero') {
    header('Location: ../../public/login.php'); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Pedido.php';

$id_pedido = (int)($_GET['id'] ?? 0);
if ($id_pedido <= 0) { header('Location: dashboard.php'); exit; }

$pedidoModel = new PedidoModel();
$pedido      = $pedidoModel->getById($id_pedido);
if (!$pedido) { header('Location: dashboard.php'); exit; }

$items  = $pedidoModel->getItemsByPedido($id_pedido);
$total  = array_sum(array_map(fn($i) => $i['precio_unitario'] * $i['cantidad'], $items));

// Obtener pago
$db   = (new Database())->getConnection();
$stmt = $db->prepare("SELECT metodo_pago, monto_pagado, fecha_pago FROM pago WHERE id_pedido=? ORDER BY id_pago DESC LIMIT 1");
$stmt->execute([$id_pedido]);
$pago = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener nombre del cliente si existe
$nombreCliente = '—';
if (!empty($pedido['id_cliente'])) {
    $stmtCli = $db->prepare("SELECT u.nombre FROM cliente c JOIN usuario u ON u.id_usuario=c.id_usuario WHERE c.id_cliente=?");
    $stmtCli->execute([$pedido['id_cliente']]);
    $cli = $stmtCli->fetch(PDO::FETCH_ASSOC);
    if ($cli) $nombreCliente = $cli['nombre'];
}

$metodo    = $pago['metodo_pago'] ?? 'Efectivo';
$fechaPago = $pago['fecha_pago']  ?? $pedido['fecha'];
$mesero    = htmlspecialchars($_SESSION['user_name'] ?? 'Mesero');
$numFac    = str_pad($id_pedido, 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Factura #<?= $numFac ?></title>
<link rel="icon" type="image/png" href="../../img/ico.png">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Courier New', Courier, monospace;
    background: #e0e0e0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    min-height: 100vh;
    padding: 20px 10px 40px;
}

/* Tiquete */
.ticket {
    background: #fff;
    width: 100%;
    max-width: 320px;
    padding: 18px 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
}

/* Tipografía base */
.ticket p, .ticket td, .ticket th, .ticket div {
    font-size: 12px;
    line-height: 1.5;
    color: #000;
}

/* Centro */
.center { text-align: center; }
.bold   { font-weight: bold; }
.upper  { text-transform: uppercase; }

/* Separadores */
.sep-solid { border-top: 1px solid #000; margin: 8px 0; }
.sep-dash  { border-top: 1px dashed #000; margin: 8px 0; }

/* Encabezado */
.header { text-align: center; margin-bottom: 8px; }
.header .nombre { font-size: 15px; font-weight: bold; text-transform: uppercase; }
.header .sub    { font-size: 11px; }

/* Título factura */
.titulo-factura {
    text-align: center;
    font-weight: bold;
    font-size: 13px;
    text-transform: uppercase;
    margin: 6px 0;
}

/* Info cliente/vendedor */
.info-bloque { margin: 6px 0; }
.info-bloque p { font-size: 12px; }

/* Tabla de ítems */
.tabla-items {
    width: 100%;
    border-collapse: collapse;
    margin: 6px 0;
}
.tabla-items th {
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    border-bottom: 1px solid #000;
    padding: 3px 2px;
    text-align: left;
}
.tabla-items th.r,
.tabla-items td.r { text-align: right; }
.tabla-items th.c,
.tabla-items td.c { text-align: center; }
.tabla-items td {
    font-size: 11px;
    padding: 3px 2px;
    vertical-align: top;
}
.tabla-items tbody tr:last-child td { border-bottom: 1px solid #000; }

/* Totales */
.totales { width: 100%; margin: 4px 0; }
.totales tr td { font-size: 12px; padding: 2px 0; }
.totales tr td:last-child { text-align: right; }
.totales .total-final td { font-weight: bold; font-size: 13px; border-top: 1px solid #000; padding-top: 4px; }

/* Pago */
.pago-bloque { margin: 6px 0; }
.pago-bloque table { width: 100%; }
.pago-bloque td { font-size: 12px; padding: 2px 0; }
.pago-bloque td:last-child { text-align: right; }

/* Footer */
.footer { text-align: center; margin-top: 8px; }
.footer p { font-size: 11px; }

/* Botones — no se imprimen */
.acciones {
    display: flex;
    gap: 10px;
    margin-top: 16px;
    width: 100%;
    max-width: 320px;
}
.btn-print {
    flex: 1;
    padding: 11px;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.btn-back {
    flex: 1;
    padding: 11px;
    background: transparent;
    color: #000;
    border: 1.5px solid #000;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 1px;
}

@media print {
    body { background: #fff; padding: 0; }
    .ticket { box-shadow: none; max-width: 100%; }
    .acciones { display: none; }
}
</style>
</head>
<body>

<div class="ticket">

    <!-- Encabezado empresa -->
    <div class="header">
        <div class="nombre">El Cielo</div>
        <div class="sub">Centro Vacacional y Recreacional</div>
        <div class="sub">Cauca, Colombia</div>
        <div class="sub">Tel: 318 2852854</div>
    </div>

    <div class="sep-solid"></div>

    <!-- Título -->
    <div class="titulo-factura">FACTURA DE VENTA</div>
    <div class="center" style="font-size:11px;">
        <?= date('d/m/Y h:i a', strtotime($fechaPago)) ?>
    </div>

    <div class="sep-dash"></div>

    <!-- Info del pedido -->
    <div class="info-bloque">
        <p>Cliente: <?= htmlspecialchars($nombreCliente) ?></p>
        <p>Factura Nro.: <?= $numFac ?></p>
        <p>Mesero: <?= $mesero ?></p>
    </div>

    <div class="sep-dash"></div>

    <!-- Tabla de ítems -->
    <table class="tabla-items">
        <thead>
            <tr>
                <th style="width:50%;">Artículo</th>
                <th class="r" style="width:25%;">Precio</th>
                <th class="c" style="width:10%;">Cant.</th>
                <th class="r" style="width:15%;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars(strtoupper($item['nombre_plato'])) ?></td>
                <td class="r">$<?= number_format($item['precio_unitario'], 0, ',', '.') ?></td>
                <td class="c"><?= $item['cantidad'] ?></td>
                <td class="r">$<?= number_format($item['precio_unitario'] * $item['cantidad'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totales -->
    <table class="totales">
        <tr>
            <td>Subtotal</td>
            <td>$<?= number_format($total, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td>Descuento</td>
            <td>$0</td>
        </tr>
        <tr class="total-final">
            <td>TOTAL</td>
            <td>$<?= number_format($total, 0, ',', '.') ?></td>
        </tr>
    </table>

    <div class="sep-dash"></div>

    <!-- Pago -->
    <div class="pago-bloque">
        <table>
            <tr>
                <td>Tipo de Pago</td>
                <td><?= htmlspecialchars($metodo) ?></td>
                <td>$<?= number_format($total, 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Cambio</td>
                <td></td>
                <td>$0</td>
            </tr>
        </table>
    </div>

    <div class="sep-dash"></div>

    <!-- Footer -->
    <div class="footer">
        <p>¡Gracias por su visita!</p>
        <p>Vuelva pronto</p>
        <br>
        <p>----------------------------------------</p>
        <p>stockyserve.byethost14.com</p>
        <p>Sistema StockYServe</p>
        <p>----------------------------------------</p>
    </div>

</div>

<!-- Botones fuera del tiquete -->
<div class="acciones">
    <button class="btn-print" onclick="window.print()">&#128438; Imprimir</button>
    <a href="dashboard.php#pedidos" class="btn-back">&#8592; Volver</a>
</div>

</body>
</html>
