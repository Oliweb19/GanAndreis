<?php
/**
 * Instalador Automático de Base de Datos - GanAndreis
 * Ejecuta este archivo en tu navegador una sola vez para crear las tablas
 */

require_once __DIR__ . '/conexion.php';

$mensaje = '';
$tipoMensaje = '';
$instalado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = obtenerConexion();
    
    if (!$pdo) {
        $mensaje = "❌ No se pudo conectar a la base de datos. Por favor revisa los datos (DB_HOST, DB_NAME, DB_USER, DB_PASS) en el archivo <code>conexion.php</code>.";
        $tipoMensaje = "error";
    } else {
        try {
            $sqlFile = __DIR__ . '/database.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception("No se encontró el archivo database.sql.");
            }
            
            $sql = file_get_contents($sqlFile);
            
            // Ejecutar las sentencias SQL
            $pdo->exec($sql);
            
            $mensaje = "✅ ¡Instalación completada con éxito! Las tablas se crearon y se registraron los datos iniciales.<br><br>
                        <strong>Credenciales por defecto de Admin:</strong><br>
                        Usuario: <code>admin@ganandreis.com</code><br>
                        Contraseña: <code>admin123</code>";
            $tipoMensaje = "success";
            $instalado = true;
        } catch (Exception $e) {
            $mensaje = "❌ Error al ejecutar el script de base de datos: " . $e->getMessage();
            $tipoMensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador | GanAndreis</title>
    <link rel="stylesheet" href="Assets/css/index.css">
    <link rel="stylesheet" href="Assets/icons/fontawesome-free-6.2.1-web/css/all.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            background: #000;
        }
        .install-card {
            background: #1C1C1E;
            border: 1px solid #262628;
            border-radius: 16px;
            max-width: 550px;
            width: 100%;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            text-align: center;
        }
        .install-card h1 {
            color: var(--color-primario);
            font-size: 26px;
            margin-bottom: 10px;
        }
        .install-card p {
            color: var(--color-terciario);
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: left;
            font-size: 14px;
            line-height: 1.6;
        }
        .alert.success {
            background: rgba(48, 209, 88, 0.15);
            border: 1px solid #30D158;
            color: #fff;
        }
        .alert.error {
            background: rgba(255, 69, 58, 0.15);
            border: 1px solid #FF453A;
            color: #fff;
        }
        .install-btn {
            background: var(--color-primario);
            color: #000;
            font-weight: 700;
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 15px;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s ease;
        }
        .install-btn:hover {
            opacity: 0.9;
            transform: scale(1.01);
        }
        .links-group {
            margin-top: 25px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .links-group a {
            color: var(--color-primario);
            font-size: 14px;
            text-decoration: underline;
        }
        code {
            background: #262628;
            padding: 2px 6px;
            border-radius: 4px;
            color: #30D158;
        }
    </style>
</head>
<body>
    <div class="install-card">
        <i class="fa-solid fa-database" style="font-size: 40px; color: var(--color-primario); margin-bottom: 15px;"></i>
        <h1>Instalador de GanAndreis</h1>
        <p>Este asistente configurará automáticamente las tablas y los datos necesarios en tu base de datos de cPanel.</p>

        <?php if ($mensaje): ?>
            <div class="alert <?= $tipoMensaje ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <?php if (!$instalado): ?>
            <form method="POST">
                <button type="submit" class="install-btn">
                    <i class="fa-solid fa-play"></i> INICIAR INSTALACIÓN AHORA
                </button>
            </form>
        <?php else: ?>
            <a href="ini_sesion.php" class="install-btn" style="display: block; text-decoration: none;">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> IR A INICIAR SESIÓN
            </a>
        <?php endif; ?>

        <div class="links-group">
            <a href="index.php">Ir a la Web</a>
            <a href="validar_ticket.php">Consultar Ticket</a>
        </div>
    </div>
</body>
</html>
