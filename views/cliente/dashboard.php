<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/login.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Pedido.php';

$db = (new Database())->getConnection();
// HU-11: cargar TODOS los platos incluyendo agotados para mostrarlos con etiqueta
$platosConEstado = $db->query("SELECT * FROM plato ORDER BY disponibilidad DESC, nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$platos = array_filter($platosConEstado, fn($p) => (int)$p['disponibilidad'] > 0);

// Obtener TODOS los pedidos del cliente (activos e históricos)
$pedidoModel = new PedidoModel();
$id_usuario  = (int)$_SESSION['user_id'];

$stmtCli = $db->prepare("SELECT id_cliente FROM cliente WHERE id_usuario=? LIMIT 1");
$stmtCli->execute([$id_usuario]);
$clienteRow = $stmtCli->fetch(PDO::FETCH_ASSOC);
$id_cliente = $clienteRow ? (int)$clienteRow['id_cliente'] : 0;

// Cargar ofertas activas
require_once __DIR__ . '/../../models/Plato.php';
$platoModel  = new PlatoModel();
$ofertasActivas = $platoModel->getOfertas();

$misPedidos = $id_cliente > 0
    ? $pedidoModel->getTodosByCliente($id_cliente, $id_usuario)
    : $pedidoModel->getTodosByCliente(0, $id_usuario);

// (debug removido en producción)
foreach ($misPedidos as &$mp) {
    $mp['items'] = $pedidoModel->getItemsByPedido($mp['id_pedido']);
    $mp['total'] = array_sum(array_map(fn($i) => $i['precio_unitario'] * $i['cantidad'], $mp['items']));
}
unset($mp);

$primerNombre = htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]);
$inicial      = strtoupper(substr($_SESSION['user_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi Panel – El Cielo</title>
<link rel="icon" type="image/png" href="../../img/ico.png">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="../../public/responsive.css">
<style>
    :root {
        --verde:        #007800;
        --verde-claro:  #00a020;
        --verde-dark:   #005500;
        --agua:         #00C8F0;
        --agua-claro:   #78DCF0;
        --agua-dark:    #0099bb;
        --sidebar-bg1:  #001a10;
        --sidebar-bg2:  #003820;
        --bg-page:      #f0f7f2;
        --white:        #ffffff;
        --border:       #c8e8d0;
        --text-dark:    #0d2b1a;
        --text-mid:     #3a6b50;
        --text-light:   #7aaa8a;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Nunito', sans-serif;
        background: var(--bg-page);
        display: flex;
        min-height: 100vh;
        color: var(--text-dark);
    }

    /* ══════════════════════════════
       SIDEBAR
    ══════════════════════════════ */
    .sidebar {
        width: 260px;
        min-height: 100vh;
        background: linear-gradient(170deg, var(--sidebar-bg1) 0%, var(--sidebar-bg2) 60%, #005530 100%);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        box-shadow: 4px 0 20px rgba(0,0,0,0.2);
        position: sticky;
        top: 0;
        height: 100vh;
    }

    .sidebar-header {
        padding: 28px 20px 22px;
        text-align: center;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        background: rgba(0,0,0,0.18);
    }

    .sidebar-logo-wrap {
        width: 82px;
        height: 82px;
        border-radius: 50%;
        margin: 0 auto 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-logo-wrap img {
        width: 82px;
        height: 82px;
        object-fit: contain;
        border-radius: 50%;
    }

    .sidebar-header h2 {
        font-size: 1.2rem;
        font-weight: 900;
        color: #fff;
        margin-bottom: 4px;
    }

    .sidebar-header p {
        font-size: 0.67rem;
        color: var(--agua-claro);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        line-height: 1.5;
    }

    .nav-links {
        flex: 1;
        padding: 14px 12px;
        list-style: none;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 14px;
        font-size: 0.92rem;
        font-weight: 700;
        color: rgba(200,240,210,0.8);
        border-radius: 10px;
        margin-bottom: 3px;
        cursor: pointer;
        transition: all 0.22s;
        text-decoration: none;
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

    .sidebar-footer {
        padding: 16px 12px;
        border-top: 1px solid rgba(255,255,255,0.07);
    }

    .btn-logout {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 14px;
        font-size: 0.88rem;
        font-weight: 700;
        color: rgba(255,160,160,0.85);
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-logout:hover {
        background: rgba(231,76,60,0.15);
        color: #ff8080;
    }

    /* ══════════════════════════════
       MAIN
    ══════════════════════════════ */
    .main {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* TOP BAR */
    .topbar {
        background: var(--white);
        padding: 14px 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 2px solid var(--border);
        box-shadow: 0 2px 10px rgba(0,80,30,0.06);
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .topbar-title {
        font-size: 1.3rem;
        font-weight: 900;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .topbar-title::before {
        content: '';
        display: inline-block;
        width: 4px;
        height: 20px;
        background: linear-gradient(180deg, var(--agua), var(--verde));
        border-radius: 4px;
    }

    .topbar-user {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--bg-page);
        padding: 8px 16px;
        border-radius: 30px;
        border: 1px solid var(--border);
        cursor: pointer;
        position: relative;
        transition: box-shadow 0.2s;
    }

    .topbar-user:hover { box-shadow: 0 4px 12px rgba(0,120,0,0.1); }

    .topbar-user .chevron {
        font-size: 0.75rem;
        color: var(--text-light);
        transition: transform 0.3s;
    }

    .topbar-user.open .chevron { transform: rotate(180deg); }

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

    .topbar-user.open .user-dropdown { display: block; animation: fadeUp 0.2s; }

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

    .topbar-user .avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--verde-claro), var(--verde));
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        font-weight: 900;
        flex-shrink: 0;
    }

    .topbar-user .info strong {
        display: block;
        font-size: 0.88rem;
        font-weight: 800;
        color: var(--text-dark);
    }

    .topbar-user .info span {
        font-size: 0.72rem;
        color: var(--text-light);
        font-weight: 600;
    }

    /* CONTENT */
    .content {
        flex: 1;
        padding: 28px 32px;
        overflow-y: auto;
    }

    .section { display: none; }
    .section.active { display: block; animation: fadeUp 0.3s ease; }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ══════════════════════════════
       BANNER
    ══════════════════════════════ */
    .banner {
        border-radius: 18px;
        overflow: hidden;
        margin-bottom: 26px;
        height: 160px;
        position: relative;
        display: flex;
        align-items: center;
        padding: 28px 32px;
    }

    .banner-bg {
        position: absolute;
        inset: 0;
        background: url('../../img/banner.png') center/cover no-repeat;
    }

    .banner-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(0,30,10,0.72) 0%, rgba(0,80,30,0.35) 100%);
    }

    .banner-text {
        position: relative;
        z-index: 1;
    }

    .banner-text h2 {
        font-size: 1.6rem;
        font-weight: 900;
        color: #fff;
        margin-bottom: 6px;
        text-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }

    .banner-text p {
        font-size: 0.9rem;
        color: rgba(255,255,255,0.88);
        font-weight: 600;
    }

    /* ══════════════════════════════
       CARDS
    ══════════════════════════════ */
    .cards-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .card {
        background: var(--white);
        border-radius: 16px;
        padding: 22px;
        border: 1px solid var(--border);
        box-shadow: 0 4px 14px rgba(0,80,30,0.05);
    }

    .card-title {
        font-size: 1rem;
        font-weight: 900;
        color: var(--text-dark);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border);
    }

    .card-title i { color: var(--verde-claro); }

    .empty-state {
        text-align: center;
        padding: 24px 16px;
        color: var(--text-light);
        font-size: 0.85rem;
        font-weight: 600;
    }

    .empty-state i {
        font-size: 2rem;
        display: block;
        margin-bottom: 8px;
        color: var(--border);
    }

    .promo-img {
        width: 100%;
        height: 130px;
        border-radius: 10px;
        object-fit: cover;
        margin-bottom: 12px;
    }

    .promo-text {
        font-size: 0.85rem;
        color: var(--text-mid);
        margin-bottom: 14px;
        line-height: 1.5;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 18px;
        background: linear-gradient(135deg, var(--verde-claro), var(--verde));
        color: #fff;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 800;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 4px 12px rgba(0,120,0,0.2);
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,120,0,0.3);
    }

    .btn-agua {
        background: linear-gradient(135deg, var(--agua), var(--agua-dark));
        box-shadow: 0 4px 12px rgba(0,200,240,0.25);
    }

    .btn-agua:hover { box-shadow: 0 6px 16px rgba(0,200,240,0.35); }

    /* ══════════════════════════════
       MENÚ / PLATOS
    ══════════════════════════════ */
    .menu-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .menu-header h3 {
        font-size: 1.15rem;
        font-weight: 900;
        color: var(--text-dark);
    }

    .platos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
        gap: 18px;
    }

    .plato-card {
        background: var(--white);
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid var(--border);
        box-shadow: 0 4px 14px rgba(0,80,30,0.05);
        transition: transform 0.22s, box-shadow 0.22s;
        display: flex;
        flex-direction: column;
    }

    .plato-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 28px rgba(0,80,30,0.1);
    }

    .plato-img {
        width: 100%;
        height: 145px;
        object-fit: cover;
    }

    .plato-body {
        padding: 14px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .plato-nombre {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 5px;
    }

    .plato-desc {
        font-size: 0.78rem;
        color: var(--text-light);
        line-height: 1.4;
        flex: 1;
        margin-bottom: 10px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .plato-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: auto;
    }

    .plato-precio {
        font-size: 1.1rem;
        font-weight: 900;
        color: var(--verde-claro);
    }

    .plato-cat {
        font-size: 0.7rem;
        font-weight: 800;
        background: #e8f5e9;
        color: var(--verde);
        padding: 3px 9px;
        border-radius: 20px;
    }

    /* ══════════════════════════════
       DATOS PERSONALES
    ══════════════════════════════ */
    .datos-card {
        background: var(--white);
        border-radius: 16px;
        padding: 28px;
        border: 1px solid var(--border);
        box-shadow: 0 4px 14px rgba(0,80,30,0.05);
        max-width: 520px;
    }

    .datos-avatar {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--verde-claro), var(--verde));
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        font-weight: 900;
        margin-bottom: 18px;
    }

    .datos-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid var(--border);
        font-size: 0.9rem;
    }

    .datos-row:last-child { border-bottom: none; }

    .datos-row i {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #e8f5e9;
        color: var(--verde-claro);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .datos-row .label { font-weight: 700; color: var(--text-light); font-size: 0.78rem; }
    .datos-row .value { font-weight: 800; color: var(--text-dark); }

    /* FOOTER */
    .page-footer {
        text-align: center;
        font-size: 0.72rem;
        color: var(--text-light);
        padding: 18px 0 4px;
        font-weight: 600;
    }

    /* BADGES ESTADO PEDIDO */
    .badge-Pendiente       { background:#fff3cd; color:#856404; }
    .badge-En\ preparación { background:#cce5ff; color:#004085; }
    .badge-Entregado       { background:#d1ecf1; color:#0c5460; }
    .badge-Pagado          { background:#d4edda; color:#155724; }
    .badge-Cancelado       { background:#f8d7da; color:#721c24; }

    /* HU-11: Platos agotados */
    .plato-agotado { opacity: 0.65; }
    .plato-agotado:hover { transform: none !important; box-shadow: 0 4px 14px rgba(0,80,30,0.05) !important; }
</style>
</head>
<body>

<!-- Overlay sidebar móvil -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ══ SIDEBAR ══ -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo-wrap">
            <img src="<?= IMG_URL ?>ico.png" alt="Logo El Cielo">
        </div>
        <h2>El Cielo</h2>
        <p>Centro Vacacional<br>y Recreacional</p>
    </div>

    <ul class="nav-links">
        <li class="nav-item active" onclick="show('inicio', this)">
            <i class="fas fa-house"></i> Inicio
        </li>
        <li class="nav-item" onclick="show('menu', this)">
            <i class="fas fa-utensils"></i> Explorar Menú
        </li>
        <li class="nav-item" onclick="show('pedidos', this)">
            <i class="fas fa-receipt"></i> Mis Pedidos
        </li>
        <li class="nav-item" onclick="show('datos', this)">
            <i class="fas fa-user-circle"></i> Mi Perfil
        </li>
    </ul>
</div>

<!-- ══ MAIN ══ -->
<div class="main">

    <!-- TOP BAR -->
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:10px;">
            <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Menú"><i class="fas fa-bars"></i></button>
            <div class="topbar-title" id="page-title">Inicio</div>
        </div>
        <div class="topbar-user" id="userMenu" onclick="toggleUserMenu()">
            <div class="avatar"><?= $inicial ?></div>
            <div class="info">
                <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                <span>Cliente</span>
            </div>
            <i class="fas fa-chevron-down chevron"></i>
            <div class="user-dropdown">
                <a href="../../controllers/AuthController.php?action=logout">
                    <i class="fas fa-right-from-bracket"></i> Cerrar sesión
                </a>
            </div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <!-- ── INICIO ── -->
        <div id="inicio" class="section active">
            <div class="banner">
                <div class="banner-bg"></div>
                <div class="banner-overlay"></div>
                <div class="banner-text">
                    <h2>¡Bienvenido, <?= $primerNombre ?>! 🌴</h2>
                    <p>Disfruta de tu experiencia en el Centro Vacacional El Cielo</p>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <div class="card">
                    <div class="card-title"><i class="fas fa-receipt"></i> Pedidos Recientes</div>
                    <?php
                    $recientes = array_slice($misPedidos, 0, 3);
                    $badgeMapInicio = [
                        'Pendiente'      => ['bg'=>'#fff3cd','color'=>'#856404','icon'=>'fa-clock'],
                        'En preparación' => ['bg'=>'#cce5ff','color'=>'#004085','icon'=>'fa-fire-burner'],
                        'Entregado'      => ['bg'=>'#d1ecf1','color'=>'#0c5460','icon'=>'fa-concierge-bell'],
                        'Pagado'         => ['bg'=>'#d4edda','color'=>'#155724','icon'=>'fa-check-circle'],
                        'Cancelado'      => ['bg'=>'#f8d7da','color'=>'#721c24','icon'=>'fa-times-circle'],
                    ];
                    if (empty($recientes)):
                    ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        Aún no tienes pedidos registrados.
                    </div>
                    <?php else: ?>
                    <?php foreach ($recientes as $rec):
                        $bc = $badgeMapInicio[$rec['estado']] ?? ['bg'=>'#e8f0ee','color'=>'#4a6a50','icon'=>'fa-circle'];
                    ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px dashed var(--border);font-size:0.85rem;">
                        <span style="font-weight:800;color:var(--text-dark);">#<?= $rec['id_pedido'] ?></span>
                        <span style="font-size:0.72rem;color:var(--text-light);"><?= date('d/m H:i', strtotime($rec['fecha'])) ?></span>
                        <span style="background:<?= $bc['bg'] ?>;color:<?= $bc['color'] ?>;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:800;">
                            <i class="fas <?= $bc['icon'] ?>"></i> <?= htmlspecialchars($rec['estado']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <button class="btn" style="margin-top:14px;" onclick="show('pedidos', document.querySelectorAll('.nav-item')[2])">
                        <i class="fas fa-receipt"></i> Ver todos mis pedidos
                    </button>
                </div>
            </div>

            <!-- ── OFERTAS Y PROMOCIONES (sección independiente, ancho completo) ── -->
            <div style="margin-top:20px;">
                <div class="card-title" style="margin-bottom:16px;font-size:1rem;font-weight:900;color:var(--text-dark);display:flex;align-items:center;gap:8px;padding-bottom:12px;border-bottom:1px solid var(--border);">
                    <i class="fas fa-star" style="color:#f39c12;"></i>
                    Promociones y Ofertas del Día
                    <?php if (!empty($ofertasActivas)): ?>
                    <span style="background:#fff3cd;color:#856404;padding:2px 10px;border-radius:20px;font-size:0.72rem;font-weight:800;margin-left:4px;">
                        <?= count($ofertasActivas) ?> activa<?= count($ofertasActivas) != 1 ? 's' : '' ?>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($ofertasActivas)): ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
                    <?php foreach ($ofertasActivas as $of):
                        $precioMostrar  = !empty($of['precio_oferta']) ? (float)$of['precio_oferta'] : null;
                        $etiqueta       = $of['etiqueta_oferta'] ?? 'Oferta especial';
                        $finOferta      = $of['fin_oferta']      ?? null;
                        $inicioOferta   = $of['inicio_oferta']   ?? null;
                        $esDia          = !empty($of['es_platillo_dia']);
                        $descuento      = ($precioMostrar && $of['precio'] > 0)
                            ? round((1 - $precioMostrar / $of['precio']) * 100) : 0;
                    ?>
                    <div style="background:var(--white);border-radius:16px;border:1px solid <?= $esDia ? '#f39c12' : 'var(--border)' ?>;box-shadow:0 4px 14px rgba(0,80,30,0.06);overflow:hidden;display:flex;flex-direction:column;<?= $esDia ? 'box-shadow:0 6px 20px rgba(243,156,18,0.2);' : '' ?>">
                        <!-- Imagen -->
                        <div style="position:relative;">
                            <img src="<?= UPLOADS_URL ?><?= htmlspecialchars($of['imagen'] ?? 'default.jpg') ?>"
                                 onerror="this.src='https://placehold.co/300x140/e8f5e9/007800?text=Oferta'"
                                 style="width:100%;height:140px;object-fit:cover;">
                            <!-- Badge etiqueta -->
                            <span style="position:absolute;top:10px;left:10px;background:<?= $esDia ? 'linear-gradient(135deg,#f39c12,#e67e22)' : 'linear-gradient(135deg,#e17055,#c0392b)' ?>;color:#fff;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:800;box-shadow:0 2px 8px rgba(0,0,0,0.2);">
                                <i class="fas <?= $esDia ? 'fa-star' : 'fa-tag' ?>"></i> <?= htmlspecialchars($etiqueta) ?>
                            </span>
                            <?php if ($descuento > 0): ?>
                            <span style="position:absolute;top:10px;right:10px;background:#e74c3c;color:#fff;padding:4px 10px;border-radius:20px;font-size:0.72rem;font-weight:900;">
                                -<?= $descuento ?>%
                            </span>
                            <?php endif; ?>
                        </div>
                        <!-- Info -->
                        <div style="padding:14px;flex:1;display:flex;flex-direction:column;gap:6px;">
                            <div style="font-size:0.95rem;font-weight:900;color:var(--text-dark);"><?= htmlspecialchars($of['nombre']) ?></div>
                            <?php if (!empty($of['descripcion'])): ?>
                            <div style="font-size:0.78rem;color:var(--text-light);line-height:1.4;"><?= htmlspecialchars(substr($of['descripcion'], 0, 60)) ?><?= strlen($of['descripcion']) > 60 ? '…' : '' ?></div>
                            <?php endif; ?>
                            <!-- Precios -->
                            <div style="display:flex;align-items:center;gap:10px;margin-top:4px;">
                                <?php if ($precioMostrar): ?>
                                <span style="font-size:1.15rem;font-weight:900;color:#e17055;">$<?= number_format($precioMostrar, 2) ?></span>
                                <span style="font-size:0.82rem;color:var(--text-light);text-decoration:line-through;">$<?= number_format($of['precio'], 2) ?></span>
                                <?php else: ?>
                                <span style="font-size:1.15rem;font-weight:900;color:var(--verde-claro);">$<?= number_format($of['precio'], 2) ?></span>
                                <?php endif; ?>
                            </div>
                            <!-- Vigencia -->
                            <?php if ($inicioOferta || $finOferta): ?>
                            <div style="font-size:0.72rem;color:var(--text-light);font-weight:600;display:flex;flex-direction:column;gap:2px;margin-top:2px;">
                                <?php if ($inicioOferta): ?>
                                <span><i class="fas fa-play-circle" style="color:var(--verde-claro);"></i> Desde: <?= date('d/m/Y H:i', strtotime($inicioOferta)) ?></span>
                                <?php endif; ?>
                                <?php if ($finOferta): ?>
                                <span><i class="fas fa-clock" style="color:#e17055;"></i> Hasta: <?= date('d/m/Y H:i', strtotime($finOferta)) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php else: ?>
                <div style="background:var(--white);border-radius:16px;border:1px solid var(--border);padding:28px;text-align:center;color:var(--text-light);">
                    <i class="fas fa-tag" style="font-size:2rem;display:block;margin-bottom:10px;color:var(--border);"></i>
                    No hay promociones activas en este momento.<br>
                    <button class="btn btn-agua" style="margin-top:14px;" onclick="show('menu', document.querySelectorAll('.nav-item')[1])">
                        <i class="fas fa-compass"></i> Ver menú
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <p class="page-footer">© <?= date('Y') ?> Centro Vacacional El Cielo · Todos los derechos reservados</p>
        </div>

        <!-- ── MENÚ ── -->
        <div id="menu" class="section">
            <div class="menu-header">
                <h3>Nuestro Menú</h3>
                <!-- HU-11 Esc.3: buscador funcional -->
                <div style="display:flex;align-items:center;gap:10px;background:var(--white);border:1.5px solid var(--border);border-radius:10px;padding:8px 14px;">
                    <i class="fas fa-search" style="color:var(--text-light);font-size:0.9rem;"></i>
                    <input type="text" id="menuSearch" placeholder="Buscar platillo…" oninput="filtrarMenu(this.value)"
                           style="border:none;outline:none;font-family:'Nunito',sans-serif;font-size:0.88rem;background:transparent;color:var(--text-dark);width:160px;">
                </div>
            </div>

            <!-- Filtro por categorías -->
            <?php
            $todasCats = array_unique(array_filter(array_column($platosConEstado, 'categoria')));
            ?>
            <div id="cat-filter" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px;">
                <button class="cat-btn active" onclick="filtrarCategoria('',this)" style="padding:6px 14px;border-radius:20px;border:1.5px solid var(--verde-claro);background:var(--verde-claro);color:#fff;font-family:'Nunito',sans-serif;font-size:0.78rem;font-weight:800;cursor:pointer;">Todos</button>
                <?php foreach ($todasCats as $cat): ?>
                <button class="cat-btn" onclick="filtrarCategoria('<?= htmlspecialchars(addslashes($cat)) ?>',this)"
                        style="padding:6px 14px;border-radius:20px;border:1.5px solid var(--border);background:transparent;color:var(--text-mid);font-family:'Nunito',sans-serif;font-size:0.78rem;font-weight:800;cursor:pointer;">
                    <?= htmlspecialchars($cat) ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Mensaje cuando no hay resultados de búsqueda -->
            <div id="menu-empty" style="display:none;text-align:center;padding:32px;color:var(--text-light);">
                <i class="fas fa-search" style="font-size:2rem;display:block;margin-bottom:10px;color:var(--border);"></i>
                No se encontraron platillos para tu búsqueda.<br>
                <button class="btn btn-agua" style="margin-top:14px;" onclick="document.getElementById('menuSearch').value='';filtrarMenu('')">
                    <i class="fas fa-compass"></i> Explorar por categorías
                </button>
            </div>

            <?php if (empty($platosConEstado)): ?>
                <div class="card">
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        No hay platos disponibles en este momento.
                    </div>
                </div>
            <?php else: ?>
                <div class="platos-grid" id="platos-grid">
                    <?php foreach ($platosConEstado as $p):
                        $agotado = ((int)$p['disponibilidad'] <= 0);
                    ?>
                    <div class="plato-card <?= $agotado ? 'plato-agotado' : '' ?>"
                         data-nombre="<?= strtolower(htmlspecialchars($p['nombre'])) ?>"
                         data-cat="<?= strtolower(htmlspecialchars($p['categoria'] ?? '')) ?>">
                        <div style="position:relative;">
                            <img class="plato-img"
                                 src="<?= UPLOADS_URL ?><?= htmlspecialchars($p['imagen'] ?? 'default.jpg') ?>"
                                 alt="<?= htmlspecialchars($p['nombre']) ?>"
                                 onerror="this.src='https://placehold.co/300x145/e8f5e9/007800?text=Sin+imagen'"
                                 style="<?= $agotado ? 'filter:grayscale(0.8);opacity:0.6;' : '' ?>">
                            <!-- HU-11 Esc.2: etiqueta 'No disponible' en platos agotados -->
                            <?php if ($agotado): ?>
                            <span style="position:absolute;top:8px;left:8px;background:rgba(100,100,100,0.85);color:#fff;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:800;">
                                <i class="fas fa-ban"></i> No disponible
                            </span>
                            <?php endif; ?>
                            <!-- HU-11 Esc.4: etiqueta de promoción activa -->
                            <?php if (!empty($p['en_oferta']) && !$agotado): ?>
                            <span style="position:absolute;top:8px;right:8px;background:linear-gradient(135deg,#e17055,#c0392b);color:#fff;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:800;">
                                <i class="fas fa-tag"></i> Promoción
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="plato-body">
                            <div class="plato-nombre" style="<?= $agotado ? 'color:var(--text-light);' : '' ?>"><?= htmlspecialchars($p['nombre']) ?></div>
                            <div class="plato-desc"><?= htmlspecialchars($p['descripcion'] ?? '') ?></div>
                            <div class="plato-footer">
                                <?php if (!empty($p['en_oferta']) && !empty($p['precio_oferta'])): ?>
                                <div>
                                    <span class="plato-precio" style="color:#e17055;">$<?= number_format($p['precio_oferta'], 2) ?></span>
                                    <span style="font-size:0.75rem;color:var(--text-light);text-decoration:line-through;margin-left:4px;">$<?= number_format($p['precio'], 2) ?></span>
                                </div>
                                <?php else: ?>
                                <span class="plato-precio" style="<?= $agotado ? 'color:var(--text-light);' : '' ?>">$<?= number_format($p['precio'], 2) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($p['categoria'])): ?>
                                    <span class="plato-cat"><?= htmlspecialchars($p['categoria']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>      <!-- ── PEDIDOS ── -->
        <div id="pedidos" class="section">
            <?php
            $activos    = array_filter($misPedidos, fn($p) => !in_array($p['estado'], ['Pagado','Cancelado']));
            $historicos = array_filter($misPedidos, fn($p) =>  in_array($p['estado'], ['Pagado','Cancelado']));
            $badgeMap = [
                'Pendiente'      => ['bg'=>'#fff3cd','color'=>'#856404','icon'=>'fa-clock'],
                'En preparación' => ['bg'=>'#cce5ff','color'=>'#004085','icon'=>'fa-fire-burner'],
                'Entregado'      => ['bg'=>'#d1ecf1','color'=>'#0c5460','icon'=>'fa-concierge-bell'],
                'Pagado'         => ['bg'=>'#d4edda','color'=>'#155724','icon'=>'fa-check-circle'],
                'Cancelado'      => ['bg'=>'#f8d7da','color'=>'#721c24','icon'=>'fa-times-circle'],
            ];
            ?>

            <?php if (empty($misPedidos)): ?>
            <div class="card">
                <div class="card-title"><i class="fas fa-receipt"></i> Mis Pedidos</div>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    No tienes pedidos registrados aún.
                </div>
            </div>

            <?php else: ?>

            <?php if (!empty($activos)): ?>
            <div style="font-size:0.78rem;font-weight:800;color:var(--text-light);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">
                <i class="fas fa-circle" style="color:var(--verde-claro);font-size:0.5rem;vertical-align:middle;margin-right:6px;"></i> En curso
                <span style="font-size:0.7rem;font-weight:600;color:var(--text-light);margin-left:8px;">
                    <span id="polling-dot" style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--verde-claro);animation:pulse 2s infinite;vertical-align:middle;margin-right:4px;"></span>
                    Actualizando en tiempo real
                </span>
            </div>
            <?php foreach ($activos as $mp):
                $bc = $badgeMap[$mp['estado']] ?? ['bg'=>'#e8f0ee','color'=>'#4a6a50','icon'=>'fa-circle'];
            ?>
            <div class="card" style="margin-bottom:14px;border-left:4px solid <?= $bc['color'] ?>;">
                <div class="card-title" style="justify-content:space-between;margin-bottom:8px;">
                    <span><i class="fas fa-receipt" style="color:var(--verde-claro);"></i> Pedido #<?= $mp['id_pedido'] ?></span>
                    <span style="background:<?= $bc['bg'] ?>;color:<?= $bc['color'] ?>;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:800;">
                        <i class="fas <?= $bc['icon'] ?>"></i> <?= htmlspecialchars($mp['estado']) ?>
                    </span>
                </div>
                <div style="font-size:0.75rem;color:var(--text-light);font-weight:600;margin-bottom:10px;">
                    <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($mp['fecha'])) ?>
                </div>
                <?php foreach ($mp['items'] as $item): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px dashed var(--border);font-size:0.86rem;">
                    <span style="font-weight:700;color:var(--text-dark);"><?= htmlspecialchars($item['nombre_plato']) ?></span>
                    <span style="color:var(--text-light);font-size:0.78rem;"><?= $item['cantidad'] ?> u.</span>
                    <span style="font-weight:800;color:var(--verde-claro);">$<?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?></span>
                </div>
                <?php endforeach; ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;font-size:0.95rem;font-weight:900;color:var(--text-dark);">
                    <span>Total</span>
                    <span style="color:var(--verde-claro);">$<?= number_format($mp['total'], 2) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($historicos)): ?>
            <div style="font-size:0.78rem;font-weight:800;color:var(--text-light);text-transform:uppercase;letter-spacing:1px;margin:20px 0 12px;">
                <i class="fas fa-history" style="margin-right:6px;"></i> Historial
            </div>
            <?php foreach ($historicos as $mp):
                $bc = $badgeMap[$mp['estado']] ?? ['bg'=>'#e8f0ee','color'=>'#4a6a50','icon'=>'fa-circle'];
            ?>
            <div class="card" style="margin-bottom:12px;opacity:0.85;">
                <div class="card-title" style="justify-content:space-between;margin-bottom:8px;">
                    <span style="color:var(--text-mid);"><i class="fas fa-receipt"></i> Pedido #<?= $mp['id_pedido'] ?></span>
                    <span style="background:<?= $bc['bg'] ?>;color:<?= $bc['color'] ?>;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:800;">
                        <i class="fas <?= $bc['icon'] ?>"></i> <?= htmlspecialchars($mp['estado']) ?>
                    </span>
                </div>
                <div style="font-size:0.75rem;color:var(--text-light);font-weight:600;margin-bottom:10px;">
                    <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($mp['fecha'])) ?>
                </div>
                <?php foreach ($mp['items'] as $item): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px dashed var(--border);font-size:0.84rem;">
                    <span style="font-weight:700;color:var(--text-mid);"><?= htmlspecialchars($item['nombre_plato']) ?></span>
                    <span style="color:var(--text-light);font-size:0.78rem;"><?= $item['cantidad'] ?> u.</span>
                    <span style="font-weight:800;color:var(--text-mid);">$<?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?></span>
                </div>
                <?php endforeach; ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;font-size:0.9rem;font-weight:900;color:var(--text-mid);">
                    <span>Total</span>
                    <span>$<?= number_format($mp['total'], 2) ?></span>
                </div>
                <!-- HU-13 Esc.3: botón Volver a pedir -->
                <div style="display:flex;justify-content:flex-end;margin-top:10px;">
                    <button class="btn" style="background:var(--bg-page);color:var(--text-mid);border:1.5px solid var(--border);box-shadow:none;font-size:0.78rem;padding:7px 14px;"
                            onclick="volverAPedir(<?= htmlspecialchars(json_encode(array_map(fn($i) => ['nombre'=>$i['nombre_plato'],'id'=>$i['id_plato']], $mp['items']))) ?>)">
                        <i class="fas fa-rotate-right"></i> Volver a pedir
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php endif; ?>
        </div>

        <!-- ── PERFIL ── -->
        <div id="datos" class="section">
            <div class="datos-card">
                <div class="card-title" style="margin-bottom:20px;">
                    <i class="fas fa-user-circle" style="color:var(--verde-claro)"></i> Mi Perfil
                </div>
                <div class="datos-avatar"><?= $inicial ?></div>

                <div class="datos-row">
                    <i class="fas fa-user"></i>
                    <div>
                        <div class="label">Nombre completo</div>
                        <div class="value"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    </div>
                </div>

                <div class="datos-row">
                    <i class="fas fa-shield-halved"></i>
                    <div>
                        <div class="label">Rol</div>
                        <div class="value">Cliente</div>
                    </div>
                </div>

                <div class="datos-row">
                    <i class="fas fa-circle-check"></i>
                    <div>
                        <div class="label">Estado de cuenta</div>
                        <div class="value" style="color:var(--verde-claro);">Activo</div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /content -->
</div><!-- /main -->

<script>
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
        const menu = document.getElementById('userMenu');
        if (!menu.contains(e.target)) {
            menu.classList.remove('open');
        }
    });

    const titles = {
        inicio:  'Inicio',
        menu:    'Explorar Menú',
        pedidos: 'Mis Pedidos',
        datos:   'Mi Perfil'
    };

    function show(id, navEl) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(a => a.classList.remove('active'));
        document.getElementById(id).classList.add('active');
        if (navEl) navEl.classList.add('active');
        document.getElementById('page-title').textContent = titles[id] ?? '';
    }

    // ── HU-11: Filtro de menú por texto ──
    function filtrarMenu(q) {
        q = q.toLowerCase().trim();
        var cards  = document.querySelectorAll('#platos-grid .plato-card');
        var shown  = 0;
        cards.forEach(function(c) {
            var nombre = c.dataset.nombre || '';
            var match  = !q || nombre.includes(q);
            c.style.display = match ? '' : 'none';
            if (match) shown++;
        });
        document.getElementById('menu-empty').style.display = shown === 0 ? 'block' : 'none';
    }

    // ── HU-11: Filtro de menú por categoría ──
    function filtrarCategoria(cat, btn) {
        document.querySelectorAll('.cat-btn').forEach(function(b) {
            b.style.background = 'transparent';
            b.style.color      = 'var(--text-mid)';
            b.style.borderColor= 'var(--border)';
        });
        btn.style.background  = 'var(--verde-claro)';
        btn.style.color       = '#fff';
        btn.style.borderColor = 'var(--verde-claro)';

        cat = cat.toLowerCase();
        var cards = document.querySelectorAll('#platos-grid .plato-card');
        var shown = 0;
        cards.forEach(function(c) {
            var match = !cat || (c.dataset.cat || '') === cat;
            c.style.display = match ? '' : 'none';
            if (match) shown++;
        });
        document.getElementById('menu-empty').style.display = shown === 0 ? 'block' : 'none';
    }

    // Prevenir retroceso sin cerrar sesión
    window.history.pushState(null, "", window.location.href);
    window.onpopstate = function () {
        window.location.href = "../../controllers/AuthController.php?action=logout";
    };

    // ── HU-13 Esc.3: Volver a pedir ──
    function volverAPedir(items) {
        var disponibles = items.filter(function(i) { return i.nombre; });
        var lista = disponibles.map(function(i) { return '• ' + i.nombre; }).join('\n');
        Swal.fire({
            icon: 'info',
            title: 'Volver a pedir',
            html: '<div style="text-align:left;font-size:0.9rem;color:#0d2b1a;">' +
                  '<p style="margin-bottom:8px;">Los siguientes platillos se agregarán al carrito cuando contactes a tu mesero:</p>' +
                  '<ul style="padding-left:18px;">' +
                  disponibles.map(function(i){ return '<li style="margin-bottom:4px;font-weight:700;">'+i.nombre+'</li>'; }).join('') +
                  '</ul></div>',
            confirmButtonColor: '#00a020',
            confirmButtonText: '<i class="fas fa-utensils"></i> Ver menú',
            showCancelButton: true,
            cancelButtonText: 'Cerrar'
        }).then(function(r) {
            if (r.isConfirmed) show('menu', document.querySelectorAll('.nav-item')[1]);
        });
    }
    // Recarga la sección de pedidos cada 20 segundos si hay pedidos activos
    <?php if (!empty($activos)): ?>
    (function startPolling() {
        setTimeout(function() {
            // Solo recarga si el usuario está viendo la sección de pedidos
            var seccion = document.getElementById('pedidos');
            if (seccion && seccion.classList.contains('active')) {
                location.reload();
            } else {
                // Si no está en pedidos, espera y vuelve a intentar
                startPolling();
            }
        }, 20000);
    })();
    <?php endif; ?>
