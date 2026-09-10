<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../models/Plato.php';
require_once __DIR__ . '/../models/Receta.php';
require_once __DIR__ . '/../models/MateriaPrima.php';
require_once __DIR__ . '/../config/database.php';

// ── Helper de redirección absoluta ──────────────────────────────────────────
function redir(string $path): void {
    $proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $fileDir = rtrim(str_replace('\\', '/', dirname(__FILE__)), '/');
    $projDir = dirname($fileDir);
    $sub     = trim(str_replace($docRoot, '', $projDir), '/');
    $base    = $sub ? "/$sub" : '';
    header("Location: $proto://$host$base/$path");
    exit;
}

class PedidoController {
    private PedidoModel      $pedidoModel;
    private RecetaModel      $recetaModel;
    private MateriaPrimaModel $materiaModel;
    private PlatoModel       $platoModel;

    public function __construct() {
        $this->pedidoModel  = new PedidoModel();
        $this->recetaModel  = new RecetaModel();
        $this->materiaModel = new MateriaPrimaModel();
        $this->platoModel   = new PlatoModel();
    }

    /**
     * Crea un pedido con sus ítems.
     * HU-14 Esc.3: valida carrito vacío.
     * HU-14 Esc.2: omite platillos agotados y avisa.
     * HU-14 Esc.5: guarda nota especial por ítem.
     */
    public function crearPedido(array $data): void {
        $id_usuario   = (int)$_SESSION['user_id'];
        $id_cliente   = (int)($data['id_cliente'] ?? 0);
        $items        = $data['items'] ?? [];

        // HU-14 Esc.3: carrito vacío
        if (empty($items)) {
            $_SESSION['alert'] = [
                'icon' => 'warning', 'title' => 'Carrito vacío',
                'text' => 'Debes seleccionar al menos un platillo.'
            ];
            redir('views/mesero/dashboard.php#menu');
        }

        $id_pedido = $this->pedidoModel->crear($id_usuario, $id_cliente);
        if ($id_pedido <= 0) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo crear el pedido.'];
            redir('views/mesero/dashboard.php#menu');
        }

        $agotados = [];
        foreach ($items as $item) {
            $id_plato = (int)($item['id_plato'] ?? 0);
            $cantidad = max(1, (int)($item['cantidad'] ?? 1));
            $precio   = (float)($item['precio'] ?? 0);
            $nota     = trim($item['nota'] ?? '');
            if ($id_plato <= 0) continue;

            // HU-14 Esc.2: verificar disponibilidad al momento de enviar
            $plato = $this->platoModel->getById($id_plato);
            if (!$plato || (int)$plato['disponibilidad'] <= 0) {
                $agotados[] = $plato['nombre'] ?? "Plato #$id_plato";
                continue;
            }

            $this->pedidoModel->agregarItemConNota($id_pedido, $id_plato, $cantidad, $precio, $nota);
        }

        $msg = "Pedido #$id_pedido creado correctamente.";
        if (!empty($agotados)) {
            $msg .= ' Platillos ya no disponibles omitidos: ' . implode(', ', $agotados) . '.';
        }

