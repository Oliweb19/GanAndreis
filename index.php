<?php
/**
 * Página Principal de Venta de Boletos - GanAndreis
 */
require_once __DIR__ . '/conexion.php';
$pdo = obtenerConexion();

// Valores por defecto
$sorteo = [
    'titulo' => 'Gran Premio iPhone 15 Pro Max',
    'descripcion' => 'Participa por este increíble premio. Tu oportunidad de ganar es ahora con GanAndreis.',
    'fecha_sorteo' => '2026-12-25',
    'hora_sorteo' => '08:00 PM',
    'precio_unitario' => 3.50,
    'imagen' => 'Assets/img/Prueba.jpg'
];

$metodosPago = [
    'pago-movil' => [
        'nombre' => 'Pago Móvil',
        'icono' => 'fa-solid fa-mobile-screen-button',
        'datos' => '<div class="datos-activos"><h4>Pago Móvil Venezuela</h4><p><strong>C.I:</strong> 12.345.678</p><p><strong>Teléfono:</strong> 0414-1234567</p><p><strong>Banco:</strong> Banco de Venezuela (0102)</p></div>'
    ],
    'binance' => [
        'nombre' => 'Binance',
        'icono' => 'fa-brands fa-bitcoin',
        'datos' => '<div class="datos-activos"><h4>Binance Pay</h4><p><strong>ID Pay:</strong> 123456789</p><p><strong>Correo:</strong> pagos@ganandreis.com</p></div>'
    ],
    'zelle' => [
        'nombre' => 'Zelle',
        'icono' => 'fa-solid fa-z',
        'datos' => '<div class="datos-activos"><h4>Zelle</h4><p><strong>Correo:</strong> zelle@ganandreis.com</p><p><strong>Titular:</strong> Inversiones GanAndreis</p></div>'
    ],
    'paypal' => [
        'nombre' => 'PayPal',
        'icono' => 'fa-brands fa-paypal',
        'datos' => '<div class="datos-activos"><h4>PayPal</h4><p><strong>Usuario:</strong> @ganandreis_oficial</p></div>'
    ]
];

if ($pdo) {
    try {
        $stmtS = $pdo->query("SELECT * FROM sorteos WHERE activo = 1 ORDER BY id DESC LIMIT 1");
        if ($s = $stmtS->fetch()) {
            $sorteo = $s;
        }

        $stmtM = $pdo->query("SELECT * FROM metodos_pago WHERE activo = 1");
        $metodosBD = $stmtM->fetchAll();
        if (!empty($metodosBD)) {
            $metodosPago = [];
            foreach ($metodosBD as $m) {
                $metodosPago[$m['codigo']] = [
                    'nombre' => $m['nombre'],
                    'icono'  => $m['icono'],
                    'datos'  => '<div class="datos-activos">' . $m['datos_html'] . '</div>'
                ];
            }
        }
    } catch (Exception $e) {
        // Fallback a defaults silenciosamente
    }
}

