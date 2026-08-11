/**
 * 💬 CHAT ENGINE v14.0 - MOTOR DE MENSAJERÍA SPA (PROTOCOL MERCURIO)
 * Optimización para Vitrina 06 y arquitectura modular Élite.
 */

document.addEventListener('DOMContentLoaded', () => {
    const chatHistory = document.getElementById('chat-history');
    if (chatHistory) chatHistory.scrollTop = chatHistory.scrollHeight;
});

window.actualizarHistorialChat = async function(chatId, chatType) {
    try {
        // Buscar el contenedor de mensajes
        const chatHistoryContainer = document.getElementById('chat-history');
        if (!chatHistoryContainer) return;

        // Realizar la petición fetch para obtener los nuevos mensajes
        const res = await fetch(`logica/obtener_mensajes_ajax.php?chat_id=${chatId}&chat_type=${chatType}`, { credentials: 'same-origin' });
        const data = await res.json();

        if (data.status === 'success') {
            chatHistoryContainer.innerHTML = data.html;
            chatHistoryContainer.scrollTop = chatHistoryContainer.scrollHeight;
        }
    } catch (error) {
        
    }
}

// LÓGICA DE ENVÍO DE MENSAJES (Delegación Elite - Protegido contra Duplicación SPA)
if (!window.chatEngineSubmitRegistered) {
    window.chatEngineSubmitRegistered = true;
    document.addEventListener('submit', async function(e) {
    if (e.target && e.target.id === 'form-mensaje') {
        e.preventDefault();
        try {
            const dest_id = document.getElementById('destinatario_id').value;
            const chat_type = document.getElementById('chat_type_input').value;
            const contenido = document.getElementById('mensaje-texto').value;
            const prioridad = document.querySelector('input[name="prioridad"]:checked')?.value || 1;
            const csrf_token = document.getElementById('csrf_token_chat')?.value;

            if (!dest_id || !contenido.trim()) return;

            const formData = new FormData();
            formData.append('destinatario_id', dest_id);
            formData.append('chat_type', chat_type);
            formData.append('contenido', contenido);
            formData.append('prioridad', prioridad);
            if (csrf_token) formData.append('csrf_token', csrf_token);

            // Ruta corregida para dashboard.php
            const res = await fetch('logica/procesar_mensaje.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                document.getElementById('mensaje-texto').value = '';
                if (typeof verificarMensajes === 'function') verificarMensajes();
                // Refresco temporal para ver el mensaje enviado (SPA)
                actualizarHistorialChat(dest_id, chat_type);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
        }
    }
});
}

window.abrirChat = async function(id, type) {
    if (!id) return;
    
    // Transición MÓVIL
    document.querySelector('.chat-elite-wrapper')?.classList.add('show-chat');
    
    const newUrl = `dashboard.php?p=mensajeria&chat=${id}&type=${type}`;
    window.history.pushState({path:newUrl}, '', newUrl);
    
    const destIdInput = document.getElementById('destinatario_id');
    const typeInput = document.getElementById('chat_type_input');
    if (destIdInput) destIdInput.value = id;
    if (typeInput) typeInput.value = type;
    
    const chatMain = document.querySelector('.chat-main');
    if (chatMain) {
        chatMain.innerHTML = `
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center p-5 animate__animated animate__fadeIn">
                <div class="loader-ball-elite mb-3"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div>
                <h5 class="fw-bold text-dark mb-1">HERMES: Conectando...</h5>
                <p class="text-secondary small opacity-50">Sincronización Segura Élite</p>
            </div>`;
    }
    
    // Estilo activo y remoción de badge de no leídos
    document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('is-active-elite'));
    const sel = type === 'group' ? `[data-group-id="${id}"]` : `[data-user-id="${id}"]`;
    const targetEl = document.querySelector(sel);
    if (targetEl) {
        targetEl.classList.add('is-active-elite');
        const badge = targetEl.querySelector('.badge-elite--danger');
        if (badge) badge.remove();
    }

    try {
        const r = await fetch(`${newUrl}&raw=1`);
        const html = await r.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const newChatMain = doc.querySelector('.chat-main');
        
        if (newChatMain && chatMain) {
            chatMain.innerHTML = newChatMain.innerHTML;
            const history = document.getElementById('chat-history');
            if (history) history.scrollTop = history.scrollHeight;
            
            // Refrescar contadores globales una vez leídos los mensajes en BD
            if (typeof verificarMensajes === 'function') verificarMensajes();
        }
    } catch (err) {
    }
}

window.cerrarChat = function() {
    document.querySelector('.chat-elite-wrapper')?.classList.remove('show-chat');
    const newUrl = 'dashboard.php?p=mensajeria';
    window.history.pushState({path:newUrl}, '', newUrl);
    
    const destIdInput = document.getElementById('destinatario_id');
    const typeInput = document.getElementById('chat_type_input');
    if (destIdInput) destIdInput.value = '0';
    if (typeInput) typeInput.value = 'direct';
    
    document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('is-active-elite'));
}

