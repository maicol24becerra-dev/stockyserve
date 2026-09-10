<?php
require_once __DIR__ . '/config/database.php';
$db = new Database();
$conn = $db->getConnection();
if ($conn) {
    try {
        $conn->exec("ALTER TABLE usuario ADD COLUMN reset_code VARCHAR(10) NULL");
        echo "Columna reset_code agregada. ";
    } catch (Exception $e) { }

    try {
        $conn->exec("ALTER TABLE usuario ADD COLUMN reset_expiry DATETIME NULL");
        echo "Columna reset_expiry agregada. ";
    } catch (Exception $e) { }

    try {
        $conn->exec("ALTER TABLE plato ADD COLUMN imagen VARCHAR(255) NULL");
        echo "Columna imagen agregada a plato. ";
    } catch (Exception $e) { echo $e->getMessage(); }

    try {
        $conn->exec("ALTER TABLE plato ADD COLUMN categoria VARCHAR(100) NULL");
        echo "Columna categoria agregada a plato. ";
    } catch (Exception $e) { echo $e->getMessage(); }
} else {
    echo "No se pudo conectar a la base de datos.";
}
?>
