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
    <title>Verificar Código | El Cielo</title>
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

        .code-inputs {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 35px;
        }

        .digit-box {
            width: 60px;
            height: 65px;
            border-radius: 16px;
            border: 2px solid #e2e8f0;
            background-color: #f8fafc;
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            color: #0f172a;
            outline: none;
            transition: all 0.3s ease;
        }

        .digit-box:focus {
            border-color: #0d9488;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
        }

        .btn-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
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

        .btn-back {
            width: 100%;
            padding: 16px;
            background: transparent;
            color: #64748b;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .btn-back:hover {
            border-color: #94a3b8;
            color: #0f172a;
        }

    </style>
</head>
<body>

<div class="recovery-container">
    <div class="icon-box">
        <i class="fas fa-shield-alt"></i>
    </div>

    <div class="form-header">
        <h2>Ingresa el Código</h2>
        <p>Hemos enviado un código de 4 dígitos a tu correo. Por favor ingrésalo a continuación.</p>
    </div>

    <form action="../../controllers/AuthController.php" method="POST">
        <input type="hidden" name="action" value="verify_code">
        <input type="hidden" name="correo" value="<?= htmlspecialchars($_SESSION['correo_reset'] ?? '') ?>">

        <div class="code-inputs">
            <input type="text" name="d1" class="digit-box" maxlength="1" required autocomplete="off">
            <input type="text" name="d2" class="digit-box" maxlength="1" required autocomplete="off">
            <input type="text" name="d3" class="digit-box" maxlength="1" required autocomplete="off">
            <input type="text" name="d4" class="digit-box" maxlength="1" required autocomplete="off">
        </div>

        <div class="btn-container">
            <button type="submit" class="btn-send">
                Verificar Código <i class="fas fa-check"></i>
            </button>
            <a href="recuperar.php" class="btn-back">Cancelar</a>
        </div>
    </form>
</div>

<?php if ($alert): ?>
<script>
    Swal.fire({
        icon: '<?= htmlspecialchars($alert['icon']) ?>',
        title: '<?= htmlspecialchars($alert['title']) ?>',
        text: '<?= htmlspecialchars($alert['text']) ?>',
        confirmButtonColor: '#0d9488',
        customClass: { popup: 'rounded-2xl' }
    });
</script>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const inputs = document.querySelectorAll(".digit-box");
    
    // Auto-focus logic
    inputs.forEach((input, index) => {
        input.addEventListener("input", function() {
            if (this.value.length === this.maxLength) {
                if (index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            }
        });

        input.addEventListener("keydown", function(e) {
            if (e.key === "Backspace" && this.value === "") {
                if (index > 0) {
                    inputs[index - 1].focus();
                }
            }
        });
    });
});
</script>
</body>
</html>