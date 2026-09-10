<?php
session_start();
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'cocinero') {
    header('Location: ../../public/login.php');
    exit;
}

require_once __DIR__ . '/../../models/Pedido.php';
$pedidoModel = new PedidoModel();

// Pedidos pendientes y en preparación (los que cocina debe atender)
$pedidosPendientes = $pedidoModel->getPedidosParaCocina();

foreach ($pedidosPendientes as &$p) {
    $p['items']   = $pedidoModel->getItemsByPedido($p['id_pedido']);
    $p['minutos'] = round((time() - strtotime($p['fecha'])) / 60);

    // Calcular minutos hasta hora de entrega (si existe)
    if (!empty($p['hora_entrega'])) {
        $hoy          = date('Y-m-d');
        $tsEntrega    = strtotime($hoy . ' ' . $p['hora_entrega']);
        // Si la hora ya pasó hoy, asumir que es para mañana
        if ($tsEntrega < time()) $tsEntrega += 86400;
        $p['mins_para_entrega'] = round(($tsEntrega - time()) / 60);
    } else {
        $p['mins_para_entrega'] = null;
    }
}
unset($p);

$alert   = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
$inicial = strtoupper(substr($_SESSION['user_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cocina — El Cielo</title>
<link rel="icon" type="image/png" href="../../img/ico.png">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
:root {
    --naranja:      #e67e22;
    --naranja-dark: #ca6f1e;
    --rojo:         #e74c3c;
    --verde:        #27ae60;
    --agua:         #00C8F0;
    --sidebar-bg1:  #1a0a00;
    --sidebar-bg2:  #3d1a00;
    --bg-page:      #fdf6f0;
    --white:        #ffffff;
    --border:       #f0d9c8;
    --text-dark:    #2c1a0e;
    --text-mid:     #7a4a2a;
    --text-light:   #b07a5a;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Nunito', sans-serif; background: var(--bg-page); display: flex; min-height: 100vh; color: var(--text-dark); }

/* SIDEBAR */
.sidebar { width: 240px; min-height: 100vh; background: linear-gradient(170deg, var(--sidebar-bg1) 0%, var(--sidebar-bg2) 60%, #5a2800 100%); display: flex; flex-direction: column; flex-shrink: 0; box-shadow: 4px 0 20px rgba(0,0,0,0.25); position: sticky; top: 0; height: 100vh; }
.sidebar-header { padding: 24px 16px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.07); background: rgba(0,0,0,0.2); }
.sidebar-logo { width: 72px; height: 72px; border-radius: 50%; margin: 0 auto 10px; }
.sidebar-logo img { width: 72px; height: 72px; object-fit: contain; border-radius: 50%; }
.sidebar-header h2 { font-size: 1.1rem; font-weight: 900; color: #fff; margin-bottom: 3px; }
.sidebar-header p { font-size: 0.63rem; color: #f0a060; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; }
.nav-links { flex: 1; padding: 14px 10px; list-style: none; }
.nav-item { display: flex; align-items: center; gap: 11px; padding: 11px 13px; font-size: 0.9rem; font-weight: 700; color: rgba(255,220,180,0.8); border-radius: 10px; margin-bottom: 3px; cursor: pointer; transition: all 0.22s; }
.nav-item i { font-size: 0.95rem; width: 18px; text-align: center; flex-shrink: 0; }
.nav-item:hover { background: rgba(255,255,255,0.07); color: #fff; }
.nav-item.active { background: linear-gradient(90deg, rgba(230,126,34,0.25), rgba(230,126,34,0.08)); color: #fff; border-left: 3px solid var(--naranja); padding-left: 10px; }
.nav-item.active i { color: var(--naranja); }

/* MAIN */
.main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.topbar { background: var(--white); padding: 13px 28px; display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--border); box-shadow: 0 2px 10px rgba(100,40,0,0.06); position: sticky; top: 0; z-index: 10; }
.topbar-title { font-size: 1.2rem; font-weight: 900; color: var(--text-dark); display: flex; align-items: center; gap: 9px; }
.topbar-title::before { content: ''; display: inline-block; width: 4px; height: 19px; background: linear-gradient(180deg, var(--naranja), var(--rojo)); border-radius: 4px; }
.topbar-user { display: flex; align-items: center; gap: 10px; background: var(--bg-page); padding: 7px 14px; border-radius: 30px; border: 1px solid var(--border); cursor: pointer; position: relative; }
.topbar-user .avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, var(--naranja), var(--naranja-dark)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.88rem; font-weight: 900; flex-shrink: 0; }
.topbar-user .info strong { display: block; font-size: 0.85rem; font-weight: 800; color: var(--text-dark); }
.topbar-user .info span { font-size: 0.7rem; color: var(--text-light); font-weight: 600; }
.topbar-user .chevron { font-size: 0.72rem; color: var(--text-light); transition: transform 0.3s; }
.topbar-user.open .chevron { transform: rotate(180deg); }
.user-dropdown { display: none; position: absolute; top: calc(100% + 8px); right: 0; background: var(--white); border-radius: 12px; box-shadow: 0 8px 28px rgba(0,0,0,0.12); border: 1px solid var(--border); min-width: 180px; z-index: 200; overflow: hidden; }
.topbar-user.open .user-dropdown { display: block; }
.user-dropdown a { display: flex; align-items: center; gap: 10px; padding: 13px 18px; font-size: 0.9rem; font-weight: 700; color: #e74c3c; text-decoration: none; }
.user-dropdown a:hover { background: #fde8e8; }

.content { flex: 1; padding: 24px 28px; overflow-y: auto; }
.section { display: none; } .section.active { display: block; animation: fadeUp 0.3s ease; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* STATS */
.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 22px; }
.stat-card { background: var(--white); border-radius: 14px; padding: 18px 20px; border: 1px solid var(--border); box-shadow: 0 4px 12px rgba(100,40,0,0.05); display: flex; align-items: center; gap: 14px; }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
.stat-label { font-size: 0.75rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.7px; margin-bottom: 3px; }
.stat-value { font-size: 1.6rem; font-weight: 900; color: var(--text-dark); }

/* FILTROS */
.filter-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 18px; }
.tab-btn { padding: 7px 16px; border-radius: 20px; border: 1.5px solid var(--border); background: transparent; font-family: 'Nunito', sans-serif; font-size: 0.82rem; font-weight: 800; color: var(--text-mid); cursor: pointer; transition: all 0.2s; }
.tab-btn.active, .tab-btn:hover { background: var(--naranja); color: #fff; border-color: var(--naranja); }

/* PEDIDO CARD */
.pedido-card { background: var(--white); border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 4px 14px rgba(100,40,0,0.05); margin-bottom: 16px; overflow: hidden; transition: box-shadow 0.2s; }
.pedido-card:hover { box-shadow: 0 8px 24px rgba(100,40,0,0.1); }
.pedido-card.urgente { border-left: 5px solid var(--rojo); }
.pedido-card.en-preparacion { border-left: 5px solid var(--naranja); }
.pedido-card.pendiente { border-left: 5px solid #f39c12; }

.pedido-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px 12px; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid var(--border); }
.pedido-num { font-size: 1.05rem; font-weight: 900; color: var(--text-dark); }
.pedido-num span { color: var(--naranja); }
.pedido-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.pedido-tiempo { font-size: 0.78rem; font-weight: 800; padding: 4px 10px; border-radius: 20px; }
.tiempo-ok     { background: #d4edda; color: #155724; }
.tiempo-alerta { background: #fff3cd; color: #856404; }
.tiempo-urgente{ background: #f8d7da; color: #721c24; }

.badge-estado { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; }
.badge-Pendiente      { background: #fff3cd; color: #856404; }
.badge-En_preparacion { background: #ffe0b2; color: #e65100; }
.badge-Listo          { background: #d4edda; color: #155724; }

.pedido-items { padding: 14px 20px; }
.item-row { display: flex; align-items: flex-start; gap: 12px; padding: 10px 0; border-bottom: 1px dashed #f0d9c8; }
.item-row:last-child { border-bottom: none; }
.item-qty { min-width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, var(--naranja), var(--naranja-dark)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.88rem; font-weight: 900; flex-shrink: 0; }
.item-info { flex: 1; }
.item-nombre { font-size: 0.92rem; font-weight: 800; color: var(--text-dark); margin-bottom: 3px; }
.item-notas { font-size: 0.78rem; font-weight: 700; color: #856404; background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 3px 8px; display: inline-flex; align-items: center; gap: 5px; margin-top: 4px; }

.pedido-footer { padding: 12px 20px; background: #fdf6f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.pedido-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 9px; font-family: 'Nunito', sans-serif; font-size: 0.85rem; font-weight: 800; border: none; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
.btn:hover { transform: translateY(-1px); }
.btn-naranja { background: linear-gradient(135deg, var(--naranja), var(--naranja-dark)); color: #fff; box-shadow: 0 4px 12px rgba(230,126,34,0.3); }
.btn-verde   { background: linear-gradient(135deg, var(--verde), #1e8449); color: #fff; box-shadow: 0 4px 12px rgba(39,174,96,0.3); }
.btn-outline { background: transparent; border: 1.5px solid var(--border); color: var(--text-mid); }
.btn-outline:hover { border-color: var(--naranja); color: var(--naranja); }

/* HISTORIAL */
.hist-table { width: 100%; border-collapse: collapse; }
.hist-table th { padding: 10px 14px; text-align: left; font-weight: 800; color: var(--text-light); text-transform: uppercase; font-size: 0.72rem; border-bottom: 2px solid var(--border); }
.hist-table td { padding: 11px 14px; font-weight: 600; border-bottom: 1px solid #f5e8dc; font-size: 0.85rem; }
.hist-table tbody tr:hover { background: #fdf0e8; }

.empty-state { text-align: center; padding: 40px 16px; color: var(--text-light); font-size: 0.88rem; font-weight: 600; }
.empty-state i { font-size: 2.5rem; display: block; margin-bottom: 12px; color: var(--border); }

/* BADGE LISTO */
.badge-Listo { background: #d4edda; color: #155724; }

/* Indicador de auto-refresh */
.refresh-indicator { display: flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 700; color: var(--text-light); }
.refresh-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--verde); animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.3;} }

/* Badge urgente */
.badge-urgente {
    display: inline-flex; align-items: center; gap: 5px;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: #fff; padding: 4px 12px; border-radius: 20px;
    font-size: 0.75rem; font-weight: 900;
    animation: urgentePulse 1.5s infinite;
    box-shadow: 0 2px 8px rgba(231,76,60,0.4);
}
@keyframes urgentePulse {
    0%,100% { box-shadow: 0 2px 8px rgba(231,76,60,0.4); }
    50%      { box-shadow: 0 4px 16px rgba(231,76,60,0.7); }
}

/* Hora de entrega */
.hora-entrega-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 0.75rem; font-weight: 800;
}
.hora-ok      { background: #d4edda; color: #155724; }
.hora-pronto  { background: #fff3cd; color: #856404; }
.hora-critica { background: #f8d7da; color: #721c24; animation: urgentePulse 1.5s infinite; }
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><img src="../../img/ico.png" alt="Logo" onerror="this.style.display='none'"></div>
        <h2>El Cielo</h2>
        <p>Panel de Cocina</p>
    </div>
    <ul class="nav-links">
        <li class="nav-item active" onclick="showSection('pedidos', this)"><i class="fas fa-fire-burner"></i> Pedidos Activos</li>
        <li class="nav-item" onclick="showSection('historial', this)"><i class="fas fa-history"></i> Historial</li>
    </ul>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-title" id="page-title">Pedidos Activos</div>
        <div style="display:flex;align-items:center;gap:16px;">
            <div class="refresh-indicator">
                <div class="refresh-dot"></div>
                Actualizando cada 15s
            </div>
            <div class="topbar-user" id="userMenu" onclick="toggleUserMenu()">
                <div class="avatar"><?= $inicial ?></div>
                <div class="info">
                    <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                    <span>Cocinero</span>
                </div>
                <i class="fas fa-chevron-down chevron"></i>
                <div class="user-dropdown">
                    <a href="../../controllers/AuthController.php?action=logout">
                        <i class="fas fa-right-from-bracket"></i> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">

        <!-- PEDIDOS ACTIVOS -->
        <div id="pedidos" class="section active">

            <?php
            $totalPendientes   = count(array_filter($pedidosPendientes, fn($p) => $p['estado'] === 'Pendiente'));
            $totalEnPrep       = count(array_filter($pedidosPendientes, fn($p) => $p['estado'] === 'En preparación'));
            $urgentes          = count(array_filter($pedidosPendientes, fn($p) => $p['minutos'] >= 15 || !empty($p['es_urgente'])));
            $conHoraEntrega    = count(array_filter($pedidosPendientes, fn($p) => !empty($p['hora_entrega'])));
            ?>

            <div class="stats-row" style="grid-template-columns:repeat(4,1fr);">
                <div class="stat-card">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#f39c12,#e67e22);color:#fff;"><i class="fas fa-clock"></i></div>
                    <div><div class="stat-label">Pendientes</div><div class="stat-value"><?= $totalPendientes ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:linear-gradient(135deg,var(--naranja),var(--naranja-dark));color:#fff;"><i class="fas fa-fire-burner"></i></div>
                    <div><div class="stat-label">En preparación</div><div class="stat-value"><?= $totalEnPrep ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:linear-gradient(135deg,var(--rojo),#c0392b);color:#fff;"><i class="fas fa-triangle-exclamation"></i></div>
                    <div><div class="stat-label">Urgentes</div><div class="stat-value"><?= $urgentes ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#6c5ce7,#a29bfe);color:#fff;"><i class="fas fa-calendar-clock"></i></div>
                    <div><div class="stat-label">Con hora entrega</div><div class="stat-value"><?= $conHoraEntrega ?></div></div>
                </div>
            </div>

            <div class="filter-tabs">
                <button class="tab-btn active" onclick="filtrar('todos', this)">Todos (<?= count($pedidosPendientes) ?>)</button>
                <button class="tab-btn" onclick="filtrar('Pendiente', this)">Pendientes</button>
                <button class="tab-btn" onclick="filtrar('En preparación', this)">En preparación</button>
                <button class="tab-btn" onclick="filtrar('urgente', this)"><i class="fas fa-triangle-exclamation"></i> Urgentes</button>
            </div>

            <?php if (empty($pedidosPendientes)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle" style="color:var(--verde);"></i>
                No hay pedidos pendientes en este momento.<br>
                <span style="font-size:0.8rem;color:var(--text-light);">La pantalla se actualiza automáticamente.</span>
            </div>
            <?php else: ?>

            <?php
            // Ordenar: urgentes primero, luego por tiempo de espera desc
            usort($pedidosPendientes, function($a, $b) {
                if ($a['minutos'] >= 15 && $b['minutos'] < 15) return -1;
                if ($b['minutos'] >= 15 && $a['minutos'] < 15) return  1;
                return $b['minutos'] - $a['minutos'];
            });
            ?>

            <?php foreach ($pedidosPendientes as $ped):
                $estadoClass = str_replace(' ', '_', $ped['estado']);
                $urgente     = $ped['minutos'] >= 15;
                $cardClass   = $urgente ? 'urgente' : ($ped['estado'] === 'En preparación' ? 'en-preparacion' : 'pendiente');
                $tiempoClass = $ped['minutos'] >= 15 ? 'tiempo-urgente' : ($ped['minutos'] >= 8 ? 'tiempo-alerta' : 'tiempo-ok');
            ?>
            <div class="pedido-card <?= $cardClass ?>" data-estado="<?= htmlspecialchars($ped['estado']) ?>" data-minutos="<?= $ped['minutos'] ?>">
                <div class="pedido-header">
                    <div>
                        <div class="pedido-num">
                            Pedido <span>#<?= $ped['id_pedido'] ?></span>
                            <?php if (!empty($ped['nombre_cliente'])): ?>
                            <span style="font-size:0.78rem;color:var(--text-light);font-weight:600;"> — <?= htmlspecialchars($ped['nombre_cliente']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:0.75rem;color:var(--text-light);font-weight:600;margin-top:3px;">
                            <i class="fas fa-clock"></i> <?= date('H:i', strtotime($ped['fecha'])) ?>
                        </div>
                    </div>
                    <div class="pedido-meta">
                        <span class="pedido-tiempo <?= $tiempoClass ?>">
                            <i class="fas fa-stopwatch"></i>
                            <?php if ($ped['minutos'] < 1): ?>
                            Recién llegado
                            <?php elseif ($ped['minutos'] < 60): ?>
                            <?= $ped['minutos'] ?> min
                            <?php else: ?>
                            <?= floor($ped['minutos']/60) ?>h <?= $ped['minutos']%60 ?>m
                            <?php endif; ?>
                            <?php if ($urgente): ?> ⚠️<?php endif; ?>
                        </span>
                        <span class="badge-estado badge-<?= $estadoClass ?>"><?= htmlspecialchars($ped['estado']) ?></span>
                    </div>
                </div>

                <div class="pedido-items">
                    <?php foreach ($ped['items'] as $item): ?>
                    <div class="item-row">
                        <div class="item-qty">×<?= $item['cantidad'] ?></div>
                        <div class="item-info">
                            <div class="item-nombre"><?= htmlspecialchars($item['nombre_plato']) ?></div>
                            <?php if (!empty($item['notas'])): ?>
                            <div class="item-notas">
                                <i class="fas fa-triangle-exclamation"></i>
                                <?= htmlspecialchars($item['notas']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($ped['items'])): ?>
                    <div style="font-size:0.82rem;color:var(--text-light);padding:8px 0;">Sin ítems registrados.</div>
                    <?php endif; ?>
                </div>

                <div class="pedido-footer">
                    <div style="font-size:0.78rem;font-weight:700;color:var(--text-light);">
                        <?= count($ped['items']) ?> platillo(s)
                    </div>
                    <div class="pedido-actions">
                        <?php if ($ped['estado'] === 'Pendiente'): ?>
                        <!-- HU-19 Esc.1: Iniciar preparación -->
                        <form action="../../controllers/PedidoController.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="cambiar_estado_cocina">
                            <input type="hidden" name="id_pedido" value="<?= $ped['id_pedido'] ?>">
                            <input type="hidden" name="estado" value="En preparación">
                            <button type="submit" class="btn btn-naranja">
                                <i class="fas fa-fire-burner"></i> Iniciar preparación
                            </button>
                        </form>
                        <?php elseif ($ped['estado'] === 'En preparación'): ?>
                        <!-- HU-19 Esc.2: Marcar listo -->
                        <form action="../../controllers/PedidoController.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="cambiar_estado_cocina">
                            <input type="hidden" name="id_pedido" value="<?= $ped['id_pedido'] ?>">
                            <input type="hidden" name="estado" value="Entregado">
                            <button type="submit" class="btn btn-verde">
                                <i class="fas fa-bell-concierge"></i> Marcar Listo
                            </button>
                        </form>
                        <!-- HU-19 Esc.4: Revertir solo dentro de 2 min -->
                        <?php if ($ped['minutos'] <= 2): ?>
                        <form action="../../controllers/PedidoController.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="revertir_estado">
                            <input type="hidden" name="id_pedido" value="<?= $ped['id_pedido'] ?>">
                            <button type="submit" class="btn btn-outline" title="Revertir a Pendiente (2 min disponibles)">
                                <i class="fas fa-rotate-left"></i> Revertir
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
                        <!-- HU-21: Marcar / quitar urgente -->
                        <?php if (empty($ped['es_urgente'])): ?>
                        <form action="../../controllers/PedidoController.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="marcar_urgente">
                            <input type="hidden" name="id_pedido" value="<?= $ped['id_pedido'] ?>">
                            <input type="hidden" name="urgente" value="1">
                            <button type="submit" class="btn btn-outline" style="color:#e74c3c;border-color:#e74c3c;">
                                <i class="fas fa-triangle-exclamation"></i> Urgente
                            </button>
                        </form>
                        <?php else: ?>
                        <form action="../../controllers/PedidoController.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="marcar_urgente">
                            <input type="hidden" name="id_pedido" value="<?= $ped['id_pedido'] ?>">
                            <input type="hidden" name="urgente" value="0">
                            <button type="submit" class="btn btn-outline">
                                <i class="fas fa-ban"></i> Quitar urgente
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- HISTORIAL -->
        <div id="historial" class="section">
            <?php
            $historial = $pedidoModel->getHistorialCocina();
            ?>
            <?php if (empty($historial)): ?>
            <div class="empty-state"><i class="fas fa-history"></i>No hay pedidos en el historial.</div>
            <?php else: ?>
            <div style="background:var(--white);border-radius:16px;padding:22px;border:1px solid var(--border);box-shadow:0 4px 14px rgba(100,40,0,0.05);">
                <table class="hist-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Hora</th>
                            <th>Platillos</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $h):
                            $hClass = str_replace(' ', '_', $h['estado']);
                        ?>
                        <tr>
                            <td><strong style="color:var(--naranja);">#<?= $h['id_pedido'] ?></strong></td>
                            <td><?= date('d/m H:i', strtotime($h['fecha'])) ?></td>
                            <td><?= (int)$h['total_items'] ?> platillo(s)</td>
                            <td><span class="badge-estado badge-<?= $hClass ?>"><?= htmlspecialchars($h['estado']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
var titles = { pedidos: 'Pedidos Activos', historial: 'Historial' };

function showSection(id, el) {
    document.querySelectorAll('.section').forEach(function(s){ s.classList.remove('active'); });
    document.querySelectorAll('.nav-item').forEach(function(a){ a.classList.remove('active'); });
    document.getElementById(id).classList.add('active');
    if (el) el.classList.add('active');
    document.getElementById('page-title').textContent = titles[id] || '';
}

function toggleUserMenu() {
    document.getElementById('userMenu').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    var m = document.getElementById('userMenu');
    if (m && !m.contains(e.target)) m.classList.remove('open');
});

function filtrar(tipo, btn) {
    document.querySelectorAll('.filter-tabs .tab-btn').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');
    document.querySelectorAll('.pedido-card').forEach(function(card) {
        var estado   = card.dataset.estado;
        var minutos  = parseInt(card.dataset.minutos) || 0;
        var mostrar  = false;
        if (tipo === 'todos')           mostrar = true;
        else if (tipo === 'urgente')    mostrar = minutos >= 15;
        else                            mostrar = estado === tipo;
        card.style.display = mostrar ? '' : 'none';
    });
}

// Auto-refresh cada 15 segundos (HU-18: recepción en tiempo real)
setTimeout(function() { location.reload(); }, 15000);

// HU-18 Esc.2: alerta visual/sonora al llegar nuevo pedido
(function() {
  var KEY   = 'cocina_last_count';
  var count = <?= count($pedidosPendientes) ?>;
  var prev  = parseInt(sessionStorage.getItem(KEY) || '-1');
  if (prev >= 0 && count > prev) {
    // Visual: flash de fondo
    document.body.style.transition = 'background 0.3s';
    document.body.style.background = '#fff0e0';
    setTimeout(function(){ document.body.style.background = ''; }, 800);
    // Sonido: beep con Web Audio API
    try {
      var ctx = new (window.AudioContext || window.webkitAudioContext)();
      [440,520,660].forEach(function(freq, i) {
        var osc  = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.value = freq;
        osc.type = 'sine';
        gain.gain.setValueAtTime(0.3, ctx.currentTime + i * 0.15);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.15 + 0.2);
        osc.start(ctx.currentTime + i * 0.15);
        osc.stop(ctx.currentTime  + i * 0.15 + 0.2);
      });
    } catch(e) {}
    // Banner de aviso
    var banner = document.createElement('div');
    banner.style.cssText = 'position:fixed;top:70px;left:50%;transform:translateX(-50%);z-index:9999;background:linear-gradient(135deg,#e67e22,#c0392b);color:#fff;padding:14px 28px;border-radius:12px;font-weight:800;font-size:1rem;box-shadow:0 8px 24px rgba(0,0,0,0.25);animation:fadeUp 0.4s ease;';
    banner.innerHTML = '<i class="fas fa-bell"></i> ¡Nuevo pedido recibido! (#' + count + ' en cola)';
    document.body.appendChild(banner);
    setTimeout(function(){ banner.remove(); }, 4000);
  }
  sessionStorage.setItem(KEY, count);
}());

window.history.pushState(null, "", window.location.href);
window.onpopstate = function() {
    window.location.href = "../../controllers/AuthController.php?action=logout";
};
</script>

<?php if ($alert): ?>
<script>
Swal.fire({
    icon: '<?= htmlspecialchars($alert['icon']) ?>',
    title: '<?= htmlspecialchars($alert['title']) ?>',
    text: '<?= htmlspecialchars($alert['text']) ?>',
    confirmButtonColor: '#e67e22'
});
</script>
<?php endif; ?>

</body>
</html>
