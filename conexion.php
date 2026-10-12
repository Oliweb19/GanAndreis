<?php
/**
 * Conexión a la Base de Datos MySQL
 * GanAndreis - Sistema de Rifas y Sorteos
 */

// Datos de configuración de tu servidor en cPanel
// NOTA: En cPanel, el nombre de la BD y del usuario suelen tener un prefijo (ej: ganandre_bd, ganandre_user)
define('DB_HOST', 'localhost');
define('DB_NAME', 'rifas');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function obtenerConexion() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // Si la conexión falla, devolvemos null o lanzamos mensaje controlado
            error_log("Error de conexión a la BD: " . $e->getMessage());
            return null;
        }
    }
    return $pdo;
}

require_once __DIR__ . '/tasa_bcv.php';
