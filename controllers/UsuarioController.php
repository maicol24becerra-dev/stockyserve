<?php
session_start();
require_once __DIR__ . '/../models/UsuarioModel.php';

class UsuarioController {
    private UsuarioModel $model;

    public function __construct() {
        $this->requireAdmin();
        $this->model = new UsuarioModel();
    }

    public function getAll(): void {
        echo json_encode($this->model->getAll());
    }

    public function create(): void {
        $data = [
            'nombre'   => trim($_POST['nombre']   ?? ''),
            'email'    => trim($_POST['email']    ?? ''),
            'password' => $_POST['password']      ?? '',
            'rol'      => $_POST['rol']           ?? 'cliente',
            'telefono' => trim($_POST['telefono'] ?? ''),
        ];
        $ok = $this->model->create($data);
        $this->alert($ok ? 'success' : 'error', $ok ? 'Trabajador creado' : 'Error', '');
        header('Location: ../../views/admin/trabajadores.php'); exit;
    }

    public function update(): void {
        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'nombre'   => trim($_POST['nombre']   ?? ''),
            'email'    => trim($_POST['email']    ?? ''),
            'rol'      => $_POST['rol']           ?? 'cliente',
            'telefono' => trim($_POST['telefono'] ?? ''),
            'estado'   => $_POST['estado']        ?? 'Activo',
        ];
        $ok = $this->model->update($id, $data);
        $this->alert($ok ? 'success' : 'error', $ok ? 'Actualizado correctamente' : 'Error al actualizar', '');
        header('Location: ../../views/admin/trabajadores.php'); exit;
    }

    public function delete(): void {
        $id = (int)($_POST['id'] ?? 0);
        $ok = $this->model->delete($id);
        $this->alert($ok ? 'success' : 'error', $ok ? 'Eliminado correctamente' : 'Error al eliminar', '');
        header('Location: ../../views/admin/trabajadores.php'); exit;
    }

    private function requireAdmin(): void {
        if (empty($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
            header('Location: ../../views/auth/index.php'); exit;
        }
    }

    private function alert(string $icon, string $title, string $text): void {
        $_SESSION['alert'] = compact('icon', 'title', 'text');
    }
}

$ctrl   = new UsuarioController();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

match($action) {
    'create' => $ctrl->create(),
    'update' => $ctrl->update(),
    'delete' => $ctrl->delete(),
    'getAll' => $ctrl->getAll(),
    default  => header('Location: ../../views/admin/trabajadores.php')
};
