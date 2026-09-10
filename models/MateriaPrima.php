<?php
require_once __DIR__ . '/../config/database.php';

class MateriaPrimaModel {
    private ?PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAll(): array {
        return $this->db->query(
            "SELECT * FROM materia_prima ORDER BY nombre"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM materia_prima WHERE id_materia=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Crea un nuevo insumo. Incluye proveedor (HU-05).
     */
    public function create(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO materia_prima (nombre, stock_actual, unidad_medida, stock_minimo, proveedor)
            VALUES (?,?,?,?,?)
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['stock_actual'],
            $data['unidad_medida'],
            $data['stock_minimo'],
            $data['proveedor'] ?? null,
        ]);
    }

    /**
     * Actualiza todos los campos incluyendo proveedor (HU-05).
     * Si el nuevo stock_minimo hace que el stock actual quede por debajo,
     * registra una alerta en tabla queja/notificaciones (HU-05 Esc.3).
     */
    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE materia_prima
            SET nombre=?, stock_actual=?, unidad_medida=?, stock_minimo=?, proveedor=?
            WHERE id_materia=?
        ");
        $ok = $stmt->execute([
            $data['nombre'],
            $data['stock_actual'],
            $data['unidad_medida'],
            $data['stock_minimo'],
            $data['proveedor'] ?? null,
            $id,
        ]);
        if ($ok) {
            $this->checkAlertaStock($id);
        }
        return $ok;
    }

    /**
     * Suma stock (entrada de insumo — HU-06 Esc.1).
     */
    public function updateStock(int $id, float $cantidad_a_sumar): bool {
        $stmt = $this->db->prepare(
            "UPDATE materia_prima SET stock_actual = stock_actual + ? WHERE id_materia=?"
        );
        return $stmt->execute([$cantidad_a_sumar, $id]);
    }

    /**
     * Descuenta stock. Bloquea si la cantidad supera el disponible (HU-06 Esc.4).
     * Lanza excepción en ese caso para que el llamador pueda manejarlo.
     */
    public function deductStock(int $id, float $cantidad_a_restar): bool {
        // Verificar stock disponible
        $mp = $this->getById($id);
        if (!$mp) return false;

        if ((float)$mp['stock_actual'] < $cantidad_a_restar) {
            throw new \RuntimeException(
                "La cantidad supera el stock disponible para '{$mp['nombre']}'. " .
                "Disponible: {$mp['stock_actual']} {$mp['unidad_medida']}, " .
                "requerido: {$cantidad_a_restar}."
            );
        }

        $stmt = $this->db->prepare(
            "UPDATE materia_prima SET stock_actual = stock_actual - ? WHERE id_materia=?"
        );
        $ok = $stmt->execute([$cantidad_a_restar, $id]);

        if ($ok) {
            $this->checkAlertaStock($id);
        }
        return $ok;
    }

    /**
     * Verifica si el stock quedó por debajo del mínimo y registra
     * una alerta en la tabla queja como notificación al admin (HU-06 Esc.3).
     */
    public function checkAlertaStock(int $id): void {
        $mp = $this->getById($id);
        if (!$mp) return;

        if ((float)$mp['stock_actual'] <= (float)$mp['stock_minimo']) {
            // Registrar notificación si no existe una pendiente para este insumo
            try {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) FROM queja
                    WHERE mensaje LIKE ? AND estado = 'pendiente'
                ");
                $stmt->execute(['%stock mínimo%' . $mp['nombre'] . '%']);
                if ($stmt->fetchColumn() == 0) {
                    $msgAlerta = "⚠️ ALERTA STOCK: El insumo '{$mp['nombre']}' ha alcanzado el stock mínimo. " .
                                 "Stock actual: {$mp['stock_actual']} {$mp['unidad_medida']} " .
                                 "(mínimo: {$mp['stock_minimo']}).";
                    $ins = $this->db->prepare(
                        "INSERT INTO queja (mensaje, estado, fecha) VALUES (?, 'alerta_stock', NOW())"
                    );
                    $ins->execute([$msgAlerta]);
                }
            } catch (\Exception $e) {
                // La tabla queja puede no existir en instalaciones antiguas
            }
        }
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM materia_prima WHERE id_materia=?");
        return $stmt->execute([$id]);
    }
}
