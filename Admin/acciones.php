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
    $sorteoId = intval($_POST['sorteo_id'] ?? 0);
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

        // Si no se pasó un ID específico, buscamos el sorteo activo
        if (!$sorteoId) {
            $stmtActivo = $pdo->query("SELECT id FROM sorteos WHERE activo = 1 ORDER BY id DESC LIMIT 1");
            $sorteoId = (int)($stmtActivo->fetchColumn() ?: 1);
        }

        if ($rutaImagen) {
            $stmt = $pdo->prepare("UPDATE sorteos SET titulo = ?, descripcion = ?, fecha_sorteo = ?, hora_sorteo = ?, precio_unitario = ?, imagen = ? WHERE id = ?");
            $stmt->execute([$titulo, $descripcion, $fecha, $hora, $precio, $rutaImagen, $sorteoId]);
        } else {
            $stmt = $pdo->prepare("UPDATE sorteos SET titulo = ?, descripcion = ?, fecha_sorteo = ?, hora_sorteo = ?, precio_unitario = ? WHERE id = ?");
            $stmt->execute([$titulo, $descripcion, $fecha, $hora, $precio, $sorteoId]);
        }

        echo json_encode(['success' => true, 'mensaje' => 'Información del sorteo actualizada correctamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($accion === 'activar_sorteo') {
    $sorteoId = intval($_POST['sorteo_id'] ?? 0);
    if (!$sorteoId) {
        echo json_encode(['success' => false, 'error' => 'ID de sorteo inválido']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        // Desactivamos todos los demás sorteos
        $pdo->exec("UPDATE sorteos SET activo = 0");
        // Activamos el sorteo seleccionado
        $stmt = $pdo->prepare("UPDATE sorteos SET activo = 1 WHERE id = ?");
        $stmt->execute([$sorteoId]);
        $pdo->commit();

        echo json_encode(['success' => true, 'mensaje' => '¡Sorteo activado correctamente!']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($accion === 'crear_sorteo') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha = trim($_POST['fecha_sorteo'] ?? '');
    $hora = trim($_POST['hora_sorteo'] ?? '08:00 PM');
    $precio = floatval($_POST['precio_unitario'] ?? 10.00);
    $activar = !empty($_POST['activo']) ? 1 : 0;

    if (empty($titulo) || empty($fecha)) {
        echo json_encode(['success' => false, 'error' => 'El título y la fecha son obligatorios']);
        exit;
    }

    try {
        $rutaImagen = 'Assets/img/Prueba.jpg';
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

        $pdo->beginTransaction();
        if ($activar) {
            $pdo->exec("UPDATE sorteos SET activo = 0");
        }

        $stmt = $pdo->prepare("INSERT INTO sorteos (titulo, descripcion, fecha_sorteo, hora_sorteo, precio_unitario, imagen, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $descripcion, $fecha, $hora, $precio, $rutaImagen, $activar]);
        $pdo->commit();

        echo json_encode(['success' => true, 'mensaje' => 'Nuevo sorteo registrado exitosamente']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($accion === 'crear_administrador') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($nombre) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Por favor introduce un correo electrónico válido']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'La contraseña debe tener un mínimo de 6 caracteres']);
        exit;
    }

    try {
        $stmtCheck = $pdo->prepare("SELECT id FROM administradores WHERE email = ?");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Ya existe un administrador registrado con ese correo']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO administradores (nombre, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $email, $hash]);

        echo json_encode(['success' => true, 'mensaje' => '¡Nuevo administrador registrado con éxito!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($accion === 'eliminar_administrador') {
    $adminId = intval($_POST['admin_id'] ?? 0);
    if (!$adminId) {
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
        exit;
    }

    if ($adminId === intval($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'error' => 'No puedes eliminar tu propia cuenta de administrador en sesión activa']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM administradores WHERE id = ?");
        $stmt->execute([$adminId]);
        echo json_encode(['success' => true, 'mensaje' => 'Administrador eliminado']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
exit;
