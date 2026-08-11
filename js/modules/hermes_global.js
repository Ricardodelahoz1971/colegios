/**
 * 🛰️ HERMES GLOBAL v1.0 - MOTOR DE NOTIFICACIONES Y LATIDO (POLLING)
 * Responsable de la escucha activa de mensajes y alertas en PENDIENTE-ARES el sistema.
 */

window.alertasMostradas = JSON.parse(sessionStorage.getItem('alertasMostradas')) || [];

window.irAlNotifChat = function(event, chatId, type) {
    if (event) event.preventDefault();
    
    // Cerrar el dropdown de la campana de notificaciones de forma segura
    try {
        const dropdownEl = document.querySelector('#campanaNotificaciones [data-bs-toggle="dropdown"]');
        if (dropdownEl && window.bootstrap && bootstrap.Dropdown) {
            const dropdown = bootstrap.Dropdown.getOrCreateInstance(dropdownEl);
            if (dropdown) dropdown.hide();
        }
    } catch (err) {
        // Fallback silencioso para no interrumpir el flujo principal
    }

    if (window.MODULO_ACTUAL === 'mensajeria' && typeof window.abrirChat === 'function' && document.getElementById('destinatario_id')) {
        window.abrirChat(chatId, type);
    } else if (typeof navegarModulo === 'function') {
        navegarModulo(`mensajeria&chat=${chatId}&type=${type}`);
    } else {
        window.location.href = `dashboard.php?p=mensajeria&chat=${chatId}&type=${type}`;
    }
};

window.iniciarCarteroDigital = function() {
    // Primera ejecución inmediata
    verificarMensajes();
    // Ciclo cada 15 segundos (Modo Elite Boost)
    setInterval(verificarMensajes, 15000);
};

window.verificarMensajes = async function() {
    try {
        const response = await fetch('logica/check_mensajes.php', {
            credentials: 'same-origin' 
        });
        const data = await response.json();
        
        if (data.error) return;

        // 1. Actualizar Contadores y Animación de Campana
        const badgeSidebar = document.getElementById('badge-sidebar-mensajes');
        const iconoCampana = document.getElementById('icono-campana');
        
        if (data.unread_count > 0) {
            if (badgeSidebar) {
                badgeSidebar.textContent = data.unread_count;
                badgeSidebar.classList.remove('u-hidden');
            }
            if (iconoCampana) {
                iconoCampana.classList.add('bell-ring-active');
            }
        } else {
            if (badgeSidebar) badgeSidebar.classList.add('u-hidden');
            if (iconoCampana) {
                iconoCampana.classList.remove('bell-ring-active');
            }
        }

        // 1.05 Popular Lista Dropdown de Notificaciones Mini
        const listContainer = document.getElementById('lista-notificaciones-mini');
        if (listContainer) {
            let htmlContent = `
                <li class="dropdown-header fw-bold text-uppercase small pb-2">Notificaciones</li>
                <li><hr class="dropdown-divider"></li>
            `;
            
            if (data.notificaciones && data.notificaciones.length > 0) {
                data.notificaciones.forEach(notif => {
                    const initials = notif.remitente ? notif.remitente.substring(0, 1).toUpperCase() : '?';
                    htmlContent += `
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="#" onclick="window.irAlNotifChat(event, ${notif.chat_id}, '${notif.tipo}')">
                                <div class="avatar-elite--sm avatar-elite--circle me-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center notif-mini-avatar">
                                    <span class="small fw-bold">${initials}</span>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold small text-dark text-truncate notif-mini-name">${notif.remitente}</span>
                                        <span class="text-muted fs-nano">${notif.fecha}</span>
                                    </div>
                                    <p class="text-muted small text-truncate mb-0 notif-mini-text">${notif.contenido}</p>
                                </div>
                            </a>
                        </li>
                    `;
                });
            } else {
                htmlContent += `<li class="text-center py-3 text-muted small">Sin mensajes pendientes</li>`;
            }
            listContainer.innerHTML = htmlContent;
        }

        // 1.1 Actualización en Tiempo Real del Dashboard
        const dashMensajes = document.getElementById('count-mensajes');
        if (dashMensajes && data.unread_count !== undefined) {
            dashMensajes.textContent = data.unread_count;
            const iconMensaje = document.getElementById('icon-mensaje-dash');
            if (iconMensaje) {
                if (data.unread_count > 0) {
                    iconMensaje.classList.add('bell-ani');
                } else {
                    iconMensaje.classList.remove('bell-ani');
                }
            }
        }

        // 1.2 Disparador de Alertas Urgentes (SweetAlert)
        if (data.notificaciones && data.notificaciones.length > 0) {
            for (let i = 0; i < data.notificaciones.length; i++) {
                const notif = data.notificaciones[i];
                if (notif.prioridad === 3 && !window.alertasMostradas.includes(notif.id)) {
                    window.alertasMostradas.push(notif.id);
                    sessionStorage.setItem('alertasMostradas', JSON.stringify(window.alertasMostradas));
                    
                    const result = await Swal.fire({
                        title: '¡MENSAJE URGENTE!',
                        html: `<b>De:</b> ${notif.remitente}<br><br>${notif.contenido}`,
                        icon: 'warning',
                        confirmButtonText: 'Ver Mensaje',
                        confirmButtonColor: 'rgb(211, 51, 51)',
                        timer: 10000,
                        timerProgressBar: true
                    });
                    
                    if (result.isConfirmed) {
                        window.location.href = `dashboard.php?p=mensajeria&chat=${notif.chat_id}&type=${notif.tipo}`;
                    }
                }
            }
        }

        // 1.25 Favicon Dinámico
        const tieneUrgentes = data.notificaciones && data.notificaciones.some(n => n.prioridad === 3);
        if (typeof window.actualizarFaviconDinamico === 'function') {
            window.actualizarFaviconDinamico(tieneUrgentes);
        }

        // 1.3 Radar de Presencia (Puntitos verdes)
        if (data.online_ids) {
            document.querySelectorAll('.contact-item').forEach(item => {
                const userId = parseInt(item.getAttribute('data-user-id'));
                if (userId) {
                    let avatar = item.querySelector('.avatar-elite--sm');
                    if (avatar) {
                        let dot = avatar.querySelector('.status-indicator');
                        if (data.online_ids.includes(userId)) {
                            if (!dot) {
                                avatar.innerHTML += '<span class="status-indicator online"></span>';
                            }
                        } else if (dot) {
                            dot.remove();
                        }
                    }
                }
            });
        }

                // 1.35 Actualizar Badges de No Leídos Individuales en la Barra Lateral
        document.querySelectorAll('.contact-item').forEach(item => {
            const groupId = parseInt(item.getAttribute('data-group-id'));
            const userId = parseInt(item.getAttribute('data-user-id'));
            let unreadCount = 0;

            if (groupId) {
                unreadCount = data.unread_per_group && data.unread_per_group[groupId] ? data.unread_per_group[groupId] : 0;
            } else if (userId) {
                unreadCount = data.unread_per_user && data.unread_per_user[userId] ? data.unread_per_user[userId] : 0;
            }

            const nameContainer = item.querySelector('.elite-chat-contact-name')?.parentNode;
            if (nameContainer) {
                let badge = nameContainer.querySelector('.badge-elite--danger');
                if (unreadCount > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'badge-elite badge-elite--danger';
                        nameContainer.appendChild(badge);
                    }
                    badge.textContent = unreadCount;
                } else if (badge) {
                    badge.remove();
                }
            }
        });

        // 1.4 Refresco del Chat (Si el módulo está activo)
        const chatHistory = document.getElementById('chat-history');
        const destIdInput = document.getElementById('destinatario_id');
        const chatTypeInput = document.getElementById('chat_type_input');
        
        if (chatHistory && destIdInput) {
            const chatId = destIdInput.value;
            const chatType = chatTypeInput ? chatTypeInput.value : 'direct';
            
            const wrapper = document.querySelector('.chat-elite-wrapper');
            const isMobileSidebar = window.innerWidth <= 992 && wrapper && !wrapper.classList.contains('show-chat');
            
            if (chatId && chatId !== '0' && !isMobileSidebar) {
                const r = await fetch(`logica/obtener_mensajes_ajax.php?chat_id=${chatId}&chat_type=${chatType}`, { credentials: 'same-origin' });
                const res = await r.json();
                
                if (chatHistory.innerHTML !== res.html) {
                    const scrollPos = chatHistory.scrollTop;
                    const isAtBottom = chatHistory.scrollHeight - chatHistory.clientHeight <= chatHistory.scrollTop + 50;
                    chatHistory.innerHTML = res.html;
                    if (isAtBottom) chatHistory.scrollTop = chatHistory.scrollHeight;
                    else chatHistory.scrollTop = scrollPos;
                }

                const statusTextContainer = document.getElementById('chat-status-text');
                if (statusTextContainer && chatType === 'direct') {
                    if (res.online) {
                        statusTextContainer.innerHTML = '<small class="status-label-elite fs-nano text-success fw-bold">● EN LÍNEA</small>';
                    } else {
                        statusTextContainer.innerHTML = '<small class="status-label-elite fs-nano text-muted opacity-50">FUERA DE LÍNEA</small>';
                    }
                }
            }
        }
    } catch(error) {
    }
};