</script>
<style>
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.3;} }
</style>

<!-- ═══════════════════════════════════════════
     CHATBOT EL CIELO
═══════════════════════════════════════════ -->
<style>
/* ── FAB del chatbot ── */
#chat-fab {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 900;
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00C8F0, #0099bb);
    color: #fff;
    border: none;
    cursor: pointer;
    box-shadow: 0 6px 22px rgba(0,200,240,0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: transform 0.2s, box-shadow 0.2s;
}
#chat-fab:hover { transform: scale(1.1); box-shadow: 0 8px 28px rgba(0,200,240,0.55); }
#chat-fab .chat-badge {
    position: absolute;
    top: -3px; right: -3px;
    background: #e74c3c;
    color: #fff;
    border-radius: 50%;
    width: 18px; height: 18px;
    font-size: 0.65rem;
    font-weight: 900;
    display: none;
    align-items: center;
    justify-content: center;
}

/* ── Ventana del chat ── */
#chat-window {
    position: fixed;
    bottom: 100px;
    right: 28px;
    z-index: 899;
    width: 360px;
    max-height: 560px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 16px 48px rgba(0,0,0,0.18);
    display: none;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #c8e8d0;
    animation: chatSlideUp 0.25s ease;
}
#chat-window.open { display: flex; }
@keyframes chatSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Header */
.chat-header {
    background: linear-gradient(135deg, #001a10, #003820);
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
.chat-header-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00C8F0, #0099bb);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.chat-header-info strong { display: block; font-size: 0.92rem; font-weight: 900; color: #fff; }
.chat-header-info span   { font-size: 0.7rem; color: #78DCF0; font-weight: 600; }
.chat-header-close {
    margin-left: auto;
    background: none; border: none;
    color: rgba(255,255,255,0.6);
    cursor: pointer; font-size: 1rem;
    transition: color 0.2s;
}
.chat-header-close:hover { color: #fff; }

/* Mensajes */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #f8fdf9;
}
.chat-messages::-webkit-scrollbar { width: 4px; }
.chat-messages::-webkit-scrollbar-thumb { background: #c8e8d0; border-radius: 4px; }

/* Burbuja bot */
.msg-bot {
    display: flex;
    align-items: flex-end;
    gap: 8px;
}
.msg-bot-avatar {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00C8F0, #0099bb);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; flex-shrink: 0; color: #fff;
}
.msg-bot-bubble {
    background: #fff;
    border: 1px solid #c8e8d0;
    border-radius: 16px 16px 16px 4px;
    padding: 10px 14px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #0d2b1a;
    max-width: 82%;
    line-height: 1.5;
    box-shadow: 0 2px 8px rgba(0,80,30,0.06);
}

/* Burbuja usuario */
.msg-user {
    display: flex;
    justify-content: flex-end;
}
.msg-user-bubble {
    background: linear-gradient(135deg, #00a020, #007800);
    color: #fff;
    border-radius: 16px 16px 4px 16px;
    padding: 10px 14px;
    font-size: 0.85rem;
    font-weight: 600;
    max-width: 82%;
    line-height: 1.5;
}

/* Typing indicator */
.msg-typing .msg-bot-bubble {
    display: flex; gap: 4px; align-items: center; padding: 12px 16px;
}
.typing-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #00a020;
    animation: typingBounce 1.2s infinite;
}
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes typingBounce {
    0%,60%,100% { transform: translateY(0); opacity: 0.4; }
    30%          { transform: translateY(-6px); opacity: 1; }
}

/* Opciones rápidas */
.chat-options {
    padding: 10px 14px 0;
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    flex-shrink: 0;
    background: #f8fdf9;
}
.chat-opt-btn {
    padding: 7px 13px;
    border-radius: 20px;
    border: 1.5px solid #00a020;
    background: transparent;
    color: #00a020;
    font-family: 'Nunito', sans-serif;
    font-size: 0.78rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.18s;
    white-space: nowrap;
}
.chat-opt-btn:hover { background: #00a020; color: #fff; }

/* Input de queja */
.chat-input-area {
    padding: 12px 14px;
    border-top: 1px solid #c8e8d0;
    display: none;
    gap: 8px;
    align-items: flex-end;
    background: #fff;
    flex-shrink: 0;
}
.chat-input-area.visible { display: flex; }
.chat-input-area textarea {
    flex: 1;
    border: 1.5px solid #c8e8d0;
    border-radius: 12px;
    padding: 9px 12px;
    font-family: 'Nunito', sans-serif;
    font-size: 0.85rem;
    resize: none;
    outline: none;
    color: #0d2b1a;
    max-height: 90px;
    min-height: 40px;
    transition: border-color 0.2s;
}
.chat-input-area textarea:focus { border-color: #00a020; }
.chat-send-btn {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00a020, #007800);
    color: #fff;
    border: none;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
    transition: transform 0.2s;
}
.chat-send-btn:hover { transform: scale(1.1); }
</style>

<!-- FAB -->
<button id="chat-fab" onclick="toggleChat()" title="Asistente El Cielo">
    <i class="fas fa-comments"></i>
    <span class="chat-badge" id="chat-badge"></span>
</button>

<!-- Ventana del chat -->
<div id="chat-window">
    <div class="chat-header">
        <div class="chat-header-avatar"><i class="fas fa-robot"></i></div>
        <div class="chat-header-info">
            <strong>Asistente El Cielo</strong>
            <span>🟢 En línea · Respuesta inmediata</span>
        </div>
        <button class="chat-header-close" onclick="toggleChat()"><i class="fas fa-times"></i></button>
    </div>

    <div class="chat-messages" id="chat-messages"></div>

    <div class="chat-options" id="chat-options"></div>

    <div class="chat-input-area" id="chat-input-area">
        <textarea id="chat-textarea" placeholder="Escribe tu queja o comentario..." rows="2"
            onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();enviarQueja();}"></textarea>
        <button class="chat-send-btn" onclick="enviarQueja()"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
// ═══════════════════════════════════════════
//  CHATBOT — El Cielo
// ═══════════════════════════════════════════

var chatAbierto   = false;
var chatEstado    = 'inicio';   // estado actual del flujo
var chatNombre    = '<?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?>';
var badgeCount    = 0;

// ── Base de conocimiento ──────────────────
var KB = {

    // Menú principal
    inicio: {
        bot: '¡Hola, ' + chatNombre + '! 👋 Soy el asistente de <strong>El Cielo</strong>. ¿En qué te puedo ayudar hoy?',
        opciones: [
            { texto: '🍽️ Información del menú',   siguiente: 'menu'      },
            { texto: '📦 Estado de mi pedido',     siguiente: 'pedido'    },
            { texto: '💳 Métodos de pago',         siguiente: 'pago'      },
            { texto: '🕐 Horarios y ubicación',    siguiente: 'horarios'  },
            { texto: '🌊 Servicios del centro',    siguiente: 'servicios' },
            { texto: '❓ Preguntas frecuentes',    siguiente: 'faq'       },
            { texto: '📝 Enviar una queja',        siguiente: 'queja'     },
        ]
    },

    // Menú
    menu: {
        bot: '🍽️ <strong>Nuestro menú</strong> incluye entradas, platos fuertes, postres y bebidas. Puedes verlo completo en la sección <em>"Explorar Menú"</em> de tu panel.<br><br>¿Tienes alguna duda específica sobre el menú?',
        opciones: [
            { texto: '🥗 ¿Tienen opciones vegetarianas?', siguiente: 'vegetariano' },
            { texto: '🌶️ ¿Platos picantes?',              siguiente: 'picante'     },
            { texto: '🍰 ¿Qué postres tienen?',           siguiente: 'postres'     },
            { texto: '🔙 Volver al inicio',               siguiente: 'inicio'      },
        ]
    },
    vegetariano: {
        bot: '🥗 Sí, contamos con opciones vegetarianas en nuestra carta. Puedes identificarlas en el menú digital. Si tienes alguna restricción alimentaria especial, puedes indicársela al mesero al momento de hacer tu pedido.',
        opciones: [
            { texto: '🍽️ Más sobre el menú', siguiente: 'menu'   },
            { texto: '🏠 Inicio',            siguiente: 'inicio' },
        ]
    },
    picante: {
        bot: '🌶️ Algunos de nuestros platos tienen preparación picante. En el menú digital encontrarás la descripción de cada plato. Si prefieres sin picante, puedes pedirle al mesero que lo prepare a tu gusto.',
        opciones: [
            { texto: '🍽️ Más sobre el menú', siguiente: 'menu'   },
            { texto: '🏠 Inicio',            siguiente: 'inicio' },
        ]
    },
    postres: {
        bot: '🍰 ¡Tenemos una deliciosa selección de postres! Desde tortas hasta helados artesanales. Encuéntralos en la categoría <em>"Postres"</em> del menú digital.',
        opciones: [
            { texto: '🍽️ Más sobre el menú', siguiente: 'menu'   },
            { texto: '🏠 Inicio',            siguiente: 'inicio' },
        ]
    },

    // Pedido
    pedido: {
        bot: '📦 Puedes ver el estado de tu pedido en tiempo real en la sección <em>"Mis Pedidos"</em> de tu panel. Los estados son:<br><br>🟡 <strong>Pendiente</strong> — recibido, esperando cocina<br>🔵 <strong>En preparación</strong> — cocina trabajando en él<br>🔵 <strong>Entregado</strong> — listo para cobrar<br>🟢 <strong>Pagado</strong> — completado<br><br>¿Tienes alguna duda sobre tu pedido?',
        opciones: [
            { texto: '⏱️ ¿Cuánto tarda un pedido?',    siguiente: 'tiempo_pedido'  },
            { texto: '❌ ¿Puedo cancelar mi pedido?',  siguiente: 'cancelar'       },
            { texto: '➕ ¿Puedo agregar más platos?',  siguiente: 'agregar_plato'  },
            { texto: '🔙 Volver al inicio',            siguiente: 'inicio'         },
        ]
    },
    tiempo_pedido: {
        bot: '⏱️ El tiempo promedio de preparación es de <strong>15 a 25 minutos</strong> dependiendo del plato. En horas de alta demanda puede tardar un poco más. El mesero te mantendrá informado.',
        opciones: [
            { texto: '📦 Más sobre pedidos', siguiente: 'pedido' },
            { texto: '🏠 Inicio',           siguiente: 'inicio' },
        ]
    },
    cancelar: {
        bot: '❌ Puedes cancelar tu pedido <strong>solo si aún está en estado Pendiente</strong> y no ha pasado a cocina. Una vez en preparación, no es posible cancelarlo. Habla con el mesero para gestionar la cancelación.',
        opciones: [
            { texto: '📦 Más sobre pedidos', siguiente: 'pedido' },
            { texto: '🏠 Inicio',           siguiente: 'inicio' },
        ]
    },
    agregar_plato: {
        bot: '➕ Sí, puedes agregar platos a tu pedido mientras esté en estado <strong>Pendiente</strong> o <strong>Entregado</strong>. Habla con el mesero y él lo gestionará desde su panel.',
        opciones: [
            { texto: '📦 Más sobre pedidos', siguiente: 'pedido' },
            { texto: '🏠 Inicio',           siguiente: 'inicio' },
        ]
    },

    // Pago
    pago: {
        bot: '💳 Aceptamos los siguientes métodos de pago:<br><br>💵 <strong>Efectivo</strong><br>💳 <strong>Tarjeta</strong> débito/crédito<br>🏦 <strong>Transferencia</strong> bancaria<br>📱 <strong>Nequi</strong><br>📱 <strong>Daviplata</strong><br><br>El cobro lo realiza el mesero al finalizar tu consumo.',
        opciones: [
            { texto: '🧾 ¿Me dan factura?',       siguiente: 'factura'  },
            { texto: '💰 ¿Hay propina incluida?', siguiente: 'propina'  },
            { texto: '🔙 Volver al inicio',       siguiente: 'inicio'   },
        ]
    },
    factura: {
        bot: '🧾 Sí, al momento del pago puedes solicitar tu comprobante al mesero. Se genera con el detalle de todos los platos consumidos y el total pagado.',
        opciones: [
            { texto: '💳 Más sobre pagos', siguiente: 'pago'   },
            { texto: '🏠 Inicio',         siguiente: 'inicio' },
        ]
    },
    propina: {
        bot: '💰 La propina <strong>no está incluida</strong> en la cuenta. Es completamente voluntaria y puedes dársela directamente al mesero si quedaste satisfecho con el servicio. 😊',
        opciones: [
            { texto: '💳 Más sobre pagos', siguiente: 'pago'   },
            { texto: '🏠 Inicio',         siguiente: 'inicio' },
        ]
    },

    // Horarios
    horarios: {
        bot: '🕐 <strong>Horarios de atención:</strong><br><br>📅 Lunes a Viernes: <strong>11:00 am – 9:00 pm</strong><br>📅 Sábados y Domingos: <strong>9:00 am – 10:00 pm</strong><br><br>📍 <strong>Ubicación:</strong> Centro Vacacional El Cielo, Cauca, Colombia.',
        opciones: [
            { texto: '🌊 Servicios del centro', siguiente: 'servicios' },
            { texto: '🏠 Inicio',              siguiente: 'inicio'    },
        ]
    },

    // Servicios
    servicios: {
        bot: '🌊 <strong>Servicios del Centro Vacacional El Cielo:</strong><br><br>🏊 Piscinas y zonas acuáticas<br>🍽️ Restaurante con menú variado<br>🎉 Salones para eventos<br>🌳 Zonas verdes y recreativas<br>🅿️ Parqueadero<br><br>¿Quieres saber más sobre algún servicio?',
        opciones: [
            { texto: '🍽️ Sobre el restaurante', siguiente: 'menu'    },
            { texto: '🕐 Horarios',             siguiente: 'horarios'},
            { texto: '🏠 Inicio',              siguiente: 'inicio'  },
        ]
    },

    // FAQ
    faq: {
        bot: '❓ <strong>Preguntas frecuentes:</strong><br><br>Selecciona la que más se ajuste a tu duda:',
        opciones: [
            { texto: '🔑 Olvidé mi contraseña',          siguiente: 'faq_pass'     },
            { texto: '👤 ¿Cómo actualizo mi perfil?',    siguiente: 'faq_perfil'   },
            { texto: '📱 ¿Funciona en celular?',         siguiente: 'faq_movil'    },
            { texto: '🔒 ¿Mis datos están seguros?',     siguiente: 'faq_seguridad'},
            { texto: '🔙 Volver al inicio',              siguiente: 'inicio'       },
        ]
    },
    faq_pass: {
        bot: '🔑 Si olvidaste tu contraseña, ve a la pantalla de inicio de sesión y haz clic en <em>"¿Olvidaste tu contraseña?"</em>. Te enviaremos un código de recuperación a tu correo registrado. El código expira en 15 minutos.',
        opciones: [
            { texto: '❓ Más preguntas', siguiente: 'faq'    },
            { texto: '🏠 Inicio',       siguiente: 'inicio' },
        ]
    },
    faq_perfil: {
        bot: '👤 Por ahora puedes ver tu información en la sección <em>"Mi Perfil"</em>. Para actualizar datos como nombre o teléfono, comunícate con el administrador del sistema.',
        opciones: [
            { texto: '❓ Más preguntas', siguiente: 'faq'    },
            { texto: '🏠 Inicio',       siguiente: 'inicio' },
        ]
    },
    faq_movil: {
        bot: '📱 ¡Sí! El sistema está optimizado para dispositivos móviles. Puedes acceder desde tu celular o tablet con cualquier navegador moderno (Chrome, Safari, Firefox).',
        opciones: [
            { texto: '❓ Más preguntas', siguiente: 'faq'    },
            { texto: '🏠 Inicio',       siguiente: 'inicio' },
        ]
    },
    faq_seguridad: {
        bot: '🔒 Sí, tus datos están protegidos. Las contraseñas se almacenan cifradas y nunca se comparten con terceros. La sesión se cierra automáticamente al salir del sistema.',
        opciones: [
            { texto: '❓ Más preguntas', siguiente: 'faq'    },
            { texto: '🏠 Inicio',       siguiente: 'inicio' },
        ]
    },

    // Queja
    queja: {
        bot: '📝 Lamentamos que hayas tenido algún inconveniente. Tu opinión es muy importante para nosotros.<br><br>Por favor escribe tu queja o comentario en el campo de abajo. La recibiremos y la atenderemos a la brevedad. 🙏',
        opciones: [],
        inputVisible: true
    },
};

// ── Funciones del chat ────────────────────

function toggleChat() {
    chatAbierto = !chatAbierto;
    var win = document.getElementById('chat-window');
    if (chatAbierto) {
        win.classList.add('open');
        document.getElementById('chat-fab').querySelector('i').className = 'fas fa-times';
        badgeCount = 0;
        actualizarBadge();
        if (document.getElementById('chat-messages').children.length === 0) {
            irA('inicio');
        }
    } else {
        win.classList.remove('open');
        document.getElementById('chat-fab').querySelector('i').className = 'fas fa-comments';
    }
}

function irA(estado) {
    chatEstado = estado;
    var nodo = KB[estado];
    if (!nodo) return;

    // Mostrar typing
    mostrarTyping();

    setTimeout(function() {
        quitarTyping();
        agregarMensajeBot(nodo.bot);

        // Opciones
        var optDiv = document.getElementById('chat-options');
        optDiv.innerHTML = '';
        if (nodo.opciones && nodo.opciones.length > 0) {
            nodo.opciones.forEach(function(op) {
                var btn = document.createElement('button');
                btn.className = 'chat-opt-btn';
                btn.textContent = op.texto;
                btn.onclick = function() {
                    agregarMensajeUsuario(op.texto);
                    limpiarOpciones();
                    irA(op.siguiente);
                };
                optDiv.appendChild(btn);
            });
        }

        // Input de queja
        var inputArea = document.getElementById('chat-input-area');
        if (nodo.inputVisible) {
            inputArea.classList.add('visible');
            setTimeout(function(){ document.getElementById('chat-textarea').focus(); }, 100);
        } else {
            inputArea.classList.remove('visible');
            document.getElementById('chat-textarea').value = '';
        }

        scrollChat();
    }, 700);
}

function agregarMensajeBot(html) {
    var msgs = document.getElementById('chat-messages');
    var div = document.createElement('div');
    div.className = 'msg-bot';
    div.innerHTML = '<div class="msg-bot-avatar"><i class="fas fa-robot"></i></div>' +
                    '<div class="msg-bot-bubble">' + html + '</div>';
    msgs.appendChild(div);
    scrollChat();
}

function agregarMensajeUsuario(texto) {
    var msgs = document.getElementById('chat-messages');
    var div = document.createElement('div');
    div.className = 'msg-user';
    div.innerHTML = '<div class="msg-user-bubble">' + escHtml(texto) + '</div>';
    msgs.appendChild(div);
    scrollChat();
}

function mostrarTyping() {
    var msgs = document.getElementById('chat-messages');
    var div = document.createElement('div');
    div.className = 'msg-bot msg-typing';
    div.id = 'typing-indicator';
    div.innerHTML = '<div class="msg-bot-avatar"><i class="fas fa-robot"></i></div>' +
                    '<div class="msg-bot-bubble">' +
                    '<span class="typing-dot"></span>' +
                    '<span class="typing-dot"></span>' +
                    '<span class="typing-dot"></span>' +
                    '</div>';
    msgs.appendChild(div);
    scrollChat();
}

function quitarTyping() {
    var t = document.getElementById('typing-indicator');
    if (t) t.remove();
}

function limpiarOpciones() {
    document.getElementById('chat-options').innerHTML = '';
}

function scrollChat() {
    var msgs = document.getElementById('chat-messages');
    msgs.scrollTop = msgs.scrollHeight;
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function actualizarBadge() {
    var badge = document.getElementById('chat-badge');
    if (badgeCount > 0) {
        badge.style.display = 'flex';
        badge.textContent = badgeCount;
    } else {
        badge.style.display = 'none';
    }
}

function enviarQueja() {
    var ta = document.getElementById('chat-textarea');
    var texto = ta.value.trim();
    if (!texto) return;

    agregarMensajeUsuario(texto);
    ta.value = '';
    document.getElementById('chat-input-area').classList.remove('visible');
    limpiarOpciones();

    // Guardar queja en localStorage para que el admin la vea
    // (solución sin BD — se almacena localmente)
    var quejas = JSON.parse(localStorage.getItem('quejas_elcielo') || '[]');
    quejas.push({
        id:      Date.now(),
        usuario: chatNombre,
        texto:   texto,
        fecha:   new Date().toLocaleString('es-CO'),
        leida:   false
    });
    localStorage.setItem('quejas_elcielo', JSON.stringify(quejas));

    mostrarTyping();
    setTimeout(function() {
        quitarTyping();
        agregarMensajeBot('✅ <strong>¡Queja recibida!</strong> Gracias por tomarte el tiempo de comunicarte con nosotros, ' + chatNombre + '.<br><br>Tu comentario ha sido registrado y será atendido por nuestro equipo a la brevedad. 🙏<br><br>¿Hay algo más en lo que pueda ayudarte?');
        var optDiv = document.getElementById('chat-options');
        optDiv.innerHTML = '';
        var btn = document.createElement('button');
        btn.className = 'chat-opt-btn';
        btn.textContent = '🏠 Volver al inicio';
        btn.onclick = function() { limpiarOpciones(); irA('inicio'); };
        optDiv.appendChild(btn);
        scrollChat();
    }, 800);
}

// Mostrar badge si hay quejas no leídas al abrir la página
(function() {
    // Pequeña notificación visual en el FAB después de 3 segundos
    setTimeout(function() {
        if (!chatAbierto) {
            badgeCount = 1;
            actualizarBadge();
        }
    }, 3000);
})();
</script>
</body>
</html>
