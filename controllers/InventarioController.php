<?php
session_start();

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'administrador') {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../models/MateriaPrima.php';
require_once __DIR__ . '/../models/Receta.php';

class InventarioController {
    private $materiaModel;
    private $recetaModel;

    public function __construct() {
        $this->materiaModel = new MateriaPrimaModel();
        $this->recetaModel = new RecetaModel();
    }

    public function addMateria(array $data) {
        if ($this->materiaModel->create($data)) {
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Materia Prima Creada', 'text' => 'El ingrediente se ha agregado al inventario.'];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo guardar la materia prima.'];
        }
        header('Location: ../views/admin/dashboard.php#inventario');
        exit;
    }

    public function editMateria(array $data) {
        $id = (int)$data['id_materia'];
        if ($this->materiaModel->update($id, $data)) {
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Materia Prima Actualizada', 'text' => 'Los datos del ingrediente se han guardado correctamente.'];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo actualizar la materia prima.'];
        }
        header('Location: ../views/admin/dashboard.php#inventario');
        exit;
    }

    public function addIngredienteReceta(array $data) {
        $id_plato = (int)$data['id_plato'];
        $id_materia = (int)$data['id_materia'];
        $cantidad = (float)$data['cantidad_requerida'];

        if ($this->recetaModel->addIngrediente($id_plato, $id_materia, $cantidad)) {
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Receta Actualizada', 'text' => 'Ingrediente agregado al plato exitosamente.'];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo agregar el ingrediente.'];
        }
        header('Location: ../views/admin/dashboard.php#inventario');
        exit;
    }
    
    public function updateStock(array $data) {
        $id_materia = (int)$data['id_materia'];
        $cantidad = (float)$data['cantidad_a_sumar'];
        
        if ($this->materiaModel->updateStock($id_materia, $cantidad)) {
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Stock Actualizado', 'text' => 'Se ha ingresado el nuevo stock exitosamente.'];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo actualizar el stock.'];
        }
        header('Location: ../views/admin/dashboard.php#inventario');
        exit;
    }
}

$controller = new InventarioController();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add_materia':
        $controller->addMateria($_POST);
        break;
    case 'edit_materia':
        $controller->editMateria($_POST);
        break;
    case 'add_receta':
        $controller->addIngredienteReceta($_POST);
        break;
    case 'update_stock':
        $controller->updateStock($_POST);
        break;
    default:
        header('Location: ../views/admin/dashboard.php#inventario');
        break;
}
