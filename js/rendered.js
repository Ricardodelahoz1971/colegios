window.MODULO_ACTUAL = "editor_preguntas";
window.CSRF_TOKEN = "dummy_token";

// 🛡️ CENTINELA DE RESOLUCIÓN ÉLITE
(function() {
    const isMobileScreen = window.innerWidth <= 1024;
    const cookieName = "dispositivo_elite=";
    const currentCookie = document.cookie.split(';').find(c => c.trim().startsWith(cookieName));
    const cookieValue = currentCookie ? currentCookie.split('=')[1] : null;

    if (isMobileScreen && cookieValue !== 'movil') {
        document.cookie = "dispositivo_elite=movil; path=/; max-age=86400";
        // Actualizar clases del documento sin recargar
        document.documentElement.setAttribute('data-device-type', 'mobile');
        document.body.classList.add('mobile-device-elite');
        document.body.classList.remove('desktop-device-elite');
    } else if (!isMobileScreen && cookieValue === 'movil') {
        document.cookie = "dispositivo_elite=escritorio; path=/; max-age=86400";
        // Actualizar clases del documento sin recargar
        document.documentElement.setAttribute('data-device-type', 'desktop');
        document.body.classList.remove('mobile-device-elite');
        document.body.classList.add('desktop-device-elite');
    }
})();

// 🛡️ MOTOR INTERCEPTOR ÉLITE (Soberanía de Red)
(function() {
    const originalFetch = window.fetch;
    window.fetch = function() {
        let [resource, config] = arguments;
        
        if (typeof resource === 'string' && (resource.includes('logica/') || resource.includes('api_'))) {
            const method = (config && config.method) ? config.method.toUpperCase() : 'GET';
            
            if (method === 'GET') {
                const separator = resource.includes('?') ? '&' : '?';
                resource += `${separator}_t=${new Date().getTime()}`;
                config = config || {};
                config.cache = 'no-store';
            }
        }
        
        return originalFetch.apply(this, [resource, config]);
    };
})();

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
    
    const currentParams = new URLSearchParams(window.location.search);
    if (currentParams.get('p') === p && push && !window.forceRefreshElite) return;
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

    fetch(newUrl)
    .then(r => r.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const nuevoHTML = doc.querySelector('.flex-fill.overflow-auto');
        const nuevoTitulo = doc.querySelector('.hero-module-title');
        
        if (nuevoHTML && mainContentArea) {
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

// 🛡️ MANDO ÚNICO DE NAVEGACIÓN TÁCTIL (Blindaje contra Ghost-Clicks)
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

// Inicialización de funciones globales
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

// 🛡️ SOBERANÍA: LIBERACIÓN REACTIVA DE MODALES (Aplica para cargas vía AJAX y dinámicas)
document.addEventListener('show.bs.modal', function (event) {
    if (event.target && event.target.classList && event.target.classList.contains('modal')) {
        if (event.target.parentNode !== document.body) {
            document.body.appendChild(event.target);
        }
        event.target.setAttribute('style', 'z-index: 1055;');
    }
}, true);

// Vincular al scope global
window.toggleDarkMode = toggleDarkMode;
window.toggleSidebarElite = toggleSidebarElite;
window.navegarModulo = navegarModulo;
window.actualizarSidebar = actualizarSidebar;
window.purgarIntentosAres = purgarIntentosAres;