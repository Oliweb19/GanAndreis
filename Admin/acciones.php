<?php
/**
 * Acciones del Panel de Administración (AJAX)
 * GanAndreis
 */
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../conexion.php';
$pdo = obtenerConexion();

if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Sin conexión a la base de datos']);
    exit;
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

if ($accion === 'cambiar_estado') {
    $compraId = intval($_POST['compra_id'] ?? 0);
    $nuevoEstado = trim($_POST['nuevo_estado'] ?? '');

    if (!$compraId || !in_array($nuevoEstado, ['validado', 'pendiente', 'rechazado'])) {
        echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Actualizar compra
        $stmtCompra = $pdo->prepare("UPDATE compras SET estado = ? WHERE id = ?");
        $stmtCompra->execute([$nuevoEstado, $compraId]);

        // Actualizar tickets asociados
        $stmtTickets = $pdo->prepare("UPDATE tickets SET estado = ? WHERE compra_id = ?");
        $stmtTickets->execute([$nuevoEstado, $compraId]);

        $pdo->commit();
        echo json_encode(['success' => true, 'mensaje' => 'Estado actualizado a ' . $nuevoEstado]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($accion === 'actualizar_sorteo') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha = trim($_POST['fecha_sorteo'] ?? '');
    $hora = trim($_POST['hora_sorteo'] ?? '');
    $precio = floatval($_POST['precio_unitario'] ?? 10.00);

    if (empty($titulo) || empty($fecha)) {
        echo json_encode(['success' => false, 'error' => 'Título y fecha son obligatorios']);
        exit;
    }

    try {
        $rutaImagen = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $permitidas)) {
                echo json_encode(['success' => false, 'error' => 'Formato de imagen no válido. Usa JPG, PNG o WEBP.']);
                exit;
            }

            $carpetaDestino = __DIR__ . '/../uploads/sorteos/';
            if (!file_exists($carpetaDestino)) {
                mkdir($carpetaDestino, 0777, true);
            }

            $nombreArchivo = 'sorteo_' . date('Ymd_His') . '_' . rand(1000, 9999) . '.' . $ext;
            $destino = $carpetaDestino . $nombreArchivo;
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
                $rutaImagen = 'uploads/sorteos/' . $nombreArchivo;
            }
        }

        if ($rutaImagen) {
            $stmt = $pdo->prepare("UPDATE sorteos SET titulo = ?, descripcion = ?, fecha_sorteo = ?, hora_sorteo = ?, precio_unitario = ?, imagen = ? WHERE id = 1");
            $stmt->execute([$titulo, $descripcion, $fecha, $hora, $precio, $rutaImagen]);
        } else {
            $stmt = $pdo->prepare("UPDATE sorteos SET titulo = ?, descripcion = ?, fecha_sorteo = ?, hora_sorteo = ?, precio_unitario = ? WHERE id = 1");
            $stmt->execute([$titulo, $descripcion, $fecha, $hora, $precio]);
        }

        echo json_encode(['success' => true, 'mensaje' => 'Información del sorteo actualizada correctamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
exit;
