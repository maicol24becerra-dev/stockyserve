<?php
require_once __DIR__ . '/../config/database.php';

class RecetaModel {
    private ?PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getRecetaByPlato(int $id_plato): array {
        $sql = "SELECT r.*, m.nombre as ingrediente, m.unidad_medida 
                FROM receta r 
                JOIN materia_prima m ON r.id_materia = m.id_materia 
                WHERE r.id_plato = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_plato]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addIngrediente(int $id_plato, int $id_materia, float $cantidad_requerida): bool {
        // Verificar si ya existe el ingrediente en la receta
        $check = $this->db->prepare("SELECT id_receta FROM receta WHERE id_plato=? AND id_materia=?");
        $check->execute([$id_plato, $id_materia]);
        if ($check->rowCount() > 0) {
            // Actualizar cantidad si ya existe
            $stmt = $this->db->prepare("UPDATE receta SET cantidad_requerida = cantidad_requerida + ? WHERE id_plato=? AND id_materia=?");
            return $stmt->execute([$cantidad_requerida, $id_plato, $id_materia]);
        } else {
            // Insertar nuevo
            $stmt = $this->db->prepare("INSERT INTO receta (id_plato, id_materia, cantidad_requerida) VALUES (?,?,?)");
            return $stmt->execute([$id_plato, $id_materia, $cantidad_requerida]);
        }
    }

    public function deleteIngrediente(int $id_receta): bool {
        $stmt = $this->db->prepare("DELETE FROM receta WHERE id_receta=?");
        return $stmt->execute([$id_receta]);
    }
}
