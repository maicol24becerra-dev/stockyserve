<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

class RegisterController {

    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->conectar();
    }

    public function register() {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../views/usuarios/register.php");
            exit;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $rol = trim($_POST['rol'] ?? 'cliente'); // por defecto cliente

        // 🔍 Validaciones
        if (empty($nombre) || empty($correo) || empty($password)) {
            $this->alert('warning', 'Campos incompletos', 'Complete todos los campos obligatorios');
            header("Location: ../views/usuarios/register.php");
            exit;
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->alert('error', 'Correo inválido', 'Ingrese un correo válido');
            header("Location: ../views/usuarios/register.php");
            exit;
        }

        if (strlen($password) < 6) {
            $this->alert('error', 'Contraseña débil', 'Debe tener al menos 6 caracteres');
            header("Location: ../views/usuarios/register.php");
            exit;
        }

        $usuarioModel = new Usuario($this->db);

        // ❌ Verificar si ya existe el correo
        if ($usuarioModel->existeCorreo($correo)) {
            $this->alert('error', 'Correo existente', 'Este correo ya está registrado');
            header("Location: ../views/usuarios/register.php");
            exit;
        }

        // 🔐 Encriptar contraseña
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 💾 Guardar usuario
        $resultado = $usuarioModel->crear([
            'nombre' => $nombre,
            'correo' => $correo,
            'contrasena' => $passwordHash,
            'telefono' => $telefono,
            'rol' => $rol
        ]);

        if ($resultado) {
            $this->alert('success', 'Registro exitoso', 'Ya puede iniciar sesión');
            header("Location: ../views/usuarios/login.php");
        } else {
            $this->alert('error', 'Error', 'No se pudo registrar el usuario');
            header("Location: ../views/usuarios/register.php");
        }

        exit;
    }

    private function alert($icon, $title, $text) {
        $_SESSION['alert'] = compact('icon', 'title', 'text');
    }
}

// 🎯 CONTROLADOR
$controller = new RegisterController();

$accion = $_POST['action'] ?? 'register';

if ($accion === 'register') {
    $controller->register();
}
?>