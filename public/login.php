<?php
session_start();
$alert          = $_SESSION['alert'] ?? null;
$locked_until   = $_SESSION['login_locked_until'] ?? 0;
$locked         = $locked_until > time();
unset($_SESSION['alert']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>El Cielo | Restaurante & Centro Vacacional</title>
  <link rel="icon" type="image/png" href="../img/ico.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; color: #0f172a; }
    .login-container { display: flex; width: 100%; max-width: 1200px; min-height: 700px; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15); margin: 20px; }
    .panel-image { width: 55%; position: relative; background: url('../img/login_bg.png') center/cover no-repeat; display: flex; flex-direction: column; justify-content: flex-end; padding: 60px; }
    .panel-image::before { content: ''; position: absolute; top:0;left:0;right:0;bottom:0; background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0) 100%); z-index: 1; }
    .image-content { position: relative; z-index: 2; color: #ffffff; }
    .brand-title { font-family: 'Playfair Display', serif; font-size: 3.5rem; font-weight: 600; margin-bottom: 15px; line-height: 1.1; }
    .brand-subtitle { font-size: 1.1rem; font-weight: 300; opacity: 0.9; max-width: 80%; line-height: 1.6; }
    .panel-form { width: 45%; padding: 60px 70px; display: flex; flex-direction: column; justify-content: center; background: #ffffff; }
    .form-header { margin-bottom: 36px; }
    .form-header p { text-transform: uppercase; font-size: 0.85rem; font-weight: 700; color: #0d9488; letter-spacing: 2px; margin-bottom: 10px; }
    .form-header h2 { font-size: 2.2rem; font-weight: 700; color: #0f172a; letter-spacing: -0.5px; }
    .field-wrap { margin-bottom: 20px; position: relative; }
    .field-label { display: block; font-size: 0.9rem; font-weight: 600; color: #334155; margin-bottom: 6px; }
    .input-style { width: 100%; padding: 14px 20px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; font-family: 'Outfit', sans-serif; font-size: 1rem; color: #0f172a; transition: all 0.3s; outline: none; }
    .input-style::placeholder { color: #94a3b8; }
    .input-style:focus { border-color: #0d9488; background: #fff; box-shadow: 0 0 0 4px rgba(13,148,136,0.1); }
    .input-style.error { border-color: #e74c3c; background: #fff8f8; }
    .input-style.error:focus { box-shadow: 0 0 0 4px rgba(231,76,60,0.1); }
    .field-error { display: none; font-size: 0.78rem; color: #e74c3c; font-weight: 600; margin-top: 5px; }
    .field-error.show { display: block; }
    .input-pass-wrap { position: relative; }
    .toggle-eye { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; font-size: 1rem; }
    .links-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; font-size: 0.88rem; }
    .links-row a { color: #64748b; text-decoration: none; font-weight: 500; transition: color 0.2s; }
    .links-row a:hover { color: #0d9488; }
    .btn-login { width: 100%; padding: 15px; background: #0f172a; color: #fff; border: none; border-radius: 12px; font-family: 'Outfit', sans-serif; font-size: 1.05rem; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; justify-content: center; align-items: center; gap: 10px; }
    .btn-login:hover { background: #1e293b; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(15,23,42,0.15); }
    .btn-login:disabled { background: #94a3b8; cursor: not-allowed; transform: none; }
    .register-prompt { margin-top: 24px; text-align: center; font-size: 0.92rem; color: #64748b; }
    .register-prompt a { color: #0d9488; font-weight: 600; text-decoration: none; }
    .register-prompt a:hover { text-decoration: underline; }
    .locked-banner { background: #fff3cd; border: 1px solid #ffc107; border-radius: 12px; padding: 14px 18px; margin-bottom: 22px; font-size: 0.88rem; color: #856404; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    @media (max-width: 992px) {
      .login-container { flex-direction: column; max-width: 500px; }
      .panel-image { width: 100%; height: 280px; padding: 40px; }
      .panel-form { width: 100%; padding: 40px 36px; }
      .brand-title { font-size: 2.5rem; }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="panel-image">
      <div class="image-content">
        <h1 class="brand-title">El Cielo</h1>
        <p class="brand-subtitle">Gastronomía de autor y experiencias inolvidables en un entorno exclusivo.</p>
      </div>
    </div>

    <div class="panel-form">
      <div class="form-header">
        <p>Acceso al Sistema</p>
        <h2>Iniciar sesión</h2>
      </div>

      <?php if ($locked): ?>
      <div class="locked-banner">
        <i class="fas fa-lock"></i>
        Cuenta bloqueada temporalmente. <a href="../views/usuarios/recuperar.php" style="color:#856404;font-weight:700;margin-left:4px;">Recuperar contraseña</a>
      </div>
      <?php endif; ?>

      <!-- HU-01 Esc.4: validación client-side antes de enviar -->
      <form id="loginForm" action="../controllers/AuthController.php" method="POST" novalidate>
        <input type="hidden" name="action" value="login">

        <div class="field-wrap">
          <label class="field-label" for="correo">Correo electrónico</label>
          <input type="email" name="correo" id="correo" class="input-style" placeholder="usuario@ejemplo.com"
                 autocomplete="email" <?= $locked ? 'disabled' : '' ?>>
          <div class="field-error" id="err-correo">Este campo es obligatorio</div>
        </div>

        <div class="field-wrap">
          <label class="field-label" for="password">Contraseña</label>
          <div class="input-pass-wrap">
            <input type="password" name="password" id="password" class="input-style" placeholder="••••••••"
                   autocomplete="current-password" <?= $locked ? 'disabled' : '' ?>>
            <button type="button" class="toggle-eye" onclick="togglePass()" <?= $locked ? 'disabled' : '' ?>>
              <i class="fas fa-eye" id="eye-icon"></i>
            </button>
          </div>
          <div class="field-error" id="err-password">Este campo es obligatorio</div>
        </div>

        <div class="links-row">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:#64748b;font-size:0.88rem;">
            <input type="checkbox" style="accent-color:#0d9488;width:15px;height:15px;"> Recordarme
          </label>
          <a href="../views/usuarios/recuperar.php">¿Olvidaste tu contraseña?</a>
        </div>

        <button type="submit" class="btn-login" id="btnLogin" <?= $locked ? 'disabled' : '' ?>>
          Ingresar <i class="fas fa-arrow-right"></i>
        </button>
      </form>

      <div class="register-prompt">
        ¿No tienes una cuenta? <a href="../views/usuarios/registro.php">Regístrate aquí</a>
      </div>
      <div style="margin-top:16px;text-align:center;">
        <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;font-size:0.85rem;color:#64748b;text-decoration:none;font-weight:500;">
          <i class="fas fa-arrow-left"></i> Volver al inicio
        </a>
      </div>
    </div>
  </div>

  <script>
    // HU-01 Esc.4: marcar en rojo y mostrar 'Este campo es obligatorio' sin enviar al servidor
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      var correo   = document.getElementById('correo');
      var password = document.getElementById('password');
      var errC     = document.getElementById('err-correo');
      var errP     = document.getElementById('err-password');
      var valid    = true;

      correo.classList.remove('error');
      password.classList.remove('error');
      errC.classList.remove('show');
      errP.classList.remove('show');

      if (!correo.value.trim()) {
        correo.classList.add('error');
        errC.classList.add('show');
        valid = false;
      }
      if (!password.value.trim()) {
        password.classList.add('error');
        errP.classList.add('show');
        valid = false;
      }
      if (!valid) e.preventDefault();
    });

    function togglePass() {
      var input = document.getElementById('password');
      var icon  = document.getElementById('eye-icon');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    }

    // Limpiar error al escribir
    ['correo', 'password'].forEach(function(id) {
      document.getElementById(id).addEventListener('input', function() {
        this.classList.remove('error');
        document.getElementById('err-' + id).classList.remove('show');
      });
    });
  </script>

  <?php if ($alert): ?>
  <script>
    Swal.fire({
      icon: '<?= htmlspecialchars($alert['icon']) ?>',
      title: '<?= htmlspecialchars($alert['title']) ?>',
      html: '<?= htmlspecialchars($alert['text']) ?><?php if (($alert['icon'] === 'error') && isset($_SESSION['login_locked_until']) && $_SESSION['login_locked_until'] > time()): ?><br><br><a href="../views/usuarios/recuperar.php" style="color:#0d9488;font-weight:700;">→ Recuperar contraseña</a><?php endif; ?>',
      confirmButtonColor: '#0d9488'
    });
  </script>
  <?php endif; ?>
</body>
</html>
