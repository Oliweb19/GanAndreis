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
$todosSorteos = [];
$todosAdmins = [];
$sorteoActual = null;

if ($pdo) {
    try {
        // 1. Obtener todos los sorteos para el selector
        $stmtTodosSorteos = $pdo->query("SELECT *, DATE_FORMAT(fecha_sorteo, '%d/%m/%Y') AS fecha_formateada FROM sorteos ORDER BY activo DESC, id DESC");
        $todosSorteos = $stmtTodosSorteos->fetchAll();

        // 2. Detectar sorteo: si se especifica ?sorteo_id se usa ese; si no, el sorteo ACTIVO (activo = 1)
        $filtroId = isset($_GET['sorteo_id']) ? intval($_GET['sorteo_id']) : 0;
        if ($filtroId > 0) {
            foreach ($todosSorteos as $st) {
                if ($st['id'] == $filtroId) {
                    $sorteoActual = $st;
                    break;
                }
            }
        }
        
        // Si no se especificó o no se encontró, seleccionar el sorteo actualmente ACTIVO
        if (!$sorteoActual) {
            foreach ($todosSorteos as $st) {
                if ($st['activo'] == 1) {
                    $sorteoActual = $st;
                    break;
                }
            }
        }

        // Si aún no hay sorteo activo, tomar el primero de la lista
        if (!$sorteoActual && !empty($todosSorteos)) {
            $sorteoActual = $todosSorteos[0];
        }

        $sorteoIdActual = $sorteoActual ? (int)$sorteoActual['id'] : 0;

        // 3. Métricas filtradas estrictamente por el sorteo detectado
        if ($sorteoIdActual > 0) {
            // Total recaudado de compras de este sorteo
            $stmtRecaudado = $pdo->prepare("
                SELECT COALESCE(SUM(c.total_monto), 0) 
                FROM compras c
                WHERE c.estado = 'validado' 
                AND c.id IN (SELECT DISTINCT compra_id FROM tickets WHERE sorteo_id = ?)
            ");
            $stmtRecaudado->execute([$sorteoIdActual]);
            $totalRecaudado = (float)$stmtRecaudado->fetchColumn();

            // Boletos validados de este sorteo
            $stmtBV = $pdo->prepare("SELECT COALESCE(COUNT(id), 0) FROM tickets WHERE sorteo_id = ? AND estado = 'validado'");
            $stmtBV->execute([$sorteoIdActual]);
            $totalBoletosValidados = (int)$stmtBV->fetchColumn();

            // Boletos pendientes de este sorteo
            $stmtBP = $pdo->prepare("SELECT COALESCE(COUNT(id), 0) FROM tickets WHERE sorteo_id = ? AND estado = 'pendiente'");
            $stmtBP->execute([$sorteoIdActual]);
            $totalBoletosPendientes = (int)$stmtBP->fetchColumn();

            // Total solicitudes de este sorteo
            $stmtTotC = $pdo->prepare("
                SELECT COUNT(DISTINCT c.id) 
                FROM compras c
                INNER JOIN tickets t ON t.compra_id = c.id
                WHERE t.sorteo_id = ?
            ");
            $stmtTotC->execute([$sorteoIdActual]);
            $totalCompras = (int)$stmtTotC->fetchColumn();

            // Listado de compras de este sorteo
            $stmtCompras = $pdo->prepare("
                SELECT 
                    c.*,
                    DATE_FORMAT(c.creado_el, '%d/%m/%Y %h:%i %p') AS fecha_formateada,
                    GROUP_CONCAT(t.numero_ticket ORDER BY t.id ASC SEPARATOR ', ') AS lista_tickets
                FROM compras c
                INNER JOIN tickets t ON t.compra_id = c.id
                WHERE t.sorteo_id = ?
                GROUP BY c.id
                ORDER BY c.id DESC
            ");
            $stmtCompras->execute([$sorteoIdActual]);
            $compras = $stmtCompras->fetchAll();
        }

        // Todos los administradores registrados
        $stmtTodosAdmins = $pdo->query("SELECT id, nombre, email, DATE_FORMAT(creado_el, '%d/%m/%Y') AS fecha_creado FROM administradores ORDER BY id DESC");
        $todosAdmins = $stmtTodosAdmins->fetchAll();

    } catch (Exception $e) {
        $errorBD = $e->getMessage();
    }
}

if (!$sorteoActual) {
    $sorteoActual = [
        'id' => 0,
        'titulo' => 'Sin sorteo registrado',
        'descripcion' => '',
        'fecha_sorteo' => '2026-12-25',
        'hora_sorteo' => '08:00 PM',
        'precio_unitario' => 10.00,
        'activo' => 0
    ];
}

$todosSorteos = $todosSorteos ?? [];
$todosAdmins = $todosAdmins ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo | GanAndreis</title>
    <link rel="stylesheet" href="../Assets/css/index.css">
    <link rel="stylesheet" href="../Assets/css/modal_alerta.css">
    <link rel="shortcut icon" href="../Assets/img/logo-transparente.png" type="image/x-icon">
    <link rel="stylesheet" href="../Assets/icons/fontawesome-free-6.2.1-web/css/all.css">
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
            background: rgba(48, 209, 88, 0.12);
            color: var(--color-primario);
            border: 1px dashed rgba(48, 209, 88, 0.45);
            padding: 5px 12px;
            border-radius: 9px;
            font-weight: 700;
            font-size: 0.82rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
        .tickets-pill:hover {
            background: rgba(48, 209, 88, 0.25);
            border: 1px solid var(--color-primario);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(48, 209, 88, 0.25);
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

        /* Uploader de Imagen de Sorteo */
        .sorteo-image-uploader {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 5px;
        }
        .preview-container {
            width: 90px;
            height: 90px;
            border-radius: 12px;
            border: 2px dashed rgba(48, 209, 88, 0.4);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #141416;
            flex-shrink: 0;
        }
        #previewImgSorteo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .btn-subir-imagen {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #262628;
            color: #fff;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s ease;
        }
        .btn-subir-imagen:hover {
            background: var(--color-primario);
            color: #000;
            border-color: var(--color-primario);
        }

        /* Buscador con Botón de Reinicio */
        .search-and-refresh {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-refresh {
            background: #1C1C1E;
            color: var(--color-terciario);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .btn-refresh:hover {
            color: var(--color-primario);
            border-color: var(--color-primario);
            background: rgba(48, 209, 88, 0.1);
            transform: rotate(45deg);
        }
        .btn-refresh.spinning i {
            animation: fa-spin 0.8s linear infinite;
        }

        /* Paginador de la Tabla */
        .table-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: #1C1C1E;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            flex-wrap: wrap;
            gap: 15px;
        }
        .pagination-info {
            font-size: 0.85rem;
            color: var(--color-terciario);
        }
        .pagination-info strong {
            color: #fff;
        }
        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-pag {
            background: #262628;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-pag:hover:not(:disabled) {
            background: var(--color-primario);
            color: #000;
            border-color: var(--color-primario);
        }
        .btn-pag:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }
        .pagination-pages {
            display: flex;
            gap: 5px;
        }
        .btn-page-num {
            background: #262628;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.08);
            min-width: 34px;
            height: 34px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-page-num:hover {
            background: rgba(48, 209, 88, 0.2);
            color: var(--color-primario);
            border-color: var(--color-primario);
        }
        .btn-page-num.active {
            background: var(--color-primario);
            color: #000;
            border-color: var(--color-primario);
            font-weight: 800;
        }

        /* Modal de Boletos del Cliente */
        .modal-boletos-grid {
            background: #141416;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            max-height: 280px;
            overflow-y: auto;
        }
        .modal-boletos-grid::-webkit-scrollbar {
            width: 6px;
        }
        .modal-boletos-grid::-webkit-scrollbar-thumb {
            background: rgba(48, 209, 88, 0.35);
            border-radius: 6px;
        }
        .modal-ticket-tag {
            background: rgba(48, 209, 88, 0.15);
            color: var(--color-primario);
            border: 1px solid rgba(48, 209, 88, 0.4);
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 800;
            font-family: monospace;
            font-size: 1.05rem;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 0 10px rgba(48, 209, 88, 0.1);
        }

        /* Barra de Sorteo Activo en Dashboard */
        .sorteo-active-bar {
            background: #141416;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 20px 24px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        .sorteo-active-bar.is-live {
            border-color: rgba(48, 209, 88, 0.45);
            background: rgba(48, 209, 88, 0.03);
            box-shadow: 0 0 25px rgba(48, 209, 88, 0.08);
        }
        .sorteo-active-info h2 {
            font-size: 1.35rem;
            font-weight: 800;
            margin: 6px 0 4px 0;
            color: #fff;
        }
        .sorteo-active-info p {
            font-size: 0.85rem;
            color: var(--color-terciario);
            margin: 0;
        }
        .sorteo-active-tag {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .tag-activo {
            background: rgba(48, 209, 88, 0.15);
            color: var(--color-primario);
            border: 1px solid rgba(48, 209, 88, 0.4);
            box-shadow: 0 0 10px rgba(48, 209, 88, 0.2);
        }
        .tag-inactivo {
            background: rgba(255, 255, 255, 0.05);
            color: var(--color-terciario);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sorteo-active-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #1C1C1E;
            padding: 8px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sorteo-active-selector label {
            font-size: 0.82rem;
            color: var(--color-terciario);
            white-space: nowrap;
        }
        .sorteo-active-selector select {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 0.88rem;
            font-weight: 600;
            outline: none;
            cursor: pointer;
            padding-right: 5px;
        }
        .sorteo-active-selector select option {
            background: #1C1C1E;
            color: #fff;
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
            min-width: 260px;
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
            animation: fadeInDrop 0.2s ease forwards;
        }
        @keyframes fadeInDrop {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
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
            transition: color 0.2s;
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

        /* Lista de Sorteos en Modal */
        .sorteos-grid-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 420px;
            overflow-y: auto;
            padding-right: 5px;
        }
        .sorteo-card-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #141416;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 12px 16px;
            gap: 15px;
            transition: all 0.25s ease;
        }
        .sorteo-card-item.is-active {
            border-color: rgba(48, 209, 88, 0.5);
            background: rgba(48, 209, 88, 0.04);
            box-shadow: 0 0 15px rgba(48, 209, 88, 0.08);
        }
        .sorteo-item-thumb {
            width: 58px;
            height: 58px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
        }
        .sorteo-card-item.is-active .sorteo-item-thumb {
            border-color: var(--color-primario);
        }
        .sorteo-item-info {
            flex-grow: 1;
            overflow: hidden;
        }
        .sorteo-item-info h4 {
            margin: 0;
            font-size: 0.95rem;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sorteo-item-info p {
            margin: 4px 0 0 0;
            font-size: 0.8rem;
            color: var(--color-terciario);
        }
        .sorteo-item-status {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .badge-sorteo-activo {
            background: rgba(48, 209, 88, 0.15);
            color: var(--color-primario);
            border: 1px solid rgba(48, 209, 88, 0.4);
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-activar-sorteo {
            background: #262628;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-activar-sorteo:hover {
            background: var(--color-primario);
            color: #000;
            border-color: var(--color-primario);
            box-shadow: 0 0 12px rgba(48, 209, 88, 0.3);
        }

        /* Tabla de Administradores */
        .admins-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            margin-top: 15px;
        }
        .admins-table th {
            text-align: left;
            padding: 10px 12px;
            color: var(--color-terciario);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        .admins-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: #fff;
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
                <button type="button" class="btn-config btn-dropdown-trigger" id="btnTriggerGestion" title="Opciones de Gestión">
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
        
        <!-- Barra del Sorteo Visualizado -->
        <div class="sorteo-active-bar <?= $sorteoActual['activo'] ? 'is-live' : '' ?>">
            <div class="sorteo-active-info">
                <span class="sorteo-active-tag <?= $sorteoActual['activo'] ? 'tag-activo' : 'tag-inactivo' ?>">
                    <i class="fa-solid <?= $sorteoActual['activo'] ? 'fa-circle-check' : 'fa-circle-pause' ?>"></i>
                    <?= $sorteoActual['activo'] ? 'SORTEO ACTIVO (PÁGINA PÚBLICA)' : 'SORTEO INACTIVO (HISTORIAL)' ?>
                </span>
                <h2><?= htmlspecialchars($sorteoActual['titulo']) ?></h2>
                <p>
                    <i class="fa-solid fa-calendar-days"></i> Sorteo: <strong><?= date('d/m/Y', strtotime($sorteoActual['fecha_sorteo'])) ?></strong> a las <strong><?= htmlspecialchars($sorteoActual['hora_sorteo']) ?></strong> &bull;
                    <i class="fa-solid fa-coins"></i> Precio: <strong style="color:var(--color-primario);">Bs. <?= number_format($sorteoActual['precio_unitario'], 2) ?></strong>
                </p>
            </div>
            <div class="sorteo-active-selector">
                <label for="selectCambiarSorteo"><i class="fa-solid fa-filter"></i> Sorteo:</label>
                <select id="selectCambiarSorteo" onchange="if(this.value) window.location.href='dashboard.php?sorteo_id=' + this.value">
                    <?php foreach ($todosSorteos as $st): ?>
                        <option value="<?= $st['id'] ?>" <?= $st['id'] == $sorteoActual['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($st['titulo']) ?> <?= $st['activo'] ? '★ (Activo)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Tarjetas de Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card recaudado">
                <i class="fa-solid fa-dollar-sign stat-icon"></i>
                <h4>Total Recaudado (Aprobado)</h4>
                <div class="stat-value">Bs. <?= number_format($totalRecaudado, 2) ?></div>
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

            <div class="search-and-refresh">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="buscadorAdmin" placeholder="Buscar por cédula, nombre, orden o #ticket...">
                </div>
                <button type="button" id="btnRecargarDashboard" class="btn-refresh" title="Recargar y comprobar nuevos tickets">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
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
                                <tr class="fila-compra" data-estado="<?= htmlspecialchars($c['estado']) ?>" data-search="<?= strtolower(htmlspecialchars($c['codigo_orden'] . ' ' . $c['cedula'] . ' ' . $c['nombre_completo'] . ' ' . $c['telefono'] . ' ' . $c['lista_tickets'])) ?>">
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
                                        <div class="tickets-pill btn-ver-boletos" 
                                             data-orden="<?= htmlspecialchars($c['codigo_orden']) ?>"
                                             data-cliente="<?= htmlspecialchars($c['nombre_completo']) ?>"
                                             data-cedula="<?= htmlspecialchars($c['cedula']) ?>"
                                             data-tickets="<?= htmlspecialchars($c['lista_tickets'] ?? '') ?>"
                                             data-cantidad="<?= htmlspecialchars($c['cantidad_tickets']) ?>"
                                             title="Haz clic para ver todos los boletos asignados">
                                            <i class="fa-solid fa-ticket"></i>
                                            <span><?= htmlspecialchars($c['cantidad_tickets']) ?> boletos: <?= htmlspecialchars($c['lista_tickets']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="color:#fff;">Bs. <?= number_format($c['total_monto'], 2) ?></strong><br>
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

            <!-- Paginador -->
            <div class="table-pagination" id="paginacionCompras">
                <div class="pagination-info" id="paginacionInfo">
                    Mostrando <strong id="pagDesde">0</strong> - <strong id="pagHasta">0</strong> de <strong id="pagTotal">0</strong> compras
                </div>
                <div class="pagination-controls">
                    <button type="button" class="btn-pag" id="btnPagAnterior" disabled>
                        <i class="fa-solid fa-chevron-left"></i> Anterior
                    </button>
                    <div class="pagination-pages" id="paginacionNumeros"></div>
                    <button type="button" class="btn-pag" id="btnPagSiguiente" disabled>
                        Siguiente <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Visor de Boletos del Cliente -->
    <div id="modalBoletosCliente" class="modal-receipt">
        <div class="modal-receipt-box" style="max-width: 580px; text-align: left;">
            <span class="close-modal-btn" id="cerrarModalBoletos">&times;</span>
            <div style="display:flex; align-items:center; gap:14px; margin-bottom:18px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:15px;">
                <div style="width:48px; height:48px; border-radius:12px; background:rgba(48,209,88,0.15); color:var(--color-primario); display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0;">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <div style="overflow:hidden;">
                    <h3 style="color:#fff; font-size:1.25rem; margin:0;" id="modalBoletosTitulo">Boletos de la Orden</h3>
                    <p style="color:var(--color-terciario); font-size:0.85rem; margin:4px 0 0 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" id="modalBoletosSubtitulo"></p>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <span style="font-size:0.88rem; color:var(--color-terciario);">
                    Total Asignados: <strong id="modalBoletosCount" style="color:var(--color-primario);"></strong>
                </span>
                <button type="button" id="btnCopiarBoletos" class="btn-config" style="padding:6px 14px; font-size:0.8rem; cursor:pointer;">
                    <i class="fa-regular fa-copy"></i> Copiar Números
                </button>
            </div>

            <div class="modal-boletos-grid" id="modalBoletosGrid">
                <!-- Se inyectan las píldoras de tickets -->
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
        <div class="modal-receipt-box" style="max-width: 540px; text-align: left;">
            <span class="close-modal-btn" id="cerrarSorteo">&times;</span>
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:14px;">
                <div style="width:40px; height:40px; border-radius:10px; background:rgba(48,209,88,0.15); color:var(--color-primario); display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0;">
                    <i class="fa-solid fa-gear"></i>
                </div>
                <h3 style="color:#fff; font-size:1.2rem; margin:0;">Configuración del Sorteo Activo</h3>
            </div>
            <form id="formSorteo" enctype="multipart/form-data">
                <input type="hidden" name="sorteo_id" value="<?= htmlspecialchars($sorteoActual['id'] ?? '') ?>">
                <div class="form-group-modal">
                    <label>Título del Premio</label>
                    <input type="text" name="titulo" value="<?= htmlspecialchars($sorteoActual['titulo'] ?? '') ?>" required>
                </div>

                <div class="form-group-modal">
                    <label>Imagen del Premio / Sorteo</label>
                    <div class="sorteo-image-uploader">
                        <div class="preview-container">
                            <?php 
                                $imgActual = !empty($sorteoActual['imagen']) ? '../' . $sorteoActual['imagen'] : '../Assets/img/Prueba.jpg';
                            ?>
                            <img src="<?= htmlspecialchars($imgActual) ?>" id="previewImgSorteo" alt="Vista previa del sorteo">
                        </div>
                        <div>
                            <label for="inputImgSorteo" class="btn-subir-imagen">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Seleccionar Imagen
                            </label>
                            <input type="file" id="inputImgSorteo" name="imagen" accept="image/*" style="display:none;">
                            <p style="font-size:0.75rem; color:var(--color-terciario); margin-top:6px;">Formatos: JPG, PNG, WEBP. Vista previa instantánea.</p>
                        </div>
                    </div>
                </div>

                <div class="form-group-modal">
                    <label>Precio por Boleto (Bs.)</label>
                    <input type="number" step="0.01" name="precio_unitario" value="<?= htmlspecialchars($sorteoActual['precio_unitario'] ?? '10.00') ?>" required>
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
            // 1. Filtrado, Búsqueda y Paginación Unificada
            let filtroActual = 'todos';
            let queryActual = '';
            let paginaActual = 1;
            const FILAS_POR_PAGINA = 10;

            const filas = Array.from(document.querySelectorAll('.fila-compra'));
            const filterBtns = document.querySelectorAll('.filter-btn');
            const buscador = document.getElementById('buscadorAdmin');
            const btnRecargar = document.getElementById('btnRecargarDashboard');

            const pagDesde = document.getElementById('pagDesde');
            const pagHasta = document.getElementById('pagHasta');
            const pagTotal = document.getElementById('pagTotal');
            const btnPagAnterior = document.getElementById('btnPagAnterior');
            const btnPagSiguiente = document.getElementById('btnPagSiguiente');
            const paginacionNumeros = document.getElementById('paginacionNumeros');

            function actualizarTabla(resetPagina = false) {
                if (resetPagina) paginaActual = 1;

                const filasVisibles = filas.filter(fila => {
                    const filaEstado = fila.getAttribute('data-estado');
                    const coincideEstado = (filtroActual === 'todos' || filaEstado === filtroActual);
                    const searchData = (fila.getAttribute('data-search') || '');
                    const coincideQuery = !queryActual || searchData.includes(queryActual);
                    return coincideEstado && coincideQuery;
                });

                const total = filasVisibles.length;
                const totalPaginas = Math.ceil(total / FILAS_POR_PAGINA) || 1;
                if (paginaActual > totalPaginas) paginaActual = totalPaginas;

                // Ocultar todas las filas
                filas.forEach(f => f.style.display = 'none');

                // Mostrar solo las correspondientes a la página actual
                const inicio = (paginaActual - 1) * FILAS_POR_PAGINA;
                const fin = inicio + FILAS_POR_PAGINA;
                const filasPagina = filasVisibles.slice(inicio, fin);
                filasPagina.forEach(f => f.style.display = '');

                // Actualizar contadores
                if (total === 0) {
                    pagDesde.textContent = '0';
                    pagHasta.textContent = '0';
                    pagTotal.textContent = '0';
                } else {
                    pagDesde.textContent = inicio + 1;
                    pagHasta.textContent = Math.min(fin, total);
                    pagTotal.textContent = total;
                }

                // Habilitar / Deshabilitar botones de paginación
                btnPagAnterior.disabled = (paginaActual <= 1);
                btnPagSiguiente.disabled = (paginaActual >= totalPaginas);

                // Generar botones numéricos
                paginacionNumeros.innerHTML = '';
                for (let i = 1; i <= totalPaginas; i++) {
                    if (totalPaginas > 7 && Math.abs(i - paginaActual) > 2 && i !== 1 && i !== totalPaginas) {
                        if (i === 2 || i === totalPaginas - 1) {
                            const dots = document.createElement('span');
                            dots.textContent = '...';
                            dots.style.color = 'var(--color-terciario)';
                            dots.style.padding = '0 4px';
                            paginacionNumeros.appendChild(dots);
                        }
                        continue;
                    }
                    const btnNum = document.createElement('button');
                    btnNum.type = 'button';
                    btnNum.className = `btn-page-num ${i === paginaActual ? 'active' : ''}`;
                    btnNum.textContent = i;
                    btnNum.addEventListener('click', () => {
                        paginaActual = i;
                        actualizarTabla();
                    });
                    paginacionNumeros.appendChild(btnNum);
                }
            }

            btnPagAnterior.addEventListener('click', () => {
                if (paginaActual > 1) {
                    paginaActual--;
                    actualizarTabla();
                }
            });

            btnPagSiguiente.addEventListener('click', () => {
                paginaActual++;
                actualizarTabla();
            });

            // Tabs de Filtros
            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    filtroActual = btn.getAttribute('data-filter');
                    actualizarTabla(true);
                });
            });

            // Buscador en Vivo (soporta cédula, nombre, orden y boletos)
            buscador.addEventListener('input', (e) => {
                queryActual = e.target.value.toLowerCase().trim();
                actualizarTabla(true);
            });

            // Botón de Reinicio / Recarga
            if (btnRecargar) {
                btnRecargar.addEventListener('click', () => {
                    btnRecargar.classList.add('spinning');
                    setTimeout(() => {
                        location.reload();
                    }, 300);
                });
            }

            // Inicializar tabla con paginación
            actualizarTabla();

            // 2. Modal de Boletos del Cliente
            const modalBoletos = document.getElementById('modalBoletosCliente');
            const cerrarModalBoletos = document.getElementById('cerrarModalBoletos');
            const modalBoletosTitulo = document.getElementById('modalBoletosTitulo');
            const modalBoletosSubtitulo = document.getElementById('modalBoletosSubtitulo');
            const modalBoletosCount = document.getElementById('modalBoletosCount');
            const modalBoletosGrid = document.getElementById('modalBoletosGrid');
            const btnCopiarBoletos = document.getElementById('btnCopiarBoletos');
            let boletosTextoActual = '';

            document.querySelectorAll('.btn-ver-boletos').forEach(btn => {
                btn.addEventListener('click', () => {
                    const orden = btn.getAttribute('data-orden') || '';
                    const cliente = btn.getAttribute('data-cliente') || '';
                    const cedula = btn.getAttribute('data-cedula') || '';
                    const ticketsStr = btn.getAttribute('data-tickets') || '';
                    const cantidad = btn.getAttribute('data-cantidad') || '0';

                    boletosTextoActual = ticketsStr;
                    modalBoletosTitulo.textContent = `Boletos - Orden ${orden}`;
                    modalBoletosSubtitulo.textContent = `Cliente: ${cliente} | C.I: ${cedula}`;
                    modalBoletosCount.textContent = `${cantidad} boleto(s)`;

                    modalBoletosGrid.innerHTML = '';
                    const ticketsArray = ticketsStr.split(',').map(t => t.trim()).filter(Boolean);

                    if (ticketsArray.length > 0) {
                        ticketsArray.forEach(num => {
                            const tag = document.createElement('div');
                            tag.className = 'modal-ticket-tag';
                            tag.innerHTML = `<i class="fa-solid fa-ticket" style="font-size:0.75rem; margin-right:7px; opacity:0.8;"></i> ${num}`;
                            modalBoletosGrid.appendChild(tag);
                        });
                    } else {
                        modalBoletosGrid.innerHTML = '<span style="color:var(--color-terciario); font-size:0.9rem;">Sin boletos asignados</span>';
                    }

                    modalBoletos.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
            });

            function ocultarModalBoletos() {
                modalBoletos.style.display = 'none';
                document.body.style.overflow = '';
            }

            if (cerrarModalBoletos) {
                cerrarModalBoletos.addEventListener('click', ocultarModalBoletos);
            }

            if (btnCopiarBoletos) {
                btnCopiarBoletos.addEventListener('click', () => {
                    if (!boletosTextoActual) return;
                    navigator.clipboard.writeText(boletosTextoActual).then(() => {
                        const origHTML = btnCopiarBoletos.innerHTML;
                        btnCopiarBoletos.innerHTML = '<i class="fa-solid fa-check" style="color:var(--color-primario);"></i> ¡Copiado!';
                        setTimeout(() => {
                            btnCopiarBoletos.innerHTML = origHTML;
                        }, 2000);
                    });
                });
            }

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
                    document.body.style.overflow = 'hidden';
                });
            });

            function ocultarModalReceipt() {
                modalReceipt.style.display = 'none';
                document.body.style.overflow = '';
            }

            cerrarReceipt.addEventListener('click', ocultarModalReceipt);

            // 4. Cambiar Estado de Compra (Validar / Rechazar con AJAX)
            document.querySelectorAll('.btn-cambiar-estado').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const compraId = btn.getAttribute('data-id');
                    const nuevoEstado = btn.getAttribute('data-nuevo');
                    
                    const seguro = await mostrarConfirmacion(
                        `¿Estás seguro de marcar esta orden como "${nuevoEstado.toUpperCase()}"?`,
                        'Confirmar Cambio de Estado',
                        'Sí, Cambiar',
                        'Cancelar'
                    );

                    if (!seguro) {
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
                            mostrarAlerta(`Estado de la orden actualizado a ${nuevoEstado.toUpperCase()}.`, 'success', 'Actualizado');
                        } else {
                            mostrarAlerta(data.error || 'Error al actualizar estado.', 'error', 'Error');
                        }
                    } catch (err) {
                        mostrarAlerta('Error de conexión al actualizar el estado.', 'error', 'Error de Red');
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

            function abrirModalSorteo() {
                modalSorteo.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            function cerrarModalSorteo() {
                modalSorteo.style.display = 'none';
                document.body.style.overflow = '';
            }

            btnAbrirSorteo.addEventListener('click', abrirModalSorteo);
            cerrarSorteo.addEventListener('click', cerrarModalSorteo);

            // Preview instantáneo al seleccionar imagen de sorteo
            const inputImgSorteo = document.getElementById('inputImgSorteo');
            const previewImgSorteo = document.getElementById('previewImgSorteo');
            if (inputImgSorteo && previewImgSorteo) {
                inputImgSorteo.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        if (!file.type.match('image.*')) {
                            mostrarAlerta('Por favor selecciona un archivo de imagen válido (JPG, PNG, WEBP).', 'warning', 'Formato no soportado');
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImgSorteo.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // 6. Menú Desplegable Gestión
            const dropdownGestion = document.getElementById('dropdownGestion');
            const btnTriggerGestion = document.getElementById('btnTriggerGestion');

            if (btnTriggerGestion && dropdownGestion) {
                btnTriggerGestion.addEventListener('click', (e) => {
                    e.stopPropagation();
                    dropdownGestion.classList.toggle('open');
                });
            }

            // Cierre global con click afuera y tecla Escape
            window.addEventListener('click', (e) => {
                if (dropdownGestion && !dropdownGestion.contains(e.target)) {
                    dropdownGestion.classList.remove('open');
                }
                if (e.target === modalReceipt) ocultarModalReceipt();
                if (e.target === modalSorteo) cerrarModalSorteo();
                if (e.target === modalBoletos) ocultarModalBoletos();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (dropdownGestion) dropdownGestion.classList.remove('open');
                    if (modalReceipt && modalReceipt.style.display === 'flex') ocultarModalReceipt();
                    if (modalSorteo && modalSorteo.style.display === 'flex') cerrarModalSorteo();
                    if (modalBoletos && modalBoletos.style.display === 'flex') ocultarModalBoletos();
                }
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
                        await mostrarAlerta('¡Los datos del sorteo se guardaron correctamente!', 'success', 'Sorteo Actualizado');
                        location.reload();
                    } else {
                        mostrarAlerta(data.error || 'Error al actualizar el sorteo.', 'error', 'Error');
                    }
                } catch (err) {
                    mostrarAlerta('Error de red al actualizar sorteo.', 'error', 'Error de Red');
                }
            });

        });
    </script>
    <script src="../Assets/js/modal_alerta.js"></script>
</body>
</html>