// Formateo de fecha
$timestampFecha = strtotime($sorteo['fecha_sorteo']);
$fechaFormateada = date('d/m/Y', $timestampFecha);
$precioUnitario = floatval($sorteo['precio_unitario']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GanAndreis | Sorteos y Rifas Oficiales</title>
    <!-- Estilos -->
    <link rel="stylesheet" href="Assets/css/index.css">
    <link rel="stylesheet" href="Assets/css/style-index.css">
    <link rel="stylesheet" href="Assets/css/menu_and_footer.css">
    <link rel="stylesheet" href="Assets/css/modal_alerta.css">
    <link rel="shortcut icon" href="Assets/img/logo-transparente.png" type="image/x-icon">
    <!-- Icons -->
    <link href="Assets/icons/fontawesome-free-6.2.1-web/css/all.css" rel="stylesheet">
    <style>
        .input-error {
            border: 1px solid #FF453A !important;
            box-shadow: 0 0 8px rgba(255, 69, 58, 0.4) !important;
        }
        /* Modal de confirmación de compra */
        .modal-compra-overlay {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(10px);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-compra-card {
            background: #1C1C1E;
            border: 1px solid rgba(48, 209, 88, 0.3);
            border-radius: 25px;
            max-width: 550px;
            width: 100%;
            padding: 35px 25px;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0,0,0,0.7);
            position: relative;
            animation: slideUp 0.3s ease;
        }
        .modal-compra-card h2 {
            color: var(--color-primario);
            margin: 15px 0 10px 0;
            font-size: 1.6rem;
        }
        .modal-tickets-box {
            background: #262628;
            border-radius: 14px;
            padding: 15px;
            margin: 20px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            max-height: 150px;
            overflow-y: auto;
        }
        .ticket-tag {
            background: rgba(48, 209, 88, 0.2);
            color: var(--color-primario);
            border: 1px solid var(--color-primario);
            padding: 5px 12px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 1.1rem;
        }
        .btn-wa-confirmar {
            background: #25D366;
            color: #fff;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 1rem;
            margin-top: 15px;
            transition: all 0.2s;
        }
        .btn-wa-confirmar:hover {
            opacity: 0.95;
            transform: scale(1.02);
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
                    <li> <a href="https://api.whatsapp.com/send/?phone=584241733873&text=Hola,%20necesito%20informacion%20sobre%20el%20sorteo" target="_blank"> <i class="fa-solid fa-phone"></i> Ayuda</a></li>
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
        <div class="cont-main">
            <div class="box-rifa">
                <img src="<?= htmlspecialchars($sorteo['imagen']) ?>" alt="<?= htmlspecialchars($sorteo['titulo']) ?>">
                <h2><?= htmlspecialchars($sorteo['titulo']) ?></h2>
                <div class="box-fecha">
                    <h3><i class="fa-solid fa-calendar"></i> <?= $fechaFormateada ?></h3>
                    <h3><i class="fa-solid fa-clock"></i> <?= htmlspecialchars($sorteo['hora_sorteo']) ?></h3>
                </div>
                <p><?= nl2br(htmlspecialchars($sorteo['descripcion'])) ?></p>
            </div>

            <div class="box-ticket">
                
                <div class="ticket-header">
                    <h1><i class="fa-solid fa-ticket"></i> Compra de Boletos</h1>
                </div>

                <div class="box-cantidad-wrapper">
                    <div class="box-cantidad">
                        <button type="button" id="btn-minus"><i class="fa-solid fa-minus"></i></button>
                        <h4 id="cantidad-tickets">1</h4>
                        <button type="button" id="btn-plus"><i class="fa-solid fa-plus"></i></button>
                    </div>
                    <div class="total-price">
                        <h5>Total a pagar:</h5>
                        <p id="total-monto">$<?= number_format($precioUnitario, 2) ?></p>
                    </div>
                </div>

                <!-- FORMULARIO ÚNICO Y VÁLIDO CON ENCTYPE MULTIPART -->
                <form id="formCompraBoleto" action="procesar_compra.php" method="POST" enctype="multipart/form-data" class="form-ticket">
                    
                    <input type="hidden" name="cantidad" id="inputCantidad" value="1">
                    <input type="hidden" name="metodo" id="inputMetodo" value="">

                    <h3><i class="fa-regular fa-id-card"></i> Datos Personales</h3>
                    <div class="grid-inputs">
                        <input type="text" name="cedula" id="inputCedula" placeholder="Cédula / DNI" required>
                        <input type="text" name="nombre" id="inputNombre" placeholder="Nombre Completo" required>
                        <input type="email" name="correo" id="inputCorreo" placeholder="Correo Electrónico" required>
                        <div class="tlf-group">
                            <select name="cod_pais" id="inputCodPais">
                                <option value="+58">+58 (VE)</option>
                                <option value="+1">+1 (US)</option>
                                <option value="+34">+34 (ES)</option>
                                <option value="+57">+57 (CO)</option>
                                <option value="+56">+56 (CL)</option>
                            </select>
                            <input type="number" name="telefono" id="inputTelefono" placeholder="Teléfono" required>
                        </div>  
                    </div>

                    <h3><i class="fa-solid fa-building-columns"></i> Métodos de Pago</h3>
                    <div class="box-pago">
                        <ul class="opciones-pago">
                            <?php foreach ($metodosPago as $key => $metodo): ?>
                                <li class="pago-item" data-metodo="<?= htmlspecialchars($key) ?>">
                                    <i class="<?= htmlspecialchars($metodo['icono']) ?>"></i> <span><?= htmlspecialchars($metodo['nombre']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="datos-pago-container" id="datos-pago-info">
                            <p class="info-placeholder">Selecciona un método de pago para ver los datos.</p>
                        </div>
                    </div>

                    <h3><i class="fa-solid fa-receipt"></i> Comprobante</h3>
                    <div class="box-comprobante">
                        <label for="file-upload" class="custom-file-upload">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Subir Capture
                        </label>
                        <input id="file-upload" type="file" name="comprobante" accept="image/*,.pdf">
                        <span id="file-name">Ningún archivo seleccionado</span>
                    </div>

                    <button type="submit" class="btn-confirmar" id="btnConfirmarCompra">CONFIRMAR COMPRA</button>
                </form>
            </div>
        </div>
    </main>

    <!-- Modal de Confirmación y Éxito -->
    <div id="modalConfirmacion" class="modal-compra-overlay">
        <div class="modal-compra-card">
            <i class="fa-solid fa-circle-check" style="font-size: 3.5rem; color: var(--color-primario);"></i>
            <h2>¡Compra Registrada!</h2>
            <p style="color: var(--color-terciario); font-size: 0.95rem;">
                Orden: <strong id="resOrden" style="color:#fff;"></strong> | Total: <strong id="resTotal" style="color:var(--color-primario);"></strong>
            </p>
            <p style="color: #fff; margin-top: 10px; font-size:0.9rem;">Tus números de boleto asignados:</p>
            
            <div class="modal-tickets-box" id="resTicketsBox">
                <!-- Badges de tickets generados -->
            </div>

            <p style="color: var(--color-terciario); font-size: 0.85rem;">
                Para finalizar la validación, envía tu comprobante por WhatsApp con un solo clic:
            </p>

            <a href="#" id="linkWhatsAppFinal" target="_blank" class="btn-wa-confirmar">
                <i class="fa-brands fa-whatsapp" style="font-size: 1.4rem;"></i> ENVIAR AHORA POR WHATSAPP
            </a>

            <div style="margin-top: 20px;">
                <a href="validar_ticket.php" style="color: var(--color-primario); font-size: 0.9rem; text-decoration: underline;">
                    Ir a Consultar mis Boletos
                </a>
            </div>
        </div>
    </div>

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
                    <li><i class="fa-brands fa-whatsapp"></i> 0424-1733873</li>
                </ul>
            </div>
        </div>
        <div class="cont-copy">
            <p>©COPYRIGHT GanAndreis <?= date('Y') ?> - Todos los Derechos Reservados</p>
        </div>
    </footer>

    <!-- Pasamos datos de PHP a Javascript -->
    <script>
        const PRECIO_UNITARIO = <?= $precioUnitario ?>;
        const DATOS_BANCARIOS = <?= json_encode(array_map(fn($m) => $m['datos'], $metodosPago)) ?>;
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Contador y precio
            const btnMinus = document.getElementById('btn-minus');
            const btnPlus = document.getElementById('btn-plus');
            const displayCantidad = document.getElementById('cantidad-tickets');
            const displayTotal = document.getElementById('total-monto');
            const inputCantidad = document.getElementById('inputCantidad');

            let cantidad = 1;

            function actualizarTotal() {
                displayCantidad.innerText = cantidad;
                inputCantidad.value = cantidad;
                const total = (cantidad * PRECIO_UNITARIO).toFixed(2);
                displayTotal.innerText = `$${total}`;
            }

            btnPlus.addEventListener('click', () => {
                cantidad++;
                actualizarTotal();
            });

            btnMinus.addEventListener('click', () => {
                if (cantidad > 1) {
                    cantidad--;
                    actualizarTotal();
                }
            });

            // 2. Métodos de pago
            const opcionesPago = document.querySelectorAll('.pago-item');
            const contenedorDatos = document.getElementById('datos-pago-info');
            const inputMetodo = document.getElementById('inputMetodo');

            opcionesPago.forEach(opcion => {
                opcion.addEventListener('click', () => {
                    opcionesPago.forEach(btn => btn.classList.remove('active'));
                    opcion.classList.add('active');

                    const metodo = opcion.getAttribute('data-metodo');
                    inputMetodo.value = metodo;

                    if (DATOS_BANCARIOS[metodo]) {
                        contenedorDatos.innerHTML = DATOS_BANCARIOS[metodo];
                        contenedorDatos.classList.remove('input-error');
                    }
                });
            });

            // 3. Subir archivo comprobante
            const fileInput = document.getElementById('file-upload');
            const fileNameSpan = document.getElementById('file-name');

            fileInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    fileNameSpan.textContent = this.files[0].name;
                    fileNameSpan.style.color = 'var(--color-primario)';
                } else {
                    fileNameSpan.textContent = "Ningún archivo seleccionado";
                    fileNameSpan.style.color = 'var(--color-terciario)';
                }
            });

            // 4. Envío del Formulario vía AJAX a procesar_compra.php
            const formTicket = document.getElementById('formCompraBoleto');
            const btnConfirmar = document.getElementById('btnConfirmarCompra');
            const modalConfirmacion = document.getElementById('modalConfirmacion');
            const resOrden = document.getElementById('resOrden');
            const resTotal = document.getElementById('resTotal');
            const resTicketsBox = document.getElementById('resTicketsBox');
            const linkWhatsAppFinal = document.getElementById('linkWhatsAppFinal');

            formTicket.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Validar método
                if (!inputMetodo.value) {
                    contenedorDatos.classList.add('input-error');
                    contenedorDatos.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    mostrarAlerta('Por favor selecciona un método de pago antes de continuar.', 'warning', 'Método Requerido');
                    return;
                }

                btnConfirmar.disabled = true;
                btnConfirmar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> REGISTRANDO COMPRA...';

                try {
                    const formData = new FormData(formTicket);
                    const res = await fetch('procesar_compra.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.success) {
                        resOrden.textContent = data.codigo_orden;
                        resTotal.textContent = `$${data.total}`;
                        resTicketsBox.innerHTML = '';
                        
                        data.tickets.forEach(ticket => {
                            const span = document.createElement('span');
                            span.className = 'ticket-tag';
                            span.textContent = ticket;
                            resTicketsBox.appendChild(span);
                        });

                        linkWhatsAppFinal.href = data.whatsapp_url;
                        modalConfirmacion.style.display = 'flex';

                        // Opcional: abrir WhatsApp en nueva pestaña
                        window.open(data.whatsapp_url, '_blank');
                    } else {
                        mostrarAlerta(data.error || 'Ocurrió un error al procesar la compra.', 'error', 'Error en la Compra');
                    }
                } catch (err) {
                    mostrarAlerta('Error de conexión con el servidor. Inténtalo nuevamente.', 'error', 'Error de Red');
                } finally {
                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = 'CONFIRMAR COMPRA';
                }
            });
        });
    </script>
    <script src="Assets/js/modal_alerta.js"></script>
</body>
</html>