window.abrirModalMasivo = function() {
    const contenedor = document.getElementById('lista-canales-masivo');
    if (!contenedor) return;

    contenedor.innerHTML = '';

    if (typeof listaCanalesGlobal !== 'undefined' && listaCanalesGlobal.length > 0) {
        contenedor.innerHTML += '<div class="px-3 py-2 bg-light small fw-bold text-primary border-bottom border-top letter-spacing-1 mb-2">CANALES DISPONIBLES</div>';

        const grid = document.createElement('div');
        grid.className = 'elite-grid-masivo';

        listaCanalesGlobal.forEach(canal => {
            const esInstitucional = canal.nombre.includes('CANAL DE');
            const icono = esInstitucional 
                ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgb(245, 158, 11)" stroke-width="3" class="me-1"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>'
                : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgb(245, 158, 11)" stroke-width="3" class="me-1"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>';

            grid.innerHTML += `
                <label class="list-group-item d-flex align-items-center cursor-pointer border-0 py-2 border-bottom border-light">
                    <input class="form-check-input-elite me-3 shadow-none" type="checkbox" value="${canal.id}" name="canales_masivo">
                    <div class="d-flex align-items-center">
                        ${icono}
                        <div class="elite-channel-name ${esInstitucional ? '' : 'text-muted'}">${canal.nombre}</div>
                    </div>
                </label>
            `;
        });

        contenedor.appendChild(grid);
        
        let modalEl = document.getElementById('modalMasivo');
        if (modalEl) {
            let modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    } else {
        Swal.fire('Atención', 'No hay destinos disponibles para la transmisión.', 'info');
    }
}

window.enviarMasivo = async function() {
    const checkboxes = document.querySelectorAll('input[name="canales_masivo"]:checked');
    const contenido = document.getElementById('masivo-texto').value;
    const prioridad = document.getElementById('masivo-prioridad').value;
    const csrf_token = document.getElementById('csrf_token_chat')?.value || window.CSRF_TOKEN;

    if (checkboxes.length === 0 || !contenido.trim()) {
        Swal.fire('Incompleto', 'Debe seleccionar canales y escribir el mensaje.', 'warning');
        return;
    }

    const ids = Array.from(checkboxes).map(cb => cb.value);
    const formData = new FormData();
    formData.append('grupos', JSON.stringify(ids));
    formData.append('contenido', contenido);
    formData.append('prioridad', prioridad);
    formData.append('csrf_token', csrf_token);

    const btnEnviando = document.querySelector('[onclick="enviarMasivo()"]');
    const originalHtml = btnEnviando.innerHTML;
    btnEnviando.disabled = true;
    btnEnviando.innerHTML = '<div class="loader-ball-elite--mini"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div> Transmitiendo...';

    try {
        const r = await fetch('logica/procesar_masivo.php', { method: 'POST', body: formData });
        const data = await r.json();

        btnEnviando.disabled = false;
        btnEnviando.innerHTML = originalHtml;
        
        if (data.status === 'success') {
            const modalEl = document.getElementById('modalMasivo');
            bootstrap.Modal.getInstance(modalEl).hide();
            Swal.fire('¡Misión Cumplida!', data.message, 'success');
            document.getElementById('masivo-texto').value = '';
        } else {
            Swal.fire('Error en Emisión', data.message, 'error');
        }
    } catch (err) {
        btnEnviando.disabled = false;
        btnEnviando.innerHTML = originalHtml;
        Swal.fire('Falla del Motor', 'No se pudo conectar con el servidor de transmisiones.', 'error');
    }
}

// APLICACIÓN DE FILTROS EN BARRA LATERAL (Vitrina 06 - BEM-Elite)
function applyContactFilters() {
    const term = document.getElementById('buscar-contacto')?.value.toLowerCase() || '';
    const unreadOnly = document.getElementById('filter-unread')?.classList.contains('is-active') || false;
    const urgentOnly = document.getElementById('filter-urgent')?.classList.contains('is-active') || false;

    document.querySelectorAll('.contact-item').forEach(item => {
        const name = item.querySelector('.elite-chat-contact-name')?.textContent.toLowerCase() || '';
        const isUnread = item.getAttribute('data-unread') === '1';
        const isUrgent = item.getAttribute('data-urgent') === '1';

        const matchesSearch = name.includes(term);
        const matchesUnread = !unreadOnly || isUnread;
        const matchesUrgent = !urgentOnly || isUrgent;

        if (matchesSearch && matchesUnread && matchesUrgent) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

document.getElementById('buscar-contacto')?.addEventListener('input', applyContactFilters);

document.getElementById('filter-unread')?.addEventListener('click', function() {
    this.classList.toggle('is-active');
    applyContactFilters();
});

document.getElementById('filter-urgent')?.addEventListener('click', function() {
    this.classList.toggle('is-active');
    applyContactFilters();
});

// ACUSE DE RECIBO FIRMADO (Para mensajes Urgentes)
document.addEventListener('click', async function(e) {
    const btn = e.target.closest('.btn-acknowledge-elite');
    if (!btn) return;

    e.preventDefault();
    const msgId = btn.getAttribute('data-msg-id');
    const csrf_token = document.getElementById('csrf_token_chat')?.value || window.CSRF_TOKEN;

    if (!msgId) return;

    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Firmando...';

    try {
        const formData = new FormData();
        formData.append('mensaje_id', msgId);
        if (csrf_token) formData.append('csrf_token', csrf_token);

        const res = await fetch('logica/acuse_recibo_ajax.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const data = await res.json();

        if (data.status === 'success') {
            const container = btn.parentNode;
            btn.remove();
            
            const badge = document.createElement('div');
            badge.className = 'acknowledged-badge-elite';
            badge.innerHTML = `<i class="bi bi-check-all"></i> Entendido (${data.fecha})`;
            container.appendChild(badge);

            // Refrescar contadores globales y barra lateral
            if (typeof verificarMensajes === 'function') {
                verificarMensajes();
            }
        } else {
            btn.disabled = false;
            btn.innerHTML = originalText;
            Swal.fire('Error', data.message || 'No se pudo firmar el acuse.', 'error');
        }
    } catch (error) {
        btn.disabled = false;
        btn.innerHTML = originalText;
        Swal.fire('Error de Red', 'Falla al conectar con el servidor para registrar el acuse.', 'error');
    }
});