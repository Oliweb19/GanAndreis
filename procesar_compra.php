<?php
/**
 * Procesamiento de Compra de Boletos
 * GanAndreis
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

// Asegurar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Recibir y sanitizar datos
$cedula    = trim($_POST['cedula'] ?? '');
$nombre    = trim($_POST['nombre'] ?? '');
$correo    = trim($_POST['correo'] ?? '');
$codPais   = trim($_POST['cod_pais'] ?? '+58');
$telefono  = trim($_POST['telefono'] ?? '');
$cantidad  = max(1, intval($_POST['cantidad'] ?? 1));
$metodo    = trim($_POST['metodo'] ?? '');

if (empty($cedula) || empty($nombre) || empty($correo) || empty($telefono) || empty($metodo)) {
    echo json_encode(['success' => false, 'error' => 'Por favor completa todos los campos requeridos.']);
    exit;
}

$pdo = obtenerConexion();
$sorteoId = 1;
$precioUnitario = 3.50;

if ($pdo) {
    try {
        $stmtSorteo = $pdo->query("SELECT id, precio_unitario, titulo FROM sorteos WHERE activo = 1 ORDER BY id DESC LIMIT 1");
        $sorteo = $stmtSorteo->fetch();
        if ($sorteo) {
            $sorteoId = $sorteo['id'];
            $precioUnitario = floatval($sorteo['precio_unitario']);
        }
    } catch (Exception $e) {
        // En caso de error leve con la tabla de sorteo usamos los defaults
    }
}

$totalMonto = round($cantidad * $precioUnitario, 2);

// Manejo de archivo de comprobante
$comprobanteUrl = null;
if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
    $dirSubida = __DIR__ . '/uploads/comprobantes/';
    if (!is_dir($dirSubida)) {
        mkdir($dirSubida, 0755, true);
    }

    $extPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    $nombreOriginal = $_FILES['comprobante']['name'];
    $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

    if (in_array($ext, $extPermitidas)) {
        $nuevoNombre = 'comp_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $destino = $dirSubida . $nuevoNombre;
        if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $destino)) {
            $comprobanteUrl = 'uploads/comprobantes/' . $nuevoNombre;
        }
    }
}

// Generar código de orden único
$codigoOrden = 'GA-' . strtoupper(substr(uniqid(), -6));

// Generar números de ticket únicos
$ticketsGenerados = [];
if ($pdo) {
    // Obtener tickets ya tomados en este sorteo
    $stmtOcupados = $pdo->prepare("SELECT numero_ticket FROM tickets WHERE sorteo_id = ?");
    $stmtOcupados->execute([$sorteoId]);
    $ocupados = $stmtOcupados->fetchAll(PDO::FETCH_COLUMN);
    $setOcupados = array_flip($ocupados);

    $intentos = 0;
    while (count($ticketsGenerados) < $cantidad && $intentos < 50000) {
        $intentos++;
        $numAleatorio = sprintf("#%05d", mt_rand(1, 99999));
        if (!isset($setOcupados[$numAleatorio]) && !in_array($numAleatorio, $ticketsGenerados)) {
            $ticketsGenerados[] = $numAleatorio;
        }
    }
}

// Respaldo de tickets si la base de datos no está activa o se alcanzaron intentos
while (count($ticketsGenerados) < $cantidad) {
    $ticketsGenerados[] = sprintf("#%05d", mt_rand(1, 99999));
}

// Guardar en la Base de Datos si la conexión está lista
if ($pdo) {
    try {
        $pdo->beginTransaction();

        $stmtCompra = $pdo->prepare("
            INSERT INTO compras 
            (codigo_orden, cedula, nombre_completo, correo, cod_pais, telefono, cantidad_tickets, total_monto, metodo_pago, comprobante_url, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
        ");
        $stmtCompra->execute([
            $codigoOrden,
            $cedula,
            $nombre,
            $correo,
            $codPais,
            $telefono,
            $cantidad,
            $totalMonto,
            $metodo,
            $comprobanteUrl
        ]);

        $compraId = $pdo->lastInsertId();

        $stmtTicket = $pdo->prepare("
            INSERT INTO tickets (compra_id, sorteo_id, numero_ticket, estado) 
            VALUES (?, ?, ?, 'pendiente')
        ");

        foreach ($ticketsGenerados as $tNum) {
            $stmtTicket->execute([$compraId, $sorteoId, $tNum]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error al registrar compra: " . $e->getMessage());
        // Continuamos para no bloquear al usuario con el envío a WhatsApp
    }
}

// Preparar URL para WhatsApp
$numeroEmpresa = "584241733873";
$ticketsStr = implode(', ', $ticketsGenerados);

$mensajeWA = "¡Hola! Quiero confirmar mi compra en *GanAndreis* 🍀\n\n"
           . "📋 *Orden:* {$codigoOrden}\n"
           . "👤 *Cliente:* {$nombre}\n"
           . "🆔 *Cédula:* {$cedula}\n"
           . "📞 *Teléfono:* {$codPais} {$telefono}\n"
           . "📧 *Correo:* {$correo}\n\n"
           . "🎟️ *Boletos ({$cantidad}):* {$ticketsStr}\n"
           . "💰 *Total a Pagar:* $" . number_format($totalMonto, 2) . "\n"
           . "🏦 *Método de Pago:* {$metodo}\n\n"
           . "📎 _Adjunto mi comprobante de pago a continuación:_";

$urlWhatsApp = "https://api.whatsapp.com/send?phone={$numeroEmpresa}&text=" . urlencode($mensajeWA);

echo json_encode([
    'success'       => true,
    'codigo_orden'  => $codigoOrden,
    'tickets'       => $ticketsGenerados,
    'cantidad'      => $cantidad,
    'total'         => number_format($totalMonto, 2),
    'whatsapp_url'  => $urlWhatsApp
]);
exit;
