<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Ticket | GanAndreis</title>
    <link rel="stylesheet" href="Assets/css/index.css">
    <link rel="stylesheet" href="Assets/css/menu_and_footer.css">
    <link rel="stylesheet" href="Assets/css/ticket.css">
    <link rel="shortcut icon" href="Assets/img/logo-transparente.png" type="image/x-icon">
    <link href="Assets/icons/fontawesome-free-6.2.1-web/css/all.css" rel="stylesheet">
    <style>
        .badge.rechazado { background: rgba(255, 69, 58, 0.2); color: #FF453A; border: 1px solid #FF453A; }
        .no-data-msg { text-align: center; padding: 30px; color: var(--color-terciario); font-size: 1.1rem; }
        .loading-spinner { text-align: center; padding: 25px; color: var(--color-primario); font-size: 1.2rem; }
    </style>
</head>
<body>

    <header>
        <div class="box-menu">
            <div class="box-menu-opciones">
                <ul>
                    <li> <a href="index.php"> <i class="fa-solid fa-house"></i> Inicio</a></li>
                    <li> <a href="validar_ticket.php"> <i class="fa-solid fa-ticket"></i> Ticket</a></li>
                    <li> <a href="https://api.whatsapp.com/send/?phone=584241733873&text=Hola,%20necesito%20ayuda%20con%20GanAndreis" target="_blank"> <i class="fa-solid fa-phone"></i> Ayuda</a></li>
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
        <div class="cont-search">
            <div class="box-search">
                <div class="icon-header">
                    <i class="fa-solid fa-magnifying-glass-dollar"></i>
                </div>
                <h1>Consultar Ticket</h1>
                <p>Ingresa tu número de cédula para verificar el estado de tus boletos y sorteos.</p>
                
                <form id="formConsulta">
                    <div class="input-group">
                        <i class="fa-solid fa-id-card"></i>
                        <input type="number" id="cedulaInput" placeholder="Ej: 28456789" required autocomplete="off">
                    </div>
                    <button type="submit" class="btn-buscar" id="btnBuscar">
                        <i class="fa-solid fa-search"></i> BUSCAR AHORA
                    </button>
                </form>
            </div>
        </div>

        <div id="modalResultados" class="modal-overlay">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                
                <div class="modal-header">
                    <h2><i class="fa-solid fa-receipt"></i> Tus Tickets Encontrados</h2>
                    <p>Resultados para la cédula: <strong id="cedulaMostrada" style="color:var(--color-primario)"></strong></p>
                </div>

                <div class="table-container" id="contenedorTabla">
                    <table>
                        <thead>
                            <tr>
                                <th># Ticket</th>
                                <th>Sorteo</th>
                                <th>Fecha Compra</th>
                                <th>Monto</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tablaCuerpo">
                            <!-- Filas dinámicas generadas por JavaScript -->
                        </tbody>
                    </table>
                </div>

                <div class="modal-footer">
                    <p>¿Algún problema con tu ticket? <a href="https://api.whatsapp.com/send/?phone=584241733873&text=Hola,%20tengo%20un%20inconveniente%20con%20mis%20tickets" target="_blank">Contáctanos por WhatsApp</a></p>
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
                    <li><i class="fa-brands fa-whatsapp"></i> 0424-1733873</li>
                </ul>
            </div>
        </div>
        <div class="cont-copy">
            <p>©COPYRIGHT GanAndreis <?= date('Y') ?> - Todos los Derechos Reservados</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('formConsulta');
            const modal = document.getElementById('modalResultados');
            const closeBtn = document.querySelector('.close-btn');
            const cedulaInput = document.getElementById('cedulaInput');
            const cedulaMostrada = document.getElementById('cedulaMostrada');
            const tablaCuerpo = document.getElementById('tablaCuerpo');
            const btnBuscar = document.getElementById('btnBuscar');

            // Abrir Modal y consultar al backend
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const cedula = cedulaInput.value.trim();
                if (!cedula) return;

                btnBuscar.disabled = true;
                btnBuscar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Buscando...';

                try {
                    const res = await fetch(`consultar_cedula.php?cedula=${encodeURIComponent(cedula)}`);
                    const data = await res.json();

                    cedulaMostrada.textContent = cedula;
                    tablaCuerpo.innerHTML = '';

                    if (data.success && data.tickets && data.tickets.length > 0) {
                        data.tickets.forEach(ticket => {
                            const tr = document.createElement('tr');
                            
                            let estadoClass = 'pendiente';
                            let estadoTexto = 'En Revisión';
                            if (ticket.estado === 'validado') {
                                estadoClass = 'validado';
                                estadoTexto = 'Validado';
                            } else if (ticket.estado === 'rechazado') {
                                estadoClass = 'rechazado';
                                estadoTexto = 'Rechazado';
                            }

                            tr.innerHTML = `
                                <td><strong style="color: var(--color-primario); font-size:1.1rem;">${ticket.numero_ticket}</strong></td>
                                <td>${ticket.sorteo_titulo}</td>
                                <td>${ticket.fecha_compra}</td>
                                <td>$${parseFloat(ticket.monto).toFixed(2)}</td>
                                <td><span class="badge ${estadoClass}">${estadoTexto}</span></td>
                            `;
                            tablaCuerpo.appendChild(tr);
                        });
                    } else {
                        tablaCuerpo.innerHTML = `
                            <tr>
                                <td colspan="5" class="no-data-msg">
                                    <i class="fa-regular fa-circle-xmark" style="font-size:2rem; display:block; margin-bottom:10px; color:#FF453A;"></i>
                                    No se encontraron tickets registrados con la cédula <strong>${cedula}</strong>.
                                </td>
                            </tr>
                        `;
                    }

                    modal.style.display = 'flex';
                } catch (err) {
                    alert('Ocurrió un error al consultar tus tickets. Por favor intenta de nuevo.');
                } finally {
                    btnBuscar.disabled = false;
                    btnBuscar.innerHTML = '<i class="fa-solid fa-search"></i> BUSCAR AHORA';
                }
            });

            // Cerrar Modal con la X
            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });

            // Cerrar Modal clicando fuera de la caja
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
