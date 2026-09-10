<?php
require_once __DIR__ . '/config/database.php';
$db = new Database();
$conn = $db->getConnection();

$tables = ['plato', 'materia_prima', 'receta', 'pedido', 'item_pedido', 'usuario', 'pago'];

foreach ($tables as $t) {
    echo "\n--- $t ---\n";
    $stmt = $conn->query("DESCRIBE $t");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
        echo str_pad($row['Field'], 20) . " | " . str_pad($row['Type'], 15) . " | " . $row['Key'] . "\n";
    }
}
?>
