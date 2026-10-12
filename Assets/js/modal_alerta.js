/**
 * Sistema de Modales Reutilizables - GanAndreis
 * Provee mostrarAlerta(), mostrarConfirmacion() y reemplaza window.alert()
 */

(function () {
    // Inyectar contenedor del modal si no existe en el DOM
    function obtenerOcrearModal() {
        let overlay = document.getElementById('ganandreis-custom-modal');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'ganandreis-custom-modal';
            overlay.className = 'custom-modal-overlay';
            overlay.innerHTML = `
                <div class="custom-modal-card" id="ganandreis-modal-card">
                    <div class="custom-modal-icon-wrapper" id="ganandreis-modal-icon">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <h3 class="custom-modal-title" id="ganandreis-modal-title">Atención</h3>
                    <p class="custom-modal-message" id="ganandreis-modal-message"></p>
                    <div class="custom-modal-actions" id="ganandreis-modal-actions">
                        <button type="button" class="custom-modal-btn custom-modal-btn-primary" id="ganandreis-modal-btn-ok">ENTENDIDO</button>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
        }
        return overlay;
    }

    const CONFIG_ICONOS = {
        success: { icono: 'fa-solid fa-circle-check', tituloDefault: '¡Excelente!' },
        error:   { icono: 'fa-solid fa-circle-xmark', tituloDefault: 'Ha ocurrido un error' },
        warning: { icono: 'fa-solid fa-triangle-exclamation', tituloDefault: 'Atención' },
        info:    { icono: 'fa-solid fa-circle-info', tituloDefault: 'Información' }
    };

    /**
     * Muestra un modal de alerta estilizado.
     * @param {string} mensaje 
     * @param {'success'|'error'|'warning'|'info'} tipo 
     * @param {string} titulo (opcional)
     * @param {Function} onCerrar (opcional)
     * @returns {Promise<void>}
     */
    window.mostrarAlerta = function (mensaje, tipo = 'info', titulo = '', onCerrar = null) {
        return new Promise((resolve) => {
            const overlay = obtenerOcrearModal();
            const card = document.getElementById('ganandreis-modal-card');
            const iconWrapper = document.getElementById('ganandreis-modal-icon');
            const titleEl = document.getElementById('ganandreis-modal-title');
            const msgEl = document.getElementById('ganandreis-modal-message');
            const actionsEl = document.getElementById('ganandreis-modal-actions');

            const tipoFinal = CONFIG_ICONOS[tipo] ? tipo : 'info';
            const config = CONFIG_ICONOS[tipoFinal];

            // Limpiar clases previas
            card.className = 'custom-modal-card custom-modal-type-' + tipoFinal;
            iconWrapper.innerHTML = `<i class="${config.icono}"></i>`;
            titleEl.textContent = titulo || config.tituloDefault;
            msgEl.textContent = mensaje;

            actionsEl.innerHTML = `
                <button type="button" class="custom-modal-btn custom-modal-btn-primary" id="ganandreis-modal-btn-ok">
                    ACEPTAR
                </button>
            `;

            function cerrar() {
                overlay.classList.remove('active');
                document.removeEventListener('keydown', onKeyDown);
                if (typeof onCerrar === 'function') {
                    onCerrar();
                }
                resolve();
            }

            function onKeyDown(e) {
                if (e.key === 'Escape' || e.key === 'Enter') {
                    cerrar();
                }
            }

            const btnOk = document.getElementById('ganandreis-modal-btn-ok');
            btnOk.onclick = cerrar;
            document.addEventListener('keydown', onKeyDown);

            // Mostrar con animación
            requestAnimationFrame(() => {
                overlay.classList.add('active');
                btnOk.focus();
            });
        });
    };

    /**
     * Muestra un modal de confirmación (tipo confirm)
     * @param {string} mensaje 
     * @param {string} titulo 
     * @param {string} textoConfirmar 
     * @param {string} textoCancelar 
     * @returns {Promise<boolean>}
     */
    window.mostrarConfirmacion = function (mensaje, titulo = '¿Estás seguro?', textoConfirmar = 'Confirmar', textoCancelar = 'Cancelar') {
        return new Promise((resolve) => {
            const overlay = obtenerOcrearModal();
            const card = document.getElementById('ganandreis-modal-card');
            const iconWrapper = document.getElementById('ganandreis-modal-icon');
            const titleEl = document.getElementById('ganandreis-modal-title');
            const msgEl = document.getElementById('ganandreis-modal-message');
            const actionsEl = document.getElementById('ganandreis-modal-actions');

            card.className = 'custom-modal-card custom-modal-type-warning';
            iconWrapper.innerHTML = `<i class="fa-solid fa-circle-question"></i>`;
            titleEl.textContent = titulo;
            msgEl.textContent = mensaje;

            actionsEl.innerHTML = `
                <button type="button" class="custom-modal-btn custom-modal-btn-secondary" id="ganandreis-modal-btn-cancel">
                    ${textoCancelar}
                </button>
                <button type="button" class="custom-modal-btn custom-modal-btn-primary" id="ganandreis-modal-btn-confirm">
                    ${textoConfirmar}
                </button>
            `;

            function finalizar(resultado) {
                overlay.classList.remove('active');
                document.removeEventListener('keydown', onKeyDown);
                resolve(resultado);
            }

            function onKeyDown(e) {
                if (e.key === 'Escape') {
                    finalizar(false);
                }
            }

            document.getElementById('ganandreis-modal-btn-cancel').onclick = () => finalizar(false);
            document.getElementById('ganandreis-modal-btn-confirm').onclick = () => finalizar(true);
            document.addEventListener('keydown', onKeyDown);

            requestAnimationFrame(() => {
                overlay.classList.add('active');
                document.getElementById('ganandreis-modal-btn-confirm').focus();
            });
        });
    };

    // Reemplazar window.alert por defecto para que cualquier alert no interceptado sea un modal elegante
    window.alert = function (mensaje) {
        return window.mostrarAlerta(mensaje, 'info');
    };
})();
