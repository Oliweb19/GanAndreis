<?php
/**
 * Gestión de Usuarios Administradores - GanAndreis Admin
 */
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../ini_sesion.php');
    exit;
}

require_once __DIR__ . '/../conexion.php';
$pdo = obtenerConexion();

$admins = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, nombre, email, DATE_FORMAT(creado_el, '%d/%m/%Y %h:%i %p') AS fecha_formateada FROM administradores ORDER BY id DESC");
        $admins = $stmt->fetchAll();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios | GanAndreis Admin</title>
    <link rel="shortcut icon" href="../Assets/img/logo-transparente.png" type="image/x-icon">
    <!-- FontAwesome -->
    <link href="../Assets/icons/fontawesome-free-6.2.1-web/css/all.css" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/css/modal_alerta.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primario: #30D158;
            --color-fondo: #0A0A0C;
            --color-tarjeta: #141416;
            --color-tarjeta-hover: #1C1C1E;
            --color-texto: #FFFFFF;
            --color-terciario: #8E8E93;
            --color-peligro: #FF453A;
            --fuente: 'Inter', system-ui, -apple-system, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--fuente);
        }

        body {
            background-color: var(--color-fondo);
            color: var(--color-texto);
            min-height: 100vh;
            padding-bottom: 50px;
        }

        /* Navbar */
        .admin-nav {
            background: rgba(20, 20, 22, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 16px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .admin-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #fff;
        }
        .admin-logo img {
            width: 38px;
            height: auto;
        }
        .admin-logo h2 {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .admin-nav-user {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .btn-nav {
            background: #1C1C1E;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 9px 16px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .btn-nav:hover {
            border-color: var(--color-primario);
            color: var(--color-primario);
            background: rgba(48, 209, 88, 0.1);
        }

        /* Dropdown Menú Gestión */
        .dropdown-gestion {
            position: relative;
            display: inline-block;
        }
        .btn-dropdown-trigger {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }
        .btn-dropdown-trigger .flecha-dropdown {
            font-size: 0.72rem;
            transition: transform 0.25s ease;
        }
        .dropdown-gestion.open .flecha-dropdown {
            transform: rotate(180deg);
        }
        .dropdown-menu-gestion {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: 250px;
            background: #1C1C1E;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 8px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6), 0 0 20px rgba(48, 209, 88, 0.12);
            z-index: 1000;
            display: none;
            flex-direction: column;
            gap: 4px;
            backdrop-filter: blur(12px);
        }
        .dropdown-gestion.open .dropdown-menu-gestion {
            display: flex;
        }
        .dropdown-item-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 12px;
            background: transparent;
            border: none;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            text-align: left;
            width: 100%;
            transition: all 0.2s ease;
        }
        .dropdown-item-btn:hover {
            background: rgba(48, 209, 88, 0.12);
            color: var(--color-primario);
        }
        .dropdown-item-btn i {
            font-size: 1.15rem;
            color: var(--color-primario);
            width: 24px;
            text-align: center;
        }
        .dropdown-item-btn strong {
            display: block;
            font-size: 0.92rem;
            color: #fff;
        }
        .dropdown-item-btn:hover strong {
            color: var(--color-primario);
        }
        .dropdown-item-btn small {
            display: block;
            font-size: 0.75rem;
            color: var(--color-terciario);
            margin-top: 2px;
        }

        .user-pill {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--color-terciario);
        }
        .user-pill i {
            color: var(--color-primario);
        }
        .btn-logout {
            background: rgba(255, 69, 58, 0.1);
            color: var(--color-peligro);
            border: 1px solid rgba(255, 69, 58, 0.2);
            padding: 8px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-logout:hover {
            background: var(--color-peligro);
            color: #fff;
        }

        /* Contenedor Principal */
        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 25px;
        }
        .page-header h1 {
            font-size: 1.7rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-header h1 i {
            color: var(--color-primario);
        }

        .layout-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 25px;
        }
        @media (max-width: 850px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--color-tarjeta);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 24px;
        }
        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .card-header i {
            color: var(--color-primario);
            font-size: 1.25rem;
        }
        .card-header h3 {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0;
        }

        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            color: var(--color-terciario);
            margin-bottom: 6px;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            background: #1C1C1E;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 11px 14px;
            color: #fff;
            outline: none;
            font-size: 0.92rem;
            transition: border 0.2s;
        }
        .form-group input:focus {
            border-color: var(--color-primario);
        }

        .btn-submit-main {
            width: 100%;
            background: var(--color-primario);
            color: #000;
            font-weight: 800;
            border: none;
            border-radius: 12px;
            padding: 13px;
            font-size: 0.95rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
            transition: all 0.25s ease;
        }
        .btn-submit-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(48, 209, 88, 0.35);
        }

        /* Tabla de Administradores */
        .table-responsive {
            overflow-x: auto;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .admin-table th {
            text-align: left;
            padding: 12px 14px;
            color: var(--color-terciario);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .admin-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: #fff;
        }
        .admin-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }
        .badge-user-active {
            background: rgba(48, 209, 88, 0.15);
            color: var(--color-primario);
            border: 1px solid rgba(48, 209, 88, 0.4);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* ========================================================
           RESPONSIVE DESIGN (TABLETS & SMARTPHONES)
           ======================================================== */
        @media (max-width: 850px) {
            .container {
                margin: 20px auto;
                padding: 0 16px;
            }
            .layout-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
                align-items: stretch;
                padding: 12px 16px;
                gap: 12px;
            }
            .admin-logo {
                justify-content: space-between;
                width: 100%;
            }
            .admin-logo img {
                height: 34px;
            }
            .admin-logo h2 {
                font-size: 1.15rem;
            }
            .admin-nav-user {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
                width: 100%;
            }
            .btn-nav, .btn-logout {
                padding: 7px 12px;
                font-size: 0.8rem;
                flex: 1 1 auto;
                justify-content: center;
            }
            .dropdown-menu-gestion {
                right: auto;
                left: 0;
                min-width: 240px;
            }

            .page-header {
                margin-bottom: 20px;
            }
            .page-header h1 {
                font-size: 1.35rem;
            }

            .card {
                padding: 18px 15px;
                border-radius: 16px;
            }
            .form-group input {
                font-size: 16px; /* Evita zoom automático en iOS */
            }
            .table-responsive {
                -webkit-overflow-scrolling: touch;
            }
            .admin-table {
                min-width: 460px;
            }
            .admin-table th, .admin-table td {
                padding: 10px 12px;
                font-size: 0.84rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }
            .card {
                padding: 16px 12px;
            }
            .btn-submit-main {
                padding: 12px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

    <!-- Navegación Superior -->
    <header class="admin-nav">
        <a href="dashboard.php" class="admin-logo">
            <img src="../Assets/img/logo-transparente.png" alt="GanAndreis">
            <h2>GANANDREIS <span style="color:var(--color-primario); font-size:0.9rem; font-weight:600;">ADMIN</span></h2>
        </a>

        <div class="admin-nav-user">
            <!-- Menú Desplegable Gestión -->
            <div class="dropdown-gestion" id="dropdownGestion">
                <button type="button" class="btn-nav btn-dropdown-trigger" id="btnTriggerGestion" title="Opciones de Gestión">
                    <i class="fa-solid fa-layer-group"></i> Gestión <i class="fa-solid fa-chevron-down flecha-dropdown"></i>
                </button>
                <div class="dropdown-menu-gestion" id="menuGestion">
                    <a href="sorteos.php" class="dropdown-item-btn">
                        <i class="fa-solid fa-ticket-simple"></i>
                        <div>
                            <strong>Sorteos</strong>
                            <small>Activar o crear sorteos</small>
                        </div>
                    </a>
                    <a href="usuarios.php" class="dropdown-item-btn">
                        <i class="fa-solid fa-user-shield"></i>
                        <div>
                            <strong>Usuarios</strong>
                            <small>Crear usuario administrador</small>
                        </div>
                    </a>
                </div>
            </div>

            <a href="dashboard.php" class="btn-nav">
                <i class="fa-solid fa-table-columns"></i> Ver Dashboard
            </a>

            <div class="user-pill">
                <i class="fa-solid fa-shield-halved"></i>
                <span><?= htmlspecialchars($_SESSION['admin_nombre'] ?? 'Administrador') ?></span>
            </div>
            <a href="logout.php" class="btn-logout">
                <i class="fa-solid fa-right-from-bracket"></i> Salir
            </a>
        </div>
    </header>

    <!-- Contenido -->
    <div class="container">
        <div class="page-header">
            <h1><i class="fa-solid fa-user-shield"></i> Gestión de Usuarios Administradores</h1>
            <p style="color:var(--color-terciario); margin-top:5px; font-size:0.92rem;">
                Crea nuevas credenciales de acceso para gestionar sorteos, validar pagos y boletos.
            </p>
        </div>

        <div class="layout-grid">
            <!-- Formulario Crear Administrador -->
            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-user-plus"></i>
                    <h3>Crear Administrador</h3>
                </div>

                <form id="formCrearAdmin">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre" placeholder="Ej: Carlos Pérez" required>
                    </div>

                    <div class="form-group">
                        <label>Correo Electrónico (Login)</label>
                        <input type="email" name="email" placeholder="carlos@ganandreis.com" required>
                    </div>

                    <div class="form-group">
                        <label>Contraseña (mínimo 6 caracteres)</label>
                        <input type="password" name="password" placeholder="••••••••" minlength="6" required>
                    </div>

                    <button type="submit" class="btn-submit-main" id="btnSubmitAdmin">
                        <i class="fa-solid fa-plus"></i> CREAR ADMINISTRADOR
                    </button>
                </form>
            </div>

            <!-- Tabla de Administradores -->
            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-users"></i>
                    <h3>Administradores Registrados (<?= count($admins) ?>)</h3>
                </div>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Fecha de Alta</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admins)): ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; padding:30px; color:var(--color-terciario);">
                                        No hay usuarios registrados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($admins as $adm): ?>
                                    <tr>
                                        <td>
                                            <strong style="color:#fff;"><?= htmlspecialchars($adm['nombre']) ?></strong>
                                            <?php if ($adm['id'] == $_SESSION['admin_id']): ?>
                                                <small style="color:var(--color-primario); font-size:0.75rem; margin-left:6px;">(Tú)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="color:var(--color-terciario);"><?= htmlspecialchars($adm['email']) ?></span>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($adm['fecha_formateada']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge-user-active">
                                                <i class="fa-solid fa-circle-check"></i> Activo
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Dropdown Gestión
            const dropdownGestion = document.getElementById('dropdownGestion');
            const btnTriggerGestion = document.getElementById('btnTriggerGestion');

            if (btnTriggerGestion && dropdownGestion) {
                btnTriggerGestion.addEventListener('click', (e) => {
                    e.stopPropagation();
                    dropdownGestion.classList.toggle('open');
                });

                window.addEventListener('click', (e) => {
                    if (!dropdownGestion.contains(e.target)) {
                        dropdownGestion.classList.remove('open');
                    }
                });
            }

            // Crear Administrador vía AJAX
            const formCrearAdmin = document.getElementById('formCrearAdmin');
            const btnSubmitAdmin = document.getElementById('btnSubmitAdmin');

            if (formCrearAdmin) {
                formCrearAdmin.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(formCrearAdmin);
                    formData.append('accion', 'crear_administrador');

                    btnSubmitAdmin.disabled = true;
                    btnSubmitAdmin.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> REGISTRANDO...';

                    try {
                        const res = await fetch('acciones.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await res.json();
                        if (data.success) {
                            await mostrarAlerta(data.mensaje || '¡Nuevo administrador creado correctamente!', 'success', 'Usuario Creado');
                            location.reload();
                        } else {
                            mostrarAlerta(data.error || 'Ocurrió un error al registrar el usuario.', 'error', 'Error');
                        }
                    } catch (err) {
                        mostrarAlerta('Error de conexión con el servidor.', 'error', 'Error de Red');
                    } finally {
                        btnSubmitAdmin.disabled = false;
                        btnSubmitAdmin.innerHTML = '<i class="fa-solid fa-plus"></i> CREAR ADMINISTRADOR';
                    }
                });
            }
        });
    </script>
    <script src="../Assets/js/modal_alerta.js"></script>
</body>
</html>
