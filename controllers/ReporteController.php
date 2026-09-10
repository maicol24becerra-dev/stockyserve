<?php
session_start();

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'administrador') {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../models/Pedido.php';

$pedidoModel = new PedidoModel();
$action      = $_GET['action'] ?? $_POST['action'] ?? '';

// Leer filtros — soporta periodo O rango de fechas (HU-04)
$periodo      = $_GET['periodo']      ?? 'mes';
$fechaInicio  = trim($_GET['fecha_inicio'] ?? '');
$fechaFin     = trim($_GET['fecha_fin']    ?? '');

if (!in_array($periodo, ['semana', 'mes', 'anio'])) $periodo = 'mes';

$filtros = [
    'periodo'      => $periodo,
    'fecha_inicio' => $fechaInicio,
    'fecha_fin'    => $fechaFin,
    'metodo_pago'  => trim($_GET['metodo_pago'] ?? ''),
    'categoria'    => trim($_GET['categoria']   ?? ''),
    'id_plato'     => (int)($_GET['id_plato']   ?? 0) ?: '',
];

// ── Función de etiqueta de período ──────────────────────────────────────────
function periodoLabel(string $periodo, string $fi, string $ff): string {
    if ($fi !== '' && $ff !== '') {
        return date('d/m/Y', strtotime($fi)) . ' — ' . date('d/m/Y', strtotime($ff));
    }
    return ['semana' => 'Última Semana', 'mes' => 'Mes Actual', 'anio' => 'Año Actual'][$periodo] ?? 'Mes Actual';
}

// ── Datos comunes ────────────────────────────────────────────────────────────
function cargarDatos(PedidoModel $m, array $f): array {
    $resumen        = $m->getResumenVentas($f['periodo'], $f);
    $platos         = $m->getPlatosPreferidos($f['periodo'], 10, $f);
    $ventas         = $m->getReporteVentas($f['periodo'], $f);
    $ventasMetodo   = $m->getVentasPorMetodoPago($f['periodo'], $f);
    $ticketPromedio = $resumen['total_pedidos'] > 0
        ? $resumen['total_ventas'] / $resumen['total_pedidos'] : 0;
    return compact('resumen', 'platos', 'ventas', 'ventasMetodo', 'ticketPromedio');
}

// ── Exportar PDF (imprimir) ──────────────────────────────────────────────────
if ($action === 'exportar_pdf') {
    $d = cargarDatos($pedidoModel, $filtros);
    extract($d); // $resumen, $platos, $ventas, $ventasMetodo, $ticketPromedio

    $label    = periodoLabel($periodo, $fechaInicio, $fechaFin);
    $fechaGen = date('d/m/Y H:i');

    $filtrosLabel = [];
    if (!empty($filtros['metodo_pago'])) $filtrosLabel[] = 'Método: '.$filtros['metodo_pago'];
    if (!empty($filtros['categoria']))   $filtrosLabel[] = 'Categoría: '.$filtros['categoria'];
    if (!empty($filtros['id_plato'])) {
        foreach ($pedidoModel->getPlatosConVentas() as $pd) {
            if ($pd['id_plato'] == $filtros['id_plato']) { $filtrosLabel[] = 'Platillo: '.$pd['nombre']; break; }
        }
    }

    header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Ventas — <?= htmlspecialchars($label) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Arial,sans-serif;font-size:13px;color:#1a1a1a;background:#fff;padding:30px;}
.header{text-align:center;border-bottom:3px solid #007800;padding-bottom:18px;margin-bottom:24px;}
.header h1{font-size:22px;color:#005500;margin-bottom:4px;}
.header p{font-size:12px;color:#555;}
.badge{display:inline-block;background:#e8f5e9;color:#2e7d32;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:bold;margin:4px 3px 0;}
.section-title{font-size:15px;font-weight:bold;color:#005500;border-left:4px solid #00a020;padding-left:10px;margin:22px 0 12px;}
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
.kpi-card{background:#f0f7f2;border:1px solid #c8e8d0;border-radius:10px;padding:14px;text-align:center;}
.kpi-card .label{font-size:11px;color:#4a7a50;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;}
.kpi-card .value{font-size:20px;font-weight:bold;color:#005500;}
.empty-msg{background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:14px 18px;color:#856404;font-weight:600;margin-bottom:20px;}
table{width:100%;border-collapse:collapse;margin-bottom:24px;}
thead tr{background:#005500;color:#fff;}
th{padding:10px 12px;text-align:left;font-size:12px;}
td{padding:9px 12px;border-bottom:1px solid #e0ede4;font-size:12px;}
tbody tr:nth-child(even){background:#f5fbf6;}
.rank{font-weight:bold;color:#007800;}
.money{font-weight:bold;color:#005500;}
.bar-wrap{display:flex;align-items:center;gap:8px;}
.bar-bg{flex:1;background:#e8f5e9;border-radius:20px;height:8px;overflow:hidden;}
.bar-fill{height:100%;background:linear-gradient(90deg,#00C8F0,#00a020);border-radius:20px;}
.footer{margin-top:30px;border-top:1px solid #c8e8d0;padding-top:12px;text-align:center;font-size:11px;color:#888;}
@media print{.no-print{display:none;}body{padding:15px;}}
</style>
</head>
<body>
<div class="header">
    <h1>El Cielo — Reporte de Ventas</h1>
    <p>Generado el <?= $fechaGen ?> por <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador') ?></p>
    <span class="badge"><?= htmlspecialchars($label) ?></span>
    <?php foreach ($filtrosLabel as $fl): ?>
    <span class="badge" style="background:#e3f2fd;color:#1565c0;"><?= htmlspecialchars($fl) ?></span>
    <?php endforeach; ?>
</div>

<div class="section-title">Resumen General</div>

<?php if ((float)$resumen['total_ventas'] == 0 && (int)$resumen['total_pedidos'] == 0): ?>
<!-- HU-04 Esc.2: sin ventas -->
<div class="empty-msg"><i>⚠️</i> No hay ventas registradas en el período seleccionado.</div>
<?php else: ?>
<div class="kpi-grid">
    <div class="kpi-card"><div class="label">Total Ventas</div><div class="value">$<?= number_format($resumen['total_ventas'],2) ?></div></div>
    <div class="kpi-card"><div class="label">Pedidos Pagados</div><div class="value"><?= number_format($resumen['total_pedidos']) ?></div></div>
    <div class="kpi-card"><div class="label">Clientes Atendidos</div><div class="value"><?= number_format($resumen['clientes_atendidos']) ?></div></div>
    <div class="kpi-card"><div class="label">Ticket Promedio</div><div class="value">$<?= number_format($ticketPromedio,2) ?></div></div>
</div>
<?php endif; ?>

<?php if (!empty($ventasMetodo)): ?>
<div class="section-title">Ventas por Método de Pago</div>
<table>
    <thead><tr><th>Método</th><th>Pedidos</th><th>Total Recaudado</th><th>Participación</th></tr></thead>
    <tbody>
        <?php
        $totalG = array_sum(array_column($ventasMetodo,'total_ventas'));
        foreach ($ventasMetodo as $vm):
            $pct = $totalG > 0 ? ($vm['total_ventas']/$totalG*100) : 0;
        ?>
        <tr>
            <td style="font-weight:bold;"><?= htmlspecialchars($vm['metodo']) ?></td>
            <td><?= number_format($vm['total_pedidos']) ?></td>
            <td class="money">$<?= number_format($vm['total_ventas'],2) ?></td>
            <td><div class="bar-wrap"><div class="bar-bg"><div class="bar-fill" style="width:<?= round($pct) ?>%;"></div></div><span style="font-size:11px;font-weight:bold;min-width:36px;"><?= number_format($pct,1) ?>%</span></div></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<div class="section-title">Platillos Más Vendidos</div>
<?php if (!empty($platos)): ?>
<table>
    <thead><tr><th>#</th><th>Platillo</th><th>Categoría</th><th>Unidades Vendidas</th><th>Ingresos Generados</th></tr></thead>
    <tbody>
        <?php foreach ($platos as $i => $p): ?>
        <tr>
            <td class="rank"><?= $i+1 ?></td>
            <td><?= htmlspecialchars($p['nombre']) ?></td>
            <td><?= htmlspecialchars($p['categoria'] ?? '—') ?></td>
            <td><?= number_format($p['total_vendido']) ?></td>
            <td class="money">$<?= number_format($p['total_ingresos'],2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="empty-msg">No hay datos de platillos para los filtros seleccionados.</div>
<?php endif; ?>

<div class="section-title">Detalle de Ventas por Período</div>
<?php if (!empty($ventas)): ?>
<table>
    <thead><tr><th>Fecha / Período</th><th style="text-align:right;">Pedidos</th><th style="text-align:right;">Total Ventas</th></tr></thead>
    <tbody>
        <?php foreach ($ventas as $v): ?>
        <tr>
            <td><?= htmlspecialchars($v['label']) ?></td>
            <td style="text-align:right;"><?= number_format($v['total_pedidos']) ?></td>
            <td class="money" style="text-align:right;">$<?= number_format($v['total_ventas'],2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="empty-msg">No hay ventas registradas en el período seleccionado.</div>
<?php endif; ?>

<div class="footer">El Cielo — Sistema de Gestión &nbsp;|&nbsp; Reporte generado automáticamente</div>

<div class="no-print" style="text-align:center;margin-top:24px;">
    <button onclick="window.print()" style="background:#005500;color:#fff;border:none;padding:10px 28px;border-radius:8px;font-size:14px;cursor:pointer;font-weight:bold;">
        🖨️ Imprimir / Guardar como PDF
    </button>
    <button onclick="window.close()" style="background:#eee;color:#333;border:none;padding:10px 20px;border-radius:8px;font-size:14px;cursor:pointer;margin-left:10px;">Cerrar</button>
</div>
<script>window.onload=function(){setTimeout(function(){window.print();},600);};</script>
</body>
</html>
<?php
    exit;
}

// ── Exportar Excel (.xlsx usando formato HTML) — HU-04 Esc.5 ────────────────
if ($action === 'exportar_excel') {
    $d = cargarDatos($pedidoModel, $filtros);
    extract($d);

    $label    = periodoLabel($periodo, $fechaInicio, $fechaFin);
    $fechaGen = date('d/m/Y H:i');
    $filename = 'reporte_ventas_' . date('Ymd_His') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF"; // BOM para UTF-8 en Excel
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Reporte</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>
<body>
<table border="1" cellpadding="5" cellspacing="0">
    <tr style="background:#005500;color:#ffffff;font-weight:bold;">
        <td colspan="5">El Cielo — Reporte de Ventas: <?= htmlspecialchars($label) ?> — Generado: <?= $fechaGen ?></td>
    </tr>

    <!-- KPIs -->
    <tr style="background:#e8f5e9;font-weight:bold;">
        <td>Total Ventas</td><td>Pedidos Pagados</td><td>Clientes Atendidos</td><td>Ticket Promedio</td><td></td>
    </tr>
    <tr>
        <td>$<?= number_format($resumen['total_ventas'],2) ?></td>
        <td><?= number_format($resumen['total_pedidos']) ?></td>
        <td><?= number_format($resumen['clientes_atendidos']) ?></td>
        <td>$<?= number_format($ticketPromedio,2) ?></td>
        <td></td>
    </tr>
    <tr><td colspan="5"></td></tr>

    <!-- Método de pago -->
    <?php if (!empty($ventasMetodo)): ?>
    <tr style="background:#005500;color:#ffffff;font-weight:bold;">
        <td>Método de Pago</td><td>Pedidos</td><td>Total Recaudado</td><td>Participación %</td><td></td>
    </tr>
    <?php
    $totalG = array_sum(array_column($ventasMetodo,'total_ventas'));
    foreach ($ventasMetodo as $vm):
        $pct = $totalG > 0 ? number_format($vm['total_ventas']/$totalG*100,1) : '0.0';
    ?>
    <tr>
        <td><?= htmlspecialchars($vm['metodo']) ?></td>
        <td><?= number_format($vm['total_pedidos']) ?></td>
        <td>$<?= number_format($vm['total_ventas'],2) ?></td>
        <td><?= $pct ?>%</td>
        <td></td>
    </tr>
    <?php endforeach; ?>
    <tr><td colspan="5"></td></tr>
    <?php endif; ?>

    <!-- Platillos -->
    <tr style="background:#005500;color:#ffffff;font-weight:bold;">
        <td>#</td><td>Platillo</td><td>Categoría</td><td>Unidades Vendidas</td><td>Ingresos Generados</td>
    </tr>
    <?php if (!empty($platos)): ?>
    <?php foreach ($platos as $i => $p): ?>
    <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($p['nombre']) ?></td>
        <td><?= htmlspecialchars($p['categoria'] ?? '—') ?></td>
        <td><?= number_format($p['total_vendido']) ?></td>
        <td>$<?= number_format($p['total_ingresos'],2) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php else: ?>
    <tr><td colspan="5">No hay datos de platillos para los filtros seleccionados.</td></tr>
    <?php endif; ?>
    <tr><td colspan="5"></td></tr>

    <!-- Serie temporal -->
    <tr style="background:#005500;color:#ffffff;font-weight:bold;">
        <td>Fecha / Período</td><td>Pedidos</td><td>Total Ventas</td><td></td><td></td>
    </tr>
    <?php if (!empty($ventas)): ?>
    <?php foreach ($ventas as $v): ?>
    <tr>
        <td><?= htmlspecialchars($v['label']) ?></td>
        <td><?= number_format($v['total_pedidos']) ?></td>
        <td>$<?= number_format($v['total_ventas'],2) ?></td>
        <td></td><td></td>
    </tr>
    <?php endforeach; ?>
    <?php else: ?>
    <tr><td colspan="5">No hay ventas registradas en el período seleccionado.</td></tr>
    <?php endif; ?>
</table>
</body></html>
<?php
    exit;
}

header('Location: ../views/admin/dashboard.php#reportes');
exit;
