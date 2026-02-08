document.addEventListener('DOMContentLoaded', () => {
    
    // --- 1. LÓGICA DE CONTADOR Y PRECIO ---
    const precioUnitario = 3.50; 
    const btnMinus = document.getElementById('btn-minus');
    const btnPlus = document.getElementById('btn-plus');
    const displayCantidad = document.getElementById('cantidad-tickets');
    const displayTotal = document.getElementById('total-monto');

    let cantidad = 1;
    let totalGlobal = 3.50; // Guardamos el total para usarlo en el WA

    function actualizarTotal() {
        displayCantidad.innerText = cantidad;
        totalGlobal = (cantidad * precioUnitario).toFixed(2);
        displayTotal.innerText = `$${totalGlobal}`;
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

    // --- 2. LÓGICA DE MÉTODOS DE PAGO ---
    const datosBancarios = {
        'pago-movil': `
            <div class="datos-activos">
                <h4>Pago Móvil Venezuela</h4>
                <p><strong>C.I:</strong> 12.345.678</p>
                <p><strong>Teléfono:</strong> 0414-1234567</p>
                <p><strong>Banco:</strong> Banco de Venezuela (0102)</p>
            </div>`,
        'binance': `
            <div class="datos-activos">
                <h4>Binance Pay</h4>
                <p><strong>ID Pay:</strong> 123456789</p>
                <p><strong>Correo:</strong> pagos@ganandreis.com</p>
            </div>`,
        'zelle': `
            <div class="datos-activos">
                <h4>Zelle</h4>
                <p><strong>Correo:</strong> zelle@ganandreis.com</p>
                <p><strong>Titular:</strong> Inversiones GanAndreis</p>
            </div>`,
        'paypal': `
            <div class="datos-activos">
                <h4>PayPal</h4>
                <p><strong>Usuario:</strong> @ganandreis_oficial</p>
            </div>`
    };

    const opcionesPago = document.querySelectorAll('.pago-item');
    const contenedorDatos = document.getElementById('datos-pago-info');
    let metodoSeleccionado = ''; // Variable para guardar qué eligió el usuario

    opcionesPago.forEach(opcion => {
        opcion.addEventListener('click', () => {
            opcionesPago.forEach(btn => btn.classList.remove('active'));
            opcion.classList.add('active');

            const metodo = opcion.getAttribute('data-metodo');
            metodoSeleccionado = metodo; // Guardamos la selección
            
            // Nombre bonito para el mensaje de WA
            if(metodo === 'pago-movil') metodoSeleccionado = 'Pago Móvil';
            if(metodo === 'binance') metodoSeleccionado = 'Binance';
            if(metodo === 'zelle') metodoSeleccionado = 'Zelle';
            if(metodo === 'paypal') metodoSeleccionado = 'PayPal';

            if (datosBancarios[metodo]) {
                contenedorDatos.innerHTML = datosBancarios[metodo];
                // Quitamos error si ya seleccionó
                contenedorDatos.classList.remove('input-error');
            }
        });
    });

    // --- 3. INPUT FILE (Nombre de archivo) ---
    const fileInput = document.getElementById('file-upload');
    const fileNameSpan = document.getElementById('file-name');

    fileInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            fileNameSpan.textContent = this.files[0].name;
            fileNameSpan.style.color = 'var(--color-primario)';
            // Quitamos error visual
            document.querySelector('.custom-file-upload').classList.remove('input-error');
        } else {
            fileNameSpan.textContent = "Ningún archivo seleccionado";
            fileNameSpan.style.color = 'var(--color-terciario)';
        }
    });

    // --- 4. VALIDACIÓN Y ENVÍO A WHATSAPP ---
    const formTicket = document.querySelector('.form-ticket');
    
    // Seleccionamos los inputs por orden (asegúrate que el HTML tenga este orden)
    // Input 0: Cedula, 1: Nombre, 2: Correo, 3: Telefono
    const inputs = formTicket.querySelectorAll('input[type="text"], input[type="email"], input[type="number"]');

    formTicket.addEventListener('submit', function(e) {
        e.preventDefault(); // Evitamos que recargue la página

        let hayError = false;

        // 1. Validar Inputs de Texto
        inputs.forEach(input => {
            if (input.value.trim() === '') {
                input.classList.add('input-error');
                hayError = true;
            } else {
                input.classList.remove('input-error');
            }
            
            // Listener para quitar el rojo cuando escriban
            input.addEventListener('input', () => {
                input.classList.remove('input-error');
            });
        });

        // 2. Validar Método de Pago
        if (metodoSeleccionado === '') {
            contenedorDatos.classList.add('input-error'); // Pintamos el cuadro de datos
            hayError = true;
        }

        // 3. Validar Comprobante (Opcional, si quieres obligarlo descomenta esto)
        /*
        if (fileInput.files.length === 0) {
            document.querySelector('.custom-file-upload').classList.add('input-error');
            hayError = true;
        }
        */

        if (hayError) {
            // Si falta algo, no enviamos nada
            return; 
        }

        // --- SI TODO ESTÁ BIEN: PREPARAMOS WHATSAPP ---
        
        const cedula = inputs[0].value;
        const nombre = inputs[1].value;
        const correo = inputs[2].value;
        const codPais = document.querySelector('select[name="cod-pais"]').value;
        const telefono = inputs[3].value;
        const tlfCompleto = `${codPais} ${telefono}`;

        // Número de destino (Tu empresa)
        const numeroEmpresa = "584241733873"; 

        // Mensaje estructurado con saltos de línea (%0A)
        const mensaje = `
*¡Hola! Quiero confirmar mi compra en GanAndreis* 🍀

👤 *Cliente:* ${nombre}
🆔 *Cédula:* ${cedula}
📞 *Teléfono:* ${tlfCompleto}
📧 *Correo:* ${correo}

🎟️ *Boletos:* ${cantidad}
💰 *Total a Pagar:* $${totalGlobal}
🏦 *Método de Pago:* ${metodoSeleccionado}

📎 _Adjunto mi comprobante de pago a continuación:_
        `.trim();

        // Crear la URL codificada
        const urlWhatsApp = `https://wa.me/${numeroEmpresa}?text=${encodeURIComponent(mensaje)}`;

        // Abrir en nueva pestaña
        window.open(urlWhatsApp, '_blank');
    });

});