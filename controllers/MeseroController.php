<?php
session_start();

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'mesero') {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../models/Plato.php';
require_once __DIR__ . '/../models/Receta.php';
require_once __DIR__ . '/../models/MateriaPrima.php';

class MeseroController {
    private $pedidoModel;
    private $recetaModel;
    private $materiaModel;

    public function __construct() {
        $this->pedidoModel  = new PedidoModel();
        $this->recetaModel  = new RecetaModel();
        $this->materiaModel = new MateriaPrimaModel();
    }

    // Crear pedido nuevo
    public function crearPedido(array $data) {
        $mesa = trim($data['mesa'] ?? '');
        if (empty($mesa)) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>'Debes indicar el número de mesa.'];
            header('Location: ../views/mesero/dashboard.php');
            exit;
        }

        $id_pedido = $this->pedidoModel->crear((int)$_SESSION['user_id'], $mesa);

        if ($id_pedido > 0) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Pedido Creado','text'=>"Pedido para Mesa $mesa creado. Ahora agrega los platos."];
        } else {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>'No se pudo crear el pedido.'];
        }
        header('Location: ../views/mesero/dashboard.php');
        exit;
    }

    // Agregar plato a un pedido existente
    public function agregarItem(array $data) {
        $id_pedido = (int)($data['id_pedido'] ?? 0);
        $id_plato  = (int)($data['id_plato']  ?? 0);
        $cantidad  = (int)($data['cantidad']   ?? 1);

        if ($id_pedido <= 0 || $id_plato <= 0) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>'Datos inválidos.'];
            header('Location: ../views/mesero/dashboard.php');
            exit;
        }

        $platoModel = new PlatoModel();
        $plato = $platoModel->getById($id_plato);

        if (!$plato) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>'Plato no encontrado.'];
            header('Location: ../views/mesero/dashboard.php');
            exit;
        }

        if ($this->pedidoModel->agregarItem($id_pedido, $id_plato, $cantidad, (float)$plato['precio'])) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Plato Agregado','text'=>"'{$plato['nombre']}' agregado al pedido."];
        } else {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>'No se pudo agregar el plato.'];
        }
        header('Location: ../views/mesero/dashboard.php');
        exit;
    }

    // Marcar pedido como servido
    public function servirPedido(array $data) {
        $id_pedido = (int)($data['id_pedido'] ?? 0);
        if ($id_pedido <= 0) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>'Pedido no válido.'];
            header('Location: ../views/mesero/dashboard.php');
            exit;
        }

        if ($this->pedidoModel->cambiarEstado($id_pedido, 'Servido')) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Pedido Servido','text'=>'El pedido fue marcado como servido.'];
        } else {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>'No se pudo actualizar el pedido.'];
        }
        header('Location: ../views/mesero/dashboard.php');
        exit;
    }

    // Completar pedido y descontar inventario
    public function completarPedido(array $data) {
        $id_pedido = (int)($data['id_pedido'] ?? 0);
        if ($id_pedido <= 0) {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>'Pedido no válido.'];
            header('Location: ../views/mesero/dashboard.php');
            exit;
        }

        $items = $this->pedidoModel->getItemsByPedido($id_pedido);
        foreach ($items as $item) {
            $receta = $this->recetaModel->getRecetaByPlato($item['id_plato']);
            foreach ($receta as $ing) {
                $this->materiaModel->deductStock($ing['id_materia'], $ing['cantidad_requerida'] * $item['cantidad']);
            }
        }

        if ($this->pedidoModel->markAsCompleted($id_pedido)) {
            $_SESSION['alert'] = ['icon'=>'success','title'=>'Completado','text'=>'Pedido completado e inventario descontado.'];
        } else {
            $_SESSION['alert'] = ['icon'=>'error','title'=>'Error','text'=>'No se pudo completar el pedido.'];
        }
        header('Location: ../views/mesero/dashboard.php');
        exit;
    }
}

$controller = new MeseroController();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'crear_pedido':
        $controller->crearPedido($_POST);
        break;
    case 'agregar_item':
        $controller->agregarItem($_POST);
        break;
    case 'servir_pedido':
        $controller->servirPedido($_POST);
        break;
    case 'completar_pedido':
        $controller->completarPedido($_POST);
        break;
    default:
        header('Location: ../views/mesero/dashboard.php');
        break;
}
