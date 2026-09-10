<?php
session_start();

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'administrador') {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../models/Plato.php';

class PlatoController {
    private $platoModel;

    public function __construct() {
        $this->platoModel = new PlatoModel();
    }

    public function addPlato(array $data, array $file) {
        $imagenNombre = 'default.jpg'; // Imagen por defecto

        // Subir la imagen si se adjuntó una
        if (isset($file['imagen']) && $file['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            
            // Crear carpeta uploads si no existe
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $extension = pathinfo($file['imagen']['name'], PATHINFO_EXTENSION);
            // Nombre único para evitar sobreescrituras
            $imagenNombre = uniqid('plato_') . '.' . $extension;
            $destino = $uploadDir . $imagenNombre;

            if (!move_uploaded_file($file['imagen']['tmp_name'], $destino)) {
                $imagenNombre = 'default.jpg'; // Si falla, poner la de por defecto
            }
        }

        $data['imagen'] = $imagenNombre;
        $data['disponibilidad'] = 1; // Por defecto activo al crear

        if ($this->platoModel->create($data)) {
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Plato Creado', 'text' => 'El nuevo plato ha sido guardado exitosamente.'];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo guardar el plato.'];
        }

        header('Location: ../views/admin/dashboard.php#inventario');
        exit;
    }
    public function editPlato(array $data, array $file) {
        $id = $data['id_plato'] ?? null;
        if (!$id) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'ID de plato no proporcionado.'];
            header('Location: ../views/admin/dashboard.php#inventario');
            exit;
        }

        $updateData = [
            'nombre' => $data['nombre'],
            'precio' => $data['precio'],
            'categoria' => $data['categoria'] ?? null,
            'disponibilidad' => $data['disponibilidad'] ?? 1
        ];

        // Subir nueva imagen si se adjuntó una
        if (isset($file['imagen']) && $file['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $extension = pathinfo($file['imagen']['name'], PATHINFO_EXTENSION);
            $imagenNombre = uniqid('plato_') . '.' . $extension;
            $destino = $uploadDir . $imagenNombre;
            if (move_uploaded_file($file['imagen']['tmp_name'], $destino)) {
                $updateData['imagen'] = $imagenNombre;
            }
        }

        if ($this->platoModel->update((int)$id, $updateData)) {
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Plato Actualizado', 'text' => 'El plato ha sido actualizado exitosamente.'];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo actualizar el plato.'];
        }

        header('Location: ../views/admin/dashboard.php#inventario');
        exit;
    }

    public function updateDisponibilidad(array $data) {
        $id        = (int)($data['id_plato'] ?? 0);
        $disponible = (int)($data['disponible'] ?? 0);
        $cantidad  = (int)($data['cantidad'] ?? 0);

        if ($id <= 0) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'Plato no válido.'];
            header('Location: ../views/admin/dashboard.php#inventario');
            exit;
        }
        if ($this->platoModel->updateDisponibilidad($id, $disponible, $cantidad)) {
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Disponibilidad Actualizada', 'text' => 'Los cambios fueron guardados correctamente.'];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo actualizar la disponibilidad.'];
        }
        header('Location: ../views/admin/dashboard.php#inventario');
        exit;
    }

    public function updateOferta(array $data) {
        $id             = (int)($data['id_plato'] ?? 0);
        $en_oferta      = (int)($data['en_oferta'] ?? 0);
        $precio_oferta  = isset($data['precio_oferta']) && $data['precio_oferta'] !== ''
                          ? (float)$data['precio_oferta'] : null;
        $inicio_oferta  = isset($data['inicio_oferta']) && $data['inicio_oferta'] !== ''
                          ? $data['inicio_oferta'] : null;
        $fin_oferta     = isset($data['fin_oferta']) && $data['fin_oferta'] !== ''
                          ? $data['fin_oferta'] : null;
        $etiqueta       = trim($data['etiqueta_oferta'] ?? '');
        if ($etiqueta === '') $etiqueta = null;
        $es_platillo_dia = (int)($data['es_platillo_dia'] ?? 0);

        if ($id <= 0) {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'Plato no válido.'];
            header('Location: ../views/admin/dashboard.php#inventario');
            exit;
        }

        if ($this->platoModel->updateOferta($id, $en_oferta, $precio_oferta, $inicio_oferta, $fin_oferta, $etiqueta, $es_platillo_dia)) {
            $msg = $en_oferta ? 'Oferta configurada correctamente.' : 'Oferta desactivada.';
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Oferta Actualizada', 'text' => $msg];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo actualizar la oferta.'];
        }
        header('Location: ../views/admin/dashboard.php#inventario');
        exit;
    }

    public function crearPlatilloDia(array $data, array $file) {
        $imagenNombre = 'default.jpg';
        if (isset($file['imagen']) && $file['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext          = pathinfo($file['imagen']['name'], PATHINFO_EXTENSION);
            $imagenNombre = uniqid('dia_') . '.' . $ext;
            if (!move_uploaded_file($file['imagen']['tmp_name'], $uploadDir . $imagenNombre))
                $imagenNombre = 'default.jpg';
        }

        $data['imagen']          = $imagenNombre;
        $data['etiqueta_oferta'] = trim($data['etiqueta_oferta'] ?? '') ?: 'Platillo del Día';
        $data['inicio_oferta']   = isset($data['inicio_oferta']) && $data['inicio_oferta'] !== '' ? $data['inicio_oferta'] : null;
        $data['fin_oferta']      = isset($data['fin_oferta'])    && $data['fin_oferta']    !== '' ? $data['fin_oferta']    : null;
        $data['precio_oferta']   = isset($data['precio_oferta']) && $data['precio_oferta'] !== '' ? (float)$data['precio_oferta'] : null;

        $id = $this->platoModel->crearPlatilloDia($data);
        if ($id > 0) {
            $_SESSION['alert'] = ['icon' => 'success', 'title' => 'Platillo del Día Creado', 'text' => 'El platillo especial ya es visible para los clientes.'];
        } else {
            $_SESSION['alert'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo crear el platillo del día.'];
        }
        header('Location: ../views/admin/dashboard.php#inventario');
        exit;
    }
}

$controller = new PlatoController();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add_plato':
        $controller->addPlato($_POST, $_FILES);
        break;
    case 'edit_plato':
        $controller->editPlato($_POST, $_FILES);
        break;
    case 'update_disponibilidad':
        $controller->updateDisponibilidad($_POST);
        break;
    case 'update_oferta':
        $controller->updateOferta($_POST);
        break;
    case 'crear_platillo_dia':
        $controller->crearPlatilloDia($_POST, $_FILES);
        break;
    default:
        header('Location: ../views/admin/dashboard.php#inventario');
        break;
}
