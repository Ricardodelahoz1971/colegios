/**
 * 🏛️ SISTEMA DE CONTROL DE CLIENTE PARA DASHBOARD (MAESTRO PORTAL)
 * Maneja la navegación SPA, temas, sidebar táctil y ciclos de vida de modales bootstrap.
 */

// 🔔 HELPER INSTITUCIONAL UNIVERSAL: TOAST FLOTANTE ÉLITE
window.lanzarToastElite = function (tipo, mensaje, titulo = null) {
    const toast = document.createElement('div');
    const tipoNormalizado = (tipo === 'error') ? 'danger' : (tipo || 'info');
    toast.className = `toast-elite toast-elite--${tipoNormalizado} toast-floating-elite animate__animated animate__fadeInRight`;

    let icon = 'bi-check-circle-fill text-success';
    if (tipoNormalizado === 'danger') icon = 'bi-exclamation-triangle-fill text-danger';
    else if (tipoNormalizado === 'warning') icon = 'bi-exclamation-circle-fill text-warning';
    else if (tipoNormalizado === 'info') icon = 'bi-info-circle-fill text-info';

    const headerText = titulo || (tipoNormalizado === 'danger' ? 'Error' : (tipoNormalizado === 'warning' ? 'Advertencia' : 'Notificación'));

    toast.innerHTML = `
        <div class="d-flex align-items-center gap-2">
            <i class="bi ${icon} fs-5"></i>
            <div>
                <div class="fw-bold small text-uppercase">${headerText}</div>
                <div class="small text-secondary">${mensaje}</div>
            </div>
        </div>
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('animate__fadeInRight');
        toast.classList.add('animate__fadeOutRight');
        setTimeout(() => toast.remove(), 400);
    }, 3500);
};

function toggleDarkMode() {
    const isDark = document.body.classList.toggle('dark-theme-mode');
    document.getElementById('icon-moon').classList.toggle('d-none', isDark);
    document.getElementById('icon-sun').classList.toggle('d-none', !isDark);
    
    const formData = new FormData();
    formData.append('dark_mode', isDark ? '1' : '0');
    fetch('logica/guardar_estetica.php', { method: 'POST', body: formData });
}

function toggleSidebarElite() {
    const sidebar = document.getElementById('sidebarMaestro');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    document.body.classList.toggle('u-overflow-hidden', sidebar.classList.contains('active'));
}

function navegarModulo(p, push = true) {
    window.MODULO_ACTUAL = p.split('&')[0];
    if (window.innerWidth <= 992) {
        const sidebar = document.getElementById('sidebarMaestro');
        if (sidebar.classList.contains('active')) {
            toggleSidebarElite();
        }
    }
    
    if (window.location.search === ('?p=' + p) && push && !window.forceRefreshElite) return;
    window.forceRefreshElite = false; 

    actualizarSidebar(p);

    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.querySelectorAll('.modal.show').forEach(el => {
        const m = bootstrap.Modal.getInstance(el);
        if (m) m.hide();
        el.remove();
    });
    document.body.classList.remove('modal-open', 'u-overflow-hidden');
    
    const newUrl = `dashboard.php?p=${p}`;
    const mainContentArea = document.querySelector('.flex-fill.overflow-auto');
    const pageHeader = document.querySelector('.hero-module-title');
    
    if (mainContentArea) {
        mainContentArea.classList.remove('u-opacity-full');
        mainContentArea.classList.add('u-opacity-muted');
    }

    if (push) window.history.pushState({p: p}, '', newUrl);

    fetch(`${newUrl}&raw=1`)
    .then(r => r.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const nuevoHTML = doc.querySelector('.flex-fill.overflow-auto');
        const nuevoTitulo = doc.querySelector('.hero-module-title');
        
        if (nuevoHTML && mainContentArea) {
            // 🛡️ SOBERANÍA DE ESTILOS DINÁMICOS (Inyección en el head para SPA)
            doc.querySelectorAll('head link[rel="stylesheet"]').forEach(newLink => {
                const href = newLink.getAttribute('href');
                if (href && !document.querySelector(`head link[href="${href}"]`)) {
                    const linkElem = document.createElement('link');
                    linkElem.rel = 'stylesheet';
                    linkElem.href = href;
                    document.head.appendChild(linkElem);
                }
            });

            mainContentArea.querySelectorAll('script.spa-injected').forEach(s => s.remove());
            mainContentArea.innerHTML = nuevoHTML.innerHTML;
            if (nuevoTitulo && pageHeader) pageHeader.innerText = nuevoTitulo.innerText;
            document.title = doc.title;
            
            // 🛡️ SOBERANÍA GLOBAL DE MODALES (Liberación de Stacking Context)
            // Extraer modales del módulo inyectado y moverlos al root (body)
            mainContentArea.querySelectorAll('.modal').forEach(modal => {
                if (modal.id) {
                    const oldModal = document.querySelector('body > #' + modal.id);
                    if (oldModal) oldModal.remove();
                }
                document.body.appendChild(modal);
            });

            const scripts = nuevoHTML.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                newScript.classList.add('spa-injected');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                if (oldScript.textContent) {
                    newScript.textContent = oldScript.textContent;
                }
                mainContentArea.appendChild(newScript);
            });

            mainContentArea.classList.remove('u-opacity-muted');
            mainContentArea.classList.add('u-opacity-full');
            mainContentArea.scrollTo({ top: 0, behavior: 'instant' });
            document.dispatchEvent(new Event('DOMContentLoaded'));
        } else {
            window.location.href = newUrl;
        }
    })
    .catch(() => {
        if (mainContentArea) {
            mainContentArea.classList.remove('u-opacity-muted');
            mainContentArea.classList.add('u-opacity-full');
        }
        window.location.href = newUrl;
    });
}

function actualizarSidebar(p) {
    const allLinks = document.querySelectorAll('.menu-link-elite, .submenu-link-elite');
    allLinks.forEach(link => link.classList.remove('active'));

    const activeLink = document.querySelector(`.sidebar-container a[onclick*="navegarModulo('${p}')"]`);
    if (activeLink) {
        activeLink.classList.add('active');
        const parentItem = activeLink.closest('.menu-item-elite');
        if (parentItem) {
             const parentLink = parentItem.querySelector('.menu-link-elite');
             if (parentLink) parentLink.classList.add('active');
        }
    }
}

function purgarIntentosAres() {
    Swal.fire({
        title: '¿ESTÁS SEGURO?',
        text: "Esta acción purgará TODOS los intentos y respuestas de exámenes registrados en el sistema Ares. No se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--el-danger)',
        cancelButtonColor: 'rgb(110, 120, 129)',
        confirmButtonText: 'SÍ, PURGAR TODO',
        cancelButtonText: 'CANCELAR',
        background: document.body.classList.contains('dark-theme-mode') ? 'var(--el-bg-card)' : 'var(--el-white)',
        color: document.body.classList.contains('dark-theme-mode') ? 'var(--el-dark)' : 'var(--el-dark)'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'ÚLTIMA CONFIRMACIÓN',
                text: "El sistema quedará en blanco para nuevos exámenes. ¿Proceder?",
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'PURGAR AHORA',
                background: document.body.classList.contains('dark-theme-mode') ? 'var(--el-bg-card)' : 'var(--el-white)',
                color: document.body.classList.contains('dark-theme-mode') ? 'var(--el-dark)' : 'var(--el-dark)'
            }).then((final) => {
                if (final.isConfirmed) {
                    fetch('logica/purgar_examenes_total.php')
                    .then(r => r.json())
                    .then(async (res) => {
                        if (res.status === 'success') {
                            await Swal.fire('¡BÓVEDA LIMPIA!', res.message, 'success');
                            // Limpiar caché y navegar sin recarga completa
                            localStorage.clear();
                            sessionStorage.clear();
                            navegarModulo('inicio', true);
                        } else {
                            Swal.fire('ERROR', res.message, 'error');
                        }
                    });
                }
            });
        }
    });
}

window.onpopstate = function(event) {
    const urlParams = new URLSearchParams(window.location.search);
    const p = urlParams.get('p') || 'inicio';
    navegarModulo(p, false); 
};

document.addEventListener('click', function(e) {
    const link = e.target.closest('.menu-link-elite');
    if (!link) return;

    if (window.innerWidth <= 992) {
        const submenu = link.nextElementSibling;
        if (submenu && submenu.classList.contains('submenu-elite')) {
            e.preventDefault();
            e.stopPropagation();
            
            const wasActive = link.classList.contains('active-mobile');
            
            document.querySelectorAll('.menu-link-elite').forEach(l => l.classList.remove('active-mobile'));
            document.querySelectorAll('.submenu-elite').forEach(s => s.classList.remove('active-mobile'));

            if (!wasActive) {
                link.classList.add('active-mobile');
                submenu.classList.add('active-mobile');
            }
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    if (typeof iniciarCarteroDigital === 'function') {
        iniciarCarteroDigital();
    }
    
    document.querySelectorAll('.main-content-fixed .modal').forEach(modal => {
        if (modal.id) {
            const oldModal = document.querySelector('body > #' + modal.id);
            if (oldModal && oldModal !== modal) oldModal.remove();
        }
        document.body.appendChild(modal);
    });
});

document.addEventListener('show.bs.modal', function (event) {
    if (event.target && event.target.classList && event.target.classList.contains('modal')) {
        if (event.target.parentNode !== document.body) {
            document.body.appendChild(event.target);
        }
        event.target.style.zIndex = "1055";
    }
}, true);
