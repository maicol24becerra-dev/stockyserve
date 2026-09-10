<?php
session_start();

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'administrador') {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Plato.php';

class AdminController {
    private UsuarioModel $usuarioModel;
    private PlatoModel   $platoModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
        $this->platoModel   = new PlatoModel();
    }

    // ── USUARIOS ────────────────────────────────────────────────────────────

    public function toggleUserStatus(int $id_usuario, string $nuevo_estado): void {
        // HU-02C Esc.3: no puede desactivarse a sí mismo
        if ($id_usuario === (int)$_SESSION['user_id']) {
            $_SESSION['alert'] = [
                'icon' => 'warning', 'title' => 'Acción denegada',
                'text' => 'No puedes eliminar tu propia cuenta mientras tienes sesión activa.'
            ];
        } else {
            $this->usuarioModel->updateStatus($id_usuario, $nuevo_estado);
            $_SESSION['alert'] = [
                'icon' => 'success', 'title' => 'Actualizado',
                'text' => 'El estado del usuario ha sido actualizado.'
            ];
        }
        header('Location: ../views/admin/dashboard.php#usuarios');
        exit;
    }

    public function deleteUser(int $id_usuario): void {
        // HU-02C Esc.3: no puede eliminarse a sí mismo
        if ($id_usuario === (int)$_SESSION['user_id']) {
            $_SESSION['alert'] = [
                'icon' => 'warning', 'title' => 'Acción denegada',
                'text' => 'No puedes eliminar tu propia cuenta mientras tienes sesión activa.'
            ];
            header('Location: ../views/admin/dashboard.php#usuarios');
            exit;
        }

        // HU-02C Esc.2: bloquear si tiene pedidos activos
        if ($this->usuarioModel->tienePedidosActivos($id_usuario)) {
            $_SESSION['alert'] = [
                'icon' => 'error', 'title' => 'No se puede eliminar',
                'text' => 'No se puede eliminar: el usuario tiene pedidos activos asignados. Reasigna los pedidos antes de continuar.'
            ];
            header('Location: ../views/admin/dashboard.php#usuarios');
            exit;
        }

        // Desactivación suave (HU-02C Esc.1: marcar como Inactivo, no borrar físicamente)
        if ($this->usuarioModel->updateStatus($id_usuario, 'inactivo')) {
            $_SESSION['alert'] = [
                'icon' => 'success', 'title' => 'Usuario eliminado',
                'text' => 'Usuario eliminado correctamente.'
            ];
        } else {
            $_SESSION['alert'] = [
                'icon' => 'error', 'title' => 'Error',
                'text' => 'No se pudo eliminar al usuario.'
            ];
        }
        header('Location: ../views/admin/dashboard.php#usuarios');
        exit;
    }

    public function addUser(array $data): void {
        $nombre   = trim($data['nombre'] ?? '');
        $correo   = trim($data['correo'] ?? '');
        $password = trim($data['password'] ?? '');
        $id_rol   = (int)($data['id_rol'] ?? 0);

        // HU-02A Esc.3: campos obligatorios
        if (!$nombre || !$correo || !$password || $id_rol === 0) {
            $_SESSION['alert'] = [
                'icon' => 'warning', 'title' => 'Campos incompletos',
                'text' => 'Todos los campos son obligatorios.'
            ];
            header('Location: ../views/admin/dashboard.php#usuarios');
            exit;
        }

        // HU-02A Esc.5: rol seleccionado
        if ($id_rol === 0) {
            $_SESSION['alert'] = [
                'icon' => 'warning', 'title' => 'Rol no seleccionado',
                'text' => 'Debe seleccionar un rol.'
            ];
            header('Location: ../views/admin/dashboard.php#usuarios');
            exit;
        }

        // HU-02A Esc.4: formato de correo
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['alert'] = [
                'icon' => 'warning', 'title' => 'Correo inválido',
                'text' => 'Formato de correo inválido.'
            ];
            header('Location: ../views/admin/dashboard.php#usuarios');
            exit;
        }

        // HU-02A Esc.2: correo ya registrado
        if ($this->usuarioModel->existeCorreo($correo)) {
            $_SESSION['alert'] = [
                'icon' => 'warning', 'title' => 'Correo existente',
                'text' => 'Este correo ya está registrado.'
            ];
            header('Location: ../views/admin/dashboard.php#usuarios');
            exit;
        }

        $data['id_rol'] = $id_rol;
        if ($this->usuarioModel->create($data)) {
            $_SESSION['alert'] = [
                'icon' => 'success', 'title' => 'Usuario creado',
                'text' => 'Usuario creado correctamente.'
            ];
        } else {
            $_SESSION['alert'] = [
                'icon' => 'error', 'title' => 'Error',
                'text' => 'No se pudo guardar el usuario.'
            ];
        }
        header('Location: ../views/admin/dashboard.php#usuarios');
        exit;
    }

    public function updateUser(int $id_usuario, array $data): void {
        // HU-02B Esc.1/3/4: validaciones en el modelo
        $result = $this->usuarioModel->updateUser($id_usuario, $data);

        if (!empty($result['no_changes'])) {
            $_SESSION['alert'] = [
                'icon' => 'info', 'title' => 'Sin cambios',
                'text' => 'No se detectaron cambios.'
            ];
        } elseif ($result['ok']) {
            $_SESSION['alert'] = [
                'icon' => 'success', 'title' => 'Actualizado',
                'text' => 'Usuario actualizado correctamente.'
            ];
        } else {
            $_SESSION['alert'] = [
                'icon' => 'error', 'title' => 'Error',
                'text' => $result['msg']
            ];
        }
        header('Location: ../views/admin/dashboard.php#usuarios');
        exit;
    }

    public function updateRole(int $id_usuario, int $id_rol): void {
        if ($id_usuario === (int)$_SESSION['user_id']) {
            $_SESSION['alert'] = [
                'icon' => 'warning', 'title' => 'Acción denegada',
                'text' => 'No puedes cambiar tu propio rol.'
            ];
        } elseif ($id_rol === 0) {
            $_SESSION['alert'] = [
                'icon' => 'warning', 'title' => 'Rol requerido',
                'text' => 'Debe seleccionar un rol.'
            ];
        } else {
            $this->usuarioModel->updateRole($id_usuario, $id_rol);
            $_SESSION['alert'] = [
                'icon' => 'success', 'title' => 'Rol actualizado',
                'text' => 'El rol del usuario ha sido actualizado.'
            ];
        }
        header('Location: ../views/admin/dashboard.php#usuarios');
        exit;
    }

    // ── PLATOS ───────────────────────────────────────────────────────────────

    public function deletePlato(int $id_plato): void {
        $result = $this->platoModel->delete($id_plato);
        if ($result['ok']) {
            $_SESSION['alert'] = [
                'icon' => 'success', 'title' => 'Eliminado',
                'text' => 'Platillo eliminado correctamente.'
            ];
        } else {
            $_SESSION['alert'] = [
                'icon' => 'error', 'title' => 'No se puede eliminar',
                'text' => $result['msg']
            ];
        }
        header('Location: ../views/admin/dashboard.php#inventario');
        exit;
    }

    // ── CATEGORÍAS ───────────────────────────────────────────────────────────

    public function crearCategoria(string $nombre): void {
        $result = $this->platoModel->crearCategoria($nombre);
        if ($result['ok']) {
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Creada', 'text' => 'Categoría creada correctamente.'];
        } else {
            $_SESSION['alert'] = ['icon' => 'warning', 'title' => 'Error', 'text' => $result['msg']];
        }
        header('Location: ../views/admin/dashboard.php#inventario');
        exit;
    }

    public function eliminarCategoria(string $nombre): void {
        $result = $this->platoModel->eliminarCategoria($nombre);
        if ($result['ok']) {
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Eliminada', 'text' => 'Categoría eliminada correctamente.'];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'No se puede eliminar', 'text' => $result['msg']];
        }
        header('Location: ../views/admin/dashboard.php#inventario');
        exit;
    }
}

$admin  = new AdminController();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'toggle_status':
        $admin->toggleUserStatus((int)$_POST['id_usuario'], $_POST['nuevo_estado']);
        break;
    case 'delete_user':
        $admin->deleteUser((int)$_POST['id_usuario']);
        break;
    case 'add_user':
        $admin->addUser($_POST);
        break;
    case 'update_user':
        $admin->updateUser((int)$_POST['id_usuario'], $_POST);
        break;
    case 'update_role':
        $admin->updateRole((int)$_POST['id_usuario'], (int)$_POST['id_rol']);
        break;
    case 'delete_plato':
        $admin->deletePlato((int)$_POST['id_plato']);
        break;
    case 'crear_categoria':
        $admin->crearCategoria($_POST['nombre'] ?? '');
        break;
    case 'eliminar_categoria':
        $admin->eliminarCategoria($_POST['nombre'] ?? '');
        break;
    default:
        header('Location: ../views/admin/dashboard.php');
        break;
}
