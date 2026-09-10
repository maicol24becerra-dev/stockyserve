<?php
require_once __DIR__ . '/../config/database.php';

class UsuarioModel {
    private ?PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function findByEmail(string $correo): ?array {
        $stmt = $this->db->prepare("
            SELECT u.*, r.nombre AS rol 
            FROM usuario u 
            LEFT JOIN rol r ON u.id_rol = r.id_rol 
            WHERE u.correo = ? LIMIT 1
        ");
        $stmt->execute([$correo]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM usuario WHERE id_usuario = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO usuario (nombre, correo, contrasena, id_rol, telefono, estado)
            VALUES (?, ?, ?, ?, ?, 'activo')
        ");

        return $stmt->execute([
            $data['nombre'],
            $data['correo'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['id_rol'], // ID del rol en lugar del string
            $data['telefono'],
        ]);
    }

    public function updatePassword(int $id, string $newPass): bool {
        $stmt = $this->db->prepare("UPDATE usuario SET contrasena=? WHERE id_usuario=?");
        return $stmt->execute([password_hash($newPass, PASSWORD_DEFAULT), $id]);
    }

    public function existeCorreo(string $correo): bool {
        $stmt = $this->db->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
        $stmt->execute([$correo]);
        return $stmt->fetch() ? true : false;
    }

    public function saveResetCode(int $id, string $code): bool {
        $stmt = $this->db->prepare("
            UPDATE usuario 
            SET reset_code=?, reset_expiry=DATE_ADD(NOW(), INTERVAL 15 MINUTE) 
            WHERE id_usuario=?
        ");
        return $stmt->execute([$code, $id]);
    }

    public function verifyResetCode(string $correo, string $code): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM usuario 
            WHERE correo=? AND reset_code=? AND reset_expiry > NOW() 
            LIMIT 1
        ");
        $stmt->execute([$correo, $code]);
        return $stmt->fetch() ?: null;
    }

    public function clearResetCode(int $id): bool {
        $stmt = $this->db->prepare("
            UPDATE usuario 
            SET reset_code=NULL, reset_expiry=NULL 
            WHERE id_usuario=?
        ");
        return $stmt->execute([$id]);
    }

    // --- MÉTODOS PARA EL ADMINISTRADOR ---

    public function getAllUsers(): array {
        $stmt = $this->db->prepare("
            SELECT u.*, r.nombre AS rol_nombre 
            FROM usuario u 
            LEFT JOIN rol r ON u.id_rol = r.id_rol 
            ORDER BY u.id_usuario DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si el correo ya está en uso por OTRO usuario (HU-02B Esc.3).
     */
    public function correoEnUsoOtroUsuario(string $correo, int $exclude_id): bool {
        $stmt = $this->db->prepare(
            "SELECT id_usuario FROM usuario WHERE correo=? AND id_usuario != ? LIMIT 1"
        );
        $stmt->execute([$correo, $exclude_id]);
        return (bool)$stmt->fetch();
    }

    /**
     * Verifica si un usuario tiene pedidos activos (HU-02C Esc.2).
     */
    public function tienePedidosActivos(int $id): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM pedido
            WHERE id_usuario=? AND estado NOT IN ('Pagado','Cancelado')
        ");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Actualiza usuario. Retorna array con ok/msg para manejar errores semánticos.
     * Detecta si no hubo cambios (HU-02B Esc.4).
     */
    public function updateUser(int $id, array $data): array {
        // Verificar correo duplicado en otro usuario
        if ($this->correoEnUsoOtroUsuario($data['correo'], $id)) {
            return ['ok' => false, 'msg' => 'Este correo ya pertenece a otro usuario'];
        }

        // Verificar si hubo cambios reales (HU-02B Esc.4)
        $actual = $this->findById($id);
        if ($actual &&
            $actual['nombre']  === $data['nombre'] &&
            $actual['correo']  === $data['correo'] &&
            $actual['telefono'] === $data['telefono'] &&
            (int)$actual['id_rol'] === (int)$data['id_rol']
        ) {
            return ['ok' => false, 'msg' => 'No se detectaron cambios', 'no_changes' => true];
        }

        $stmt = $this->db->prepare("
            UPDATE usuario 
            SET nombre=?, correo=?, telefono=?, id_rol=? 
            WHERE id_usuario=?
        ");
        $ok = $stmt->execute([
            $data['nombre'],
            $data['correo'],
            $data['telefono'],
            $data['id_rol'],
            $id
        ]);
        return ['ok' => $ok, 'msg' => $ok ? '' : 'Error al actualizar el usuario'];
    }

    public function updateStatus(int $id, string $estado): bool {
        $stmt = $this->db->prepare("UPDATE usuario SET estado=? WHERE id_usuario=?");
        return $stmt->execute([$estado, $id]);
    }

    public function updateRole(int $id, int $id_rol): bool {
        $stmt = $this->db->prepare("UPDATE usuario SET id_rol=? WHERE id_usuario=?");
        return $stmt->execute([$id_rol, $id]);
    }

    public function deleteUser(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM usuario WHERE id_usuario=?");
        return $stmt->execute([$id]);
    }

    // Obtener todos los clientes registrados (rol = Cliente)
    public function getClientes(): array {
        $stmt = $this->db->prepare("
            SELECT u.id_usuario, u.nombre, u.correo, c.id_cliente
            FROM usuario u
            LEFT JOIN cliente c ON c.id_usuario = u.id_usuario
            INNER JOIN rol r ON u.id_rol = r.id_rol
            WHERE r.nombre = 'Cliente'
              AND u.estado = 'activo'
            ORDER BY u.nombre ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}