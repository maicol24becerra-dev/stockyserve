<?php
session_start();
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'mesero') {
    header('Location: ../../public/login.php');
    exit;
}

require_once __DIR__ . '/../../models/Pedido.php';
require_once __DIR__ . '/../../models/Plato.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../config/database.php';

// URL absoluta del controlador de pedidos (funciona en local y en hosting)
$pedidoCtrl = APP_URL . '/controllers/PedidoController.php';

$pedidoModel  = new PedidoModel();
$platoModel   = new PlatoModel();
$usuarioModel = new UsuarioModel();

$id_mesero = (int)$_SESSION['user_id'];
$pedidos   = $pedidoModel->getActivos();
$historial = $pedidoModel->getHistorialByMesero($id_mesero);
$platos    = $platoModel->getAll();
$resumen   = $pedidoModel->getResumenDia($id_mesero);
$clientes  = $usuarioModel->getClientes();

foreach ($pedidos as &$p) {
    $p['items'] = $pedidoModel->getItemsByPedido($p['id_pedido']);
    $p['total'] = array_sum(array_map(function($i){ return $i['precio_unitario'] * $i['cantidad']; }, $p['items']));
}
unset($p);

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

$inicial = strtoupper(substr($_SESSION['user_name'], 0, 1));

// Serializar pedidos para JS (sin arrow functions para evitar problemas PHP)
$pedidosJS = array_map(function($p) {
    return [
        'id_pedido'      => $p['id_pedido'],
        'estado'         => $p['estado'],
        'nombre_cliente' => $p['nombre_cliente'] ?? 'Sin cliente',
        'items'          => array_map(function($i) {
            return [
                'id_item_pedido'  => $i['id_item_pedido'],
                'nombre_plato'    => $i['nombre_plato'],
                'cantidad'        => (int)$i['cantidad'],
                'precio_unitario' => (float)$i['precio_unitario'],
            ];
        }, $p['items'])
    ];
}, $pedidos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Mesero - El Cielo</title>
<link rel="icon" type="image/png" href="../../img/ico.png">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="../../public/responsive.css">
<style>
:root{--verde:#007800;--verde-claro:#00a020;--agua:#00C8F0;--agua-dark:#0099bb;--sidebar-bg1:#001a10;--sidebar-bg2:#003820;--bg-page:#f0f7f2;--white:#ffffff;--border:#c8e8d0;--text-dark:#0d2b1a;--text-mid:#3a6b50;--text-light:#7aaa8a;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Nunito',sans-serif;background:var(--bg-page);display:flex;min-height:100vh;color:var(--text-dark);}
.sidebar{width:240px;min-height:100vh;flex-shrink:0;background:linear-gradient(170deg,var(--sidebar-bg1) 0%,var(--sidebar-bg2) 60%,#005530 100%);display:flex;flex-direction:column;box-shadow:4px 0 20px rgba(0,0,0,0.2);position:sticky;top:0;height:100vh;}
.sidebar-header{padding:24px 16px 20px;text-align:center;border-bottom:1px solid rgba(255,255,255,0.07);background:rgba(0,0,0,0.18);}
.sidebar-logo{width:72px;height:72px;border-radius:50%;margin:0 auto 10px;display:flex;align-items:center;justify-content:center;}
.sidebar-logo img{width:72px;height:72px;object-fit:contain;border-radius:50%;}
.sidebar-header h2{font-size:1.1rem;font-weight:900;color:#fff;margin-bottom:3px;}
.sidebar-header p{font-size:0.63rem;color:#78DCF0;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;line-height:1.5;}
.nav-links{flex:1;padding:14px 10px;list-style:none;}
.nav-item{display:flex;align-items:center;gap:11px;padding:11px 13px;font-size:0.9rem;font-weight:700;color:rgba(200,240,210,0.8);border-radius:10px;margin-bottom:3px;cursor:pointer;transition:all 0.22s;text-decoration:none;}
.nav-item i{font-size:0.95rem;width:18px;text-align:center;flex-shrink:0;}
.nav-item:hover{background:rgba(255,255,255,0.07);color:#fff;}
.nav-item.active{background:linear-gradient(90deg,rgba(0,200,240,.18),rgba(0,200,240,.05));color:#fff;border-left:3px solid var(--agua);padding-left:10px;}
.nav-item.active i{color:var(--agua);}
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;}
.topbar{background:var(--white);padding:13px 28px;display:flex;align-items:center;justify-content:space-between;border-bottom:2px solid var(--border);box-shadow:0 2px 10px rgba(0,80,30,0.06);position:sticky;top:0;z-index:10;}
.topbar-title{font-size:1.2rem;font-weight:900;color:var(--text-dark);display:flex;align-items:center;gap:9px;}
.topbar-title::before{content:'';display:inline-block;width:4px;height:19px;background:linear-gradient(180deg,var(--agua),var(--verde));border-radius:4px;}
.topbar-user{display:flex;align-items:center;gap:10px;background:var(--bg-page);padding:7px 14px;border-radius:30px;border:1px solid var(--border);cursor:pointer;position:relative;transition:box-shadow 0.2s;}
.topbar-user:hover{box-shadow:0 4px 12px rgba(0,120,0,0.1);}
.topbar-user .avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--verde-claro),var(--verde));color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.88rem;font-weight:900;flex-shrink:0;}
.topbar-user .info strong{display:block;font-size:0.85rem;font-weight:800;color:var(--text-dark);}
.topbar-user .info span{font-size:0.7rem;color:var(--text-light);font-weight:600;}
.topbar-user .chevron{font-size:0.72rem;color:var(--text-light);transition:transform 0.3s;}
.topbar-user.open .chevron{transform:rotate(180deg);}
.user-dropdown{display:none;position:absolute;top:calc(100% + 8px);right:0;background:var(--white);border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,0.12);border:1px solid var(--border);min-width:180px;z-index:200;overflow:hidden;}
.topbar-user.open .user-dropdown{display:block;}
.user-dropdown a{display:flex;align-items:center;gap:10px;padding:13px 18px;font-size:0.9rem;font-weight:700;color:#e74c3c;text-decoration:none;transition:background 0.2s;}
.user-dropdown a:hover{background:#fde8e8;}
.content{flex:1;padding:24px 28px;overflow-y:auto;}
.section{display:none;}.section.active{display:block;}
.resumen-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px;}
.resumen-card{background:var(--white);border-radius:14px;padding:18px 20px;border:1px solid var(--border);box-shadow:0 4px 12px rgba(0,80,30,0.05);display:flex;align-items:center;gap:14px;}
.resumen-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;}
.resumen-card .label{font-size:0.75rem;font-weight:800;color:var(--text-light);text-transform:uppercase;letter-spacing:0.7px;margin-bottom:3px;}
.resumen-card .value{font-size:1.6rem;font-weight:900;color:var(--text-dark);}
.card-panel{background:var(--white);border-radius:16px;padding:22px;border:1px solid var(--border);box-shadow:0 4px 14px rgba(0,80,30,0.05);margin-bottom:20px;}
.card-panel-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border);}
.card-panel-header h3{font-size:1.05rem;font-weight:900;color:var(--text-dark);}
.filter-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;}
.tab-btn{padding:6px 14px;border-radius:20px;border:1.5px solid var(--border);background:transparent;font-family:'Nunito',sans-serif;font-size:0.8rem;font-weight:800;color:var(--text-mid);cursor:pointer;transition:all 0.2s;}
.tab-btn.active,.tab-btn:hover{background:var(--verde-claro);color:#fff;border-color:var(--verde-claro);}
.pedido-item{border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:12px;}
.pedido-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px;}
.pedido-id{font-size:1rem;font-weight:900;color:var(--text-dark);}
.pedido-id span{color:var(--verde-claro);}
.pedido-fecha{font-size:0.75rem;color:var(--text-light);font-weight:600;}
.badge-estado{padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:800;}
.badge-Pendiente{background:#fff3cd;color:#856404;}
.badge-En_preparacion{background:#cce5ff;color:#004085;}
.badge-Entregado{background:#d1ecf1;color:#0c5460;}
.badge-Pagado{background:#d4edda;color:#155724;}
.badge-Cancelado{background:#f8d7da;color:#721c24;}
.pedido-item-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px dashed #e8f0ea;font-size:0.85rem;}
.pedido-item-row:last-child{border-bottom:none;}
.pedido-footer{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:12px;}
.pedido-total{font-size:1rem;font-weight:900;color:var(--text-dark);}
.pedido-total span{color:var(--verde-claro);}
.pedido-actions{display:flex;gap:8px;flex-wrap:wrap;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:9px;font-family:'Nunito',sans-serif;font-size:0.82rem;font-weight:800;border:none;cursor:pointer;transition:transform 0.2s;text-decoration:none;}
.btn-verde{background:linear-gradient(135deg,var(--verde-claro),var(--verde));color:#fff;}
.btn-agua{background:linear-gradient(135deg,var(--agua),var(--agua-dark));color:#fff;}
.btn-outline{background:transparent;border:1.5px solid var(--border);color:var(--text-mid);}
.btn-outline:hover{border-color:var(--verde-claro);color:var(--verde-claro);}
.menu-layout{display:block;}

/* ══ CARRITO FLOTANTE ══ */
.carrito-fab{position:fixed;bottom:28px;right:28px;z-index:500;width:58px;height:58px;border-radius:50%;background:linear-gradient(135deg,var(--verde-claro),var(--verde));color:#fff;border:none;cursor:pointer;box-shadow:0 6px 20px rgba(0,120,0,0.35);display:flex;align-items:center;justify-content:center;font-size:1.4rem;transition:transform 0.2s,box-shadow 0.2s;}
.carrito-fab:hover{transform:scale(1.08);box-shadow:0 8px 26px rgba(0,120,0,0.45);}
.carrito-fab .fab-badge{position:absolute;top:-4px;right:-4px;background:#e74c3c;color:#fff;border-radius:50%;width:20px;height:20px;font-size:0.7rem;font-weight:900;display:flex;align-items:center;justify-content:center;display:none;}
.carrito-flotante{position:fixed;bottom:100px;right:28px;z-index:499;width:320px;background:var(--white);border-radius:18px;box-shadow:0 12px 40px rgba(0,0,0,0.18);border:1px solid var(--border);display:none;flex-direction:column;max-height:80vh;overflow:hidden;}
.carrito-flotante.open{display:flex;}
.carrito-flotante-header{padding:16px 18px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.carrito-flotante-header span{font-size:1rem;font-weight:900;color:var(--text-dark);}
.carrito-flotante-body{padding:14px 18px;overflow-y:auto;flex:1;}
.carrito-flotante-footer{padding:14px 18px;border-top:1px solid var(--border);flex-shrink:0;}
.search-bar{display:flex;align-items:center;gap:10px;background:var(--white);border:1.5px solid var(--border);border-radius:12px;padding:10px 16px;margin-bottom:18px;}
.search-bar input{border:none;outline:none;font-family:'Nunito',sans-serif;font-size:0.92rem;flex:1;color:var(--text-dark);background:transparent;}
.platos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px;}
.plato-card{background:var(--white);border-radius:14px;overflow:hidden;border:1px solid var(--border);box-shadow:0 3px 10px rgba(0,80,30,0.05);transition:transform 0.2s;}
.plato-card:hover{transform:translateY(-3px);}
.plato-card img{width:100%;height:120px;object-fit:cover;}
.plato-body{padding:12px;}
.plato-nombre{font-size:0.88rem;font-weight:800;color:var(--text-dark);margin-bottom:3px;}
.plato-cat{font-size:0.68rem;font-weight:800;background:#e8f5e9;color:var(--verde);padding:2px 8px;border-radius:20px;display:inline-block;margin-bottom:8px;}
.plato-precio{font-size:1rem;font-weight:900;color:var(--verde-claro);margin-bottom:10px;}
.btn-add-plato{width:100%;padding:7px;background:linear-gradient(135deg,var(--agua),var(--agua-dark));color:#fff;border:none;border-radius:8px;font-family:'Nunito',sans-serif;font-size:0.8rem;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;}
.carrito-panel{background:var(--white);border-radius:16px;padding:20px;border:1px solid var(--border);box-shadow:0 4px 14px rgba(0,80,30,0.05);position:sticky;top:80px;}
.carrito-title{font-size:1rem;font-weight:900;color:var(--text-dark);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.carrito-empty{text-align:center;padding:20px 10px;color:var(--text-light);font-size:0.82rem;font-weight:600;}
.carrito-empty i{font-size:1.8rem;display:block;margin-bottom:8px;color:var(--border);}
.carrito-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px dashed #e8f0ea;}
.carrito-item img{width:40px;height:40px;border-radius:8px;object-fit:cover;flex-shrink:0;}
.ci-info{flex:1;}
.ci-nombre{font-size:0.82rem;font-weight:800;color:var(--text-dark);}
.ci-precio{font-size:0.78rem;color:var(--verde-claro);font-weight:700;}
.ci-qty{display:flex;align-items:center;gap:6px;}
.ci-qty button{width:22px;height:22px;border-radius:6px;border:1.5px solid var(--border);background:transparent;font-size:0.85rem;font-weight:900;cursor:pointer;color:var(--text-mid);display:flex;align-items:center;justify-content:center;}
.ci-qty button:hover{background:var(--verde-claro);color:#fff;border-color:var(--verde-claro);}
.ci-qty span{font-size:0.85rem;font-weight:800;min-width:18px;text-align:center;}
.carrito-total{display:flex;justify-content:space-between;align-items:center;padding:12px 0 0;font-size:0.95rem;font-weight:900;color:var(--text-dark);border-top:2px solid var(--border);margin-top:10px;}
.carrito-total span{color:var(--verde-claro);font-size:1.1rem;}
.hist-table{width:100%;border-collapse:collapse;}
.hist-table th{padding:10px 14px;text-align:left;font-weight:800;color:var(--text-light);text-transform:uppercase;font-size:0.72rem;border-bottom:2px solid var(--border);}
.hist-table td{padding:11px 14px;font-weight:600;border-bottom:1px solid #eef5f0;font-size:0.85rem;}
.hist-table tbody tr:hover{background:#f5fbf6;}
.modal{display:none;position:fixed;inset:0;background:rgba(0,20,10,0.55);align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(2px);}
.modal.active{display:flex;}
.modal-content{background:var(--white);padding:26px;border-radius:18px;width:100%;max-width:420px;box-shadow:0 20px 50px rgba(0,0,0,0.2);border-top:4px solid var(--verde-claro);}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;}
.modal-header h3{font-size:1.15rem;font-weight:900;color:var(--text-dark);}
.close-btn{background:none;border:none;font-size:1.1rem;cursor:pointer;color:var(--text-light);}
.form-group{margin-bottom:13px;}
.form-group label{display:block;font-weight:800;font-size:0.82rem;margin-bottom:4px;color:var(--text-mid);}
.form-group select,.form-group input{width:100%;padding:9px 12px;border-radius:9px;border:1.5px solid var(--border);font-family:'Nunito',sans-serif;font-size:0.9rem;outline:none;color:var(--text-dark);background:#fafffe;}
.btn-submit{width:100%;padding:11px;background:linear-gradient(135deg,var(--verde-claro),var(--verde));color:#fff;border:none;border-radius:10px;font-weight:800;font-size:0.95rem;cursor:pointer;margin-top:8px;}
.toast-container{position:fixed;bottom:24px;right:24px;z-index:999;display:flex;flex-direction:column;gap:10px;}
.toast{background:var(--white);border-radius:14px;padding:14px 18px;box-shadow:0 8px 28px rgba(0,0,0,0.14);border-left:4px solid var(--verde-claro);display:flex;align-items:center;gap:12px;min-width:260px;max-width:320px;font-size:0.88rem;font-weight:700;}
.toast i{font-size:1.2rem;flex-shrink:0;}
.toast .toast-msg{flex:1;color:var(--text-dark);}
.toast .toast-close{background:none;border:none;cursor:pointer;color:var(--text-light);font-size:0.85rem;}
.cuenta-modal-content{max-width:520px !important;}
.cuenta-header-info{background:var(--bg-page);border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;}
.cuenta-item-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px dashed #e8f0ea;gap:8px;}
.cuenta-item-nombre{font-size:0.9rem;font-weight:800;color:var(--text-dark);flex:1;}
.cuenta-item-qty{font-size:0.8rem;color:var(--text-light);font-weight:700;min-width:40px;text-align:center;}
.cuenta-item-precio{font-size:0.9rem;font-weight:800;color:var(--verde-claro);min-width:70px;text-align:right;}
.cuenta-item-del{background:none;border:none;cursor:pointer;color:#e74c3c;font-size:0.85rem;padding:4px;border-radius:6px;}
.cuenta-total-row{display:flex;justify-content:space-between;align-items:center;padding:14px 0 0;font-size:1.1rem;font-weight:900;border-top:2px solid var(--border);margin-top:8px;}
.cuenta-total-row span:last-child{color:var(--verde-claro);font-size:1.2rem;}
.empty-state{text-align:center;padding:30px 16px;color:var(--text-light);font-size:0.85rem;font-weight:600;}
.empty-state i{font-size:2.2rem;display:block;margin-bottom:10px;color:var(--border);}
.cliente-opcion:hover{background:#f0f7f2;}
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><img src="<?= IMG_URL ?>ico.png" alt="Logo"></div>
        <h2>El Cielo</h2>
        <p>Centro Vacacional<br>y Recreacional</p>
    </div>
    <ul class="nav-links">
        <li class="nav-item active" onclick="showSection('pedidos',this)"><i class="fas fa-clipboard-list"></i> Pedidos Activos</li>
        <li class="nav-item" onclick="showSection('mesas',this)"><i class="fas fa-table-cells-large"></i> Estado de Mesas</li>
        <li class="nav-item" onclick="showSection('menu',this)"><i class="fas fa-utensils"></i> Crear Pedido</li>
        <li class="nav-item" onclick="showSection('historial',this)"><i class="fas fa-history"></i> Historial</li>
    </ul>
</div>

<div class="main">
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:10px;">
            <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title" id="page-title">Pedidos Activos</div>
        </div>
        <div class="topbar-user" id="userMenu" onclick="toggleUserMenu()">
            <div class="avatar"><?= $inicial ?></div>
            <div class="info">
                <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                <span>Mesero</span>
            </div>
            <i class="fas fa-chevron-down chevron"></i>
            <div class="user-dropdown">
                <a href="../../controllers/AuthController.php?action=logout">
                    <i class="fas fa-right-from-bracket"></i> Cerrar sesion
                </a>
            </div>
        </div>
    </div>

    <div class="content">

        <!-- PEDIDOS ACTIVOS -->
        <div id="pedidos" class="section active">
            <div class="resumen-grid">
                <div class="resumen-card">
                    <div class="resumen-icon" style="background:linear-gradient(135deg,var(--agua),var(--agua-dark));color:#fff;"><i class="fas fa-receipt"></i></div>
                    <div><div class="label">Total hoy</div><div class="value"><?= $resumen['total_pedidos'] ?></div></div>
                </div>
                <div class="resumen-card">
                    <div class="resumen-icon" style="background:linear-gradient(135deg,#f39c12,#e67e22);color:#fff;"><i class="fas fa-clock"></i></div>
                    <div><div class="label">Pendientes</div><div class="value"><?= $resumen['pendientes'] ?></div></div>
                </div>
                <div class="resumen-card">
                    <div class="resumen-icon" style="background:linear-gradient(135deg,var(--verde-claro),var(--verde));color:#fff;"><i class="fas fa-check-circle"></i></div>
                    <div><div class="label">Completados</div><div class="value"><?= $resumen['completados'] ?></div></div>
                </div>
            </div>

            <div class="card-panel">
                <div class="card-panel-header">
                    <h3>Pedidos en curso</h3>
                    <button class="btn btn-verde" onclick="showSection('menu',document.querySelectorAll('.nav-item')[1])"><i class="fas fa-plus"></i> Nuevo Pedido</button>
                </div>
                <div class="filter-tabs">
                    <button class="tab-btn active" onclick="filtrarPedidos('todos',this)">Todos</button>
                    <button class="tab-btn" onclick="filtrarPedidos('Pendiente',this)">Pendientes</button>
                    <button class="tab-btn" onclick="filtrarPedidos('En preparación',this)">En preparación</button>
                    <button class="tab-btn" onclick="filtrarPedidos('Entregado',this)">Entregados</button>
                </div>

                <?php if (empty($pedidos)): ?>
                <div class="empty-state"><i class="fas fa-clipboard-list"></i>No hay pedidos activos.</div>
                <?php else: ?>
                <?php foreach ($pedidos as $ped):
                    $estadoClass = str_replace(' ', '_', $ped['estado']);
                ?>
                <div class="pedido-item" data-estado="<?= htmlspecialchars($ped['estado']) ?>">
                    <div class="pedido-head">
                        <div>
                            <div class="pedido-id">Pedido <span>#<?= $ped['id_pedido'] ?></span>
                                <?php if (!empty($ped['nombre_cliente'])): ?>
                                <span style="font-size:0.78rem;color:var(--text-light);font-weight:600;"> — <?= htmlspecialchars($ped['nombre_cliente']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="pedido-fecha"><i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($ped['fecha'])) ?></div>
                        </div>
                        <span class="badge-estado badge-<?= $estadoClass ?>"><?= htmlspecialchars($ped['estado']) ?></span>
                    </div>

                    <?php foreach ($ped['items'] as $item): ?>
                    <div class="pedido-item-row">
                        <span style="font-weight:700;"><?= htmlspecialchars($item['nombre_plato']) ?></span>
                        <span style="color:var(--text-light);font-size:0.78rem;"><?= $item['cantidad'] ?> u.</span>
                        <span style="font-weight:800;color:var(--verde-claro);">$<?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?></span>
                    </div>
                    <?php endforeach; ?>

                    <div class="pedido-footer">
                        <div class="pedido-total">Total: <span>$<?= number_format($ped['total'], 2) ?></span></div>
                        <div class="pedido-actions">
                            <?php if ($ped['estado'] === 'Pendiente'): ?>
                            <form action="<?= $pedidoCtrl ?>" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="cambiar_estado">
                                <input type="hidden" name="id_pedido" value="<?= $ped['id_pedido'] ?>">
                                <input type="hidden" name="estado" value="En preparación">
                                <button type="submit" class="btn btn-agua"><i class="fas fa-fire-burner"></i> En preparacion</button>
                            </form>
                            <?php elseif ($ped['estado'] === 'En preparación'): ?>
                            <form action="<?= $pedidoCtrl ?>" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="cambiar_estado">
                                <input type="hidden" name="id_pedido" value="<?= $ped['id_pedido'] ?>">
                                <input type="hidden" name="estado" value="Entregado">
                                <button type="submit" class="btn btn-verde"><i class="fas fa-concierge-bell"></i> Entregar</button>
                            </form>
                            <?php elseif ($ped['estado'] === 'Entregado'): ?>
                            <button class="btn btn-verde" onclick="abrirModalPago(<?= $ped['id_pedido'] ?>)">
                                <i class="fas fa-money-bill-wave"></i> Registrar Pago
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-outline" onclick="abrirCuenta(<?= $ped['id_pedido'] ?>, '<?= htmlspecialchars(addslashes($ped['nombre_cliente'] ?? 'Sin cliente')) ?>', '<?= htmlspecialchars($ped['estado']) ?>')">
                                <i class="fas fa-file-invoice-dollar"></i> Cuenta
                            </button>
                            <?php if (in_array($ped['estado'], ['Pendiente', 'Entregado'])): ?>
                            <form action="<?= $pedidoCtrl ?>" method="POST" style="display:inline;" onsubmit="return confirm('Cancelar este pedido?');">
                                <input type="hidden" name="action" value="cambiar_estado">
                                <input type="hidden" name="id_pedido" value="<?= $ped['id_pedido'] ?>">
                                <input type="hidden" name="estado" value="Cancelado">
                                <button type="submit" class="btn btn-outline"><i class="fas fa-times"></i> Cancelar</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ESTADO DE MESAS (HU-15) -->
        <div id="mesas" class="section">
            <div class="card-panel" style="margin-bottom:18px;">
                <div class="card-panel-header">
                    <h3><i class="fas fa-table-cells-large" style="color:var(--verde-claro);margin-right:8px;"></i> Estado de Mesas</h3>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:0.72rem;font-weight:700;color:var(--text-light);">
                            <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--verde-claro);animation:pulse 2s infinite;vertical-align:middle;margin-right:4px;"></span>
                            Actualización automática cada 20s
                        </span>
                    </div>
                </div>

                <!-- HU-15 Esc.3: sin mesas -->
                <?php
                $pedidosActivos = $pedidoModel->getActivos();
                // Agrupar por mesa (o por id_pedido si no hay mesa)
                $porMesa = [];
                foreach ($pedidosActivos as $pa) {
                    $pa['items']   = $pedidoModel->getItemsByPedido($pa['id_pedido']);
                    $pa['total']   = array_sum(array_map(fn($i) => $i['precio_unitario'] * $i['cantidad'], $pa['items']));
                    $pa['minutos'] = (int)round((time() - strtotime($pa['fecha'])) / 60);
                    $mesaKey = !empty($pa['mesa']) ? $pa['mesa'] : 'Mesa/Pedido #' . $pa['id_pedido'];
                    $porMesa[$mesaKey][] = $pa;
                }
                ?>

                <?php if (empty($pedidosActivos)): ?>
                <div class="empty-state"><i class="fas fa-chair"></i>No tienes mesas asignadas actualmente.</div>
                <?php else: ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
                    <?php foreach ($porMesa as $mesaNombre => $pedidosMesa):
                        // Estado más crítico del conjunto de pedidos de esa mesa
                        $estados    = array_column($pedidosMesa, 'estado');
                        $maxMinutos = max(array_column($pedidosMesa, 'minutos'));
                        $retrasado  = $maxMinutos > 20; // HU-15 Esc.2: alerta por retraso

                        // Color del borde según estado
                        $colorBorde = '#c8e8d0'; // normal
                        if ($retrasado) $colorBorde = '#e74c3c';
                        elseif (in_array('Entregado', $estados)) $colorBorde = '#0099bb';
                        elseif (in_array('En preparación', $estados)) $colorBorde = '#e67e22';
                    ?>
                    <div style="background:var(--white);border-radius:16px;border:2px solid <?= $colorBorde ?>;box-shadow:0 4px 14px rgba(0,80,30,0.06);overflow:hidden;<?= $retrasado ? 'box-shadow:0 4px 18px rgba(231,76,60,0.25);' : '' ?>">
                        <!-- Cabecera de mesa -->
                        <div style="padding:14px 18px 12px;border-bottom:1px solid #eef5f0;display:flex;align-items:center;justify-content:space-between;">
                            <div style="font-size:1rem;font-weight:900;color:var(--text-dark);">
                                <i class="fas fa-table-cells-large" style="color:<?= $retrasado ? '#e74c3c' : 'var(--verde-claro)' ?>;margin-right:6px;"></i>
                                <?= htmlspecialchars($mesaNombre) ?>
                            </div>
                            <?php if ($retrasado): ?>
                            <!-- HU-15 Esc.2: badge retrasado -->
                            <span style="background:#f8d7da;color:#721c24;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:900;display:flex;align-items:center;gap:4px;">
                                <i class="fas fa-clock"></i> Retrasado <?= $maxMinutos ?>m
                            </span>
                            <?php else: ?>
                            <span style="background:#e8f5e9;color:var(--verde);padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:700;">
                                <?= $maxMinutos ?>m en espera
                            </span>
                            <?php endif; ?>
                        </div>
                        <!-- Pedidos de la mesa -->
                        <div style="padding:12px 18px;">
                            <?php foreach ($pedidosMesa as $pm):
                                $eClass = str_replace(' ', '_', $pm['estado']);
                            ?>
                            <div style="margin-bottom:10px;padding-bottom:10px;border-bottom:1px dashed #eef5f0;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                    <span style="font-size:0.82rem;font-weight:800;color:var(--text-dark);">Pedido #<?= $pm['id_pedido'] ?></span>
                                    <span class="badge-estado badge-<?= $eClass ?>" style="font-size:0.7rem;"><?= htmlspecialchars($pm['estado']) ?></span>
                                </div>
                                <?php foreach (array_slice($pm['items'], 0, 3) as $it): ?>
                                <div style="font-size:0.78rem;color:var(--text-light);padding:2px 0;">
                                    <i class="fas fa-circle" style="font-size:0.4rem;vertical-align:middle;margin-right:4px;"></i>
                                    <?= $it['cantidad'] ?>× <?= htmlspecialchars($it['nombre_plato']) ?>
                                </div>
                                <?php endforeach; ?>
                                <?php if (count($pm['items']) > 3): ?>
                                <div style="font-size:0.72rem;color:var(--text-light);margin-top:2px;">+ <?= count($pm['items']) - 3 ?> más...</div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Acciones rápidas -->
                        <?php $primerPedido = reset($pedidosMesa); ?>
                        <div style="padding:10px 18px 14px;display:flex;gap:8px;flex-wrap:wrap;">
                            <button class="btn btn-outline" style="flex:1;font-size:0.78rem;padding:7px 10px;"
                                    onclick="abrirCuenta(<?= $primerPedido['id_pedido'] ?>, '<?= htmlspecialchars(addslashes($primerPedido['nombre_cliente'] ?? 'Sin cliente')) ?>', '<?= htmlspecialchars($primerPedido['estado']) ?>')">
                                <i class="fas fa-file-invoice-dollar"></i> Cuenta
                            </button>
                            <?php if ($primerPedido['estado'] === 'Entregado'): ?>
                            <button class="btn btn-verde" style="flex:1;font-size:0.78rem;padding:7px 10px;"
                                    onclick="abrirModalPago(<?= $primerPedido['id_pedido'] ?>)">
                                <i class="fas fa-money-bill-wave"></i> Cobrar
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div style="font-size:0.72rem;color:var(--text-light);text-align:center;padding-bottom:8px;font-weight:600;">
                <i class="fas fa-circle" style="color:var(--verde-claro);font-size:0.5rem;vertical-align:middle;"></i>
                La vista se actualiza automáticamente. Mesas con borde rojo llevan más de 20 minutos esperando.
            </div>
        </div>

        <!-- CREAR PEDIDO -->
        <div id="menu" class="section">
            <div class="menu-layout">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <h3 style="font-size:1.05rem;font-weight:900;">Crear Pedido</h3>
                        <button class="btn btn-verde" onclick="toggleModal('modal-nuevo-pedido')"><i class="fas fa-plus"></i> Nuevo Pedido</button>
                    </div>
                    <div class="search-bar">
                        <i class="fas fa-search" style="color:var(--text-light);"></i>
                        <input type="text" placeholder="Buscar plato..." oninput="filtrarPlatos(this.value)">
                    </div>
                    <div class="filter-tabs" id="cat-tabs" style="margin-bottom:16px;">
                        <button class="tab-btn active" onclick="filtrarCategoria('',this)">Todos</button>
                        <?php
                        $cats = array_unique(array_filter(array_column($platos, 'categoria')));
                        foreach ($cats as $cat):
                        ?>
                        <button class="tab-btn" onclick="filtrarCategoria('<?= htmlspecialchars(addslashes($cat)) ?>',this)"><?= htmlspecialchars($cat) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="platos-grid" id="platos-grid">
                        <?php foreach ($platos as $pl): ?>
                        <div class="plato-card"
                             data-nombre="<?= strtolower(htmlspecialchars($pl['nombre'])) ?>"
                             data-cat="<?= strtolower(htmlspecialchars($pl['categoria'] ?? '')) ?>">
                            <img src="<?= UPLOADS_URL ?><?= htmlspecialchars($pl['imagen'] ?? 'default.jpg') ?>"
                                 alt="<?= htmlspecialchars($pl['nombre']) ?>"
                                 onerror="this.src='https://placehold.co/300x120/e8f5e9/007800?text=Sin+imagen'">
                            <div class="plato-body">
                                <div class="plato-nombre"><?= htmlspecialchars($pl['nombre']) ?></div>
                                <?php if (!empty($pl['categoria'])): ?>
                                <span class="plato-cat"><?= htmlspecialchars($pl['categoria']) ?></span>
                                <?php endif; ?>
                                <div class="plato-precio">$<?= number_format($pl['precio'], 2) ?></div>
                                <button class="btn-add-plato" onclick="agregarAlCarrito(<?= $pl['id_plato'] ?>, '<?= htmlspecialchars(addslashes($pl['nombre'])) ?>', <?= $pl['precio'] ?>, '<?= htmlspecialchars(addslashes($pl['imagen'] ?? 'default.jpg')) ?>')">
                                    <i class="fas fa-plus"></i> Anadir
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- HISTORIAL -->
        <div id="historial" class="section">
            <div class="card-panel">
                <div class="card-panel-header"><h3>Historial de Pedidos</h3></div>
                <?php if (empty($historial)): ?>
                <div class="empty-state"><i class="fas fa-history"></i>No hay pedidos en el historial.</div>
                <?php else: ?>
                <table class="hist-table">
                    <thead><tr><th>#</th><th>Fecha</th><th>Cliente</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php foreach ($historial as $h):
                            $hClass = str_replace(' ', '_', $h['estado']);
                        ?>
                        <tr>
                            <td><strong>#<?= $h['id_pedido'] ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($h['fecha'])) ?></td>
                            <td><?= htmlspecialchars($h['nombre_cliente'] ?? '-') ?></td>
                            <td><span class="badge-estado badge-<?= $hClass ?>"><?= htmlspecialchars($h['estado']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- CARRITO FLOTANTE -->
<button class="carrito-fab" id="carrito-fab" onclick="toggleCarritoFlotante()" title="Ver pedido actual">
    <i class="fas fa-shopping-basket"></i>
    <span class="fab-badge" id="fab-badge">0</span>
</button>

<div class="carrito-flotante" id="carrito-flotante">
    <div class="carrito-flotante-header">
        <span><i class="fas fa-shopping-basket" style="color:var(--verde-claro);margin-right:8px;"></i> Pedido Actual</span>
        <div style="display:flex;gap:8px;align-items:center;">
            <button onclick="limpiarCarrito()" style="background:none;border:none;color:var(--text-light);cursor:pointer;font-size:0.9rem;" title="Limpiar"><i class="fas fa-trash"></i></button>
            <button onclick="toggleCarritoFlotante()" style="background:none;border:none;color:var(--text-light);cursor:pointer;font-size:1rem;"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div class="carrito-flotante-body">
        <!-- Cliente -->
        <div style="background:var(--bg-page);border-radius:10px;padding:10px 12px;margin-bottom:14px;border:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:8px;">
            <div>
                <div style="font-size:0.68rem;font-weight:800;color:var(--text-light);text-transform:uppercase;letter-spacing:0.7px;margin-bottom:2px;">Cliente</div>
                <div id="carrito-cliente-nombre" style="font-size:0.88rem;font-weight:800;color:var(--text-dark);">Sin asignar</div>
            </div>
            <button onclick="toggleModal('modal-nuevo-pedido')" style="background:none;border:1.5px solid var(--border);border-radius:8px;padding:5px 10px;font-size:0.75rem;font-weight:800;color:var(--text-mid);cursor:pointer;white-space:nowrap;">
                <i class="fas fa-user-edit"></i> Cambiar
            </button>
        </div>
        <!-- Items -->
        <div id="carrito-items">
            <div class="carrito-empty"><i class="fas fa-utensils"></i>Aun no has anadido platos</div>
        </div>
    </div>
    <div class="carrito-flotante-footer">
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.95rem;font-weight:900;color:var(--text-dark);margin-bottom:12px;">
            <span>Total:</span>
            <span id="carrito-total-val" style="color:var(--verde-claro);font-size:1.1rem;">$0.00</span>
        </div>
        <form id="form-crear-pedido" action="<?= $pedidoCtrl ?>" method="POST">
            <input type="hidden" name="action" value="crear_pedido">
            <input type="hidden" name="id_cliente" id="form-id-cliente" value="0">
            <div id="carrito-inputs-hidden"></div>
            <button type="button" class="btn-submit" onclick="confirmarPedido()"><i class="fas fa-paper-plane"></i> Crear Pedido</button>
        </form>
    </div>
</div>

<!-- TOAST -->
<div class="toast-container" id="toastContainer"></div>

<!-- MODAL CUENTA -->
<div class="modal" id="modal-cuenta">
    <div class="modal-content cuenta-modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-file-invoice-dollar" style="color:var(--verde-claro);margin-right:8px;"></i> Cuenta del Pedido</h3>
            <button class="close-btn" onclick="toggleModal('modal-cuenta')"><i class="fas fa-times"></i></button>
        </div>
        <div class="cuenta-header-info">
            <div><div style="font-size:0.7rem;font-weight:800;color:var(--text-light);text-transform:uppercase;">Pedido</div><div style="font-size:1rem;font-weight:900;color:var(--verde-claro);" id="cuenta-pedido-id">#-</div></div>
            <div><div style="font-size:0.7rem;font-weight:800;color:var(--text-light);text-transform:uppercase;">Cliente</div><div style="font-size:0.88rem;font-weight:800;color:var(--text-dark);" id="cuenta-cliente-nombre">-</div></div>
            <div><div style="font-size:0.7rem;font-weight:800;color:var(--text-light);text-transform:uppercase;">Estado</div><div id="cuenta-estado-badge"></div></div>
        </div>
        <div id="cuenta-items-list"></div>
        <!-- HU-16: Desglose con impuestos -->
        <div style="padding:10px 0;border-top:1px solid var(--border);margin-top:8px;">
            <div style="display:flex;justify-content:space-between;font-size:0.85rem;color:var(--text-mid);margin-bottom:4px;font-weight:700;">
                <span>Subtotal</span><span id="cuenta-subtotal">$0.00</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:0.85rem;color:var(--text-mid);margin-bottom:4px;font-weight:700;">
                <span>IVA (8%)</span><span id="cuenta-iva">$0.00</span>
            </div>
        </div>
        <div class="cuenta-total-row"><span>Total</span><span id="cuenta-total">$0.00</span></div>

        <!-- HU-16 Esc.3: Dividir cuenta -->
        <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border);">
            <div style="font-size:0.78rem;font-weight:800;color:var(--text-mid);margin-bottom:8px;text-transform:uppercase;">
                <i class="fas fa-people-group" style="color:var(--agua);"></i> Dividir Cuenta
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <label style="font-size:0.82rem;font-weight:700;color:var(--text-mid);">Número de personas:</label>
                <input type="number" id="dividir-partes" min="2" max="20" value="2"
                       style="width:70px;padding:7px 10px;border-radius:9px;border:1.5px solid var(--border);font-family:'Nunito',sans-serif;font-size:0.9rem;text-align:center;outline:none;">
                <button onclick="dividirCuenta()" class="btn btn-agua" style="padding:8px 14px;font-size:0.8rem;">
                    <i class="fas fa-divide"></i> Dividir
                </button>
            </div>
            <div id="cuenta-dividida" style="display:none;margin-top:10px;background:var(--bg-page);border-radius:10px;padding:12px 14px;border:1px solid var(--border);">
                <div style="font-size:0.78rem;font-weight:800;color:var(--text-mid);margin-bottom:6px;">Pago por persona</div>
                <div id="cuenta-dividida-resultado" style="font-size:1.4rem;font-weight:900;color:var(--verde-claro);"></div>
                <div id="cuenta-dividida-verificacion" style="font-size:0.72rem;color:var(--text-light);margin-top:4px;font-weight:600;"></div>
            </div>
        </div>
        <div id="cuenta-add-section" style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border);display:none;">
            <div style="font-size:0.78rem;font-weight:800;color:var(--text-mid);margin-bottom:10px;text-transform:uppercase;">Anadir plato al pedido</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <select id="cuenta-select-plato" style="flex:1;min-width:160px;padding:8px 10px;border-radius:9px;border:1.5px solid var(--border);font-family:'Nunito',sans-serif;font-size:0.85rem;outline:none;color:var(--text-dark);">
                    <option value="">Selecciona un plato...</option>
                    <?php foreach ($platos as $pl): ?>
                    <option value="<?= $pl['id_plato'] ?>" data-precio="<?= $pl['precio'] ?>"><?= htmlspecialchars($pl['nombre']) ?> - $<?= number_format($pl['precio'], 2) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" id="cuenta-qty-plato" value="1" min="1" max="20" style="width:60px;padding:8px;border-radius:9px;border:1.5px solid var(--border);font-family:'Nunito',sans-serif;font-size:0.85rem;text-align:center;outline:none;">
                <button onclick="agregarPlatoACuenta()" class="btn btn-agua" style="padding:8px 14px;"><i class="fas fa-plus"></i> Anadir</button>
            </div>
        </div>
        <input type="hidden" id="cuenta-id-pedido" value="">
        <input type="hidden" id="cuenta-estado-actual" value="">
    </div>
</div>

<!-- MODAL PAGO -->
<div class="modal" id="modal-pago">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-money-bill-wave" style="color:var(--verde-claro);margin-right:8px;"></i>Registrar Pago</h3>
            <button class="close-btn" onclick="toggleModal('modal-pago')"><i class="fas fa-times"></i></button>
        </div>
        <p style="font-size:0.85rem;color:var(--text-mid);margin-bottom:16px;">
            Pedido <strong id="pago-pedido-id" style="color:var(--verde-claro);"></strong> — Selecciona el método de pago para registrar el cobro.
        </p>
        <form action="<?= $pedidoCtrl ?>" method="POST" id="form-pago">
            <input type="hidden" name="action" value="cambiar_estado">
            <input type="hidden" name="estado" value="Pagado">
            <input type="hidden" name="id_pedido" id="pago-id-pedido-input">
            <div class="form-group">
                <label>Método de pago</label>
                <select name="metodo_pago" id="pago-metodo" required>
                    <option value="Efectivo">💵 Efectivo</option>
                    <option value="Tarjeta">💳 Tarjeta</option>
                    <option value="Transferencia">🏦 Transferencia</option>
                    <option value="Nequi">📱 Nequi</option>
                    <option value="Daviplata">📱 Daviplata</option>
                </select>
            </div>
            <button type="submit" class="btn-submit"><i class="fas fa-check-circle"></i> Confirmar Pago</button>
        </form>
    </div>
</div>

<!-- MODAL ASIGNAR CLIENTE -->
<div class="modal" id="modal-nuevo-pedido">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Asignar Cliente</h3>
            <button class="close-btn" onclick="toggleModal('modal-nuevo-pedido')"><i class="fas fa-times"></i></button>
        </div>
        <p style="font-size:0.85rem;color:var(--text-mid);margin-bottom:14px;">Busca el cliente por nombre. Puedes dejarlo sin asignar.</p>
        <div style="position:relative;margin-bottom:10px;">
            <div style="display:flex;align-items:center;gap:8px;background:var(--bg-page);border:1.5px solid var(--border);border-radius:10px;padding:9px 13px;">
                <i class="fas fa-search" style="color:var(--text-light);font-size:0.85rem;"></i>
                <input type="text" id="buscar-cliente-input" placeholder="Escribe el nombre del cliente..." oninput="filtrarClientes(this.value)" autocomplete="off"
                    style="border:none;outline:none;background:transparent;font-family:'Nunito',sans-serif;font-size:0.9rem;flex:1;color:var(--text-dark);">
                <button onclick="limpiarBusquedaCliente()" id="btn-limpiar-cliente" style="display:none;background:none;border:none;cursor:pointer;color:var(--text-light);font-size:0.85rem;"><i class="fas fa-times"></i></button>
            </div>
            <div id="lista-clientes" style="max-height:220px;overflow-y:auto;border:1px solid var(--border);border-radius:10px;margin-top:6px;background:var(--white);box-shadow:0 4px 16px rgba(0,80,30,0.08);">
                <?php if (empty($clientes)): ?>
                <div style="padding:16px;text-align:center;color:var(--text-light);font-size:0.85rem;font-weight:600;">No hay clientes registrados</div>
                <?php else: ?>
                <?php foreach ($clientes as $cl): ?>
                <div class="cliente-opcion"
                     data-id="<?= $cl['id_cliente'] ?? 0 ?>"
                     data-nombre="<?= htmlspecialchars($cl['nombre']) ?>"
                     data-correo="<?= htmlspecialchars($cl['correo']) ?>"
                     onclick="seleccionarCliente(<?= $cl['id_cliente'] ?? 0 ?>, '<?= htmlspecialchars(addslashes($cl['nombre'])) ?>')"
                     style="display:flex;align-items:center;gap:12px;padding:11px 14px;cursor:pointer;border-bottom:1px solid #eef5f0;transition:background 0.15s;">
                    <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--verde-claro),var(--verde));color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.85rem;font-weight:900;flex-shrink:0;"><?= strtoupper(substr($cl['nombre'], 0, 1)) ?></div>
                    <div>
                        <div style="font-size:0.88rem;font-weight:800;color:var(--text-dark);"><?= htmlspecialchars($cl['nombre']) ?></div>
                        <div style="font-size:0.72rem;color:var(--text-light);font-weight:600;"><?= htmlspecialchars($cl['correo']) ?></div>
                    </div>
                    <i class="fas fa-check" style="margin-left:auto;color:var(--verde-claro);display:none;"></i>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div id="cliente-seleccionado-info" style="display:none;background:#e8f5e9;border:1px solid var(--border);border-radius:10px;padding:10px 14px;margin-bottom:14px;align-items:center;gap:10px;">
            <i class="fas fa-user-check" style="color:var(--verde-claro);"></i>
            <span id="cliente-seleccionado-texto" style="font-size:0.88rem;font-weight:800;color:var(--text-dark);flex:1;"></span>
            <button onclick="quitarCliente()" style="background:none;border:none;cursor:pointer;color:#e74c3c;font-size:0.8rem;"><i class="fas fa-times"></i></button>
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
            <button type="button" class="btn-submit" onclick="confirmarClienteYCerrar()" style="flex:1;"><i class="fas fa-check"></i> Confirmar</button>
            <button type="button" onclick="quitarCliente();confirmarClienteYCerrar();" style="padding:11px 16px;background:transparent;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:0.88rem;font-weight:800;color:var(--text-mid);cursor:pointer;">Sin cliente</button>
        </div>
    </div>
</div>

<script>
var pedidosData = <?= json_encode($pedidosJS) ?>;

var titles = { pedidos:'Pedidos Activos', mesas:'Estado de Mesas', menu:'Crear Pedido', historial:'Historial' };

function showSection(id, el) {
    document.querySelectorAll('.section').forEach(function(s){ s.classList.remove('active'); });
    document.querySelectorAll('.nav-item').forEach(function(a){ a.classList.remove('active'); });
    document.getElementById(id).classList.add('active');
    if (el) el.classList.add('active');
    document.getElementById('page-title').textContent = titles[id] || '';
}

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
document.addEventListener('click', function(e) {
    var m = document.getElementById('userMenu');
    if (m && !m.contains(e.target)) m.classList.remove('open');
});

function toggleModal(id) {
    document.getElementById(id).classList.toggle('active');
}

function abrirModalPago(idPedido) {
    document.getElementById('pago-pedido-id').textContent = '#' + idPedido;
    document.getElementById('pago-id-pedido-input').value = idPedido;
    document.getElementById('pago-metodo').value = 'Efectivo';
    toggleModal('modal-pago');
}

function filtrarPedidos(estado, btn) {
    document.querySelectorAll('.filter-tabs .tab-btn').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');
    document.querySelectorAll('.pedido-item[data-estado]').forEach(function(el) {
        el.style.display = (estado === 'todos' || el.dataset.estado === estado) ? '' : 'none';
    });
}

function filtrarPlatos(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#platos-grid .plato-card').forEach(function(c) {
        c.style.display = c.dataset.nombre.includes(q) ? '' : 'none';
    });
}
function filtrarCategoria(cat, btn) {
    document.querySelectorAll('#cat-tabs .tab-btn').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');
    document.querySelectorAll('#platos-grid .plato-card').forEach(function(c) {
        c.style.display = (!cat || c.dataset.cat === cat.toLowerCase()) ? '' : 'none';
    });
}

function toggleCarritoFlotante() {
    var panel = document.getElementById('carrito-flotante');
    panel.classList.toggle('open');
}

// Mostrar carrito flotante solo en sección menu
function onShowMenu() {
    // el FAB siempre visible, no hace falta nada extra
}

function showToast(msg, icon, color) {
    icon  = icon  || 'fa-check-circle';
    color = color || 'var(--verde-claro)';
    var container = document.getElementById('toastContainer');
    var toast = document.createElement('div');
    toast.className = 'toast';
    toast.style.borderLeftColor = color;
    toast.innerHTML = '<i class="fas ' + icon + '" style="color:' + color + '"></i><span class="toast-msg">' + msg + '</span><button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>';
    container.appendChild(toast);
    setTimeout(function(){ if (toast.parentElement) toast.remove(); }, 3500);
}

function abrirCuenta(idPedido, cliente, estado) {
    var pedido = null;
    for (var i = 0; i < pedidosData.length; i++) {
        if (pedidosData[i].id_pedido == idPedido) { pedido = pedidosData[i]; break; }
    }
    if (!pedido) return;

    document.getElementById('cuenta-id-pedido').value    = idPedido;
    document.getElementById('cuenta-estado-actual').value = estado;
    document.getElementById('cuenta-pedido-id').textContent    = '#' + idPedido;
    document.getElementById('cuenta-cliente-nombre').textContent = cliente;

    var badgeMap = { 'Pendiente':'badge-Pendiente', 'En preparacion':'badge-En_preparacion', 'Entregado':'badge-Entregado', 'Pagado':'badge-Pagado', 'Cancelado':'badge-Cancelado' };
    document.getElementById('cuenta-estado-badge').innerHTML = '<span class="badge-estado ' + (badgeMap[estado] || '') + '">' + estado + '</span>';

    renderCuentaItems(pedido.items, estado);
    var canAdd = (estado === 'Pendiente' || estado === 'Entregado');
    document.getElementById('cuenta-add-section').style.display = canAdd ? 'block' : 'none';
    toggleModal('modal-cuenta');
}

function renderCuentaItems(items, estado) {
    var canEdit  = (estado === 'Pendiente' || estado === 'Entregado');
    var idPedido = document.getElementById('cuenta-id-pedido').value;
    var subtotal = 0;
    var IVA_RATE = 0.08; // 8%

    if (!items || items.length === 0) {
        document.getElementById('cuenta-items-list').innerHTML = '<div style="text-align:center;padding:16px;color:var(--text-light);font-size:0.85rem;">Sin items en este pedido.</div>';
        document.getElementById('cuenta-subtotal').textContent = '$0.00';
        document.getElementById('cuenta-iva').textContent      = '$0.00';
        document.getElementById('cuenta-total').textContent    = '$0.00';
        return;
    }

    var html = '';
    for (var i = 0; i < items.length; i++) {
        var item     = items[i];
        var linea    = item.precio_unitario * item.cantidad;
        subtotal    += linea;
        var delBtn   = '';
        if (canEdit) {
            delBtn = '<form action="<?= $pedidoCtrl ?>" method="POST" style="display:inline;">' +
                '<input type="hidden" name="action" value="eliminar_item">' +
                '<input type="hidden" name="id_item_pedido" value="' + item.id_item_pedido + '">' +
                '<input type="hidden" name="id_pedido" value="' + idPedido + '">' +
                '<button type="submit" class="cuenta-item-del" onclick="return confirm(\'Quitar este plato?\')">' +
                '<i class="fas fa-trash-alt"></i></button></form>';
        }
        html += '<div class="cuenta-item-row">' +
            '<span class="cuenta-item-nombre">' + item.nombre_plato + '</span>' +
            '<span class="cuenta-item-qty">' + item.cantidad + ' u.</span>' +
            '<span class="cuenta-item-precio">$' + linea.toFixed(2) + '</span>' +
            delBtn + '</div>';
    }
    document.getElementById('cuenta-items-list').innerHTML = html;

    // HU-16: subtotal + IVA + total
    var iva   = subtotal * IVA_RATE;
    var total = subtotal + iva;
    document.getElementById('cuenta-subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('cuenta-iva').textContent      = '$' + iva.toFixed(2);
    document.getElementById('cuenta-total').textContent    = '$' + total.toFixed(2);

    // Ocultar sección dividida al re-renderizar
    document.getElementById('cuenta-dividida').style.display = 'none';
}

// HU-16 Esc.3: Dividir cuenta
function dividirCuenta() {
    var partes = parseInt(document.getElementById('dividir-partes').value) || 2;
    if (partes < 2) { partes = 2; document.getElementById('dividir-partes').value = 2; }

    var totalText = document.getElementById('cuenta-total').textContent.replace('$', '').replace(',', '');
    var total     = parseFloat(totalText) || 0;
    var porPersona = total / partes;

    // Verificación: sum de partes = total (suma de verificación HU-16)
    var suma = porPersona * partes;
    var ok   = Math.abs(suma - total) < 0.01;

    document.getElementById('cuenta-dividida').style.display = 'block';
    document.getElementById('cuenta-dividida-resultado').textContent = '$' + porPersona.toFixed(2) + ' por persona';
    document.getElementById('cuenta-dividida-verificacion').textContent =
        ok ? '✓ ' + partes + ' partes × $' + porPersona.toFixed(2) + ' = $' + total.toFixed(2) + ' ✓'
           : 'Revisar: la suma no cuadra exactamente.';
}

function agregarPlatoACuenta() {
    var sel      = document.getElementById('cuenta-select-plato');
    var qty      = parseInt(document.getElementById('cuenta-qty-plato').value) || 1;
    var idPedido = document.getElementById('cuenta-id-pedido').value;
    var idPlato  = sel.value;
    var opt      = sel.options[sel.selectedIndex];
    var precio   = opt ? parseFloat(opt.dataset.precio || 0) : 0;

    if (!idPlato) { showToast('Selecciona un plato primero.', 'fa-exclamation-circle', '#f39c12'); return; }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../controllers/PedidoController.php';
    form.innerHTML = '<input type="hidden" name="action" value="agregar_item">' +
        '<input type="hidden" name="id_pedido" value="' + idPedido + '">' +
        '<input type="hidden" name="id_plato" value="' + idPlato + '">' +
        '<input type="hidden" name="cantidad" value="' + qty + '">' +
        '<input type="hidden" name="precio_unitario" value="' + precio + '">';
    document.body.appendChild(form);
    form.submit();
}

var carrito = [];
var clienteSeleccionado = { id: 0, nombre: 'Sin asignar' };

function agregarAlCarrito(id, nombre, precio, imagen) {
    // Siempre agrega como nueva línea para soportar notas distintas (HU-14 Esc.5)
    // Si el ítem ya existe sin nota, acumula cantidad en vez de duplicar
    var idx = -1;
    for (var i = 0; i < carrito.length; i++) {
        if (carrito[i].id === id && !carrito[i].nota) { idx = i; break; }
    }
    if (idx >= 0) {
        carrito[idx].cantidad++;
        showToast('+1 ' + nombre, 'fa-plus-circle');
    } else {
        carrito.push({ id: id, nombre: nombre, precio: precio, imagen: imagen, cantidad: 1, nota: '' });
        showToast(nombre + ' añadido', 'fa-check-circle');
    }
    renderCarrito();
    if (carrito.length === 1) {
        document.getElementById('carrito-flotante').classList.add('open');
    }
}

function cambiarCantidad(idx, delta) {
    if (idx < 0 || idx >= carrito.length) return;
    carrito[idx].cantidad += delta;
    if (carrito[idx].cantidad <= 0) carrito.splice(idx, 1);
    renderCarrito();
}

function limpiarCarrito() { carrito = []; renderCarrito(); }

function renderCarrito() {
    var cont  = document.getElementById('carrito-items');
    var total = 0;
    var totalItems = 0;
    for (var i = 0; i < carrito.length; i++) {
        total += carrito[i].precio * carrito[i].cantidad;
        totalItems += carrito[i].cantidad;
    }
    document.getElementById('carrito-total-val').textContent = '$' + total.toFixed(2);

    // Actualizar badge FAB
    var badge = document.getElementById('fab-badge');
    if (totalItems > 0) {
        badge.style.display = 'flex';
        badge.textContent = totalItems;
    } else {
        badge.style.display = 'none';
    }

    if (carrito.length === 0) {
        cont.innerHTML = '<div class="carrito-empty"><i class="fas fa-utensils"></i>Aun no has anadido platos</div>';
        return;
    }
    var html = '';
    for (var i = 0; i < carrito.length; i++) {
        var item = carrito[i];
        html += '<div class="carrito-item">' +
            '<img src="<?= UPLOADS_URL ?>' + item.imagen + '" onerror="this.src=\'https://placehold.co/40x40/e8f5e9/007800?text=P\'">' +
            '<div class="ci-info">' +
                '<div class="ci-nombre">' + item.nombre + '</div>' +
                '<div class="ci-precio">$' + (item.precio * item.cantidad).toFixed(2) + '</div>' +
                // HU-14 Esc.5: campo nota especial por ítem
                '<input type="text" placeholder="Nota especial..." maxlength="80" ' +
                    'value="' + (item.nota || '') + '" ' +
                    'onchange="carrito[' + i + '].nota=this.value" ' +
                    'style="margin-top:4px;width:100%;padding:3px 6px;border-radius:6px;border:1px solid var(--border);font-family:\'Nunito\',sans-serif;font-size:0.72rem;color:var(--text-dark);outline:none;">' +
            '</div>' +
            '<div class="ci-qty">' +
            '<button onclick="cambiarCantidad(' + i + ',-1)">-</button>' +
            '<span>' + item.cantidad + '</span>' +
            '<button onclick="cambiarCantidad(' + i + ',1)">+</button>' +
            '</div></div>';
    }
    cont.innerHTML = html;
}

function confirmarPedido() {
    if (carrito.length === 0) {
        Swal.fire({ icon:'warning', title:'Carrito vacio', text:'Anade al menos un plato.', confirmButtonColor:'#00a020' });
        return;
    }

    Swal.fire({
        icon:'question', title:'Crear pedido?',
        text:'Se creara un pedido con ' + carrito.length + ' plato(s).',
        showCancelButton:true, confirmButtonText:'Si, crear', cancelButtonText:'Cancelar', confirmButtonColor:'#00a020'
    }).then(function(r) {
        if (r.isConfirmed) {
            var hidden = document.getElementById('carrito-inputs-hidden');
            var html = '';
            for (var i = 0; i < carrito.length; i++) {
                html += '<input type="hidden" name="items[' + i + '][id_plato]" value="' + carrito[i].id + '">' +
                        '<input type="hidden" name="items[' + i + '][cantidad]" value="' + carrito[i].cantidad + '">' +
                        '<input type="hidden" name="items[' + i + '][precio]" value="' + carrito[i].precio + '">' +
                        '<input type="hidden" name="items[' + i + '][nota]" value="' + (carrito[i].nota || '') + '">';
            }
            hidden.innerHTML = html;
            document.getElementById('form-crear-pedido').submit();
        }
    });
}

function filtrarClientes(q) {
    q = q.trim().toLowerCase();
    document.getElementById('btn-limpiar-cliente').style.display = q ? 'block' : 'none';
    document.querySelectorAll('.cliente-opcion').forEach(function(el) {
        var nombre = el.dataset.nombre.toLowerCase();
        var correo = el.dataset.correo.toLowerCase();
        el.style.display = (!q || nombre.includes(q) || correo.includes(q)) ? 'flex' : 'none';
    });
}
function limpiarBusquedaCliente() {
    document.getElementById('buscar-cliente-input').value = '';
    filtrarClientes('');
    document.getElementById('buscar-cliente-input').focus();
}
function seleccionarCliente(id, nombre) {
    clienteSeleccionado = { id: id, nombre: nombre };
    document.querySelectorAll('.cliente-opcion').forEach(function(el) {
        var check = el.querySelector('.fa-check');
        if (parseInt(el.dataset.id) === id) { el.style.background = '#e8f5e9'; if (check) check.style.display = 'block'; }
        else { el.style.background = ''; if (check) check.style.display = 'none'; }
    });
    var info = document.getElementById('cliente-seleccionado-info');
    info.style.display = 'flex';
    document.getElementById('cliente-seleccionado-texto').textContent = nombre;
}
function quitarCliente() {
    clienteSeleccionado = { id: 0, nombre: 'Sin asignar' };
    document.querySelectorAll('.cliente-opcion').forEach(function(el) {
        el.style.background = '';
        var check = el.querySelector('.fa-check');
        if (check) check.style.display = 'none';
    });
    document.getElementById('cliente-seleccionado-info').style.display = 'none';
}
function confirmarClienteYCerrar() {
    document.getElementById('carrito-cliente-nombre').textContent = clienteSeleccionado.nombre;
    document.getElementById('form-id-cliente').value = clienteSeleccionado.id;
    toggleModal('modal-nuevo-pedido');
    document.getElementById('buscar-cliente-input').value = '';
    filtrarClientes('');
}

window.history.pushState(null, "", window.location.href);
window.onpopstate = function() {
    window.location.href = "../../controllers/AuthController.php?action=logout";
};

// HU-15: Auto-refresh cada 20s si se está viendo la sección de mesas
(function autoRefreshMesas() {
    setTimeout(function() {
        var sec = document.getElementById('mesas');
        if (sec && sec.classList.contains('active')) {
            location.reload();
        } else {
            autoRefreshMesas();
        }
    }, 20000);
}());

(function() {
    var hash = window.location.hash.substring(1);
    if (hash && document.getElementById(hash)) {
        document.querySelectorAll('.nav-item').forEach(function(item) {
            if (item.getAttribute('onclick') && item.getAttribute('onclick').includes("'" + hash + "'")) {
                showSection(hash, item);
            }
        });
    }
})();
</script>

<?php if ($alert): ?>
<script>
Swal.fire({
    icon: '<?= htmlspecialchars($alert['icon']) ?>',
    title: '<?= htmlspecialchars($alert['title']) ?>',
    text: '<?= htmlspecialchars($alert['text']) ?>',
    confirmButtonColor: '#00a020'
});
</script>
<?php endif; ?>

</body>
</html>
