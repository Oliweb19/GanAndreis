<?php
/**
 * Gestión de Sorteos - GanAndreis Admin
 */
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../ini_sesion.php');
    exit;
}

require_once __DIR__ . '/../conexion.php';
$pdo = obtenerConexion();

$sorteos = [];
$sorteoActivo = null;

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT *, DATE_FORMAT(fecha_sorteo, '%d/%m/%Y') AS fecha_formateada FROM sorteos ORDER BY activo DESC, id DESC");
        $sorteos = $stmt->fetchAll();

        foreach ($sorteos as $s) {
            if ($s['activo']) {
                $sorteoActivo = $s;
                break;
            }
        }
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
    <title>Gestión de Sorteos | GanAndreis Admin</title>
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Banner de Encabezado */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
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

        /* Layout Grid: Formulario y Lista */
        .sorteos-layout {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 25px;
        }
        @media (max-width: 900px) {
            .sorteos-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Tarjeta */
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

        /* Formulario */
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
        .form-group input,
        .form-group textarea {
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
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--color-primario);
        }

        /* Uploader de Imagen */
        .img-preview-box {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 5px;
        }
        .img-preview-thumb {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            border: 2px dashed rgba(48, 209, 88, 0.4);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #101012;
            flex-shrink: 0;
        }
        .img-preview-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .btn-upload-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #262628;
            color: #fff;
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s;
        }
        .btn-upload-label:hover {
            background: var(--color-primario);
            color: #000;
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

        /* Lista de Sorteos */
        .sorteos-grid {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .sorteo-card {
            background: #18181B;
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 16px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            transition: all 0.25s ease;
        }
        .sorteo-card.activo-banner {
            border-color: rgba(48, 209, 88, 0.6);
            background: rgba(48, 209, 88, 0.04);
            box-shadow: 0 0 25px rgba(48, 209, 88, 0.12);
        }
        .sorteo-card-img {
            width: 90px;
            height: 90px;
            border-radius: 14px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
        }
        .sorteo-card.activo-banner .sorteo-card-img {
            border-color: var(--color-primario);
        }
        .sorteo-info {
            flex-grow: 1;
        }
        .sorteo-info h4 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: #fff;
        }
        .sorteo-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 0.85rem;
            color: var(--color-terciario);
            margin-bottom: 6px;
        }
        .sorteo-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .sorteo-meta strong {
            color: #fff;
        }
        .sorteo-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
            flex-shrink: 0;
        }
        .badge-activo {
            background: rgba(48, 209, 88, 0.15);
            color: var(--color-primario);
            border: 1px solid rgba(48, 209, 88, 0.4);
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 0 15px rgba(48, 209, 88, 0.2);
        }
        .badge-inactivo {
            background: rgba(255, 255, 255, 0.05);
            color: var(--color-terciario);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-activar {
            background: var(--color-primario);
            color: #000;
            border: none;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .btn-activar:hover {
            transform: scale(1.04);
            box-shadow: 0 0 20px rgba(48, 209, 88, 0.4);
        }

        /* ========================================================
           RESPONSIVE DESIGN (TABLETS & SMARTPHONES)
           ======================================================== */
        @media (max-width: 900px) {
            .container {
                margin: 20px auto;
                padding: 0 16px;
            }
            .sorteos-layout {
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
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                margin-bottom: 20px;
            }
            .page-header h1 {
                font-size: 1.35rem;
            }

            .card {
                padding: 18px 15px;
                border-radius: 16px;
            }
            .form-group input,
            .form-group textarea {
                font-size: 16px; /* Evita zoom automático en Safari */
            }
        }

        @media (max-width: 600px) {
            .container {
                padding: 0 10px;
            }
            .sorteo-card {
                flex-direction: column;
                align-items: stretch;
                padding: 16px 14px;
                gap: 14px;
            }
            .sorteo-thumb {
                width: 100%;
                height: 160px;
                border-radius: 12px;
            }
            .sorteo-info h4 {
                font-size: 1.05rem;
            }
            .sorteo-actions {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                width: 100%;
                border-top: 1px solid rgba(255, 255, 255, 0.08);
                padding-top: 12px;
                margin-top: 4px;
            }
            .btn-activar {
                padding: 9px 16px;
                font-size: 0.84rem;
            }
            .badge-activo, .badge-inactivo {
                font-size: 0.78rem;
                padding: 5px 10px;
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
            <div>
                <h1><i class="fa-solid fa-ticket-simple"></i> Gestión de Sorteos</h1>
                <p style="color:var(--color-terciario); margin-top:5px; font-size:0.92rem;">
                    Activa un sorteo existente para mostrarlo en la página principal o crea nuevos sorteos para tus rifas.
                </p>
            </div>
        </div>

        <div class="sorteos-layout">
            <!-- Formulario de Crear Nuevo Sorteo -->
            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-circle-plus"></i>
                    <h3>Crear Nuevo Sorteo</h3>
                </div>

                <form id="formCrearSorteo" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Título del Sorteo / Premio</label>
                        <input type="text" name="titulo" placeholder="Ej: Moto Keeway Xpress Lite" required>
                    </div>

                    <div class="form-group">
                        <label>Precio por Boleto (Bs.)</label>
                        <input type="number" step="0.01" name="precio_unitario" value="500.00" required>
                    </div>

                    <div class="form-group">
                        <label>Fecha del Sorteo</label>
                        <input type="date" name="fecha_sorteo" value="2026-12-25" required>
                    </div>

                    <div class="form-group">
                        <label>Hora del Sorteo</label>
                        <input type="text" name="hora_sorteo" value="08:00 PM" required>
                    </div>

                    <div class="form-group">
                        <label>Descripción del Sorteo</label>
                        <textarea name="descripcion" rows="3" placeholder="Información del premio, condiciones y entrega..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Imagen del Premio</label>
                        <div class="img-preview-box">
                            <div class="img-preview-thumb">
                                <img src="../Assets/img/Prueba.jpg" id="previewNuevoSorteo" alt="Vista previa">
                            </div>
                            <div>
                                <label for="inputImgNuevo" class="btn-upload-label">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Seleccionar
                                </label>
                                <input type="file" id="inputImgNuevo" name="imagen" accept="image/*" style="display:none;">
                                <p style="font-size:0.75rem; color:var(--color-terciario); margin-top:5px;">JPG, PNG, WEBP.</p>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; align-items:center; gap:8px; margin: 15px 0;">
                        <input type="checkbox" name="activo" id="checkActivar" value="1" checked style="width:18px; height:18px; accent-color:var(--color-primario); cursor:pointer;">
                        <label for="checkActivar" style="margin:0; font-size:0.85rem; color:#fff; cursor:pointer;">
                            Activar inmediatamente este sorteo en la página principal
                        </label>
                    </div>

                    <button type="submit" class="btn-submit-main" id="btnSubmitCrear">
                        <i class="fa-solid fa-floppy-disk"></i> REGISTRAR SORTEO
                    </button>
                </form>
            </div>

            <!-- Listado de Sorteos -->
            <div class="card">
                <div class="card-header" style="justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fa-solid fa-list-check"></i>
                        <h3>Sorteos Registrados (<?= count($sorteos) ?>)</h3>
                    </div>
                </div>

                <div class="sorteos-grid">
                    <?php if (empty($sorteos)): ?>
                        <div style="text-align:center; padding: 40px; color:var(--color-terciario);">
                            <i class="fa-regular fa-folder-open" style="font-size:2.5rem; display:block; margin-bottom:10px;"></i>
                            Aún no hay sorteos registrados en la base de datos.
                        </div>
                    <?php else: ?>
                        <?php foreach ($sorteos as $s): ?>
                            <?php 
                                $imgRuta = !empty($s['imagen']) ? '../' . $s['imagen'] : '../Assets/img/Prueba.jpg';
                            ?>
                            <div class="sorteo-card <?= $s['activo'] ? 'activo-banner' : '' ?>">
                                <img src="<?= htmlspecialchars($imgRuta) ?>" class="sorteo-card-img" alt="Premio">
                                
                                <div class="sorteo-info">
                                    <h4><?= htmlspecialchars($s['titulo']) ?></h4>
                                    <div class="sorteo-meta">
                                        <span><i class="fa-solid fa-calendar"></i> Fecha: <strong><?= htmlspecialchars($s['fecha_formateada']) ?></strong></span>
                                        <span><i class="fa-solid fa-clock"></i> Hora: <strong><?= htmlspecialchars($s['hora_sorteo']) ?></strong></span>
                                        <span><i class="fa-solid fa-coins"></i> Precio: <strong style="color:var(--color-primario);">Bs. <?= number_format($s['precio_unitario'], 2) ?></strong></span>
                                    </div>
                                    <?php if (!empty($s['descripcion'])): ?>
                                        <p style="font-size:0.82rem; color:var(--color-terciario); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                            <?= htmlspecialchars($s['descripcion']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="sorteo-actions">
                                    <?php if ($s['activo']): ?>
                                        <span class="badge-activo">
                                            <i class="fa-solid fa-circle-check"></i> EN CURSO (ACTIVO)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-inactivo">
                                            <i class="fa-solid fa-circle-pause"></i> Inactivo
                                        </span>
                                        <button type="button" class="btn-activar btn-activar-accion" data-id="<?= $s['id'] ?>" data-titulo="<?= htmlspecialchars($s['titulo']) ?>">
                                            <i class="fa-solid fa-power-off"></i> Activar Sorteo
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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

            // Preview de Imagen en Formulario
            const inputImgNuevo = document.getElementById('inputImgNuevo');
            const previewNuevoSorteo = document.getElementById('previewNuevoSorteo');
            if (inputImgNuevo && previewNuevoSorteo) {
                inputImgNuevo.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        if (!file.type.match('image.*')) {
                            mostrarAlerta('Por favor selecciona una imagen válida (JPG, PNG o WEBP).', 'warning', 'Formato Inválido');
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewNuevoSorteo.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Registrar Nuevo Sorteo
            const formCrearSorteo = document.getElementById('formCrearSorteo');
            const btnSubmitCrear = document.getElementById('btnSubmitCrear');

            if (formCrearSorteo) {
                formCrearSorteo.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(formCrearSorteo);
                    formData.append('accion', 'crear_sorteo');

                    btnSubmitCrear.disabled = true;
                    btnSubmitCrear.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> REGISTRANDO...';

                    try {
                        const res = await fetch('acciones.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await res.json();
                        if (data.success) {
                            await mostrarAlerta(data.mensaje || '¡Sorteo registrado exitosamente!', 'success', 'Sorteo Creado');
                            location.reload();
                        } else {
                            mostrarAlerta(data.error || 'Ocurrió un error al registrar el sorteo.', 'error', 'Error');
                        }
                    } catch (err) {
                        mostrarAlerta('Error de red al guardar el sorteo.', 'error', 'Error de Conexión');
                    } finally {
                        btnSubmitCrear.disabled = false;
                        btnSubmitCrear.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> REGISTRAR SORTEO';
                    }
                });
            }

            // Activar un sorteo desactivado
            document.querySelectorAll('.btn-activar-accion').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const sorteoId = btn.getAttribute('data-id');
                    const titulo = btn.getAttribute('data-titulo') || 'este sorteo';

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Activando...';

                    const formData = new FormData();
                    formData.append('accion', 'activar_sorteo');
                    formData.append('sorteo_id', sorteoId);

                    try {
                        const res = await fetch('acciones.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await res.json();
                        if (data.success) {
                            await mostrarAlerta(`¡El sorteo "${titulo}" ahora está ACTIVO en la página principal!`, 'success', 'Sorteo Activado');
                            location.reload();
                        } else {
                            mostrarAlerta(data.error || 'Error al activar el sorteo.', 'error', 'Error');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fa-solid fa-power-off"></i> Activar Sorteo';
                        }
                    } catch (err) {
                        mostrarAlerta('Error de conexión con el servidor.', 'error', 'Error de Red');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-power-off"></i> Activar Sorteo';
                    }
                });
            });
        });
    </script>
    <script src="../Assets/js/modal_alerta.js"></script>
</body>
</html>
