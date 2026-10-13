<?php
/**
 * Inicio de Sesión Administrativo - GanAndreis
 */
session_start();

// Si ya está logueado, redirigir al Dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: Admin/dashboard.php');
    exit;
}

require_once __DIR__ . '/conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Por favor ingresa tu correo y contraseña.';
    } else {
        $pdo = obtenerConexion();
        if (!$pdo) {
            $error = 'Error de conexión con la base de datos. Por favor verifica conexion.php o ejecuta install.php.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM administradores WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $admin = $stmt->fetch();

                if ($admin) {
                    $valido = false;
                    
                    // Verificar hash seguro (BCrypt / Argon)
                    if (password_verify($password, $admin['password'])) {
                        $valido = true;
                    } 
                    // Fallback para hashes md5 / sha256 o texto plano (actualización automática)
                    elseif ($admin['password'] === md5($password) || $admin['password'] === hash('sha256', $password) || $admin['password'] === $password) {
                        $valido = true;
                        // Actualizar a hash seguro de PHP
                        $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
                        $stmtUpdate = $pdo->prepare("UPDATE administradores SET password = ? WHERE id = ?");
                        $stmtUpdate->execute([$nuevoHash, $admin['id']]);
                    }

                    if ($valido) {
                        $_SESSION['admin_id']     = $admin['id'];
                        $_SESSION['admin_nombre'] = $admin['nombre'];
                        $_SESSION['admin_email']  = $admin['email'];

                        header('Location: Admin/dashboard.php');
                        exit;
                    } else {
                        $error = 'Contraseña incorrecta. Inténtalo nuevamente.';
                    }
                } else {
                    $error = 'No existe ningún usuario registrado con ese correo.';
                }
            } catch (Exception $e) {
                $error = 'Ocurrió un error al procesar el ingreso: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | GanAndreis</title>
    <link rel="stylesheet" href="Assets/css/index.css">
    <link rel="stylesheet" href="Assets/css/menu_and_footer.css">
    <link rel="stylesheet" href="Assets/css/logeo.css">
    <link rel="stylesheet" href="Assets/css/modal_alerta.css">
    <link rel="shortcut icon" href="Assets/img/logo-transparente.png" type="image/x-icon">
    <link href="Assets/icons/fontawesome-free-6.2.1-web/css/all.css" rel="stylesheet">
    <style>
        .login-alert-error {
            background: rgba(255, 69, 58, 0.15);
            border: 1px solid #FF453A;
            color: #FF8F87;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    
    <header>
        <div class="box-menu">
            <div class="box-menu-opciones">
                <ul>
                    <li> <a href="index.php"> <i class="fa-solid fa-house"></i> Inicio</a></li>
                    <li> <a href="validar_ticket.php"> <i class="fa-solid fa-ticket"></i> Ticket</a></li>
                    <li> <a href="https://api.whatsapp.com/send/?phone=584226361960&text=Hola,%20necesito%20ayuda" target="_blank"> <i class="fa-solid fa-phone"></i> Ayuda</a></li>
                </ul>
            </div>

            <div class="box-menu-logo">
                <a href="index.php"><img src="Assets/img/logo-transparente.png" alt="GanAndreis Logo"></a>
            </div>

            <div class="box-menu-botones">
                <a href="ini_sesion.php" id="ini">Iniciar Sesion</a>
            </div>
        </div>
    </header>

    <main>
        <div class="cont-log">
            <div class="box-form">
                <div class="header-form">
                    <h1>Acceso Administrativo</h1>
                    <p>Ingresa tus credenciales maestras</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="login-alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                
                <form action="ini_sesion.php" method="POST">
                    
                    <div class="input-group">
                        <label>Usuario / Correo</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="admin@ganandreis.com" required autocomplete="email">
                    </div>

                    <div class="input-group">
                        <label>Contraseña</label>
                        <div class="password-container">
                            <input type="password" name="password" id="passwordInput" placeholder="••••••••" required autocomplete="current-password">
                            <i class="fa-solid fa-eye" id="togglePassword"></i>
                        </div>
                    </div>

                    <div class="actions">
                        <a href="https://api.whatsapp.com/send/?phone=584226361960&text=Hola,%20olvide%20mi%20clave%20de%20administrador" target="_blank">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="btn-login">INGRESAR AL SISTEMA</button>
                </form>

                <div class="social-login">
                    <p>Contactar Soporte Técnico</p>
                    <div class="box-social">
                        <a href="https://www.instagram.com/Andreis_busines" target="_blank"><i class="fa-brands fa-instagram"></i></a>
                        <a href="https://www.tiktok.com/@Andreis_business" target="_blank"><i class="fa-brands fa-tiktok"></i></a>
                        <a href="https://wa.me/584226361960" target="_blank"><i class="fa-brands fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>

            <div class="box-redireccion">
                <div class="overlay-content">
                    <h1>Control Total</h1>
                    <p>Bienvenido al panel central de GanAndreis.<br>Gestiona tus sorteos, supervisa tickets y conecta con tus ganadores desde un solo lugar.</p>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="cont-footer">
            <div class="box-empresa">
                <h1>GanAndreis</h1>
                <p>
                    ¿Y si hoy es tu día de suerte? 🍀 Únete a nuestra comunidad de ganadores. Rápido, seguro y a un solo 
                    clic de distancia. ¡Tu premio te está esperando!
                </p>
            </div>
            <div class="box-sociales">
                <h1>Redes Sociales</h1>
                <ul>
                    <li><i class="fa-brands fa-instagram"></i> @Andreis_busines</li>
                    <li><i class="fa-brands fa-tiktok"></i> @Andreis_business</li>
                    <li><i class="fa-brands fa-whatsapp"></i> 0422-6361960</li>
                </ul>
            </div>
        </div>
        <div class="cont-copy">
            <p>©COPYRIGHT GanAndreis <?= date('Y') ?> - Todos los Derechos Reservados</p>
        </div>
    </footer>

    <script src="Assets/js/ojo_clave.js"></script>
    <script src="Assets/js/modal_alerta.js"></script>
</body>
</html>
