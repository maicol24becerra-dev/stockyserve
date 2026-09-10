<?php
session_start();
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>El Cielo | Registro</title>
  <link rel="icon" type="image/png" href="../../img/ico.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; color: #0f172a; }
    .login-container { display: flex; width: 100%; max-width: 1200px; background: #fff; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15); margin: 20px; }
    .panel-image { width: 50%; position: relative; background: url('../../img/login_bg.png') center/cover no-repeat; display: flex; flex-direction: column; justify-content: flex-end; padding: 60px; min-height: 620px; }
    .panel-image::before { content: ''; position: absolute; top:0;left:0;right:0;bottom:0; background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0) 100%); z-index: 1; }
    .image-content { position: relative; z-index: 2; color: #fff; }
    .brand-title { font-family: 'Playfair Display', serif; font-size: 3.2rem; font-weight: 600; margin-bottom: 14px; line-height: 1.1; }
    .brand-subtitle { font-size: 1.05rem; font-weight: 300; opacity: 0.9; max-width: 80%; line-height: 1.6; }
    .panel-form { width: 50%; padding: 48px 64px; display: flex; flex-direction: column; justify-content: center; background: #fff; overflow-y: auto; }
    .form-header { margin-bottom: 28px; }
    .form-header p { text-transform: uppercase; font-size: 0.82rem; font-weight: 700; color: #0d9488; letter-spacing: 2px; margin-bottom: 8px; }
    .form-header h2 { font-size: 2rem; font-weight: 700; color: #0f172a; }
    .field-wrap { margin-bottom: 16px; }
    .field-label { display: block; font-size: 0.88rem; font-weight: 600; color: #334155; margin-bottom: 5px; }
    .input-style { width: 100%; padding: 13px 18px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; font-family: 'Outfit', sans-serif; font-size: 0.95rem; color: #0f172a; transition: all 0.3s; outline: none; }
    .input-style:focus { border-color: #0d9488; background: #fff; box-shadow: 0 0 0 4px rgba(13,148,136,0.1); }
    .input-style.error { border-color: #e74c3c; background: #fff8f8; }
    .field-error { display: none; font-size: 0.76rem; color: #e74c3c; font-weight: 600; margin-top: 4px; }
    .field-error.show { display: block; }
    .input-pass-wrap { position: relative; }
    .toggle-eye { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; font-size: 1rem; }
    /* Indicadores de política de contraseña */
    .pass-rules { margin-top: 8px; display: flex; flex-direction: column; gap: 3px; }
    .pass-rule { font-size: 0.76rem; font-weight: 600; color: #94a3b8; display: flex; align-items: center; gap: 6px; transition: color 0.25s; }
    .pass-rule.ok { color: #10b981; }
    .pass-rule i { font-size: 0.7rem; }
    /* Términos y condiciones */
    .terms-wrap { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; margin-bottom: 16px; cursor: pointer; }
    .terms-wrap input[type=checkbox] { margin-top: 2px; accent-color: #0d9488; width: 16px; height: 16px; flex-shrink: 0; cursor: pointer; }
    .terms-wrap label { font-size: 0.83rem; color: #475569; font-weight: 500; cursor: pointer; line-height: 1.5; }
    .terms-wrap label a { color: #0d9488; font-weight: 700; text-decoration: none; }
    .btn-login { width: 100%; padding: 14px; background: #0f172a; color: #fff; border: none; border-radius: 12px; font-family: 'Outfit', sans-serif; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 4px; }
    .btn-login:hover { background: #1e293b; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(15,23,42,0.15); }
    .register-prompt { margin-top: 20px; text-align: center; font-size: 0.9rem; color: #64748b; }
    .register-prompt a { color: #0d9488; font-weight: 600; text-decoration: none; }
    @media (max-width: 992px) {
      .login-container { flex-direction: column; max-width: 500px; }
      .panel-image { width: 100%; min-height: 220px; padding: 36px; }
      .panel-form { width: 100%; padding: 36px 28px; }
      .brand-title { font-size: 2.2rem; }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="panel-image">
      <div class="image-content">
        <h1 class="brand-title">El Cielo</h1>
        <p class="brand-subtitle">Únete a nuestra familia y descubre los sabores más exquisitos.</p>
      </div>
    </div>

    <div class="panel-form">
      <div class="form-header">
        <p>Centro Vacacional El Cielo</p>
        <h2>Crear una cuenta</h2>
      </div>

      <!-- HU-09: validación client-side completa -->
      <form id="regForm" action="../../controllers/AuthController.php" method="POST" novalidate>
        <input type="hidden" name="action" value="register">

        <!-- Nombre -->
        <div class="field-wrap">
          <label class="field-label" for="nombre">Nombre completo</label>
          <input type="text" name="nombre" id="nombre" class="input-style" placeholder="Escribe tu nombre" autocomplete="name">
          <div class="field-error" id="err-nombre">Este campo es obligatorio</div>
        </div>

        <!-- Correo -->
        <div class="field-wrap">
          <label class="field-label" for="correo">Correo electrónico</label>
          <input type="email" name="correo" id="correo" class="input-style" placeholder="usuario@ejemplo.com" autocomplete="email">
          <div class="field-error" id="err-correo">Este campo es obligatorio</div>
          <div class="field-error" id="err-correo-fmt" style="display:none;">Formato de correo inválido (usuario@dominio)</div>
        </div>

        <!-- Teléfono -->
        <div class="field-wrap">
          <label class="field-label" for="telefono">Número telefónico</label>
          <input type="tel" name="telefono" id="telefono" class="input-style" placeholder="Ej. 3001234567" maxlength="15" autocomplete="tel">
          <div class="field-error" id="err-telefono">Este campo es obligatorio</div>
          <div class="field-error" id="err-telefono-fmt" style="display:none;">El teléfono debe tener mínimo 10 dígitos numéricos</div>
        </div>

        <!-- Contraseña -->
        <div class="field-wrap">
          <label class="field-label" for="passwordField">Contraseña</label>
          <div class="input-pass-wrap">
            <input type="password" name="password" id="passwordField" class="input-style" placeholder="••••••••" autocomplete="new-password">
            <button type="button" class="toggle-eye" id="togglePassword">
              <i class="fas fa-eye-slash" id="eye-icon"></i>
            </button>
          </div>
          <div class="field-error" id="err-password">Este campo es obligatorio</div>
          <!-- HU-09 Esc.4: indicadores de política -->
          <div class="pass-rules" id="passRules">
            <div class="pass-rule" id="rule-len"><i class="fas fa-circle-xmark"></i> Mínimo 8 caracteres</div>
            <div class="pass-rule" id="rule-letter"><i class="fas fa-circle-xmark"></i> Al menos una letra</div>
            <div class="pass-rule" id="rule-num"><i class="fas fa-circle-xmark"></i> Al menos un número</div>
          </div>
        </div>

        <!-- Términos y condiciones -->
        <div class="terms-wrap">
          <input type="checkbox" id="terminos" name="terminos">
          <label for="terminos">
            Acepto los <a href="#" onclick="return false;">Términos y Condiciones</a> y la
            <a href="#" onclick="return false;">Política de Privacidad</a> del Centro Vacacional El Cielo.
          </label>
        </div>
        <div class="field-error" id="err-terminos">Debes aceptar los términos y condiciones</div>

        <button type="submit" class="btn-login">
          Crear cuenta <i class="fas fa-user-plus"></i>
        </button>
      </form>

      <div class="register-prompt">
        ¿Ya tienes una cuenta? <a href="../../public/login.php">Inicia sesión aquí</a>
      </div>
    </div>
  </div>

  <script>
    var passField = document.getElementById('passwordField');
    var toggleBtn = document.getElementById('togglePassword');
    var eyeIcon   = document.getElementById('eye-icon');

    // Reglas de contraseña en tiempo real
    passField.addEventListener('input', function() {
      var v = this.value;
      setRule('rule-len',    v.length >= 8);
      setRule('rule-letter', /[a-zA-Z]/.test(v));
      setRule('rule-num',    /[0-9]/.test(v));
      this.classList.remove('error');
      document.getElementById('err-password').classList.remove('show');
    });

    function setRule(id, ok) {
      var el = document.getElementById(id);
      el.className = 'pass-rule' + (ok ? ' ok' : '');
      el.querySelector('i').className = ok ? 'fas fa-circle-check' : 'fas fa-circle-xmark';
    }

    // Mostrar/ocultar contraseña
    toggleBtn.addEventListener('click', function() {
      if (passField.type === 'password') {
        passField.type = 'text';
        eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
      } else {
        passField.type = 'password';
        eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
      }
    });

    // Validación de teléfono: solo dígitos al escribir
    document.getElementById('telefono').addEventListener('input', function() {
      this.value = this.value.replace(/[^0-9+\s\-()]/g, '');
      this.classList.remove('error');
      document.getElementById('err-telefono').classList.remove('show');
      document.getElementById('err-telefono-fmt').style.display = 'none';
    });

    // Limpiar errores al escribir
    ['nombre','correo'].forEach(function(id) {
      document.getElementById(id).addEventListener('input', function() {
        this.classList.remove('error');
        document.getElementById('err-' + id).classList.remove('show');
        if (id === 'correo') document.getElementById('err-correo-fmt').style.display = 'none';
      });
    });

    // Validación al enviar (HU-09 Esc.3,4,5)
    document.getElementById('regForm').addEventListener('submit', function(e) {
      var valid = true;

      // Nombre
      var nombre = document.getElementById('nombre');
      if (!nombre.value.trim()) {
        nombre.classList.add('error');
        document.getElementById('err-nombre').classList.add('show');
        valid = false;
      }

      // Correo
      var correo = document.getElementById('correo');
      var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!correo.value.trim()) {
        correo.classList.add('error');
        document.getElementById('err-correo').classList.add('show');
        valid = false;
      } else if (!emailRe.test(correo.value.trim())) {
        correo.classList.add('error');
        document.getElementById('err-correo-fmt').style.display = 'block';
        valid = false;
      }

      // Teléfono: mínimo 10 dígitos
      var tel = document.getElementById('telefono');
      var digits = tel.value.replace(/\D/g, '');
      if (!tel.value.trim()) {
        tel.classList.add('error');
        document.getElementById('err-telefono').classList.add('show');
        valid = false;
      } else if (digits.length < 10) {
        tel.classList.add('error');
        document.getElementById('err-telefono-fmt').style.display = 'block';
        valid = false;
      }

      // Contraseña: mínimo 8 chars, letras y números
      var pass = document.getElementById('passwordField');
      if (!pass.value) {
        pass.classList.add('error');
        document.getElementById('err-password').classList.add('show');
        valid = false;
      } else if (pass.value.length < 8 || !/[a-zA-Z]/.test(pass.value) || !/[0-9]/.test(pass.value)) {
        pass.classList.add('error');
        document.getElementById('err-password').textContent = 'La contraseña debe tener mínimo 8 caracteres e incluir letras y números';
        document.getElementById('err-password').classList.add('show');
        valid = false;
      }

      // Términos
      if (!document.getElementById('terminos').checked) {
        document.getElementById('err-terminos').classList.add('show');
        valid = false;
      } else {
        document.getElementById('err-terminos').classList.remove('show');
      }

      if (!valid) e.preventDefault();
    });
  </script>

  <?php if ($alert): ?>
  <script>
    Swal.fire({
      icon: '<?= htmlspecialchars($alert['icon']) ?>',
      title: '<?= htmlspecialchars($alert['title']) ?>',
      text: '<?= htmlspecialchars($alert['text']) ?>',
      confirmButtonColor: '#0d9488'
    });
  </script>
  <?php endif; ?>
</body>
</html>
