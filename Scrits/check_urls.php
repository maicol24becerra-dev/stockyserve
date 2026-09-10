<?php
require_once __DIR__ . '/../config/database.php';
echo "APP_URL: " . APP_URL . "\n";
echo "UPLOADS_URL: " . UPLOADS_URL . "\n";
echo "IMG_URL: " . IMG_URL . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Este archivo: " . __FILE__ . "\n";
