<?php
/**
 * Consulta de Tickets por Cédula (API JSON)
 * GanAndreis
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

$cedula = trim($_GET['cedula'] ?? $_POST['cedula'] ?? '');

if (empty($cedula)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Por favor ingresa un número de cédula válido.'
    ]);
    exit;
}

$pdo = obtenerConexion();

if (!$pdo) {
    echo json_encode([
        'success' => false,
        'error'   => 'El servicio de base de datos no está disponible en este momento. Inténtalo más tarde.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            t.numero_ticket,
            t.estado,
            DATE_FORMAT(t.creado_el, '%d/%m/%Y') AS fecha_compra,
            COALESCE(s.titulo, 'Sorteo Oficial') AS sorteo_titulo,
            COALESCE(s.precio_unitario, 3.50) AS monto,
            c.codigo_orden,
            c.nombre_completo,
            c.estado AS estado_compra
        FROM tickets t
        INNER JOIN compras c ON t.compra_id = c.id
        LEFT JOIN sorteos s ON t.sorteo_id = s.id
        WHERE c.cedula = ?
        ORDER BY t.id DESC
    ");
    $stmt->execute([$cedula]);
    $tickets = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'cedula'  => htmlspecialchars($cedula),
        'total'   => count($tickets),
        'tickets' => $tickets
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'Error en la consulta: ' . $e->getMessage()
    ]);
}
exit;
