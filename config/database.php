<?php
// Archivo de conexión a la base de datos: StockYServe

// ── URL base del proyecto ──────────────────────────────────────────────────
if (!defined('APP_URL')) {
    $proto    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Detectar subcarpeta: comparar DOCUMENT_ROOT con la ruta física de este archivo
    // Este archivo está en /proyecto/config/database.php
    // DOCUMENT_ROOT apunta a /laragon/www  (o /public_html en producción)
    $docRoot   = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $fileDir   = rtrim(str_replace('\\', '/', dirname(__FILE__)), '/'); // .../config
    $projectDir = dirname($fileDir); // sube un nivel → raíz del proyecto
    $subPath   = str_replace($docRoot, '', $projectDir); // ej: /StockYServe
    $subPath   = '/' . trim($subPath, '/');
    if ($subPath === '/') $subPath = '';

    define('APP_URL',     $proto . '://' . $host . $subPath);
    define('UPLOADS_URL', APP_URL . '/uploads/');
    define('IMG_URL',     APP_URL . '/img/');
    define('UPLOADS_PATH', $projectDir . '/uploads/');
}

// Protección contra doble inclusión
if (!class_exists('Database')) {

class Database {
    private $host     = "localhost";
    private $port     = "3320";
    private $db_name  = "bdstockyserve";
    private $username = "root";
    private $password = "";
    public  $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

} // fin class_exists
