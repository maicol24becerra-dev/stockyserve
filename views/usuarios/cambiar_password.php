<?php
session_start();
if (!isset($_SESSION['reset_user_id'])) {
    header('Location: ../../public/login.php');
    exit;
}
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña | El Cielo</title>
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
            background: url('../../img/login_bg.png') center/cover no-repeat;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.7); 
            backdrop-filter: blur(8px);
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
            margin-bottom: 20px;
            position: relative;
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
        }

        .input-style::placeholder {
            color: #94a3b8;
        }

        .input-style:focus {
            border-color: #0d9488;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
        }

        .input-pass-wrap {
            position: relative;
        }

        .toggle-eye {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.2s;
        }

        .toggle-eye:hover {
            color: #0f172a;
        }

        .btn-send {
            width: 100%;
            padding: 16px;
            background: #0f172a;
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
            margin-top: 10px;
        }

        .btn-send:hover {
            background: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.15);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 25px;
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .btn-back:hover {
            color: #0f172a;
        }

    </style>
</head>
<body>

    <div class="recovery-container">
        
        <div class="icon-box" style="background:#f1f5f9; color:#0f172a;">
            <i class="fas fa-lock"></i>
        </div>

        <div class="form-header">
            <h2>Nueva Contraseña</h2>
            <p>Ingresa y confirma tu nueva contraseña para acceder al sistema.</p>
        </div>

        <form action="../../controllers/AuthController.php" method="POST">
            <input type="hidden" name="action" value="update_password">
            
            <div class="form-group">
                <label class="field-label">Nueva contraseña</label>
                <div class="input-pass-wrap">
                    <input type="password" name="password" id="pass1" class="input-style" placeholder="Mínimo 6 caracteres" required minlength="6">
                    <button type="button" class="toggle-eye" onclick="togglePass('pass1', 'eye1')"><i class="fas fa-eye-slash" id="eye1"></i></button>
                </div>
            </div>

            <div class="form-group">
                <label class="field-label">Confirmar contraseña</label>
                <div class="input-pass-wrap">
                    <input type="password" name="confirm_password" id="pass2" class="input-style" placeholder="Repite tu contraseña" required minlength="6">
                    <button type="button" class="toggle-eye" onclick="togglePass('pass2', 'eye2')"><i class="fas fa-eye-slash" id="eye2"></i></button>
                </div>
            </div>

            <button type="submit" class="btn-send">
                Actualizar Contraseña <i class="fas fa-save"></i>
            </button>
        </form>

        <a href="../../public/login.php" class="btn-back">
            <i class="fas fa-times"></i> Cancelar
        </a>
    </div>

    <script>
        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            }
        }
    </script>

    <?php if ($alert): ?>
    <script>
        Swal.fire({
            icon: '<?= htmlspecialchars($alert['icon']) ?>',
            title: '<?= htmlspecialchars($alert['title']) ?>',
            text: '<?= htmlspecialchars($alert['text']) ?>',
            confirmButtonColor: '#0f172a',
            customClass: { popup: 'rounded-2xl' }
        });
    </script>
    <?php endif; ?>

</body>
</html>