// Auto-inicio
if (document.getElementById('campanaNotificaciones')) {
    window.iniciarCarteroDigital();
}

window.actualizarFaviconDinamico = function(tieneUrgentes) {
    let favicon = document.querySelector('link[rel="icon"]') || document.querySelector('link[rel="shortcut icon"]');
    if (!favicon) {
        favicon = document.createElement('link');
        favicon.rel = 'icon';
        document.head.appendChild(favicon);
    }
    
    if (!tieneUrgentes) {
        if (window.faviconOriginalUrl) {
            favicon.href = window.faviconOriginalUrl;
        }
        return;
    }
    
    if (!window.faviconOriginalUrl) {
        const logoImg = document.querySelector('.logo-img-elite') || document.querySelector('.school-logo-global');
        const candidateHref = favicon.getAttribute('href');
        // Si el href es una ruta relativa que no contiene '../', pero estamos en php/, adaptarla o usar la del logo
        if (candidateHref && !candidateHref.startsWith('http') && !candidateHref.startsWith('/') && !candidateHref.startsWith('../')) {
            window.faviconOriginalUrl = '../' + candidateHref;
        } else {
            window.faviconOriginalUrl = (logoImg && logoImg.src) ? logoImg.src : '../perseus.png';
        }
    }
    
    const img = new Image();
    img.src = window.faviconOriginalUrl;
    img.onload = function() {
        const canvas = document.createElement('canvas');
        canvas.width = 32;
        canvas.height = 32;
        const ctx = canvas.getContext('2d');
        
        ctx.drawImage(img, 0, 0, 32, 32);
        
        ctx.beginPath();
        ctx.arc(24, 8, 6, 0, 2 * Math.PI);
        ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--el-danger').trim() || 'red';
        ctx.fill();
        
        ctx.lineWidth = 1.5;
        ctx.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--el-white').trim() || 'white';
        ctx.stroke();
        
        favicon.href = canvas.toDataURL('image/png');
    };
};
