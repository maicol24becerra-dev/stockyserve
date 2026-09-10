<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3320;dbname=bdstockyserve', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("
    CREATE TABLE IF NOT EXISTS `queja` (
        `id_queja`    INT NOT NULL AUTO_INCREMENT,
        `id_usuario`  INT DEFAULT NULL,
        `nombre`      VARCHAR(100) NOT NULL,
        `mensaje`     TEXT NOT NULL,
        `leida`       TINYINT(1) NOT NULL DEFAULT 0,
        `fecha`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_queja`),
        KEY `id_usuario` (`id_usuario`),
        CONSTRAINT `queja_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

echo "Tabla queja creada correctamente." . PHP_EOL;
