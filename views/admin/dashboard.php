<?php
session_start();
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'administrador') {
    header('Location: ../../public/login.php');
    exit;
}

require_once __DIR__ . '/../../models/Usuario.php';
$usuarioModel = new UsuarioModel();
$usuarios = $usuarioModel->getAllUsers();

require_once __DIR__ . '/../../models/Plato.php';
$platoModel = new PlatoModel();
$platos = $platoModel->getAll();

require_once __DIR__ . '/../../models/MateriaPrima.php';
$materiaModel = new MateriaPrimaModel();
$materias = $materiaModel->getAll();

require_once __DIR__ . '/../../models/Receta.php';
$recetaModel = new RecetaModel();

require_once __DIR__ . '/../../models/Pedido.php';
$pedidoModel = new PedidoModel();
$todosPedidos = $pedidoModel->getTodos();
foreach ($todosPedidos as &$tp) {
    $tp['items'] = $pedidoModel->getItemsByPedido($tp['id_pedido']);
    $tp['total'] = array_sum(array_map(fn($i) => $i['precio_unitario'] * $i['cantidad'], $tp['items']));
}
unset($tp);

// ── Datos para la sección de Reportes ──
$reportePeriodo  = $_GET['periodo']      ?? 'mes';
$filtroMetodo    = $_GET['metodo_pago']  ?? '';
$filtroCategoria = $_GET['categoria']    ?? '';
$filtroPlato     = (int)($_GET['id_plato'] ?? 0);
$filtroFechaIni  = trim($_GET['fecha_inicio'] ?? '');
$filtroFechaFin  = trim($_GET['fecha_fin']    ?? '');

if (!in_array($reportePeriodo, ['semana', 'mes', 'anio'])) $reportePeriodo = 'mes';

$filtros = [
    'periodo'      => $reportePeriodo,
    'fecha_inicio' => $filtroFechaIni,
    'fecha_fin'    => $filtroFechaFin,
    'metodo_pago'  => $filtroMetodo,
    'categoria'    => $filtroCategoria,
    'id_plato'     => $filtroPlato > 0 ? $filtroPlato : '',
];