        $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Pedido Creado', 'text' => $msg];
        redir('views/mesero/dashboard.php#pedidos');
    }

    public function agregarItem(array $data): void {
        $id_pedido       = (int)($data['id_pedido'] ?? 0);
        $id_plato        = (int)($data['id_plato'] ?? 0);
        $cantidad        = max(1, (int)($data['cantidad'] ?? 1));
        $precio_unitario = (float)($data['precio_unitario'] ?? 0);

        if ($id_pedido <= 0 || $id_plato <= 0) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'Datos inválidos.'];
            redir('views/mesero/dashboard.php#pedidos');
        }

        $pedido = $this->pedidoModel->getById($id_pedido);
        // HU-17 Esc.2: no se puede modificar si está Listo o Pagado
        if (!$pedido || in_array($pedido['estado'], ['Pagado', 'Cancelado', 'En preparación'])) {
            $_SESSION['alert'] = [
                'icon' => 'warning', 'title' => 'No permitido',
                'text' => 'Este pedido ya fue preparado, para agregar platillos crea un nuevo pedido para la mesa.'
            ];
            redir('views/mesero/dashboard.php#pedidos');
        }

        if ($this->pedidoModel->agregarItemConNota($id_pedido, $id_plato, $cantidad, $precio_unitario, '')) {
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Ítem Agregado', 'text' => 'El plato fue añadido al pedido.'];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo agregar el ítem.'];
        }
        redir('views/mesero/dashboard.php#pedidos');
    }

    public function eliminarItem(array $data): void {
        $id_item   = (int)($data['id_item_pedido'] ?? 0);
        $id_pedido = (int)($data['id_pedido'] ?? 0);

        if ($id_item <= 0 || $id_pedido <= 0) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'Datos inválidos.'];
            redir('views/mesero/dashboard.php#pedidos');
        }

        $pedido = $this->pedidoModel->getById($id_pedido);
        if (!$pedido || !in_array($pedido['estado'], ['Pendiente', 'Entregado'])) {
            $_SESSION['alert'] = [
                'icon' => 'warning', 'title' => 'No permitido',
                'text' => 'Solo puedes modificar pedidos en estado Pendiente o Entregado.'
            ];
            redir('views/mesero/dashboard.php#pedidos');
        }

        if ($this->pedidoModel->eliminarItemPedido($id_item, $id_pedido)) {
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Ítem Eliminado', 'text' => 'El plato fue quitado del pedido.'];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo eliminar el ítem.'];
        }
        redir('views/mesero/dashboard.php#pedidos');
    }

    /**
     * Cambia estado de pedido.
     * HU-19 Esc.3: valida transición de estados.
     * HU-19 Esc.4: permite revertir En preparación→Pendiente dentro de 2 min.
     */
    public function cambiarEstado(array $data): void {
        $id_pedido = (int)($data['id_pedido'] ?? 0);
        $estado    = $data['estado'] ?? '';
        $origen    = $data['_redirect'] ?? 'mesero';
        $estados_validos = ['Pendiente', 'En preparación', 'Entregado', 'Pagado', 'Cancelado'];

        $destino = $origen === 'cocinero'
            ? 'views/cocinero/dashboard.php'
            : 'views/mesero/dashboard.php#pedidos';

        if ($id_pedido <= 0 || !in_array($estado, $estados_validos)) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'Datos inválidos.'];
            redir($destino);
        }

        // HU-19 Esc.3: no se puede marcar Listo sin haber pasado por En preparación
        if ($estado === 'Entregado') {
            $pedido = $this->pedidoModel->getById($id_pedido);
            if ($pedido && $pedido['estado'] === 'Pendiente') {
                $_SESSION['alert'] = [
                    'icon' => 'warning', 'title' => 'Transición inválida',
                    'text' => 'Primero debes iniciar la preparación del pedido.'
                ];
                redir($destino);
            }
        }

        if ($this->pedidoModel->cambiarEstado($id_pedido, $estado)) {
            // Descontar inventario al iniciar preparación (HU-19 Esc.1)
            if ($estado === 'En preparación') {
                $items = $this->pedidoModel->getItemsByPedido($id_pedido);
                foreach ($items as $item) {
                    $receta = $this->recetaModel->getRecetaByPlato($item['id_plato']);
                    foreach ($receta as $ing) {
                        try {
                            $this->materiaModel->deductStock(
                                $ing['id_materia'],
                                $ing['cantidad_requerida'] * $item['cantidad']
                            );
                        } catch (\RuntimeException $e) {
                            // Stock insuficiente: registrar aviso pero continuar
                        }
                    }
                }
            }

            if ($estado === 'Pagado') {
                $metodo = trim($data['metodo_pago'] ?? 'Efectivo');
                if ($metodo === '') $metodo = 'Efectivo';
                $monto  = $this->pedidoModel->getTotalPedido($id_pedido);
                $this->pedidoModel->registrarPago($id_pedido, $monto, $metodo);
                redir("views/mesero/factura.php?id=$id_pedido");
            }

            $_SESSION['alert'] = [
                'icon' => 'success', 'title' => 'Estado Actualizado',
                'text' => "Pedido #$id_pedido marcado como $estado."
            ];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo actualizar el estado.'];
        }
        redir($destino);
    }

    /**
     * Revertir pedido de "En preparación" a "Pendiente" dentro de 2 minutos (HU-19 Esc.4).
     */
    public function revertirEstado(int $id_pedido): void {
        $pedido = $this->pedidoModel->getById($id_pedido);
        if (!$pedido || $pedido['estado'] !== 'En preparación') {
            $_SESSION['alert'] = [
                'icon' => 'warning', 'title' => 'No permitido',
                'text' => 'Solo puedes revertir pedidos en estado En preparación.'
            ];
            redir('views/cocinero/dashboard.php');
        }

        // Verificar que no hayan pasado más de 2 minutos
        $minutos = (time() - strtotime($pedido['fecha'])) / 60;
        if ($minutos > 2) {
            // Usamos la fecha de modificación si existe, sino la de creación
            $_SESSION['alert'] = [
                'icon' => 'warning', 'title' => 'Tiempo agotado',
                'text' => 'Solo puedes revertir el estado dentro de los 2 minutos siguientes al cambio.'
            ];
            redir('views/cocinero/dashboard.php');
        }

        if ($this->pedidoModel->cambiarEstado($id_pedido, 'Pendiente')) {
            $_SESSION['alert'] = [
                'icon' => 'success', 'title' => 'Estado revertido',
                'text' => "Pedido #$id_pedido revertido a Pendiente."
            ];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo revertir el estado.'];
        }
        redir('views/cocinero/dashboard.php');
    }

    public function completarPedido(int $id_pedido, string $metodo_pago = 'Efectivo'): void {
        $items = $this->pedidoModel->getItemsByPedido($id_pedido);
        foreach ($items as $item) {
            $receta = $this->recetaModel->getRecetaByPlato($item['id_plato']);
            foreach ($receta as $ingrediente) {
                try {
                    $this->materiaModel->deductStock(
                        $ingrediente['id_materia'],
                        $ingrediente['cantidad_requerida'] * $item['cantidad']
                    );
                } catch (\RuntimeException $e) {
                    // Registrar pero no bloquear el pago
                }
            }
        }

        if ($this->pedidoModel->markAsCompleted($id_pedido)) {
            if ($metodo_pago === '') $metodo_pago = 'Efectivo';
            $monto = $this->pedidoModel->getTotalPedido($id_pedido);
            $this->pedidoModel->registrarPago($id_pedido, $monto, $metodo_pago);
            redir("views/mesero/factura.php?id=$id_pedido");
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo completar el pedido.'];
            redir('views/mesero/dashboard.php#pedidos');
        }
    }

    /**
     * HU-21: Marcar pedido como urgente / quitar prioridad.
     */
    public function marcarUrgente(int $id_pedido, int $urgente): void {
        $this->pedidoModel->setUrgente($id_pedido, $urgente);
        $msg = $urgente ? "Pedido #$id_pedido marcado como Urgente." : "Prioridad removida del pedido #$id_pedido.";
        $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Actualizado', 'text' => $msg];
        redir('views/cocinero/dashboard.php');
    }
}

