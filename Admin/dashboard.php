<?php
/**
 * Panel de Control Administrativo - GanAndreis
 */
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../ini_sesion.php');
    exit;
}

require_once __DIR__ . '/../conexion.php';
$pdo = obtenerConexion();

$compras = [];
$totalRecaudado = 0;
$totalBoletosValidados = 0;
$totalBoletosPendientes = 0;
$totalCompras = 0;
$sorteoActual = [
    'titulo' => 'Gran Premio',
    'descripcion' => '',
    'fecha_sorteo' => '2026-12-25',
    'hora_sorteo' => '08:00 PM',
    'precio_unitario' => 3.50
];

if ($pdo) {
    try {
        // Datos del sorteo
        $stmtS = $pdo->query("SELECT * FROM sorteos WHERE activo = 1 ORDER BY id DESC LIMIT 1");
        if ($s = $stmtS->fetch()) {
            $sorteoActual = $s;
        }

        // Métricas
        $totalRecaudado = (float)$pdo->query("SELECT COALESCE(SUM(total_monto), 0) FROM compras WHERE estado = 'validado'")->fetchColumn();
        $totalBoletosValidados = (int)$pdo->query("SELECT COALESCE(COUNT(id), 0) FROM tickets WHERE estado = 'validado'")->fetchColumn();
        $totalBoletosPendientes = (int)$pdo->query("SELECT COALESCE(COUNT(id), 0) FROM tickets WHERE estado = 'pendiente'")->fetchColumn();
        $totalCompras = (int)$pdo->query("SELECT COUNT(id) FROM compras")->fetchColumn();

        // Listado de compras con sus tickets
        $stmtCompras = $pdo->query("
            SELECT 
                c.*,
                DATE_FORMAT(c.creado_el, '%d/%m/%Y %h:%i %p') AS fecha_formateada,
                GROUP_CONCAT(t.numero_ticket ORDER BY t.id ASC SEPARATOR ', ') AS lista_tickets
            FROM compras c
            LEFT JOIN tickets t ON t.compra_id = c.id
            GROUP BY c.id
            ORDER BY c.id DESC
        ");
        $compras = $stmtCompras->fetchAll();

    } catch (Exception $e) {
        $errorBD = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo | GanAndreis</title>
    <link rel="stylesheet" href="../Assets/css/index.css">
    <link rel="stylesheet" href="../Assets/icons/fontawesome-free-6.2.1-web/css/all.css">
    <link rel="shortcut icon" href="../Assets/img/logo-transparente.png" type="image/x-icon">
    <style>
        :root {
            --sidebar-width: 260px;
        }
        body {
            background-color: #0c0c0e;
            color: #FFFFFF;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* Barra de navegación superior */
        .admin-nav {
            background: rgba(28, 28, 30, 0.9);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .admin-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: #fff;
        }
        .admin-logo img {
            height: 40px;
        }
        .admin-logo h2 {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .admin-nav-user {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .user-pill {
            background: #262628;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--color-primario);
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(48, 209, 88, 0.2);
        }
        .btn-logout {
            background: rgba(255, 69, 58, 0.15);
            color: #FF453A;
            border: 1px solid #FF453A;
            padding: 8px 16px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-logout:hover {
            background: #FF453A;
            color: #fff;
        }

        /* Contenedor principal */
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 30px 20px;
            flex: 1;
        }

        /* Tarjetas de estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: #1C1C1E;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 22px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 2.2rem;
            opacity: 0.15;
            color: var(--color-primario);
        }
        .stat-card h4 {
            color: var(--color-terciario);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #FFFFFF;
        }
        .stat-card.recaudado .stat-value {
            color: var(--color-primario);
        }
        .stat-card.pendiente .stat-value {
            color: #FFD60A;
        }

        /* Barra de filtros y búsqueda */
        .controls-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .filter-tabs {
            display: flex;
            background: #1C1C1E;
            padding: 5px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.08);
            gap: 5px;
        }
        .filter-btn {
            background: transparent;
            border: none;
            color: var(--color-terciario);
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-btn.active {
            background: var(--color-primario);
            color: #000;
        }
        .search-box {
            position: relative;
            min-width: 280px;
        }
        .search-box input {
            width: 100%;
            height: 44px;
            background: #1C1C1E;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 0 15px 0 40px;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
        }
        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-terciario);
        }
        .btn-config {
            background: #262628;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.1);
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-config:hover {
            border-color: var(--color-primario);
            color: var(--color-primario);
        }

        /* Tabla principal de Compras */
        .table-card {
            background: #1C1C1E;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.08);
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }
        .table-responsive {
            overflow-x: auto;
        }
        table.admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        table.admin-table th {
            background: #262628;
            color: var(--color-terciario);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 16px 20px;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        table.admin-table td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.9rem;
            vertical-align: middle;
        }
        table.admin-table tr:hover {
            background: rgba(255,255,255,0.02);
        }

        /* Badges de Estado */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-badge.validado {
            background: rgba(48, 209, 88, 0.15);
            color: var(--color-primario);
            border: 1px solid var(--color-primario);
        }
        .status-badge.pendiente {
            background: rgba(255, 214, 10, 0.15);
            color: #FFD60A;
            border: 1px solid #FFD60A;
        }
        .status-badge.rechazado {
            background: rgba(255, 69, 58, 0.15);
            color: #FF453A;
            border: 1px solid #FF453A;
        }

        /* Tickets pill */
        .tickets-pill {
            background: rgba(48, 209, 88, 0.1);
            color: var(--color-primario);
            border: 1px dashed rgba(48, 209, 88, 0.4);
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-block;
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Botones de acción */
        .action-btns {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn-action {
            border: none;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-action.approve {
            background: rgba(48, 209, 88, 0.2);
            color: var(--color-primario);
        }
        .btn-action.approve:hover {
            background: var(--color-primario);
            color: #000;
        }
        .btn-action.reject {
            background: rgba(255, 69, 58, 0.2);
            color: #FF453A;
        }
        .btn-action.reject:hover {
            background: #FF453A;
            color: #fff;
        }
        .btn-action.whatsapp {
            background: rgba(37, 211, 102, 0.2);
            color: #25D366;
        }
        .btn-action.whatsapp:hover {
            background: #25D366;
            color: #fff;
        }
        .btn-action.view-img {
            background: #262628;
            color: #fff;
        }
        .btn-action.view-img:hover {
            background: var(--color-primario);
            color: #000;
        }

        /* Modal visor de comprobante */
        .modal-receipt {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(10px);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-receipt-box {
            background: #1C1C1E;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            max-width: 700px;
            width: 100%;
            padding: 25px;
            position: relative;
            text-align: center;
        }
        .modal-receipt-img {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 12px;
            margin: 15px 0;
            object-fit: contain;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .close-modal-btn {
            position: absolute;
            right: 20px;
            top: 20px;
            color: var(--color-terciario);
            font-size: 1.8rem;
            cursor: pointer;
        }
        .close-modal-btn:hover {
            color: #fff;
        }

        /* Modal Configuración Sorteo */
        .form-group-modal {
            margin-bottom: 15px;
            text-align: left;
        }
        .form-group-modal label {
            display: block;
            font-size: 0.85rem;
            color: var(--color-terciario);
            margin-bottom: 6px;
        }
        .form-group-modal input,
        .form-group-modal textarea {
            width: 100%;
            background: #262628;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 10px 14px;
            color: #fff;
            outline: none;
            font-size: 0.95rem;
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
            <button class="btn-config" id="btnAbrirSorteo">
                <i class="fa-solid fa-gear"></i> Editar Sorteo
            </button>
            <div class="user-pill">
                <i class="fa-solid fa-shield-halved"></i>
                <span><?= htmlspecialchars($_SESSION['admin_nombre'] ?? 'Administrador') ?></span>
            </div>
            <a href="logout.php" class="btn-logout">
                <i class="fa-solid fa-right-from-bracket"></i> Salir
            </a>
        </div>
    </header>

    <!-- Contenido Principal -->
    <div class="admin-container">
        
        <!-- Tarjetas de Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card recaudado">
                <i class="fa-solid fa-dollar-sign stat-icon"></i>
                <h4>Total Recaudado (Aprobado)</h4>
                <div class="stat-value">$<?= number_format($totalRecaudado, 2) ?></div>
            </div>

            <div class="stat-card">
                <i class="fa-solid fa-ticket stat-icon"></i>
                <h4>Boletos Validados</h4>
                <div class="stat-value"><?= number_format($totalBoletosValidados) ?></div>
            </div>

            <div class="stat-card pendiente">
                <i class="fa-solid fa-hourglass-half stat-icon"></i>
                <h4>Boletos Pendientes</h4>
                <div class="stat-value"><?= number_format($totalBoletosPendientes) ?></div>
            </div>

            <div class="stat-card">
                <i class="fa-solid fa-cart-shopping stat-icon"></i>
                <h4>Total Solicitudes</h4>
                <div class="stat-value"><?= number_format($totalCompras) ?></div>
            </div>
        </div>

        <!-- Barra de Controles y Filtros -->
        <div class="controls-bar">
            <div class="filter-tabs">
                <button class="filter-btn active" data-filter="todos">Todos</button>
                <button class="filter-btn" data-filter="pendiente">Pendientes</button>
                <button class="filter-btn" data-filter="validado">Validados</button>
                <button class="filter-btn" data-filter="rechazado">Rechazados</button>
            </div>

            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="buscadorAdmin" placeholder="Buscar por cédula, nombre u orden...">
            </div>
        </div>

        <!-- Tabla de Compras -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Orden / Fecha</th>
                            <th>Cliente</th>
                            <th>Cédula / Teléfono</th>
                            <th>Boletos</th>
                            <th>Monto / Pago</th>
                            <th>Comprobante</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="comprasTbody">
                        <?php if (empty($compras)): ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding: 40px; color: var(--color-terciario);">
                                    <i class="fa-regular fa-folder-open" style="font-size: 2.5rem; display:block; margin-bottom: 10px;"></i>
                                    Aún no hay compras registradas en el sistema.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($compras as $c): ?>
                                <?php 
                                    $tlfLimpio = preg_replace('/[^0-9]/', '', $c['cod_pais'] . $c['telefono']);
                                    $msgWAAprobado = urlencode("¡Hola {$c['nombre_completo']}! 🍀 Tu compra de boletos para el sorteo de GanAndreis ha sido VALIDADA con éxito.\n\n🎟️ Tus Boletos: {$c['lista_tickets']}\n📋 Orden: {$c['codigo_orden']}\n\n¡Mucha suerte en el sorteo!");
                                ?>
                                <tr class="fila-compra" data-estado="<?= htmlspecialchars($c['estado']) ?>" data-search="<?= strtolower(htmlspecialchars($c['codigo_orden'] . ' ' . $c['cedula'] . ' ' . $c['nombre_completo'] . ' ' . $c['telefono'])) ?>">
                                    <td>
                                        <strong style="color:var(--color-primario); font-family: monospace; font-size:1rem;"><?= htmlspecialchars($c['codigo_orden']) ?></strong><br>
                                        <small style="color:var(--color-terciario); font-size:0.75rem;"><?= htmlspecialchars($c['fecha_formateada']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($c['nombre_completo']) ?></strong><br>
                                        <small style="color:var(--color-terciario);"><?= htmlspecialchars($c['correo']) ?></small>
                                    </td>
                                    <td>
                                        <span><?= htmlspecialchars($c['cedula']) ?></span><br>
                                        <small><?= htmlspecialchars($c['cod_pais'] . ' ' . $c['telefono']) ?></small>
                                    </td>
                                    <td>
                                        <div class="tickets-pill" title="<?= htmlspecialchars($c['lista_tickets']) ?>">
                                            <?= htmlspecialchars($c['cantidad_tickets']) ?> boletos: <?= htmlspecialchars($c['lista_tickets']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="color:#fff;">$<?= number_format($c['total_monto'], 2) ?></strong><br>
                                        <small style="color:var(--color-terciario); text-transform:capitalize;"><?= htmlspecialchars($c['metodo_pago']) ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['comprobante_url'])): ?>
                                            <button class="btn-action view-img btn-ver-capture" data-img="../<?= htmlspecialchars($c['comprobante_url']) ?>" data-orden="<?= htmlspecialchars($c['codigo_orden']) ?>" title="Ver Capture">
                                                <i class="fa-solid fa-image"></i>
                                            </button>
                                        <?php else: ?>
                                            <span style="color:var(--color-terciario); font-size:0.8rem;">Sin capture</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= htmlspecialchars($c['estado']) ?>" id="badge-<?= $c['id'] ?>">
                                            <?= htmlspecialchars($c['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn-action approve btn-cambiar-estado" data-id="<?= $c['id'] ?>" data-nuevo="validado" title="Validar Pago">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button class="btn-action reject btn-cambiar-estado" data-id="<?= $c['id'] ?>" data-nuevo="rechazado" title="Rechazar">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                            <a href="https://api.whatsapp.com/send?phone=<?= $tlfLimpio ?>&text=<?= $msgWAAprobado ?>" target="_blank" class="btn-action whatsapp" title="Notificar por WhatsApp">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal Visor de Comprobante -->
    <div id="modalReceipt" class="modal-receipt">
        <div class="modal-receipt-box">
            <span class="close-modal-btn" id="cerrarReceipt">&times;</span>
            <h3 id="receiptTitulo" style="color:var(--color-primario); margin-bottom:10px;">Comprobante de Pago</h3>
            <img src="" id="receiptImg" class="modal-receipt-img" alt="Capture de Pago">
            <div>
                <a href="" id="receiptLinkDescarga" target="_blank" class="btn-config" style="display:inline-block;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir en tamaño completo
                </a>
            </div>
        </div>
    </div>

    <!-- Modal Configuración Sorteo -->
    <div id="modalSorteo" class="modal-receipt">
        <div class="modal-receipt-box" style="max-width: 500px;">
            <span class="close-modal-btn" id="cerrarSorteo">&times;</span>
            <h3 style="color:var(--color-primario); margin-bottom:20px;">Configuración del Sorteo Activo</h3>
            <form id="formSorteo">
                <div class="form-group-modal">
                    <label>Título del Premio</label>
                    <input type="text" name="titulo" value="<?= htmlspecialchars($sorteoActual['titulo'] ?? '') ?>" required>
                </div>
                <div class="form-group-modal">
                    <label>Precio por Boleto ($)</label>
                    <input type="number" step="0.01" name="precio_unitario" value="<?= htmlspecialchars($sorteoActual['precio_unitario'] ?? '3.50') ?>" required>
                </div>
                <div class="form-group-modal">
                    <label>Fecha del Sorteo</label>
                    <input type="date" name="fecha_sorteo" value="<?= htmlspecialchars($sorteoActual['fecha_sorteo'] ?? '2026-12-25') ?>" required>
                </div>
                <div class="form-group-modal">
                    <label>Hora del Sorteo</label>
                    <input type="text" name="hora_sorteo" value="<?= htmlspecialchars($sorteoActual['hora_sorteo'] ?? '08:00 PM') ?>" required>
                </div>
                <div class="form-group-modal">
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="3"><?= htmlspecialchars($sorteoActual['descripcion'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn-config" style="width:100%; justify-content:center; background:var(--color-primario); color:#000; font-weight:800; margin-top:10px;">
                    <i class="fa-solid fa-floppy-disk"></i> GUARDAR CAMBIOS
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Filtrado por Estado (Tabs)
            const filterBtns = document.querySelectorAll('.filter-btn');
            const filas = document.querySelectorAll('.fila-compra');

            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    const estado = btn.getAttribute('data-filter');

                    filas.forEach(fila => {
                        const filaEstado = fila.getAttribute('data-estado');
                        if (estado === 'todos' || filaEstado === estado) {
                            fila.style.display = '';
                        } else {
                            fila.style.display = 'none';
                        }
                    });
                });
            });

            // 2. Buscador en Vivo
            const buscador = document.getElementById('buscadorAdmin');
            buscador.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase().trim();
                filas.forEach(fila => {
                    const searchData = fila.getAttribute('data-search') || '';
                    if (searchData.includes(query)) {
                        fila.style.display = '';
                    } else {
                        fila.style.display = 'none';
                    }
                });
            });

            // 3. Modal Visor de Comprobantes
            const modalReceipt = document.getElementById('modalReceipt');
            const receiptImg = document.getElementById('receiptImg');
            const receiptLinkDescarga = document.getElementById('receiptLinkDescarga');
            const receiptTitulo = document.getElementById('receiptTitulo');
            const cerrarReceipt = document.getElementById('cerrarReceipt');

            document.querySelectorAll('.btn-ver-capture').forEach(btn => {
                btn.addEventListener('click', () => {
                    const src = btn.getAttribute('data-img');
                    const orden = btn.getAttribute('data-orden');
                    receiptImg.src = src;
                    receiptLinkDescarga.href = src;
                    receiptTitulo.textContent = `Comprobante - Orden ${orden}`;
                    modalReceipt.style.display = 'flex';
                });
            });

            cerrarReceipt.addEventListener('click', () => modalReceipt.style.display = 'none');
            window.addEventListener('click', (e) => {
                if (e.target === modalReceipt) modalReceipt.style.display = 'none';
            });

            // 4. Cambiar Estado de Compra (Validar / Rechazar con AJAX)
            document.querySelectorAll('.btn-cambiar-estado').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const compraId = btn.getAttribute('data-id');
                    const nuevoEstado = btn.getAttribute('data-nuevo');
                    
                    if (!confirm(`¿Estás seguro de marcar esta orden como "${nuevoEstado.toUpperCase()}"?`)) {
                        return;
                    }

                    btn.disabled = true;

                    try {
                        const formData = new FormData();
                        formData.append('accion', 'cambiar_estado');
                        formData.append('compra_id', compraId);
                        formData.append('nuevo_estado', nuevoEstado);

                        const res = await fetch('acciones.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await res.json();

                        if (data.success) {
                            const badge = document.getElementById(`badge-${compraId}`);
                            if (badge) {
                                badge.className = `status-badge ${nuevoEstado}`;
                                badge.textContent = nuevoEstado;
                            }
                            const tr = btn.closest('tr');
                            if (tr) {
                                tr.setAttribute('data-estado', nuevoEstado);
                            }
                            alert('Estado actualizado correctamente.');
                        } else {
                            alert(data.error || 'Error al actualizar estado.');
                        }
                    } catch (err) {
                        alert('Error de conexión al actualizar.');
                    } finally {
                        btn.disabled = false;
                    }
                });
            });

            // 5. Modal de Configuración del Sorteo
            const modalSorteo = document.getElementById('modalSorteo');
            const btnAbrirSorteo = document.getElementById('btnAbrirSorteo');
            const cerrarSorteo = document.getElementById('cerrarSorteo');
            const formSorteo = document.getElementById('formSorteo');

            btnAbrirSorteo.addEventListener('click', () => modalSorteo.style.display = 'flex');
            cerrarSorteo.addEventListener('click', () => modalSorteo.style.display = 'none');
            window.addEventListener('click', (e) => {
                if (e.target === modalSorteo) modalSorteo.style.display = 'none';
            });

            formSorteo.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(formSorteo);
                formData.append('accion', 'actualizar_sorteo');

                try {
                    const res = await fetch('acciones.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert('¡Sorteo actualizado con éxito!');
                        location.reload();
                    } else {
                        alert(data.error || 'Error al actualizar el sorteo.');
                    }
                } catch (err) {
                    alert('Error de red al actualizar sorteo.');
                }
            });

        });
    </script>
</body>
</html>
