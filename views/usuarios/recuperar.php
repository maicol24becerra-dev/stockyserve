<!DOCTYPE html>
<html lang="es">
<head>
    <?php
    session_start();
    $alert = $_SESSION['alert'] ?? null;
    unset($_SESSION['alert']);
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña | El Cielo</title>
    <link rel="icon" type="image/png" href="../../img/ico.png">
    
    <!-- Modern Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Fondo de pantalla completa con la imagen del restaurante */
            background: url('../../img/login_bg.png') center/cover no-repeat;
            position: relative;
        }

        /* Capa oscura superpuesta para que resalte la tarjeta */
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.7); /* Azul oscuro translúcido */
            backdrop-filter: blur(8px); /* Efecto cristal desenfocado */
            z-index: 1;
        }

        .recovery-container {
            position: relative;
            z-index: 2;
            background: #ffffff;
            width: 100%;
            max-width: 480px;
            padding: 50px 45px;
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: #f0fdfa;
            color: #0d9488;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 25px auto;
            box-shadow: 0 10px 20px rgba(13, 148, 136, 0.1);
        }

        .form-header {
            margin-bottom: 35px;
        }

        .form-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
        }

        .form-header p {
            font-size: 0.95rem;
            color: #64748b;
            line-height: 1.5;
        }

        .form-group {
            text-align: left;
            margin-bottom: 25px;
        }

        .field-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .input-style {
            width: 100%;
            padding: 16px 20px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            color: #0f172a;
            transition: all 0.3s ease;
            outline: none;
            text-align: center;
        }

        .input-style::placeholder {
            color: #94a3b8;
        }

        .input-style:focus {
            border-color: #0d9488;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
        }

        .btn-send {
            width: 100%;
            padding: 16px;
            background: #0d9488;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .btn-send:hover {
            background: #0f766e;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(13, 148, 136, 0.2);
        }

        .btn-send:active {
            transform: translateY(0);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #0f172a;
        }

    </style>
</head>
<body>

    <div class="recovery-container">
        
        <div class="icon-box">
            <i class="fas fa-key"></i>
        </div>

        <div class="form-header">
            <h2>¿Olvidaste tu contraseña?</h2>
            <p>Ingresa la dirección de correo electrónico asociada a tu cuenta y te enviaremos un código para restablecerla.</p>
        </div>

        <form action="../../controllers/AuthController.php" method="POST">
            <input type="hidden" name="action" value="send_code">
            
            <div class="form-group">
                <input type="email" name="correo" class="input-style" placeholder="ejemplo@correo.com" required>
            </div>

            <button type="submit" class="btn-send">
                Enviar Código <i class="fas fa-paper-plane"></i>
            </button>
        </form>

        <a href="../../public/login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Volver al inicio de sesión
        </a>
    </div>

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