<?php
session_start();
// Si ya hay sesión activa, redirigir al panel correspondiente
if (isset($_SESSION['user_id'])) {
    $rol = strtolower($_SESSION['user_role'] ?? '');
    switch ($rol) {
        case 'administrador': header('Location: ../views/admin/dashboard.php');   exit;
        case 'mesero':        header('Location: ../views/mesero/dashboard.php');  exit;
        case 'cocinero':      header('Location: ../views/cocinero/dashboard.php');exit;
        default:              header('Location: ../views/cliente/dashboard.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>El Cielo | Centro Vacacional y Recreacional</title>
  <link rel="icon" type="image/png" href="../img/ico.png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,500;0,600;1,500;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --verde:       #007800;
      --verde-claro: #00a020;
      --agua:        #00C8F0;
      --agua-dark:   #0099bb;
      --dark:        #0a1a0e;
      --white:       #ffffff;
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'Outfit', sans-serif;
      background: var(--dark);
      color: var(--white);
      overflow-x: hidden;
    }

    /* ══ NAVBAR ══ */
    .navbar {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 18px 60px;
      background: rgba(10,26,14,0.85);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255,255,255,0.06);
      transition: padding 0.3s;
    }

    .navbar.scrolled { padding: 12px 60px; }

    .nav-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }

    .nav-brand img {
      width: 42px; height: 42px;
      border-radius: 50%;
      object-fit: contain;
    }

    .nav-brand span {
      font-family: 'Playfair Display', serif;
      font-size: 1.4rem;
      font-weight: 600;
      color: var(--white);
      letter-spacing: 0.5px;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 32px;
      list-style: none;
    }

    .nav-links a {
      color: rgba(255,255,255,0.75);
      text-decoration: none;
      font-size: 0.92rem;
      font-weight: 500;
      transition: color 0.2s;
    }

    .nav-links a:hover { color: var(--white); }

    .btn-nav-login {
      padding: 10px 26px;
      background: linear-gradient(135deg, var(--verde-claro), var(--verde));
      color: var(--white);
      border-radius: 30px;
      font-weight: 700;
      font-size: 0.9rem;
      text-decoration: none;
      transition: transform 0.2s, box-shadow 0.2s;
      box-shadow: 0 4px 14px rgba(0,160,32,0.3);
    }

    .btn-nav-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0,160,32,0.45);
    }

    .btn-nav-register {
      padding: 10px 26px;
      background: rgba(255,255,255,0.08);
      color: var(--white);
      border-radius: 30px;
      font-weight: 700;
      font-size: 0.9rem;
      text-decoration: none;
      border: 1px solid rgba(255,255,255,0.18);
      transition: background 0.2s, transform 0.2s;
    }

    .btn-nav-register:hover {
      background: rgba(255,255,255,0.15);
      transform: translateY(-2px);
    }

    .nav-actions {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    /* ══ HERO ══ */
    .hero {
      min-height: 100vh;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 120px 24px 80px;
      overflow: hidden;
    }

    .hero-bg {
      position: absolute;
      inset: 0;
      background: url('../img/login_bg.png') center/cover no-repeat;
      filter: brightness(0.35);
    }

    .hero-overlay {
      position: absolute;
      inset: 0;
      background: radial-gradient(ellipse at center, rgba(0,120,0,0.15) 0%, rgba(10,26,14,0.7) 70%);
    }

    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 780px;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(0,200,240,0.12);
      border: 1px solid rgba(0,200,240,0.3);
      color: var(--agua);
      padding: 7px 18px;
      border-radius: 30px;
      font-size: 0.82rem;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 28px;
    }

    .hero-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(3rem, 7vw, 5.5rem);
      font-weight: 600;
      line-height: 1.1;
      margin-bottom: 24px;
      letter-spacing: -1px;
    }

    .hero-title span {
      background: linear-gradient(135deg, var(--agua), var(--verde-claro));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .hero-subtitle {
      font-size: 1.15rem;
      font-weight: 300;
      color: rgba(255,255,255,0.78);
      line-height: 1.7;
      max-width: 580px;
      margin: 0 auto 44px;
    }

    .hero-actions {
      display: flex;
      gap: 16px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn-primary {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 16px 36px;
      background: linear-gradient(135deg, var(--verde-claro), var(--verde));
      color: var(--white);
      border-radius: 14px;
      font-size: 1rem;
      font-weight: 700;
      text-decoration: none;
      transition: transform 0.2s, box-shadow 0.2s;
      box-shadow: 0 6px 22px rgba(0,160,32,0.35);
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(0,160,32,0.45);
    }

    .btn-secondary {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 16px 36px;
      background: rgba(255,255,255,0.08);
      color: var(--white);
      border-radius: 14px;
      font-size: 1rem;
      font-weight: 700;
      text-decoration: none;
      border: 1px solid rgba(255,255,255,0.18);
      transition: background 0.2s, transform 0.2s;
      backdrop-filter: blur(6px);
    }

    .btn-secondary:hover {
      background: rgba(255,255,255,0.14);
      transform: translateY(-3px);
    }

    .hero-scroll {
      position: absolute;
      bottom: 36px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      color: rgba(255,255,255,0.4);
      font-size: 0.75rem;
      font-weight: 600;
      letter-spacing: 1px;
      text-transform: uppercase;
      animation: scrollBounce 2s infinite;
    }

    @keyframes scrollBounce {
      0%,100% { transform: translateX(-50%) translateY(0); }
      50%      { transform: translateX(-50%) translateY(8px); }
    }

    /* ══ STATS BAR ══ */
    .stats-bar {
      background: rgba(255,255,255,0.04);
      border-top: 1px solid rgba(255,255,255,0.06);
      border-bottom: 1px solid rgba(255,255,255,0.06);
      padding: 32px 60px;
      display: flex;
      justify-content: center;
      gap: 80px;
      flex-wrap: wrap;
    }

    .stat-item {
      text-align: center;
    }

    .stat-item .num {
      font-size: 2.2rem;
      font-weight: 900;
      background: linear-gradient(135deg, var(--agua), var(--verde-claro));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      display: block;
    }

    .stat-item .lbl {
      font-size: 0.82rem;
      color: rgba(255,255,255,0.5);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-top: 4px;
    }

    /* ══ SECCIÓN: CARACTERÍSTICAS ══ */
    .section {
      padding: 100px 60px;
    }

    .section-label {
      text-align: center;
      font-size: 0.8rem;
      font-weight: 800;
      color: var(--agua);
      text-transform: uppercase;
      letter-spacing: 2px;
      margin-bottom: 14px;
    }

    .section-title {
      text-align: center;
      font-family: 'Playfair Display', serif;
      font-size: clamp(1.8rem, 4vw, 2.8rem);
      font-weight: 600;
      margin-bottom: 16px;
      line-height: 1.2;
    }

    .section-sub {
      text-align: center;
      color: rgba(255,255,255,0.55);
      font-size: 1rem;
      max-width: 520px;
      margin: 0 auto 60px;
      line-height: 1.7;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 24px;
      max-width: 1100px;
      margin: 0 auto;
    }

    .feature-card {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 20px;
      padding: 32px 28px;
      transition: transform 0.25s, background 0.25s, border-color 0.25s;
    }

    .feature-card:hover {
      transform: translateY(-6px);
      background: rgba(255,255,255,0.07);
      border-color: rgba(0,200,240,0.25);
    }

    .feature-icon {
      width: 54px; height: 54px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 20px;
    }

    .feature-card h3 {
      font-size: 1.05rem;
      font-weight: 800;
      margin-bottom: 10px;
      color: var(--white);
    }

    .feature-card p {
      font-size: 0.88rem;
      color: rgba(255,255,255,0.55);
      line-height: 1.65;
    }

    /* ══ SECCIÓN: ROLES ══ */
    .roles-section {
      background: rgba(0,200,240,0.03);
      border-top: 1px solid rgba(255,255,255,0.05);
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .roles-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      max-width: 1000px;
      margin: 0 auto;
    }

    .role-card {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 18px;
      padding: 28px 24px;
      text-align: center;
      transition: transform 0.2s, border-color 0.2s;
    }

    .role-card:hover {
      transform: translateY(-4px);
      border-color: rgba(0,200,240,0.3);
    }

    .role-avatar {
      width: 64px; height: 64px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      margin: 0 auto 16px;
    }

    .role-card h3 {
      font-size: 1rem;
      font-weight: 800;
      margin-bottom: 8px;
    }

    .role-card p {
      font-size: 0.82rem;
      color: rgba(255,255,255,0.5);
      line-height: 1.6;
    }

    /* ══ CTA ══ */
    .cta-section {
      padding: 100px 60px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .cta-section::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(ellipse at center, rgba(0,160,32,0.12) 0%, transparent 70%);
    }

    .cta-section h2 {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2rem, 4vw, 3rem);
      font-weight: 600;
      margin-bottom: 16px;
      position: relative;
    }

    .cta-section p {
      color: rgba(255,255,255,0.6);
      font-size: 1rem;
      margin-bottom: 40px;
      position: relative;
    }

    .cta-actions {
      display: flex;
      gap: 16px;
      justify-content: center;
      flex-wrap: wrap;
      position: relative;
    }

    /* ══ FOOTER ══ */
    .footer {
      background: rgba(0,0,0,0.3);
      border-top: 1px solid rgba(255,255,255,0.06);
      padding: 40px 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
    }

    .footer-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }

    .footer-brand img { width: 32px; height: 32px; border-radius: 50%; }
    .footer-brand span { font-size: 0.95rem; font-weight: 700; color: rgba(255,255,255,0.7); }

    .footer-copy {
      font-size: 0.8rem;
      color: rgba(255,255,255,0.35);
    }

    .footer-links {
      display: flex;
      gap: 24px;
    }

    .footer-links a {
      font-size: 0.82rem;
      color: rgba(255,255,255,0.5);
      text-decoration: none;
      transition: color 0.2s;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .footer-links a:hover { color: var(--agua); }

    /* ══ RESPONSIVE ══ */
    @media (max-width: 768px) {
      .navbar { padding: 14px 24px; }
      .navbar.scrolled { padding: 10px 24px; }
      .nav-links { display: none; }
      .section { padding: 70px 24px; }
      .stats-bar { padding: 28px 24px; gap: 40px; }
      .cta-section { padding: 70px 24px; }
      .footer { padding: 28px 24px; flex-direction: column; text-align: center; }
      .footer-links { justify-content: center; }
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar" id="navbar">
    <a href="#" class="nav-brand">
      <img src="../img/ico.png" alt="El Cielo">
      <span>El Cielo</span>
    </a>
    <ul class="nav-links">
      <li><a href="#caracteristicas">Características</a></li>
      <li><a href="#roles">Roles</a></li>
      <li><a href="#contacto">Contacto</a></li>
    </ul>
    <div class="nav-actions">
      <a href="login.php" class="btn-nav-login">
        <i class="fas fa-sign-in-alt"></i> Ingresar al sistema
      </a>
      <a href="../views/usuarios/registro.php" class="btn-nav-register">
        <i class="fas fa-user-plus"></i> Crear cuenta
      </a>
    </div>
  </nav>

  <!-- HERO -->
  <section class="hero" id="inicio">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <div class="hero-badge">
        <i class="fas fa-leaf"></i>
        Sistema de Gestión Integral
      </div>
      <h1 class="hero-title">
        Bienvenido a<br><span>El Cielo</span>
      </h1>
      <p class="hero-subtitle">
        La plataforma digital del Centro Vacacional y Recreacional <strong>El Cielo</strong>. 
        Gestiona pedidos, inventario, reportes y más desde un solo lugar.
      </p>

    </div>
    <div class="hero-scroll">
      <span>Descubrir</span>
      <i class="fas fa-chevron-down"></i>
    </div>
  </section>

  <!-- STATS BAR -->
  <div class="stats-bar">
    <div class="stat-item">
      <span class="num">4</span>
      <span class="lbl">Roles de usuario</span>
    </div>
    <div class="stat-item">
      <span class="num">100%</span>
      <span class="lbl">Web — sin instalación</span>
    </div>
    <div class="stat-item">
      <span class="num">24/7</span>
      <span class="lbl">Disponibilidad</span>
    </div>
    <div class="stat-item">
      <span class="num">∞</span>
      <span class="lbl">Pedidos gestionados</span>
    </div>
  </div>

  <!-- CARACTERÍSTICAS -->
  <section class="section" id="caracteristicas">
    <p class="section-label">¿Qué ofrece?</p>
    <h2 class="section-title">Todo lo que necesitas en un solo sistema</h2>
    <p class="section-sub">Desde la toma del pedido hasta el reporte de ventas, El Cielo cubre cada etapa del servicio.</p>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon" style="background:linear-gradient(135deg,#00C8F0,#0099bb);color:#fff;">
          <i class="fas fa-clipboard-list"></i>
        </div>
        <h3>Gestión de Pedidos</h3>
        <p>El mesero toma pedidos desde el menú digital, los envía a cocina y gestiona el cobro con método de pago.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:linear-gradient(135deg,#e67e22,#ca6f1e);color:#fff;">
          <i class="fas fa-fire-burner"></i>
        </div>
        <h3>Panel de Cocina</h3>
        <p>El cocinero ve los pedidos en tiempo real, actualiza el estado y recibe alertas de pedidos urgentes.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:linear-gradient(135deg,#00a020,#007800);color:#fff;">
          <i class="fas fa-chart-line"></i>
        </div>
        <h3>Reportes de Ventas</h3>
        <p>Filtra por período, platillo, categoría y método de pago. Exporta informes en PDF con un clic.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:linear-gradient(135deg,#a29bfe,#6c5ce7);color:#fff;">
          <i class="fas fa-box-open"></i>
        </div>
        <h3>Control de Inventario</h3>
        <p>Gestiona materia prima con alertas de stock mínimo y descuento automático al procesar ventas.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:linear-gradient(135deg,#fd79a8,#e84393);color:#fff;">
          <i class="fas fa-utensils"></i>
        </div>
        <h3>Menú Digital</h3>
        <p>El cliente explora el menú con fotos, precios y categorías. Ve el estado de su pedido en tiempo real.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:linear-gradient(135deg,#fdcb6e,#e17055);color:#fff;">
          <i class="fas fa-comments"></i>
        </div>
        <h3>Asistente Virtual</h3>
        <p>Chatbot integrado que responde dudas frecuentes y permite enviar quejas directamente al administrador.</p>
      </div>
    </div>
  </section>

  <!-- ROLES -->
  <section class="section roles-section" id="roles">
    <p class="section-label">Acceso por rol</p>
    <h2 class="section-title">Un panel para cada persona</h2>
    <p class="section-sub">Cada usuario accede únicamente a las funciones de su rol. Seguro y organizado.</p>

    <div class="roles-grid">
      <div class="role-card">
        <div class="role-avatar" style="background:linear-gradient(135deg,#00C8F0,#0099bb);">
          <i class="fas fa-user-shield"></i>
        </div>
        <h3>Administrador</h3>
        <p>Gestiona usuarios, menú, inventario y accede a todos los reportes de ventas.</p>
      </div>
      <div class="role-card">
        <div class="role-avatar" style="background:linear-gradient(135deg,#00a020,#007800);">
          <i class="fas fa-concierge-bell"></i>
        </div>
        <h3>Mesero</h3>
        <p>Toma pedidos, gestiona la cuenta del cliente y registra el pago con método seleccionado.</p>
      </div>
      <div class="role-card">
        <div class="role-avatar" style="background:linear-gradient(135deg,#e67e22,#ca6f1e);">
          <i class="fas fa-hat-chef"></i>
        </div>
        <h3>Cocinero</h3>
        <p>Ve los pedidos pendientes en tiempo real y actualiza el estado de preparación.</p>
      </div>
      <div class="role-card">
        <div class="role-avatar" style="background:linear-gradient(135deg,#a29bfe,#6c5ce7);">
          <i class="fas fa-user"></i>
        </div>
        <h3>Cliente</h3>
        <p>Explora el menú, consulta sus pedidos y usa el asistente virtual para resolver dudas.</p>
      </div>
    </div>
  </section>



  <!-- FOOTER -->
  <footer class="footer">
    <a href="#" class="footer-brand">
      <img src="../img/ico.png" alt="El Cielo">
      <span>El Cielo</span>
    </a>
    <span class="footer-copy">© <?= date('Y') ?> Centro Vacacional El Cielo · Cauca, Colombia</span>
    <div class="footer-links">
      <a href="https://www.instagram.com/centro_vacacional_elcielo" target="_blank" rel="noopener">
        <i class="fab fa-instagram"></i> centro_vacacional_elcielo
      </a>
      <a href="mailto:centrovacacionalelcielo@gmail.com">
        <i class="fas fa-at"></i> centrovacacionalelcielo@gmail.com
      </a>
      <a href="tel:+573182852854">
        <i class="fas fa-phone"></i> 318 2852854
      </a>
    </div>
  </footer>

  <script>
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
      document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
    });
  </script>

</body>
</html>