$resumenVentas    = $pedidoModel->getResumenVentas($reportePeriodo, $filtros);
$platosTop        = $pedidoModel->getPlatosPreferidos($reportePeriodo, 8, $filtros);
$ventasSerie      = $pedidoModel->getReporteVentas($reportePeriodo, $filtros);
$ventasMetodo     = $pedidoModel->getVentasPorMetodoPago($reportePeriodo, $filtros);
$categoriasDisp   = $pedidoModel->getCategoriasConVentas();
$platosDisp       = $pedidoModel->getPlatosConVentas();
$ticketPromedio   = ($resumenVentas['total_pedidos'] > 0)
    ? $resumenVentas['total_ventas'] / $resumenVentas['total_pedidos']
    : 0;

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador - El Cielo</title>
    <link rel="icon" type="image/png" href="../../img/ico.png">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="../../public/responsive.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --verde:       #007800;
            --verde-claro: #00a020;
            --verde-dark:  #005500;
            --agua:        #00C8F0;
            --agua-claro:  #78DCF0;
            --agua-dark:   #0099bb;
            --azul:        #0014A0;
            --azul-dark:   #000e70;
            --sidebar-bg1: #001a10;
            --sidebar-bg2: #003820;
            --text-dark:   #0d2b1a;
            --text-mid:    #3a6b50;
            --text-light:  #7aaa8a;
            --bg-page:     #f0f7f2;
            --white:       #ffffff;
            --border:      #c8e8d0;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--bg-page);
            display: flex;
            height: 100vh;
            overflow: hidden;
            color: var(--text-dark);
        }

        /* En móvil se sobreescribe con responsive.css */

        /* ═══════════════════════════════
           SIDEBAR
        ═══════════════════════════════ */
        .sidebar {
            width: 265px;
            background: linear-gradient(170deg, var(--sidebar-bg1) 0%, var(--sidebar-bg2) 60%, #005530 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 24px rgba(0,0,0,0.25);
            z-index: 10;
            flex-shrink: 0;
        }

        .sidebar-header {
            padding: 30px 20px 22px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            background: rgba(0,0,0,0.2);
        }

        .sidebar-logo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
        }

        .sidebar-logo img { width: 90px; height: 90px; object-fit: contain; border-radius: 50%; }

        .sidebar-header h2 {
            font-size: 1.25rem;
            font-weight: 900;
            letter-spacing: 0.5px;
            color: #fff;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 0.68rem;
            color: var(--agua-claro);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            line-height: 1.4;
        }

        .nav-links {
            flex: 1;
            padding: 16px 12px;
            list-style: none;
        }

        .nav-label {
            padding: 14px 12px 5px;
            font-size: 0.65rem;
            font-weight: 800;
            color: rgba(120,220,240,0.45);
            text-transform: uppercase;
            letter-spacing: 1.8px;
        }

        .nav-item {
            padding: 11px 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.93rem;
            font-weight: 700;
            transition: all 0.22s;
            color: rgba(200,240,210,0.8);
            border-radius: 10px;
            margin-bottom: 2px;
        }

        .nav-item i { font-size: 1rem; width: 20px; text-align: center; flex-shrink: 0; }

        .nav-item:hover {
            background: rgba(255,255,255,0.07);
            color: #fff;
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(0,200,240,0.18), rgba(0,200,240,0.05));
            color: #fff;
            border-left: 3px solid var(--agua);
            padding-left: 11px;
        }

        .nav-item.active i { color: var(--agua); }

        /* ═══════════════════════════════
           TOP BAR
        ═══════════════════════════════ */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            background-color: var(--bg-page);
        }

        .top-bar {
            background: var(--white);
            padding: 16px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 12px rgba(0,80,30,0.07);
            z-index: 5;
            border-bottom: 2px solid var(--border);
        }

        .top-bar h1 {
            font-size: 1.45rem;
            font-weight: 900;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-bar h1::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 22px;
            background: linear-gradient(180deg, var(--agua), var(--verde));
            border-radius: 4px;
        }

        .user-profile {
            font-weight: 800;
            color: var(--text-mid);
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            cursor: pointer;
            background: var(--bg-page);
            padding: 8px 16px;
            border-radius: 30px;
            border: 1px solid var(--border);
            transition: box-shadow 0.2s;
        }

        .user-profile:hover { box-shadow: 0 4px 12px rgba(0,120,0,0.1); }
        .user-profile i.fa-user-circle { font-size: 1.4rem; color: var(--verde); }
        .user-profile i.fa-chevron-down { font-size: 0.75rem; color: var(--text-light); transition: transform 0.3s; }
        .user-profile.open i.fa-chevron-down { transform: rotate(180deg); }

        .user-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.12);
            border: 1px solid var(--border);
            min-width: 185px;
            z-index: 200;
            overflow: hidden;
        }

        .user-profile.open .user-dropdown { display: block; animation: fadeIn 0.2s; }

        .user-dropdown a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 18px;
            font-size: 0.92rem;
            font-weight: 700;
            color: #e74c3c;
            text-decoration: none;
            transition: background 0.2s;
        }

        .user-dropdown a:hover { background: #fde8e8; }

        /* ═══════════════════════════════
           CONTENT
        ═══════════════════════════════ */
        .content-area { padding: 32px 36px; flex: 1; min-width: 0; }

        .section { display: none; animation: fadeIn 0.35s ease-in-out; width: 100%; }
        .section.active { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ═══════════════════════════════
           STAT CARDS
        ═══════════════════════════════ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 22px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--white);
            padding: 22px;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 16px rgba(0,80,30,0.06);
            display: flex;
            align-items: center;
            gap: 18px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,80,30,0.1); }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--agua), var(--agua-dark));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(0,200,240,0.3);
        }

        .stat-info h3 { font-size: 0.78rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px; }
        .stat-info p  { font-size: 1.7rem; font-weight: 900; color: var(--text-dark); }

        /* ═══════════════════════════════
           CARD PANELS & TABLES
        ═══════════════════════════════ */
        .card-panel {
            background: var(--white);
            border-radius: 16px;
            padding: 26px;
            box-shadow: 0 4px 18px rgba(0,80,30,0.06);
            border: 1px solid var(--border);
            overflow-x: auto;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .card-header h3 {
            font-size: 1.15rem;
            font-weight: 900;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header h3::before {
            content: '';
            display: inline-block;
            width: 3px;
            height: 18px;
            background: linear-gradient(180deg, var(--agua), var(--verde));
            border-radius: 3px;
        }

        .btn-add {
            background: linear-gradient(135deg, var(--verde-claro), var(--verde));
            color: white;
            border: none;
            padding: 9px 20px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.88rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 12px rgba(0,120,0,0.25);
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,120,0,0.35); }

        table { width: 100%; border-collapse: collapse; }

        thead tr { background: var(--bg-page); }

        th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 800;
            color: var(--text-light);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.8px;
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 13px 15px;
            font-weight: 600;
            border-bottom: 1px solid #eef5f0;
            color: var(--text-dark);
        }

        tbody tr:hover { background: #f5fbf6; }
        tbody tr:last-child td { border-bottom: none; }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .badge.admin    { background: #fff3cd; color: #856404; }
        .badge.mesero   { background: #d1f5f5; color: #007a7a; }
        .badge.cocinero { background: #ffe0d6; color: #c0392b; }
        .badge.cliente  { background: #e8f0ee; color: #4a6a50; }

        /* ═══════════════════════════════
           MODALS
        ═══════════════════════════════ */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,20,10,0.55);
            align-items: center;
            justify-content: center;
            z-index: 100;
            backdrop-filter: blur(2px);
        }

        .modal.active { display: flex; animation: fadeIn 0.25s; }

        .modal-content {
            background: var(--white);
            padding: 26px 28px;
            border-radius: 18px;
            width: 100%;
            max-width: 420px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            border-top: 4px solid var(--verde-claro);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        .modal-header h3 { font-size: 1.25rem; font-weight: 900; color: var(--text-dark); }
        .close-btn { background: none; border: none; font-size: 1.1rem; cursor: pointer; color: var(--text-light); transition: color 0.2s; }
        .close-btn:hover { color: #e74c3c; }

        .form-group { margin-bottom: 10px; }

        .form-group label {
            display: block;
            font-weight: 800;
            font-size: 0.82rem;
            margin-bottom: 4px;
            color: var(--text-mid);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 9px 12px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            font-family: 'Nunito', sans-serif;
            font-size: 0.9rem;
            outline: none;
            color: var(--text-dark);
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #fafffe;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--verde-claro);
            box-shadow: 0 0 0 3px rgba(0,160,32,0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--verde-claro), var(--verde));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 10px;
            transition: box-shadow 0.2s, transform 0.2s;
            box-shadow: 0 4px 14px rgba(0,120,0,0.25);
        }

        .btn-submit:hover { box-shadow: 0 6px 18px rgba(0,120,0,0.35); transform: translateY(-1px); }

        /* ═══════════════════════════════
           REPORTES
        ═══════════════════════════════ */
        .periodo-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .periodo-btn {
            padding: 9px 22px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: var(--white);
            color: var(--text-mid);
            font-family: 'Nunito', sans-serif;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .periodo-btn:hover {
            border-color: var(--verde-claro);
            color: var(--verde-claro);
        }

        .periodo-btn.active {
            background: linear-gradient(135deg, var(--verde-claro), var(--verde));
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(0,120,0,0.25);
        }

        .btn-export {
            margin-left: auto;
            padding: 9px 22px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #e17055, #c0392b);
            color: #fff;
            font-family: 'Nunito', sans-serif;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 7px;
            box-shadow: 0 4px 12px rgba(192,57,43,0.25);
            text-decoration: none;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(192,57,43,0.35);
        }

        .kpi-grid-reportes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .kpi-card-r {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 14px rgba(0,80,30,0.06);
            transition: transform 0.2s;
        }

        .kpi-card-r:hover { transform: translateY(-2px); }

        .kpi-icon-r {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .kpi-info-r .label { font-size: 0.72rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px; }
        .kpi-info-r .value { font-size: 1.55rem; font-weight: 900; color: var(--text-dark); }

        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            margin-bottom: 24px;
        }

        @media (max-width: 900px) {
            .charts-grid { grid-template-columns: 1fr; }
        }

        .chart-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 4px 14px rgba(0,80,30,0.06);
        }

        .chart-card h4 {
            font-size: 1rem;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-card h4::before {
            content: '';
            display: inline-block;
            width: 3px;
            height: 16px;
            background: linear-gradient(180deg, var(--agua), var(--verde));
            border-radius: 3px;
        }

        .chart-card canvas { max-height: 260px; }

        .top-platos-table td img {
            width: 38px;
            height: 38px;
            object-fit: cover;
            border-radius: 8px;
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            font-size: 0.78rem;
            font-weight: 900;
        }

        .rank-1 { background: #ffd700; color: #7a5800; }
        .rank-2 { background: #c0c0c0; color: #444; }
        .rank-3 { background: #cd7f32; color: #fff; }
        .rank-other { background: var(--bg-page); color: var(--text-mid); }

    </style>
</head>
<body>

    <!-- Overlay sidebar móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="<?= IMG_URL ?>ico.png" alt="Logo El Cielo">
            </div>
            <h2>El Cielo</h2>
            <p>Centro Vacacional y Recreacional</p>
        </div>
        <ul class="nav-links">
            <li class="nav-item active" onclick="showSection('dashboard', this)"><i class="fas fa-chart-pie"></i> Estadísticas</li>
            <li class="nav-item" onclick="showSection('reportes', this)"><i class="fas fa-file-chart-line"></i> Reportes</li>
            <li class="nav-item" onclick="showSection('pedidos', this)"><i class="fas fa-clipboard-list"></i> Pedidos</li>
            <li class="nav-item" onclick="showSection('usuarios', this)"><i class="fas fa-users"></i> Usuarios</li>
            <li class="nav-item" onclick="showSection('inventario', this)"><i class="fas fa-box-open"></i> Inventario</li>
            <li class="nav-item" id="nav-quejas" onclick="showSection('quejas', this)" style="position:relative;">
                <i class="fas fa-comment-exclamation"></i> Quejas
                <span id="quejas-badge" style="display:none;position:absolute;top:6px;right:10px;background:#e74c3c;color:#fff;border-radius:50%;width:18px;height:18px;font-size:0.65rem;font-weight:900;align-items:center;justify-content:center;">0</span>
            </li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="top-bar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Menú"><i class="fas fa-bars"></i></button>
                <h1 id="page-title">Estadísticas del Mes</h1>
            </div>
            <div class="user-profile" id="userMenu" onclick="toggleUserMenu()">
                <i class="fas fa-user-circle"></i>
                <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador') ?>
                <i class="fas fa-chevron-down"></i>
                <div class="user-dropdown">
                    <a href="../../controllers/AuthController.php?action=logout">
                        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>

        <div class="content-area">
            
            <!-- SECCIÓN 1: ESTADÍSTICAS -->
            <div id="dashboard" class="section active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                        <div class="stat-info">
                            <h3>Ventas del Mes</h3>
                            <p>$<?= number_format($resumenVentas['total_ventas'], 2) ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%); box-shadow: 0 5px 15px rgba(108,92,231,0.3);"><i class="fas fa-shopping-bag"></i></div>
                        <div class="stat-info">
                            <h3>Pedidos Completados</h3>
                            <p><?= number_format($resumenVentas['total_pedidos']) ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #fab1a0 0%, #e17055 100%); box-shadow: 0 5px 15px rgba(225,112,85,0.3);"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <h3>Clientes Atendidos</h3>
                            <p><?= number_format($resumenVentas['clientes_atendidos']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="card-panel">
                    <div class="card-header">
                        <h3>Platos Más Vendidos</h3>
                        <a href="?periodo=mes#reportes" onclick="event.preventDefault(); showSection('reportes', document.querySelector('.nav-item:nth-child(2)'));" style="font-size:0.85rem;color:var(--agua);font-weight:800;text-decoration:none;">Ver reporte completo →</a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Plato</th>
                                <th>Cantidad Vendida</th>
                                <th>Ingresos Generados</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($platosTop)): ?>
                                <?php foreach (array_slice($platosTop, 0, 5) as $p): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                                    <td><?= number_format($p['total_vendido']) ?> uds.</td>
                                    <td style="font-weight:800;color:var(--verde-claro);">$<?= number_format($p['total_ingresos'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center; color:#7a9aa0;">No hay datos suficientes</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECCIÓN 2: REPORTES -->
            <div id="reportes" class="section">

                <?php
                $chartLabels    = json_encode(array_column($ventasSerie, 'label'));
                $chartVentas    = json_encode(array_map('floatval', array_column($ventasSerie, 'total_ventas')));
                $chartPedidos   = json_encode(array_map('intval',   array_column($ventasSerie, 'total_pedidos')));
                $platosNombres  = json_encode(array_column($platosTop, 'nombre'));
                $platosVendido  = json_encode(array_map('intval',   array_column($platosTop, 'total_vendido')));
                $platosIngresos = json_encode(array_map('floatval', array_column($platosTop, 'total_ingresos')));
                $metodoNombres  = json_encode(array_column($ventasMetodo, 'metodo'));
                $metodoVentas   = json_encode(array_map('floatval', array_column($ventasMetodo, 'total_ventas')));
                $periodoActual  = htmlspecialchars($reportePeriodo);
                ?>

                <!-- ── BARRA DE FILTROS ── -->
                <form method="GET" id="form-filtros" style="margin-bottom:24px;">
                    <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:18px 22px;box-shadow:0 4px 14px rgba(0,80,30,0.05);">
                        <div style="font-size:0.78rem;font-weight:800;color:var(--text-light);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:14px;">
                            <i class="fas fa-filter" style="color:var(--agua);margin-right:6px;"></i> Filtros del Reporte
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end;">

                            <!-- HU-04: Rango de fechas personalizado -->
                            <div>
                                <label style="display:block;font-size:0.78rem;font-weight:800;color:var(--text-mid);margin-bottom:5px;">Fecha inicio</label>
                                <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($filtroFechaIni) ?>"
                                       style="width:100%;padding:9px 12px;border-radius:9px;border:1.5px solid var(--border);font-family:'Nunito',sans-serif;font-size:0.88rem;font-weight:700;color:var(--text-dark);background:#fafffe;outline:none;"
                                       onchange="document.getElementById('form-filtros').submit()">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.78rem;font-weight:800;color:var(--text-mid);margin-bottom:5px;">Fecha fin</label>
                                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($filtroFechaFin) ?>"
                                       style="width:100%;padding:9px 12px;border-radius:9px;border:1.5px solid var(--border);font-family:'Nunito',sans-serif;font-size:0.88rem;font-weight:700;color:var(--text-dark);background:#fafffe;outline:none;"
                                       onchange="document.getElementById('form-filtros').submit()">
                            </div>

                            <div>
                                <label style="display:block;font-size:0.78rem;font-weight:800;color:var(--text-mid);margin-bottom:5px;">Período rápido</label>
                                <select name="periodo" onchange="this.form.submit()" style="width:100%;padding:9px 12px;border-radius:9px;border:1.5px solid var(--border);font-family:'Nunito',sans-serif;font-size:0.88rem;font-weight:700;color:var(--text-dark);background:#fafffe;outline:none;">
                                    <option value="semana" <?= $reportePeriodo==='semana'?'selected':'' ?>>Última semana</option>
                                    <option value="mes"    <?= $reportePeriodo==='mes'   ?'selected':'' ?>>Mes actual</option>
                                    <option value="anio"   <?= $reportePeriodo==='anio'  ?'selected':'' ?>>Año actual</option>
                                </select>
                            </div>

                            <div>
                                <label style="display:block;font-size:0.78rem;font-weight:800;color:var(--text-mid);margin-bottom:5px;">Método de pago</label>
                                <select name="metodo_pago" onchange="this.form.submit()" style="width:100%;padding:9px 12px;border-radius:9px;border:1.5px solid var(--border);font-family:'Nunito',sans-serif;font-size:0.88rem;font-weight:700;color:var(--text-dark);background:#fafffe;outline:none;">
                                    <option value="">Todos</option>
                                    <?php
                                    $metodosBase = ['Efectivo','Tarjeta','Transferencia','Nequi','Daviplata'];
                                    foreach ($metodosBase as $mb):
                                    ?>
                                    <option value="<?= $mb ?>" <?= $filtroMetodo===$mb?'selected':'' ?>><?= $mb ?></option>
                                    <?php endforeach;
                                    foreach ($ventasMetodo as $vm):
                                        if ($vm['metodo'] !== 'Sin registrar' && !in_array($vm['metodo'], $metodosBase)):
                                    ?>
                                    <option value="<?= htmlspecialchars($vm['metodo']) ?>" <?= $filtroMetodo===$vm['metodo']?'selected':'' ?>><?= htmlspecialchars($vm['metodo']) ?></option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label style="display:block;font-size:0.78rem;font-weight:800;color:var(--text-mid);margin-bottom:5px;">Categoría</label>
                                <select name="categoria" onchange="this.form.submit()" style="width:100%;padding:9px 12px;border-radius:9px;border:1.5px solid var(--border);font-family:'Nunito',sans-serif;font-size:0.88rem;font-weight:700;color:var(--text-dark);background:#fafffe;outline:none;">
                                    <option value="">Todas</option>
                                    <?php foreach ($categoriasDisp as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" <?= $filtroCategoria===$cat?'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label style="display:block;font-size:0.78rem;font-weight:800;color:var(--text-mid);margin-bottom:5px;">Platillo</label>
                                <select name="id_plato" onchange="this.form.submit()" style="width:100%;padding:9px 12px;border-radius:9px;border:1.5px solid var(--border);font-family:'Nunito',sans-serif;font-size:0.88rem;font-weight:700;color:var(--text-dark);background:#fafffe;outline:none;">
                                    <option value="">Todos</option>
                                    <?php foreach ($platosDisp as $pd): ?>
                                    <option value="<?= $pd['id_plato'] ?>" <?= $filtroPlato===$pd['id_plato']?'selected':'' ?>>
                                        <?= htmlspecialchars($pd['nombre']) ?><?= $pd['categoria'] ? ' ('.$pd['categoria'].')' : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div style="display:flex;gap:8px;">
                                <button type="submit" style="flex:1;padding:9px 10px;background:linear-gradient(135deg,var(--verde-claro),var(--verde));color:#fff;border:none;border-radius:9px;font-family:'Nunito',sans-serif;font-size:0.82rem;font-weight:800;cursor:pointer;">
                                    <i class="fas fa-search"></i> Aplicar
                                </button>
                                <a href="?periodo=<?= $periodoActual ?>" style="flex:1;padding:9px 10px;background:var(--bg-page);color:var(--text-mid);border:1.5px solid var(--border);border-radius:9px;font-family:'Nunito',sans-serif;font-size:0.82rem;font-weight:800;cursor:pointer;text-decoration:none;text-align:center;display:flex;align-items:center;justify-content:center;gap:4px;">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>

                        </div>

                        <?php
                        $filtrosActivos = [];
                        if ($filtroMetodo)    $filtrosActivos[] = ['Método: '.$filtroMetodo, 'metodo_pago'];
                        if ($filtroCategoria) $filtrosActivos[] = ['Categoría: '.$filtroCategoria, 'categoria'];
                        if ($filtroPlato) {
                            $np = '';
                            foreach ($platosDisp as $pd) { if ($pd['id_plato'] == $filtroPlato) { $np = $pd['nombre']; break; } }
                            $filtrosActivos[] = ['Platillo: '.$np, 'id_plato'];
                        }
                        if (!empty($filtrosActivos)):
                        ?>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border);">
                            <span style="font-size:0.75rem;font-weight:800;color:var(--text-light);align-self:center;">Activos:</span>
                            <?php foreach ($filtrosActivos as $fa):
                                $qp = ['periodo'=>$reportePeriodo,'metodo_pago'=>$filtroMetodo,'categoria'=>$filtroCategoria,'id_plato'=>$filtroPlato?:0];
                                $qp[$fa[1]] = '';
                                $clearUrl = '?'.http_build_query(array_filter($qp, fn($v)=>$v!==''&&$v!==0));
                            ?>
                            <span style="background:#e8f5e9;color:var(--verde);border:1px solid #c8e8d0;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:800;display:inline-flex;align-items:center;gap:6px;">
                                <?= htmlspecialchars($fa[0]) ?>
                                <a href="<?= $clearUrl ?>" style="color:var(--verde);text-decoration:none;font-size:1rem;line-height:1;">×</a>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- KPIs -->
                <?php if ((float)$resumenVentas['total_ventas'] == 0 && (int)$resumenVentas['total_pedidos'] == 0 && ($filtroFechaIni || $filtroFechaFin || $filtroMetodo || $filtroCategoria || $filtroPlato)): ?>
                <!-- HU-04 Esc.2: No hay ventas en el período seleccionado -->
                <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:12px;padding:18px 22px;margin-bottom:24px;color:#856404;font-weight:700;font-size:0.92rem;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-circle-info" style="font-size:1.2rem;"></i>
                    No hay ventas registradas en el período seleccionado.
                </div>
                <?php endif; ?>
                <div class="kpi-grid-reportes">
                    <div class="kpi-card-r">
                        <div class="kpi-icon-r" style="background:linear-gradient(135deg,#00C8F0,#0099bb);color:#fff;box-shadow:0 4px 12px rgba(0,200,240,0.3);"><i class="fas fa-dollar-sign"></i></div>
                        <div class="kpi-info-r"><div class="label">Total Ingresos</div><div class="value">$<?= number_format($resumenVentas['total_ventas'], 2) ?></div></div>
                    </div>
                    <div class="kpi-card-r">
                        <div class="kpi-icon-r" style="background:linear-gradient(135deg,#a29bfe,#6c5ce7);color:#fff;box-shadow:0 4px 12px rgba(108,92,231,0.3);"><i class="fas fa-shopping-bag"></i></div>
                        <div class="kpi-info-r"><div class="label">Pedidos Pagados</div><div class="value"><?= number_format($resumenVentas['total_pedidos']) ?></div></div>
                    </div>
                    <div class="kpi-card-r">
                        <div class="kpi-icon-r" style="background:linear-gradient(135deg,#fab1a0,#e17055);color:#fff;box-shadow:0 4px 12px rgba(225,112,85,0.3);"><i class="fas fa-users"></i></div>
                        <div class="kpi-info-r"><div class="label">Clientes Atendidos</div><div class="value"><?= number_format($resumenVentas['clientes_atendidos']) ?></div></div>
                    </div>
                    <div class="kpi-card-r">
                        <div class="kpi-icon-r" style="background:linear-gradient(135deg,#55efc4,#00b894);color:#fff;box-shadow:0 4px 12px rgba(0,184,148,0.3);"><i class="fas fa-receipt"></i></div>
                        <div class="kpi-info-r"><div class="label">Ticket Promedio</div><div class="value">$<?= number_format($ticketPromedio, 2) ?></div></div>
                    </div>
                </div>

                <!-- Gráficas -->
                <div class="charts-grid">
                    <div class="chart-card" style="grid-column:span 2;">
                        <h4><i class="fas fa-chart-line" style="color:var(--agua);"></i> Evolución de Ventas</h4>
                        <canvas id="chartVentas"></canvas>
                    </div>
                    <div class="chart-card">
                        <h4><i class="fas fa-utensils" style="color:var(--verde-claro);"></i> Platillos Más Vendidos</h4>
                        <canvas id="chartPlatos"></canvas>
                    </div>
                    <div class="chart-card">
                        <h4><i class="fas fa-credit-card" style="color:#e17055;"></i> Ventas por Método de Pago</h4>
                        <canvas id="chartMetodos"></canvas>
                    </div>
                </div>

                <!-- Ranking platillos -->
                <div class="card-panel">
                    <div class="card-header">
                        <h3><i class="fas fa-star" style="color:#f39c12;margin-right:6px;"></i> Ranking de Platillos</h3>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <a href="../../controllers/ReporteController.php?action=exportar_pdf&periodo=<?= $reportePeriodo ?>&fecha_inicio=<?= urlencode($filtroFechaIni) ?>&fecha_fin=<?= urlencode($filtroFechaFin) ?>&metodo_pago=<?= urlencode($filtroMetodo) ?>&categoria=<?= urlencode($filtroCategoria) ?>&id_plato=<?= $filtroPlato ?>"
                           target="_blank" class="btn-export">
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </a>
                        <!-- HU-04 Esc.5: Exportar a Excel -->
                        <a href="../../controllers/ReporteController.php?action=exportar_excel&periodo=<?= $reportePeriodo ?>&fecha_inicio=<?= urlencode($filtroFechaIni) ?>&fecha_fin=<?= urlencode($filtroFechaFin) ?>&metodo_pago=<?= urlencode($filtroMetodo) ?>&categoria=<?= urlencode($filtroCategoria) ?>&id_plato=<?= $filtroPlato ?>"
                           class="btn-export" style="background:linear-gradient(135deg,#217346,#1a5c37);">
                            <i class="fas fa-file-excel"></i> Exportar Excel
                        </a>
                        </div>
                    </div>
                    <?php if (!empty($platosTop)): ?>
                    <table class="top-platos-table">
                        <thead><tr><th>#</th><th>Imagen</th><th>Platillo</th><th>Categoría</th><th>Unidades</th><th>Ingresos</th></tr></thead>
                        <tbody>
                            <?php foreach ($platosTop as $i => $p):
                                $rc = match($i) { 0=>'rank-1', 1=>'rank-2', 2=>'rank-3', default=>'rank-other' };
                            ?>
                            <tr>
                                <td><span class="rank-badge <?= $rc ?>"><?= $i+1 ?></span></td>
                                <td>
                                    <?php if (!empty($p['imagen'])): ?>
                                    <img src="<?= UPLOADS_URL ?><?= htmlspecialchars($p['imagen']) ?>" alt="" style="width:38px;height:38px;object-fit:cover;border-radius:8px;">
                                    <?php else: ?>
                                    <div style="width:38px;height:38px;background:var(--bg-page);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--text-light);"><i class="fas fa-utensils"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                                <td><span class="badge" style="background:#eef8f9;color:#2d4a50;border:1px solid #cfe8eb;"><?= htmlspecialchars($p['categoria'] ?? '—') ?></span></td>
                                <td style="font-weight:800;color:var(--verde-claro);"><?= number_format($p['total_vendido']) ?> uds.</td>
                                <td style="font-weight:900;color:var(--text-dark);">$<?= number_format($p['total_ingresos'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align:center;padding:30px;color:var(--text-light);font-weight:600;">
                        <i class="fas fa-chart-bar" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                        No hay ventas para los filtros seleccionados.
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Métodos de pago -->
                <div class="card-panel" style="margin-top:22px;">
                    <div class="card-header">
                        <h3><i class="fas fa-wallet" style="color:var(--agua);margin-right:6px;"></i> Desglose por Método de Pago</h3>
                    </div>
                    <?php if (!empty($ventasMetodo)): ?>
                    <table>
                        <thead><tr><th>Método</th><th>Pedidos</th><th>Total Recaudado</th><th>% del Total</th></tr></thead>
                        <tbody>
                            <?php
                            $totalGeneral = array_sum(array_column($ventasMetodo, 'total_ventas'));
                            $iconosMetodo = ['Efectivo'=>'fa-money-bill-wave','Tarjeta'=>'fa-credit-card','Transferencia'=>'fa-building-columns','Nequi'=>'fa-mobile-screen','Daviplata'=>'fa-mobile-alt'];
                            foreach ($ventasMetodo as $vm):
                                $pct   = $totalGeneral > 0 ? ($vm['total_ventas'] / $totalGeneral * 100) : 0;
                                $icono = $iconosMetodo[$vm['metodo']] ?? 'fa-circle-dollar-to-slot';
                            ?>
                            <tr>
                                <td><span style="display:flex;align-items:center;gap:8px;font-weight:800;"><i class="fas <?= $icono ?>" style="color:var(--agua);width:16px;"></i><?= htmlspecialchars($vm['metodo']) ?></span></td>
                                <td><?= number_format($vm['total_pedidos']) ?></td>
                                <td style="font-weight:800;color:var(--verde-claro);">$<?= number_format($vm['total_ventas'], 2) ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="flex:1;background:#e8f5e9;border-radius:20px;height:8px;overflow:hidden;">
                                            <div style="width:<?= round($pct) ?>%;background:linear-gradient(90deg,var(--agua),var(--verde-claro));height:100%;border-radius:20px;"></div>
                                        </div>
                                        <span style="font-size:0.82rem;font-weight:800;color:var(--text-mid);min-width:38px;"><?= number_format($pct,1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align:center;padding:20px;color:var(--text-light);font-weight:600;">No hay pagos registrados para este período.</div>
                    <?php endif; ?>
                </div>

                <!-- Detalle serie temporal -->
                <div class="card-panel" style="margin-top:22px;">
                    <div class="card-header">
                        <h3><i class="fas fa-table" style="color:var(--agua);margin-right:6px;"></i> Detalle de Ventas</h3>
                    </div>
                    <?php if (!empty($ventasSerie)): ?>
                    <table>
                        <thead><tr><th>Período</th><th>Pedidos</th><th>Total Ventas</th></tr></thead>
                        <tbody>
                            <?php foreach ($ventasSerie as $v): ?>
                            <tr>
                                <td><?= htmlspecialchars($v['label']) ?></td>
                                <td><?= number_format($v['total_pedidos']) ?></td>
                                <td style="font-weight:800;color:var(--verde-claro);">$<?= number_format($v['total_ventas'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align:center;padding:20px;color:var(--text-light);font-weight:600;">No hay datos para este período.</div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- SECCIÓN 3: PEDIDOS -->
            <!-- SECCIÓN 3: PEDIDOS -->
            <div id="pedidos" class="section">

                <!-- Filtros -->
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
                    <button class="btn-add" style="background:transparent;color:var(--text-mid);border:1.5px solid var(--border);box-shadow:none;" onclick="filtrarAdminPedidos('todos',this)" id="apf-todos">Todos (<?= count($todosPedidos) ?>)</button>
                    <button class="btn-add" style="background:transparent;color:#856404;border:1.5px solid #f39c12;box-shadow:none;" onclick="filtrarAdminPedidos('Pendiente',this)">Pendientes</button>
                    <button class="btn-add" style="background:transparent;color:#004085;border:1.5px solid #2980b9;box-shadow:none;" onclick="filtrarAdminPedidos('En preparación',this)">En preparación</button>
                    <button class="btn-add" style="background:transparent;color:#0c5460;border:1.5px solid #17a2b8;box-shadow:none;" onclick="filtrarAdminPedidos('Entregado',this)">Entregados</button>
                    <button class="btn-add" style="background:transparent;color:#155724;border:1.5px solid #28a745;box-shadow:none;" onclick="filtrarAdminPedidos('Pagado',this)">Pagados</button>
                    <button class="btn-add" style="background:transparent;color:#721c24;border:1.5px solid #e74c3c;box-shadow:none;" onclick="filtrarAdminPedidos('Cancelado',this)">Cancelados</button>
                </div>

                <?php if (empty($todosPedidos)): ?>
                <div class="card-panel">
                    <div style="text-align:center;padding:30px;color:var(--text-light);font-weight:600;">
                        <i class="fas fa-clipboard-list" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                        No hay pedidos registrados aún.
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($todosPedidos as $tp):
                    $badgeColors = [
                        'Pendiente'      => ['bg'=>'#fff3cd','color'=>'#856404'],
                        'En preparación' => ['bg'=>'#cce5ff','color'=>'#004085'],
                        'Entregado'      => ['bg'=>'#d1ecf1','color'=>'#0c5460'],
                        'Pagado'         => ['bg'=>'#d4edda','color'=>'#155724'],
                        'Cancelado'      => ['bg'=>'#f8d7da','color'=>'#721c24'],
                    ];
                    $bc = $badgeColors[$tp['estado']] ?? ['bg'=>'#e8f0ee','color'=>'#4a6a50'];
                ?>
                <div class="card-panel admin-pedido-row" data-estado="<?= htmlspecialchars($tp['estado']) ?>" style="margin-bottom:14px;padding:18px 22px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
                        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                            <span style="font-size:1rem;font-weight:900;color:var(--text-dark);">Pedido <span style="color:var(--verde-claro);">#<?= $tp['id_pedido'] ?></span></span>
                            <span style="font-size:0.78rem;color:var(--text-light);font-weight:600;"><i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($tp['fecha'])) ?></span>
                            <?php if (!empty($tp['nombre_cliente'])): ?>
                            <span style="font-size:0.82rem;font-weight:700;color:var(--text-mid);"><i class="fas fa-user"></i> <?= htmlspecialchars($tp['nombre_cliente']) ?></span>
                            <?php endif; ?>
                        </div>
                        <span style="background:<?= $bc['bg'] ?>;color:<?= $bc['color'] ?>;padding:4px 14px;border-radius:20px;font-size:0.78rem;font-weight:800;">
                            <?= htmlspecialchars($tp['estado']) ?>
                        </span>
                    </div>

                    <?php if (!empty($tp['items'])): ?>
                    <div style="border-top:1px solid var(--border);padding-top:10px;">
                        <?php foreach ($tp['items'] as $ti): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;font-size:0.85rem;border-bottom:1px dashed #eef5f0;">
                            <span style="font-weight:700;color:var(--text-dark);"><?= htmlspecialchars($ti['nombre_plato']) ?></span>
                            <span style="color:var(--text-light);"><?= $ti['cantidad'] ?> u.</span>
                            <span style="font-weight:800;color:var(--verde-claro);">$<?= number_format($ti['precio_unitario'] * $ti['cantidad'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div style="display:flex;justify-content:flex-end;padding-top:8px;font-size:0.95rem;font-weight:900;color:var(--text-dark);">
                            Total: <span style="color:var(--verde-claro);margin-left:8px;">$<?= number_format($tp['total'], 2) ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <p style="font-size:0.82rem;color:var(--text-light);padding-top:8px;">Sin ítems registrados.</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- SECCIÓN 3: USUARIOS -->
            <div id="usuarios" class="section">
                <div class="card-panel">
                    <div class="card-header">
                        <h3>Lista de Usuarios</h3>
                        <button class="btn-add" onclick="toggleModal('modal-add-user')"><i class="fas fa-plus"></i> Nuevo Empleado</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="user-table-body">
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['nombre']) ?></td>
                                <td><?= htmlspecialchars($u['correo']) ?></td>
                                <td>
                                    <?php
                                        $rol = strtolower($u['rol_nombre'] ?? 'cliente');
                                        $badgeClass = '';
                                        if ($rol == 'administrador') $badgeClass = 'admin';
                                        elseif ($rol == 'mesero') $badgeClass = 'mesero';
                                        elseif ($rol == 'cocinero') $badgeClass = 'cocinero';
                                        else $badgeClass = 'cliente';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($u['rol_nombre'] ?? 'Sin rol') ?></span>
                                </td>
                                <td>
                                    <span style="color: <?= strtolower($u['estado']) == 'activo' ? '#2dbab5' : '#e74c3c' ?>; font-weight:800;">
                                        <?= htmlspecialchars($u['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Botones de Acción -->
                                    <button onclick="openEditRoleModal(<?= $u['id_usuario'] ?>, '<?= $u['id_rol'] ?? 4 ?>')" style="background:none; border:none; color:#3498db; cursor:pointer; margin-right:10px;" title="Cambiar Rol">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <form action="../../controllers/AdminController.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                        <input type="hidden" name="nuevo_estado" value="<?= strtolower($u['estado']) == 'activo' ? 'inactivo' : 'activo' ?>">
                                        <button type="submit" style="background:none; border:none; color:#f39c12; cursor:pointer; margin-right:10px;" title="Cambiar Estado">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>

                                    <form action="../../controllers/AdminController.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                        <button type="submit" style="background:none; border:none; color:#e74c3c; cursor:pointer;" title="Eliminar Usuario">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($usuarios)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; color:#7a9aa0;">No hay usuarios registrados</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECCIÓN 4: INVENTARIO -->
            <div id="inventario" class="section">
                <div class="card-panel">
                    <div class="card-header">
                        <h3>Menú</h3>
                        <div style="display:flex;gap:8px;">
                            <button class="btn-add" onclick="toggleModal('modal-platillo-dia')" style="background:linear-gradient(135deg,#f39c12,#e67e22);box-shadow:0 4px 12px rgba(243,156,18,0.3);">
                                <i class="fas fa-star"></i> Platillo del Día
                            </button>
                            <button class="btn-add" onclick="toggleModal('modal-add-plato')"><i class="fas fa-plus"></i> Agregar Plato</button>
                        </div>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Plato</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Disponibilidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($platos as $p): ?>
                            <tr>
                                <td>
                                    <img src="<?= UPLOADS_URL ?><?= htmlspecialchars($p['imagen'] ?? 'default.jpg') ?>" alt="Plato" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($p['nombre']) ?></strong><br>
                                    <span style="font-size:0.8rem; color:#7a9aa0;"><?= htmlspecialchars(substr($p['descripcion'] ?? '', 0, 30)) ?>...</span>
                                </td>
                                <td><span class="badge" style="background:#eef8f9; color:#2d4a50; border:1px solid #cfe8eb;"><?= htmlspecialchars($p['categoria'] ?? 'Sin Categoría') ?></span></td>
                                <td>$<?= number_format($p['precio'], 2) ?></td>
                                <td>
                                    <?php $disp = $p['disponibilidad']; ?>
                                    <button onclick="openDisponibilidadModal(<?= $p['id_plato'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>', <?= (int)$disp ?>)"
                                        style="background:<?= $disp > 0 ? '#d4f5f0' : '#fde8e8' ?>; border:none; border-radius:20px; padding:5px 14px; font-weight:800; font-size:0.82rem; cursor:pointer; color:<?= $disp > 0 ? '#1e9995' : '#e74c3c' ?>;">
                                        <i class="fas fa-<?= $disp > 0 ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= $disp > 0 ? 'Disponible (' . (int)$disp . ')' : 'No disponible' ?>
                                    </button>
                                </td>
                                <td>
                                    <button onclick="openEditPlatoModal(<?= $p['id_plato'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>', '<?= htmlspecialchars(addslashes($p['categoria'] ?? '')) ?>', <?= $p['precio'] ?>, <?= $p['disponibilidad'] ?>)" style="background:none; border:none; color:#f39c12; cursor:pointer; margin-right:8px;" title="Editar Plato">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button onclick="openRecetaModal(<?= $p['id_plato'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>')" style="background:none; border:none; color:#3498db; cursor:pointer; margin-right:8px;" title="Configurar Receta">
                                        <i class="fas fa-list-ul"></i> Receta
                                    </button>
                                    <button onclick="openOfertaModal(<?= $p['id_plato'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>', <?= (int)($p['en_oferta'] ?? 0) ?>, '<?= htmlspecialchars(addslashes($p['precio_oferta'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($p['inicio_oferta'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($p['fin_oferta'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($p['etiqueta_oferta'] ?? '')) ?>', <?= (int)($p['es_platillo_dia'] ?? 0) ?>)" style="background:none; border:none; color:<?= ($p['en_oferta'] ?? 0) ? '#e17055' : '#7aaa8a' ?>; cursor:pointer; font-weight:800;" title="Gestionar Oferta">
                                        <i class="fas fa-tag"></i> <?= ($p['en_oferta'] ?? 0) ? 'En oferta' : 'Oferta' ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($platos)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; color:#7a9aa0;">No hay platos registrados en el menú</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                </div>

                <div class="card-panel" style="margin-top: 30px;">
                    <div class="card-header">
                        <h3>Materia Prima (Inventario Físico)</h3>
                        <button class="btn-add" onclick="toggleModal('modal-add-materia')"><i class="fas fa-plus"></i> Nueva Materia Prima</button>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Ingrediente</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materias as $m): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($m['nombre']) ?></strong></td>
                                <td>
                                    <span style="font-size: 1.1rem; font-weight: 800; color: <?= $m['stock_actual'] <= $m['stock_minimo'] ? '#e74c3c' : '#2dbab5' ?>;">
                                        <?= number_format($m['stock_actual'], 2) ?> <?= htmlspecialchars($m['unidad_medida']) ?>
                                    </span>
                                </td>
                                <td><?= number_format($m['stock_minimo'], 2) ?> <?= htmlspecialchars($m['unidad_medida']) ?></td>
                                <td>
                                    <button onclick="openEditMateriaModal(<?= $m['id_materia'] ?>, '<?= htmlspecialchars(addslashes($m['nombre'])) ?>', <?= $m['stock_actual'] ?>, '<?= htmlspecialchars(addslashes($m['unidad_medida'])) ?>', <?= $m['stock_minimo'] ?>)" style="background:none; border:none; color:#f39c12; cursor:pointer; font-weight:bold; margin-right:8px;" title="Editar">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button onclick="openStockModal(<?= $m['id_materia'] ?>, '<?= htmlspecialchars(addslashes($m['nombre'])) ?>', '<?= htmlspecialchars(addslashes($m['unidad_medida'])) ?>')" style="background:none; border:none; color:#2dbab5; cursor:pointer; font-weight:bold;" title="Añadir Stock">
                                        <i class="fas fa-box"></i> Ingresar Stock
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($materias)): ?>
                            <tr>
                                <td colspan="4" style="text-align:center; color:#7a9aa0;">No hay materias primas registradas</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- SECCIÓN 5: QUEJAS -->
    <div id="quejas" class="section" style="width:100%;">
        <div class="card-panel" style="width:100%;">
            <div class="card-header">
                <h3><i class="fas fa-comment-exclamation" style="color:#e74c3c;margin-right:6px;"></i> Quejas y Comentarios de Clientes</h3>
                <button onclick="marcarTodasLeidas()" style="background:transparent;border:1.5px solid var(--border);color:var(--text-mid);padding:8px 16px;border-radius:10px;font-family:'Nunito',sans-serif;font-size:0.85rem;font-weight:800;cursor:pointer;">
                    <i class="fas fa-check-double"></i> Marcar todas como leídas
                </button>
            </div>
            <div id="quejas-lista" style="width:100%;">
                <div style="text-align:center;padding:40px;color:var(--text-light);font-weight:600;">
                    <i class="fas fa-comment-slash" style="font-size:2.5rem;display:block;margin-bottom:12px;color:var(--border);"></i>
                    No hay quejas registradas aún.
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL AGREGAR USUARIO -->
    <div class="modal" id="modal-add-user">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Agregar Empleado</h3>
                <button class="close-btn" onclick="toggleModal('modal-add-user')"><i class="fas fa-times"></i></button>
            </div>
            <form action="../../controllers/AdminController.php" method="POST">
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-group">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" required placeholder="Ej. Juan Pérez">
                </div>
                
                <div class="form-group">
                    <label>Correo electrónico</label>
                    <input type="email" name="correo" required placeholder="correo@ejemplo.com">
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" required placeholder="3001234567">
                </div>

                <div class="form-group">
                    <label>Contraseña temporal</label>
                    <input type="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres">
                </div>

                <div class="form-group">
                    <label>Rol del empleado</label>
                    <select name="id_rol" required>
                        <option value="">Seleccione un rol...</option>
                        <option value="1">Administrador</option>
                        <option value="2">Mesero</option>
                        <option value="3">Cocinero</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Guardar Empleado</button>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR ROL -->
    <div class="modal" id="modal-edit-role">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cambiar Rol</h3>
                <button class="close-btn" onclick="toggleModal('modal-edit-role')"><i class="fas fa-times"></i></button>
            </div>
            <form action="../../controllers/AdminController.php" method="POST">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="id_usuario" id="edit-role-id">
                
                <div class="form-group">
                    <label>Nuevo Rol</label>
                    <select name="id_rol" id="edit-role-select" required>
                        <option value="1">Administrador</option>
                        <option value="2">Mesero</option>
                        <option value="3">Cocinero</option>
                        <option value="4">Cliente</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <!-- MODAL AGREGAR PLATO -->
    <div class="modal" id="modal-add-plato">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Agregar Plato al Menú</h3>
                <button class="close-btn" onclick="toggleModal('modal-add-plato')"><i class="fas fa-times"></i></button>
            </div>
            <form action="../../controllers/PlatoController.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_plato">
                
                <div class="form-group">
                    <label>Nombre del Plato</label>
                    <input type="text" name="nombre" required placeholder="Ej. Hamburguesa Doble">
                </div>
                
                <div style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;">
                        <label>Precio ($)</label>
                        <input type="number" step="0.01" name="precio" required placeholder="Ej. 15.50">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Categoría</label>
                        <input type="text" name="categoria" required placeholder="Ej. Platos Fuertes">
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripción corta</label>
                    <input type="text" name="descripcion" placeholder="Ingredientes o descripción breve">
                </div>

                <div class="form-group">
                    <label>Imagen del Plato</label>
                    <input type="file" name="imagen" accept="image/*" required style="padding: 5px;">
                </div>

                <button type="submit" class="btn-submit">Guardar Plato</button>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR PLATO -->
    <div class="modal" id="modal-edit-plato">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Editar Plato</h3>
                <button class="close-btn" onclick="toggleModal('modal-edit-plato')"><i class="fas fa-times"></i></button>
            </div>
            <form action="../../controllers/PlatoController.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_plato">
                <input type="hidden" name="id_plato" id="edit-plato-id">
                
                <div class="form-group">
                    <label>Nombre del Plato</label>
                    <input type="text" name="nombre" id="edit-plato-nombre" required placeholder="Ej. Hamburguesa Doble">
                </div>
                
                <div style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;">
                        <label>Precio ($)</label>
                        <input type="number" step="0.01" name="precio" id="edit-plato-precio" required placeholder="Ej. 15.50">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Categoría</label>
                        <input type="text" name="categoria" id="edit-plato-categoria" required placeholder="Ej. Platos Fuertes">
                    </div>
                </div>

                <div class="form-group">
                    <label>Disponibilidad</label>
                    <select name="disponibilidad" id="edit-plato-disponibilidad" required>
                        <option value="1">Disponible</option>
                        <option value="0">Agotado / Oculto</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Imagen del Plato (Opcional)</label>
                    <input type="file" name="imagen" accept="image/*" style="padding: 5px;">
                    <small style="color: #7a9aa0; display: block; margin-top: 5px;">Sube una nueva imagen sólo si deseas cambiar la actual.</small>
                </div>

                <button type="submit" class="btn-submit">Actualizar Plato</button>
            </form>
        </div>
    </div>

    <!-- MODAL DISPONIBILIDAD PLATO -->
    <div class="modal" id="modal-disponibilidad">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Disponibilidad del Plato</h3>
                <button class="close-btn" onclick="toggleModal('modal-disponibilidad')"><i class="fas fa-times"></i></button>
            </div>
            <form action="../../controllers/PlatoController.php" method="POST">
                <input type="hidden" name="action" value="update_disponibilidad">
                <input type="hidden" name="id_plato" id="disp-id-plato">
                <p style="margin-bottom:15px; font-weight:600; color:#4a6a70;">
                    Plato: <span id="disp-nombre-plato" style="color:#2dbab5;"></span>
                </p>
                <div class="form-group">
                    <label>¿Está disponible?</label>
                    <select name="disponible" id="disp-disponible" required>
                        <option value="1">Sí, disponible</option>
                        <option value="0">No disponible</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>¿Cuántos hay? <span style="font-weight:normal; color:#7a9aa0;">(0 = sin límite definido)</span></label>
                    <input type="number" name="cantidad" id="disp-cantidad" min="0" required placeholder="Ej. 10">
                </div>
                <button type="submit" class="btn-submit">Guardar</button>
            </form>
        </div>
    </div>

    <!-- MODAL AGREGAR MATERIA PRIMA -->
    <div class="modal" id="modal-add-materia">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nueva Materia Prima</h3>
                <button class="close-btn" onclick="toggleModal('modal-add-materia')"><i class="fas fa-times"></i></button>
            </div>
            <form action="../../controllers/InventarioController.php" method="POST">
                <input type="hidden" name="action" value="add_materia">
                
                <div class="form-group">
                    <label>Nombre del Ingrediente</label>
                    <input type="text" name="nombre" required placeholder="Ej. Tomate, Carne de Res, Sal">
                </div>
                
                <div style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;">
                        <label>Stock Inicial</label>
                        <input type="number" step="0.01" name="stock_actual" required placeholder="0.00">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Stock Mínimo (Alerta)</label>
                        <input type="number" step="0.01" name="stock_minimo" required placeholder="0.00">
                    </div>
                </div>

                <div class="form-group">
                    <label>Unidad de Medida</label>
                    <select name="unidad_medida" required>
                        <option value="Gramos (g)">Gramos (g)</option>
                        <option value="Kilogramos (kg)">Kilogramos (kg)</option>
                        <option value="Mililitros (ml)">Mililitros (ml)</option>
                        <option value="Litros (L)">Litros (L)</option>
                        <option value="Unidades (und)">Unidades (und)</option>
                        <option value="Libras (lb)">Libras (lb)</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Guardar Ingrediente</button>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR MATERIA PRIMA -->
    <div class="modal" id="modal-edit-materia">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Materia Prima</h3>
                <button class="close-btn" onclick="toggleModal('modal-edit-materia')"><i class="fas fa-times"></i></button>
            </div>
            <form action="../../controllers/InventarioController.php" method="POST">
                <input type="hidden" name="action" value="edit_materia">
                <input type="hidden" name="id_materia" id="edit-materia-id">

                <div class="form-group">
                    <label>Nombre del Ingrediente</label>
                    <input type="text" name="nombre" id="edit-materia-nombre" required placeholder="Ej. Tomate, Carne de Res, Sal">
                </div>

                <div style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;">
                        <label>Stock Actual</label>
                        <input type="number" step="0.01" name="stock_actual" id="edit-materia-stock" required placeholder="0.00">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Stock Mínimo (Alerta)</label>
                        <input type="number" step="0.01" name="stock_minimo" id="edit-materia-minimo" required placeholder="0.00">
                    </div>
                </div>

                <div class="form-group">
                    <label>Unidad de Medida</label>
                    <select name="unidad_medida" id="edit-materia-unidad" required>
                        <option value="Gramos (g)">Gramos (g)</option>
                        <option value="Kilogramos (kg)">Kilogramos (kg)</option>
                        <option value="Mililitros (ml)">Mililitros (ml)</option>
                        <option value="Litros (L)">Litros (L)</option>
                        <option value="Unidades (und)">Unidades (und)</option>
                        <option value="Libras (lb)">Libras (lb)</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <!-- MODAL INGRESAR STOCK -->
    <div class="modal" id="modal-add-stock">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ingresar Stock</h3>
                <button class="close-btn" onclick="toggleModal('modal-add-stock')"><i class="fas fa-times"></i></button>
            </div>
            <form action="../../controllers/InventarioController.php" method="POST">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" name="id_materia" id="stock-id-materia">
                
                <p style="margin-bottom: 15px; font-weight: 600; color: #4a6a70;">Ingrediente: <span id="stock-nombre" style="color: #2dbab5;"></span></p>

                <div class="form-group">
                    <label>Cantidad a Sumar <span id="stock-unidad" style="font-weight:normal; color:#7a9aa0;"></span></label>
                    <input type="number" step="0.01" name="cantidad_a_sumar" required placeholder="Ej. 1000">
                </div>

                <button type="submit" class="btn-submit">Actualizar Stock</button>
            </form>
        </div>
    </div>

    <!-- MODAL CONFIGURAR OFERTA -->
    <div class="modal" id="modal-oferta">
        <div class="modal-content" style="max-width:480px;">
            <div class="modal-header">
                <h3><i class="fas fa-tag" style="color:#e17055;margin-right:8px;"></i>Gestionar Oferta</h3>
                <button class="close-btn" onclick="toggleModal('modal-oferta')"><i class="fas fa-times"></i></button>
            </div>
            <p style="font-size:0.88rem;font-weight:800;color:var(--text-mid);margin-bottom:18px;" id="oferta-plato-nombre"></p>
            <form action="../../controllers/PlatoController.php" method="POST">
                <input type="hidden" name="action" value="update_oferta">
                <input type="hidden" name="id_plato" id="oferta-id-plato">

                <div class="form-group">
                    <label>¿Está en oferta?</label>
                    <select name="en_oferta" id="oferta-activa" onchange="toggleOfertaFields(this.value)" required>
                        <option value="0">No — quitar de oferta</option>
                        <option value="1">Sí — activar oferta</option>
                    </select>
                </div>

                <div id="oferta-fields">
                    <div class="form-group">
                        <label>Etiqueta <span style="font-weight:400;color:var(--text-light);">(ej. "Especial del día", "20% OFF")</span></label>
                        <input type="text" name="etiqueta_oferta" id="oferta-etiqueta" placeholder="Ej. Especial del día" maxlength="60">
                    </div>
                    <div class="form-group">
                        <label>Precio en oferta <span style="font-weight:400;color:var(--text-light);">(vacío = mantener precio original)</span></label>
                        <input type="number" step="0.01" min="0" name="precio_oferta" id="oferta-precio" placeholder="Ej. 12.50">
                    </div>
                    <div class="modal-grid-2">
                        <div class="form-group">
                            <label>Fecha de inicio <span style="font-weight:400;color:var(--text-light);">(vacío = ahora)</span></label>
                            <input type="datetime-local" name="inicio_oferta" id="oferta-inicio">
                        </div>
                        <div class="form-group">
                            <label>Fecha de fin <span style="font-weight:400;color:var(--text-light);">(vacío = sin límite)</span></label>
                            <input type="datetime-local" name="fin_oferta" id="oferta-fin">
                        </div>
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;gap:10px;background:#fff8f0;border:1px solid #f0d9c8;border-radius:10px;padding:12px 14px;">
                        <input type="checkbox" name="es_platillo_dia" id="oferta-dia" value="1" style="width:18px;height:18px;accent-color:#e17055;cursor:pointer;flex-shrink:0;">
                        <label for="oferta-dia" style="cursor:pointer;font-size:0.88rem;font-weight:800;color:#7a4a2a;margin:0;">
                            <i class="fas fa-star" style="color:#f39c12;margin-right:4px;"></i>
                            Marcar como <strong>Platillo Especial del Día</strong>
                            <span style="display:block;font-size:0.75rem;font-weight:600;color:#b07a5a;margin-top:2px;">Aparecerá destacado en el panel del cliente</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-submit" style="background:linear-gradient(135deg,#e17055,#c0392b);margin-top:14px;">
                    <i class="fas fa-tag"></i> Guardar Oferta
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL CREAR PLATILLO DEL DÍA -->
    <div class="modal" id="modal-platillo-dia">
        <div class="modal-content" style="max-width:500px;">
            <div class="modal-header">
                <h3><i class="fas fa-star" style="color:#f39c12;margin-right:8px;"></i>Crear Platillo Especial del Día</h3>
                <button class="close-btn" onclick="toggleModal('modal-platillo-dia')"><i class="fas fa-times"></i></button>
            </div>
            <p style="font-size:0.82rem;color:var(--text-mid);margin-bottom:16px;">
                Crea un platillo único que solo estará disponible durante el período que definas. Aparecerá destacado en el panel del cliente.
            </p>
            <form action="../../controllers/PlatoController.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="crear_platillo_dia">

                <div class="form-group">
                    <label>Nombre del platillo especial</label>
                    <input type="text" name="nombre" required placeholder="Ej. Bandeja Paisa Especial">
                </div>
                <div class="modal-grid-2">
                    <div class="form-group">
                        <label>Precio normal ($)</label>
                        <input type="number" step="0.01" name="precio" required placeholder="Ej. 18.00">
                    </div>
                    <div class="form-group">
                        <label>Precio en oferta ($) <span style="font-weight:400;color:var(--text-light);">(opcional)</span></label>
                        <input type="number" step="0.01" name="precio_oferta" placeholder="Ej. 14.00">
                    </div>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <input type="text" name="descripcion" placeholder="Ingredientes o descripción breve">
                </div>
                <div class="modal-grid-2">
                    <div class="form-group">
                        <label>Categoría</label>
                        <input type="text" name="categoria" value="Especial" placeholder="Ej. Especial">
                    </div>
                    <div class="form-group">
                        <label>Etiqueta de oferta</label>
                        <input type="text" name="etiqueta_oferta" value="Platillo del Día" maxlength="60">
                    </div>
                </div>
                <div class="modal-grid-2">
                    <div class="form-group">
                        <label>Disponible desde <span style="font-weight:400;color:var(--text-light);">(vacío = ahora)</span></label>
                        <input type="datetime-local" name="inicio_oferta" id="dia-inicio">
                    </div>
                    <div class="form-group">
                        <label>Disponible hasta <span style="font-weight:400;color:var(--text-light);">(vacío = sin límite)</span></label>
                        <input type="datetime-local" name="fin_oferta" id="dia-fin">
                    </div>
                </div>
                <div class="form-group">
                    <label>Imagen <span style="font-weight:400;color:var(--text-light);">(opcional)</span></label>
                    <input type="file" name="imagen" accept="image/*" style="padding:5px;">
                </div>
                <button type="submit" class="btn-submit" style="background:linear-gradient(135deg,#f39c12,#e67e22);">
                    <i class="fas fa-star"></i> Crear Platillo Especial
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL CONFIGURAR RECETA -->
    <div class="modal" id="modal-receta">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Receta del Plato</h3>
                <button class="close-btn" onclick="toggleModal('modal-receta')"><i class="fas fa-times"></i></button>
            </div>
            
            <p style="margin-bottom: 15px; font-weight: 800; color: #2d4a50; font-size:1.1rem;" id="receta-plato-nombre"></p>
            
            <form action="../../controllers/InventarioController.php" method="POST" style="background:#f4f9f9; padding: 15px; border-radius: 10px; border: 1px solid #cfe8eb; margin-bottom: 20px;">
                <h4 style="margin-bottom: 10px; font-size: 0.9rem; color: #4a6a70;">Añadir Ingrediente a la Receta</h4>
                <input type="hidden" name="action" value="add_receta">
                <input type="hidden" name="id_plato" id="receta-id-plato">
                
                <div class="form-group">
                    <label>Ingrediente (Materia Prima)</label>
                    <select name="id_materia" required>
                        <option value="">Seleccione...</option>
                        <?php foreach($materias as $m): ?>
                            <option value="<?= $m['id_materia'] ?>"><?= htmlspecialchars($m['nombre']) ?> (<?= htmlspecialchars($m['unidad_medida']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Cantidad Requerida por Plato</label>
                    <input type="number" step="0.01" name="cantidad_requerida" required placeholder="Ej. 150">
                </div>

                <button type="submit" class="btn-submit" style="padding: 8px; font-size:0.9rem;">Agregar a la Receta</button>
            </form>
            
            <div style="font-size:0.85rem; color:#7a9aa0; text-align:center;">
                (Para ver la lista completa de ingredientes de esta receta, se debe implementar una vista de detalles o consulta AJAX).
            </div>
        </div>
    </div>

    <script>
        const titles = {
            'dashboard': 'Estadísticas del Mes',
            'reportes':  'Reportes de Ventas',
            'pedidos':   'Control de Pedidos',
            'usuarios':  'Gestión de Usuarios',
            'inventario':'Inventario y Menú',
            'quejas':    'Quejas y Comentarios'
        };

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }

        function toggleUserMenu() {
            document.getElementById('userMenu').classList.toggle('open');
        }

        function filtrarAdminPedidos(estado, btn) {
            document.querySelectorAll('.admin-pedido-row').forEach(function(el) {
                el.style.display = (estado === 'todos' || el.dataset.estado === estado) ? '' : 'none';
            });
        }

        // Cerrar el menú al hacer clic fuera
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('userMenu');
            if (!menu.contains(e.target)) {
                menu.classList.remove('open');
            }
        });

        function showSection(sectionId, element) {
            document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            
            document.getElementById(sectionId).classList.add('active');
            element.classList.add('active');
            
            document.getElementById('page-title').innerText = titles[sectionId];
        }

        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.toggle('active');
        }

        function openEditRoleModal(id_usuario, current_role) {
            document.getElementById('edit-role-id').value = id_usuario;
            document.getElementById('edit-role-select').value = current_role;
            toggleModal('modal-edit-role');
        }

        function openEditPlatoModal(id, nombre, categoria, precio, disponibilidad) {
            document.getElementById('edit-plato-id').value = id;
            document.getElementById('edit-plato-nombre').value = nombre;
            document.getElementById('edit-plato-categoria').value = categoria;
            document.getElementById('edit-plato-precio').value = precio;
            document.getElementById('edit-plato-disponibilidad').value = disponibilidad;
            toggleModal('modal-edit-plato');
        }

        function openStockModal(id_materia, nombre, unidad) {
            document.getElementById('stock-id-materia').value = id_materia;
            document.getElementById('stock-nombre').innerText = nombre;
            document.getElementById('stock-unidad').innerText = '(' + unidad + ')';
            toggleModal('modal-add-stock');
        }

        function openEditMateriaModal(id, nombre, stock, unidad, minimo) {
            document.getElementById('edit-materia-id').value = id;
            document.getElementById('edit-materia-nombre').value = nombre;
            document.getElementById('edit-materia-stock').value = stock;
            document.getElementById('edit-materia-minimo').value = minimo;
            document.getElementById('edit-materia-unidad').value = unidad;
            toggleModal('modal-edit-materia');
        }

        function openRecetaModal(id_plato, nombre_plato) {
            document.getElementById('receta-id-plato').value = id_plato;
            document.getElementById('receta-plato-nombre').innerText = nombre_plato;
            toggleModal('modal-receta');
        }

        function openOfertaModal(id_plato, nombre, en_oferta, precio_oferta, inicio_oferta, fin_oferta, etiqueta, es_dia) {
            document.getElementById('oferta-id-plato').value  = id_plato;
            document.getElementById('oferta-plato-nombre').textContent = nombre;
            document.getElementById('oferta-activa').value   = en_oferta ? '1' : '0';
            document.getElementById('oferta-precio').value   = precio_oferta || '';
            document.getElementById('oferta-etiqueta').value = etiqueta || '';
            document.getElementById('oferta-dia').checked    = !!parseInt(es_dia);
            // Convertir fecha BD (YYYY-MM-DD HH:MM:SS) → datetime-local (YYYY-MM-DDTHH:MM)
            var toLocal = function(dt) { return dt ? dt.replace(' ', 'T').substring(0, 16) : ''; };
            document.getElementById('oferta-inicio').value = toLocal(inicio_oferta);
            document.getElementById('oferta-fin').value    = toLocal(fin_oferta);
            toggleOfertaFields(en_oferta ? '1' : '0');
            toggleModal('modal-oferta');
        }

        function toggleOfertaFields(val) {
            document.getElementById('oferta-fields').style.display = val === '1' ? 'block' : 'none';
        }

        function openDisponibilidadModal(id_plato, nombre, disponibilidad) {
            document.getElementById('disp-id-plato').value = id_plato;
            document.getElementById('disp-nombre-plato').innerText = nombre;
            // disponibilidad > 0 = disponible, el valor numérico es la cantidad
            document.getElementById('disp-disponible').value = disponibilidad > 0 ? 1 : 0;
            document.getElementById('disp-cantidad').value = disponibilidad > 0 ? disponibilidad : 0;
            toggleModal('modal-disponibilidad');
        }

        // ── Cambiar período de reportes ──
        function cambiarPeriodo(periodo) {
            const url = new URL(window.location.href);
            url.searchParams.set('periodo', periodo);
            url.hash = 'reportes';
            window.location.href = url.toString();
        }

        // ── Inicializar gráficas de reportes ──
        (function initCharts() {
            const labels    = <?= $chartLabels ?>;
            const ventas    = <?= $chartVentas ?>;
            const pedidos   = <?= $chartPedidos ?>;
            const pNombres  = <?= $platosNombres ?>;
            const pVendido  = <?= $platosVendido ?>;
            const mNombres  = <?= $metodoNombres ?>;
            const mVentas   = <?= $metodoVentas ?>;

            const colores = [
                '#00C8F0','#00a020','#a29bfe','#fab1a0','#55efc4',
                '#fdcb6e','#e17055','#74b9ff','#fd79a8','#6c5ce7'
            ];

            // Gráfica de línea: evolución de ventas
            const ctxVentas = document.getElementById('chartVentas');
            if (ctxVentas && labels.length > 0) {
                new Chart(ctxVentas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Ventas ($)',
                                data: ventas,
                                borderColor: '#00C8F0',
                                backgroundColor: 'rgba(0,200,240,0.1)',
                                borderWidth: 2.5,
                                pointBackgroundColor: '#00C8F0',
                                pointRadius: 4,
                                tension: 0.4,
                                fill: true,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Pedidos',
                                data: pedidos,
                                borderColor: '#00a020',
                                backgroundColor: 'rgba(0,160,32,0.08)',
                                borderWidth: 2,
                                pointBackgroundColor: '#00a020',
                                pointRadius: 4,
                                tension: 0.4,
                                fill: false,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'top', labels: { font: { family: 'Nunito', weight: '700' } } },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ctx.datasetIndex === 0
                                        ? ' $' + ctx.parsed.y.toFixed(2)
                                        : ' ' + ctx.parsed.y + ' pedidos'
                                }
                            }
                        },
                        scales: {
                            y:  { type: 'linear', position: 'left',  ticks: { callback: v => '$' + v.toFixed(0) } },
                            y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false }, ticks: { stepSize: 1 } }
                        }
                    }
                });
            }

            // Gráfica de barras: platillos más vendidos
            const ctxPlatos = document.getElementById('chartPlatos');
            if (ctxPlatos && pNombres.length > 0) {
                new Chart(ctxPlatos, {
                    type: 'bar',
                    data: {
                        labels: pNombres,
                        datasets: [{
                            label: 'Unidades vendidas',
                            data: pVendido,
                            backgroundColor: colores.slice(0, pNombres.length),
                            borderRadius: 8,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y + ' uds.' } }
                        },
                        scales: {
                            x: { ticks: { font: { family: 'Nunito', size: 11 }, maxRotation: 30 } },
                            y: { ticks: { stepSize: 1 } }
                        }
                    }
                });
            }

            // Gráfica de dona: métodos de pago
            const ctxMetodos = document.getElementById('chartMetodos');
            if (ctxMetodos && mNombres.length > 0) {
                new Chart(ctxMetodos, {
                    type: 'doughnut',
                    data: {
                        labels: mNombres,
                        datasets: [{
                            data: mVentas,
                            backgroundColor: colores.slice(0, mNombres.length),
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'right', labels: { font: { family: 'Nunito', size: 11 }, boxWidth: 14 } },
                            tooltip: { callbacks: { label: ctx => ' $' + ctx.parsed.toFixed(2) } }
                        }
                    }
                });
            }
        })();

        // Seleccionar pestaña según el hash en la URL
        (function() {
            const hash = window.location.hash.substring(1);
            if (hash && document.getElementById(hash)) {
                // Encontrar el nav-item correspondiente
                const navItems = document.querySelectorAll('.nav-item');
                let targetNav = null;
                navItems.forEach(item => {
                    if (item.getAttribute('onclick').includes("'" + hash + "'")) {
                        targetNav = item;
                    }
                });
                if (targetNav) {
                    showSection(hash, targetNav);
                }
            }
        })();

        // Prevenir el regreso sin cerrar sesión (Cerrar sesión al presionar "Atrás")
        window.history.pushState(null, "", window.location.href);
        window.onpopstate = function () {
            window.location.href = "../../controllers/AuthController.php?action=logout";
        };

        // ── Quejas desde localStorage ──
        function cargarQuejas() {
            var quejas = JSON.parse(localStorage.getItem('quejas_elcielo') || '[]');
            var noLeidas = quejas.filter(function(q){ return !q.leida; }).length;

            // Badge en sidebar
            var badge = document.getElementById('quejas-badge');
            if (badge) {
                if (noLeidas > 0) {
                    badge.style.display = 'flex';
                    badge.textContent = noLeidas;
                } else {
                    badge.style.display = 'none';
                }
            }

            // Renderizar lista
            var lista = document.getElementById('quejas-lista');
            if (!lista) return;

            if (quejas.length === 0) {
                lista.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-light);font-weight:600;"><i class="fas fa-comment-slash" style="font-size:2.5rem;display:block;margin-bottom:12px;color:var(--border);"></i>No hay quejas registradas aún.</div>';
                return;
            }

            // Ordenar: no leídas primero, luego por fecha desc
            quejas.sort(function(a,b){
                if (!a.leida && b.leida) return -1;
                if (a.leida && !b.leida) return 1;
                return b.id - a.id;
            });

            var html = '';
            quejas.forEach(function(q, idx) {
                var bg     = q.leida ? '#f8fdf9' : '#fff8f0';
                var border = q.leida ? 'var(--border)' : '#f39c12';
                var badge  = q.leida
                    ? '<span style="background:#d4edda;color:#155724;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:800;">Leída</span>'
                    : '<span style="background:#fff3cd;color:#856404;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:800;"><i class="fas fa-circle" style="font-size:0.5rem;vertical-align:middle;margin-right:4px;"></i>Nueva</span>';

                html += '<div style="background:' + bg + ';border:1px solid ' + border + ';border-radius:12px;padding:18px 20px;margin-bottom:12px;">' +
                    '<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px;">' +
                        '<div style="display:flex;align-items:center;gap:10px;">' +
                            '<div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#e17055,#c0392b);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.9rem;font-weight:900;flex-shrink:0;">' +
                                q.usuario.charAt(0).toUpperCase() +
                            '</div>' +
                            '<div>' +
                                '<div style="font-size:0.9rem;font-weight:900;color:var(--text-dark);">' + escAdminHtml(q.usuario) + '</div>' +
                                '<div style="font-size:0.72rem;color:var(--text-light);font-weight:600;">' + escAdminHtml(q.fecha) + '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div style="display:flex;align-items:center;gap:8px;">' +
                            badge +
                            (!q.leida ? '<button onclick="marcarLeida(' + idx + ')" style="background:none;border:1.5px solid var(--border);color:var(--text-mid);padding:5px 12px;border-radius:8px;font-family:\'Nunito\',sans-serif;font-size:0.78rem;font-weight:800;cursor:pointer;"><i class="fas fa-check"></i> Marcar leída</button>' : '') +
                            '<button onclick="eliminarQueja(' + idx + ')" style="background:none;border:none;color:#e74c3c;cursor:pointer;font-size:0.9rem;" title="Eliminar"><i class="fas fa-trash-alt"></i></button>' +
                        '</div>' +
                    '</div>' +
                    '<div style="font-size:0.88rem;font-weight:600;color:var(--text-dark);line-height:1.6;background:#fff;border-radius:8px;padding:12px 14px;border:1px solid var(--border);">' +
                        escAdminHtml(q.texto) +
                    '</div>' +
                '</div>';
            });
            lista.innerHTML = html;
        }

        function escAdminHtml(str) {
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        function marcarLeida(idx) {
            var quejas = JSON.parse(localStorage.getItem('quejas_elcielo') || '[]');
            // Reordenar igual que en cargarQuejas para que el índice coincida
            quejas.sort(function(a,b){
                if (!a.leida && b.leida) return -1;
                if (a.leida && !b.leida) return 1;
                return b.id - a.id;
            });
            if (quejas[idx]) quejas[idx].leida = true;
            localStorage.setItem('quejas_elcielo', JSON.stringify(quejas));
            cargarQuejas();
        }

        function marcarTodasLeidas() {
            var quejas = JSON.parse(localStorage.getItem('quejas_elcielo') || '[]');
            quejas.forEach(function(q){ q.leida = true; });
            localStorage.setItem('quejas_elcielo', JSON.stringify(quejas));
            cargarQuejas();
        }

        function eliminarQueja(idx) {
            var quejas = JSON.parse(localStorage.getItem('quejas_elcielo') || '[]');
            quejas.sort(function(a,b){
                if (!a.leida && b.leida) return -1;
                if (a.leida && !b.leida) return 1;
                return b.id - a.id;
            });
            quejas.splice(idx, 1);
            localStorage.setItem('quejas_elcielo', JSON.stringify(quejas));
            cargarQuejas();
        }

        // Cargar quejas al iniciar y cada 10 segundos
        cargarQuejas();
        setInterval(cargarQuejas, 10000);
    </script>

    <?php if ($alert): ?>
    <script>
        Swal.fire({
            icon: '<?= htmlspecialchars($alert['icon']) ?>',
            title: '<?= htmlspecialchars($alert['title']) ?>',
            text: '<?= htmlspecialchars($alert['text']) ?>',
            confirmButtonColor: '#2dbab5'
        });
    </script>
    <?php endif; ?>

</body>
</html>
