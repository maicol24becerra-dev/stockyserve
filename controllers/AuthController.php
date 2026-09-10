<?php
session_start();
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {

    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    // 🔐 LOGIN
    public function login($correo, $password) {

        // ── Contador de intentos fallidos en sesión ──
        if (!isset($_SESSION['login_attempts']))  $_SESSION['login_attempts']  = 0;
        if (!isset($_SESSION['login_locked_until'])) $_SESSION['login_locked_until'] = 0;

        // Verificar bloqueo activo
        if ($_SESSION['login_locked_until'] > time()) {
            $mins = ceil(($_SESSION['login_locked_until'] - time()) / 60);
            $_SESSION['alert'] = [
                'icon'  => 'error',
                'title' => 'Cuenta bloqueada',
                'text'  => "Demasiados intentos fallidos. Intenta de nuevo en $mins minuto(s)."
            ];
            header('Location: ../public/login.php');
            exit;
        }

        $usuario = $this->usuarioModel->findByEmail($correo);

        if (!$usuario || !password_verify($password, $usuario['contrasena'])) {
            $_SESSION['login_attempts']++;
            $restantes = max(0, 3 - $_SESSION['login_attempts']);

            if ($_SESSION['login_attempts'] >= 3) {
                $_SESSION['login_locked_until'] = time() + (15 * 60); // 15 minutos
                $_SESSION['login_attempts']     = 0;
                $_SESSION['alert'] = [
                    'icon'  => 'error',
                    'title' => 'Cuenta bloqueada',
                    'text'  => 'Superaste el límite de intentos. Acceso bloqueado por 15 minutos.'
                ];
            } else {
                $_SESSION['alert'] = [
                    'icon'  => 'error',
                    'title' => 'Credenciales incorrectas',
                    'text'  => "Usuario o contraseña incorrectos. Te quedan $restantes intento(s)."
                ];
            }
            header('Location: ../public/login.php');
            exit;
        }

        if (strtolower($usuario['estado']) !== 'activo') {
            $_SESSION['alert'] = [
                'icon' => 'error',
                'title' => 'Cuenta inactiva',
                'text' => 'Contacte al administrador'
            ];
            header('Location: ../public/login.php');
            exit;
        }

        // Login exitoso — limpiar contadores
        $_SESSION['login_attempts']     = 0;
        $_SESSION['login_locked_until'] = 0;

        session_regenerate_id(true);

        $_SESSION['user_id']   = $usuario['id_usuario'];
        $_SESSION['user_name'] = $usuario['nombre'];
        $_SESSION['user_role'] = $usuario['rol'];

        $this->redirectByRole($usuario['rol']);
    }

    // 📝 REGISTRO
    public function register($data) {

        if ($this->usuarioModel->findByEmail($data['correo'])) {
            $_SESSION['alert'] = [
                'icon' => 'warning',
                'title' => 'Correo existente',
                'text' => 'Ya está registrado'
            ];
            header('Location: ../views/usuarios/registro.php');
            exit;
        }

        $data['id_rol'] = 4; // 4 = Cliente

        if ($this->usuarioModel->create($data)) {
            // Insertar en tabla cliente para asociar pedidos
            $nuevoUsuario = $this->usuarioModel->findByEmail($data['correo']);
            if ($nuevoUsuario) {
                require_once __DIR__ . '/../config/database.php';
                $db = (new Database())->getConnection();
                $stmt = $db->prepare("INSERT INTO cliente (id_usuario) VALUES (?)");
                $stmt->execute([$nuevoUsuario['id_usuario']]);
            }
            $_SESSION['alert'] = [
                'icon' => 'success',
                'title' => 'Registro exitoso',
                'text' => 'Ahora puede iniciar sesión'
            ];
            header('Location: ../public/login.php');
        } else {
            $_SESSION['alert'] = [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se pudo registrar'
            ];
            header('Location: ../views/usuarios/registro.php');
        }
        exit;
    }

    // 🚪 LOGOUT
    public function logout() {
        // 1. Limpiar todas las variables de sesión
        $_SESSION = [];

        // 2. Eliminar la cookie de sesión del navegador
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // 3. Destruir la sesión en el servidor
        session_destroy();

        // 4. Redirigir al login
        header('Location: ../public/login.php');
        exit;
    }

    // 📩 RECUPERAR CONTRASEÑA
    public function sendCode($correo) {
        $usuario = $this->usuarioModel->findByEmail($correo);
        
        if (!$usuario) {
            $_SESSION['alert'] = [
                'icon' => 'error',
                'title' => 'Correo no encontrado',
                'text' => 'Ese correo no está registrado.'
            ];
            header('Location: ../views/usuarios/recuperar.php');
            exit;
        }

        // Verificar rol — solo clientes pueden recuperar contraseña
        $rol = strtolower($usuario['rol'] ?? '');
        $rolesNoPermitidos = ['mesero', 'cocinero', 'administrador'];

        if (in_array($rol, $rolesNoPermitidos)) {
            $_SESSION['alert'] = [
                'icon' => 'info',
                'title' => 'Recuperación no disponible',
                'text' => 'El personal del establecimiento no puede recuperar su contraseña por este medio. Por favor, solicita al administrador que te actualice la contraseña.'
            ];
            header('Location: ../views/usuarios/recuperar.php');
            exit;
        }

        // Generar código aleatorio de 4 dígitos
        $codigo = sprintf("%04d", mt_rand(1000, 9999));
        
        if (!$this->usuarioModel->saveResetCode($usuario['id_usuario'], $codigo)) {
            $_SESSION['alert'] = [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se pudo generar el código.'
            ];
            header('Location: ../views/usuarios/recuperar.php');
            exit;
        }

        // ── Enviar correo con PHPMailer + Gmail SMTP ──
        $enviado  = false;
        $errorMsg = '';

        // ── CONFIGURACIÓN DE CORREO (editar aquí) ──────────────────────────
        $gmailUser = 'centrovacacionalelcielo@gmail.com'; // Correo Gmail del sistema
        $gmailPass = 'xkwq wnzb iqhb iqhb';              // App Password de Gmail (16 chars sin espacios)
        // ───────────────────────────────────────────────────────────────────

        try {
            require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
            require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
            require_once __DIR__ . '/../lib/PHPMailer/Exception.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $gmailUser;
            $mail->Password   = str_replace(' ', '', $gmailPass); // quitar espacios del App Password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($gmailUser, 'El Cielo - Centro Vacacional');
            $mail->addAddress($correo, $usuario['nombre']);

            $mail->isHTML(true);
            $mail->Subject = '🔐 Código de recuperación — El Cielo';
            $mail->Body    = '
            <div style="font-family:Arial,sans-serif;max-width:480px;margin:0 auto;background:#f0f7f2;padding:30px;border-radius:16px;">
                <div style="text-align:center;margin-bottom:24px;">
                    <h2 style="color:#005500;margin:0;">Centro Vacacional El Cielo</h2>
                    <p style="color:#7aaa8a;font-size:14px;margin:4px 0 0;">Recuperación de contraseña</p>
                </div>
                <div style="background:#fff;border-radius:12px;padding:28px;border:1px solid #c8e8d0;">
                    <p style="color:#0d2b1a;font-size:15px;margin:0 0 16px;">
                        Hola <strong>' . htmlspecialchars($usuario['nombre']) . '</strong>,
                    </p>
                    <p style="color:#3a6b50;font-size:14px;margin:0 0 24px;">
                        Recibimos una solicitud para restablecer tu contraseña. Usa el siguiente código:
                    </p>
                    <div style="text-align:center;background:#e8f5e9;border:2px dashed #00a020;border-radius:12px;padding:20px;margin-bottom:24px;">
                        <span style="font-size:42px;font-weight:900;letter-spacing:12px;color:#005500;">' . $codigo . '</span>
                    </div>
                    <p style="color:#7aaa8a;font-size:13px;margin:0;text-align:center;">
                        ⏱️ Este código expira en <strong>15 minutos</strong>.<br>
                        Si no solicitaste esto, ignora este mensaje.
                    </p>
                </div>
                <p style="color:#b0c8b8;font-size:12px;text-align:center;margin-top:20px;">
                    © ' . date('Y') . ' Centro Vacacional El Cielo · Cauca, Colombia
                </p>
            </div>';
            $mail->AltBody = "Tu código de recuperación es: $codigo\n\nEste código expira en 15 minutos.";

            $mail->send();
            $enviado = true;

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
        }

        if ($enviado) {
            $_SESSION['alert'] = [
                'icon'  => 'success',
                'title' => 'Código enviado',
                'text'  => "Revisa tu correo $correo. El código expira en 15 minutos."
            ];
        } else {
            // Fallback: mostrar el código en pantalla si el correo falla
            $_SESSION['alert'] = [
                'icon'  => 'warning',
                'title' => 'Correo no enviado',
                'text'  => "No se pudo enviar el correo. Tu código temporal es: $codigo (válido 15 min)"
            ];
        }

        header('Location: ../views/usuarios/verificar_codigo.php');
        exit;
    }

    public function verifyCode($d1, $d2, $d3, $d4, $correo) {
        $codigo = $d1 . $d2 . $d3 . $d4;
        
        $usuario = $this->usuarioModel->verifyResetCode($correo, $codigo);
        
        if ($usuario) {
            // Código válido
            $this->usuarioModel->clearResetCode($usuario['id_usuario']);
            $_SESSION['reset_user_id'] = $usuario['id_usuario']; // Para saber qué usuario cambia la clave
            
            header('Location: ../views/usuarios/cambiar_password.php');
        } else {
            $_SESSION['alert'] = [
                'icon' => 'error',
                'title' => 'Código inválido',
                'text' => 'El código es incorrecto o expiró.'
            ];
            header('Location: ../views/usuarios/verificar_codigo.php');
        }
        exit;
    }

    // 🔒 CAMBIAR CONTRASEÑA
    public function updatePassword($password, $confirm) {
        if (!isset($_SESSION['reset_user_id'])) {
            header('Location: ../public/login.php');
            exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'Mínimo 6 caracteres.'];
            header('Location: ../views/usuarios/cambiar_password.php');
            exit;
        }

        if ($password !== $confirm) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Las contraseñas no coinciden', 'text' => 'Asegúrate de escribir la misma contraseña.'];
            header('Location: ../views/usuarios/cambiar_password.php');
            exit;
        }

        if ($this->usuarioModel->updatePassword($_SESSION['reset_user_id'], $password)) {
            unset($_SESSION['reset_user_id'], $_SESSION['correo_reset']);
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Contraseña actualizada', 'text' => 'Tu contraseña ha sido cambiada exitosamente.'];
            header('Location: ../public/login.php');
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo actualizar.'];
            header('Location: ../views/usuarios/cambiar_password.php');
        }
        exit;
    }

    // 🔁 REDIRECCIÓN
    private function redirectByRole($rol) {
        switch (strtolower($rol)) {
            case 'administrador':
                header('Location: ../views/admin/dashboard.php');
                break;
            case 'mesero':
                header('Location: ../views/mesero/dashboard.php');
                break;
            case 'cocinero':
                header('Location: ../views/cocinero/dashboard.php');
                break;
            default:
                header('Location: ../views/cliente/dashboard.php');
                break;
        }
        exit;
    }
}

// CONTROL
$auth = new AuthController();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        $auth->login($_POST['correo'], $_POST['password']);
        break;
    case 'register':
        $auth->register($_POST);
        break;
    case 'logout':
        $auth->logout();
        break;
    case 'send_code':
        $auth->sendCode($_POST['correo'] ?? '');
        break;
    case 'verify_code':
        $auth->verifyCode($_POST['d1'], $_POST['d2'], $_POST['d3'], $_POST['d4'], $_POST['correo'] ?? '');
        break;
    case 'update_password':
        $auth->updatePassword($_POST['password'] ?? '', $_POST['confirm_password'] ?? '');
        break;
    default:
        header('Location: ../public/login.php');
        break;
}