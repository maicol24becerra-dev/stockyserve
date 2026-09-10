<?php
require_once __DIR__ . '/../config/database.php';

class PlatoModel {
    private ?PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAll(): array {
        return $this->db->query("SELECT * FROM plato ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Todos los platos incluyendo los agotados (para el menú del cliente — HU-11) */
    public function getAllConEstado(): array {
        return $this->db->query(
            "SELECT * FROM plato ORDER BY disponibilidad DESC, nombre ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDisponibles(): array {
        return $this->db->query(
            "SELECT * FROM plato WHERE disponibilidad > 0 ORDER BY nombre"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Platos en oferta activa (HU-11 Esc.4).
     */
    public function getOfertas(): array {
        try {
            return $this->db->query("
                SELECT * FROM plato
                WHERE en_oferta = 1
                  AND disponibilidad > 0
                  AND (inicio_oferta IS NULL OR inicio_oferta <= NOW())
                  AND (fin_oferta IS NULL OR fin_oferta > NOW())
                ORDER BY es_platillo_dia DESC, nombre ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Verifica si ya existe un plato con el mismo nombre en la misma categoría (HU-03A Esc.2).
     * Si $exclude_id > 0 excluye ese registro (para edición).
     */
    public function existeNombreEnCategoria(string $nombre, string $categoria, int $exclude_id = 0): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM plato
            WHERE LOWER(nombre) = LOWER(?) AND LOWER(categoria) = LOWER(?) AND id_plato != ?
        ");
        $stmt->execute([$nombre, $categoria, $exclude_id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Comprueba si un plato tiene pedidos activos (HU-03C Esc.2).
     */
    public function tienePedidosActivos(int $id): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM item_pedido ip
            JOIN pedido p ON p.id_pedido = ip.id_pedido
            WHERE ip.id_plato = ?
              AND p.estado NOT IN ('Pagado','Cancelado')
        ");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM plato WHERE id_plato=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Crea plato. Valida precio > 0 y nombre duplicado en categoría (HU-03A).
     * Retorna ['ok'=>bool, 'msg'=>string].
     */
    public function create(array $data): array {
        $nombre    = trim($data['nombre'] ?? '');
        $precio    = (float)($data['precio'] ?? 0);
        $categoria = trim($data['categoria'] ?? '');

        if ($precio <= 0) {
            return ['ok' => false, 'msg' => 'El precio debe ser mayor a cero'];
        }
        if ($nombre === '') {
            return ['ok' => false, 'msg' => 'El nombre es obligatorio'];
        }
        if ($categoria === '') {
            return ['ok' => false, 'msg' => 'La categoría es obligatoria'];
        }
        if ($this->existeNombreEnCategoria($nombre, $categoria)) {
            return ['ok' => false, 'msg' => 'Ya existe un platillo con este nombre en esta categoría'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO plato (nombre, precio, descripcion, disponibilidad, imagen, categoria)
            VALUES (?,?,?,?,?,?)
        ");
        $ok = $stmt->execute([
            $nombre,
            $precio,
            $data['descripcion'] ?? null,
            $data['disponibilidad'] ?? 1,
            $data['imagen'] ?? null,
            $categoria,
        ]);
        return ['ok' => $ok, 'msg' => $ok ? '' : 'Error al guardar el platillo'];
    }

    /**
     * Actualiza plato. Valida precio y nombre duplicado (HU-03B).
     */
    public function update(int $id, array $data): array {
        $nombre    = trim($data['nombre'] ?? '');
        $precio    = (float)($data['precio'] ?? 0);
        $categoria = trim($data['categoria'] ?? '');

        if ($precio <= 0) {
            return ['ok' => false, 'msg' => 'El precio debe ser mayor a cero'];
        }
        if ($nombre === '') {
            return ['ok' => false, 'msg' => 'El nombre es obligatorio'];
        }
        if ($this->existeNombreEnCategoria($nombre, $categoria, $id)) {
            return ['ok' => false, 'msg' => 'Ya existe un platillo con este nombre en esta categoría'];
        }

        if (!empty($data['imagen'])) {
            $stmt = $this->db->prepare(
                "UPDATE plato SET nombre=?, precio=?, descripcion=?, categoria=?, disponibilidad=?, imagen=? WHERE id_plato=?"
            );
            $ok = $stmt->execute([
                $nombre, $precio, $data['descripcion'] ?? null,
                $categoria, $data['disponibilidad'] ?? 1, $data['imagen'], $id
            ]);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE plato SET nombre=?, precio=?, descripcion=?, categoria=?, disponibilidad=? WHERE id_plato=?"
            );
            $ok = $stmt->execute([
                $nombre, $precio, $data['descripcion'] ?? null,
                $categoria, $data['disponibilidad'] ?? 1, $id
            ]);
        }
        return ['ok' => $ok, 'msg' => $ok ? '' : 'Error al actualizar el platillo'];
    }

    public function updateDisponibilidad(int $id, int $disponible, int $cantidad): bool {
        $valor = $disponible == 1 ? max(1, $cantidad) : 0;
        $stmt  = $this->db->prepare("UPDATE plato SET disponibilidad=? WHERE id_plato=?");
        return $stmt->execute([$valor, $id]);
    }

    /**
     * Elimina plato. Bloquea si tiene pedidos activos (HU-03C Esc.2).
     * Retorna ['ok'=>bool, 'msg'=>string].
     */
    public function delete(int $id): array {
        if ($this->tienePedidosActivos($id)) {
            return [
                'ok'  => false,
                'msg' => 'No se puede eliminar: el platillo está incluido en pedidos activos'
            ];
        }
        $stmt = $this->db->prepare("DELETE FROM plato WHERE id_plato=?");
        $ok   = $stmt->execute([$id]);
        return ['ok' => $ok, 'msg' => $ok ? '' : 'Error al eliminar el platillo'];
    }

    public function updateOferta(int $id, int $en_oferta, ?float $precio_oferta,
                                  ?string $inicio_oferta, ?string $fin_oferta,
                                  ?string $etiqueta, int $es_platillo_dia = 0): bool {
        $stmt = $this->db->prepare("
            UPDATE plato
            SET en_oferta=?, precio_oferta=?, inicio_oferta=?, fin_oferta=?,
                etiqueta_oferta=?, es_platillo_dia=?
            WHERE id_plato=?
        ");
        return $stmt->execute([
            $en_oferta,
            $en_oferta ? $precio_oferta : null,
            ($en_oferta && $inicio_oferta) ? $inicio_oferta : null,
            ($en_oferta && $fin_oferta)    ? $fin_oferta    : null,
            $en_oferta ? $etiqueta         : null,
            $en_oferta ? $es_platillo_dia  : 0,
            $id
        ]);
    }

    public function crearPlatilloDia(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO plato
                (nombre, precio, descripcion, disponibilidad, imagen, categoria,
                 en_oferta, precio_oferta, inicio_oferta, fin_oferta, etiqueta_oferta, es_platillo_dia)
            VALUES (?,?,?,1,?,?,1,?,?,?,?,1)
        ");
        $stmt->execute([
            $data['nombre'],
            $data['precio'],
            $data['descripcion']   ?? null,
            $data['imagen']        ?? 'default.jpg',
            $data['categoria']     ?? 'Especial',
            $data['precio_oferta'] ?? null,
            $data['inicio_oferta'] ?? null,
            $data['fin_oferta']    ?? null,
            $data['etiqueta_oferta'] ?? 'Platillo del Día',
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ── Categorías ──────────────────────────────────────────────────────────

    /** Lista todas las categorías de la tabla dedicada + las usadas en platos */
    public function getCategorias(): array {
        try {
            $rows = $this->db->query(
                "SELECT nombre FROM categoria ORDER BY nombre"
            )->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            $rows = [];
        }
        // Complementar con categorías ya usadas en platos
        $fromPlatos = $this->db->query(
            "SELECT DISTINCT categoria FROM plato WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria"
        )->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_unique(array_merge($rows, $fromPlatos)));
    }

    /** Crea categoría. Valida duplicado (HU-03D Esc.2). */
    public function crearCategoria(string $nombre): array {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return ['ok' => false, 'msg' => 'El nombre de la categoría es obligatorio'];
        }
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM categoria WHERE LOWER(nombre)=LOWER(?)"
            );
            $stmt->execute([$nombre]);
            if ($stmt->fetchColumn() > 0) {
                return ['ok' => false, 'msg' => 'Ya existe una categoría con este nombre'];
            }
            $ins = $this->db->prepare("INSERT INTO categoria (nombre) VALUES (?)");
            $ins->execute([$nombre]);
            return ['ok' => true, 'msg' => ''];
        } catch (\Exception $e) {
            return ['ok' => false, 'msg' => 'Error al crear la categoría: ' . $e->getMessage()];
        }
    }

    /**
     * Elimina categoría. Bloquea si tiene platos asociados (HU-03D Esc.3).
     * Retorna ['ok'=>bool, 'msg'=>string, 'count'=>int].
     */
    public function eliminarCategoria(string $nombre): array {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM plato WHERE LOWER(categoria)=LOWER(?)"
        );
        $stmt->execute([$nombre]);
        $count = (int)$stmt->fetchColumn();
        if ($count > 0) {
            return [
                'ok'    => false,
                'msg'   => "No se puede eliminar: existen $count platillo(s) asociado(s) a esta categoría",
                'count' => $count,
            ];
        }
        try {
            $del = $this->db->prepare("DELETE FROM categoria WHERE LOWER(nombre)=LOWER(?)");
            $del->execute([$nombre]);
            return ['ok' => true, 'msg' => '', 'count' => 0];
        } catch (\Exception $e) {
            return ['ok' => false, 'msg' => 'Error al eliminar la categoría', 'count' => 0];
        }
    }
}