// ── ENRUTADOR ────────────────────────────────────────────────────────────────
$controller = new PedidoController();
$action     = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'cambiar_estado_cocina':
        $id_pedido = (int)($_POST['id_pedido'] ?? 0);
        $estado    = $_POST['estado'] ?? '';
        if ($id_pedido > 0 && in_array($estado, ['En preparación', 'Entregado', 'Pendiente'])) {
            $controller->cambiarEstado([
                'id_pedido' => $id_pedido,
                'estado'    => $estado,
                '_redirect' => 'cocinero'
            ]);
        } else {
            redir('views/cocinero/dashboard.php');
        }
        break;

    case 'revertir_estado':
        $controller->revertirEstado((int)($_POST['id_pedido'] ?? 0));
        break;

    case 'marcar_urgente':
        $controller->marcarUrgente(
            (int)($_POST['id_pedido'] ?? 0),
            (int)($_POST['urgente']   ?? 0)
        );
        break;

    case 'crear_pedido':
        $controller->crearPedido($_POST);
        break;

    case 'agregar_item':
        $controller->agregarItem($_POST);
        break;

    case 'eliminar_item':
        $controller->eliminarItem($_POST);
        break;

    case 'cambiar_estado':
        $controller->cambiarEstado($_POST);
        break;

    case 'completar_pedido':
        $id_pedido   = (int)($_POST['id_pedido'] ?? 0);
        $metodo_pago = trim($_POST['metodo_pago'] ?? 'Efectivo');
        if ($metodo_pago === '') $metodo_pago = 'Efectivo';
        if ($id_pedido > 0) $controller->completarPedido($id_pedido, $metodo_pago);
        else redir('views/mesero/dashboard.php#pedidos');
        break;

    default:
        $rol = strtolower($_SESSION['user_role'] ?? '');
        redir($rol === 'mesero' ? 'views/mesero/dashboard.php' : 'views/admin/dashboard.php');
        break;
}
