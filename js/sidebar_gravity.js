/**
 * SIDEBAR_GRAVITY.JS - MOTOR DE GRAVEDAD ÉLITE v1.0
 * Detecta si los submenús deben abrirse hacia arriba o abajo.
 */

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebarMaestro');
    if (!sidebar) return;

    const items = sidebar.querySelectorAll('.menu-item-elite');

    items.forEach(item => {
        const submenu = item.querySelector('.submenu-elite');
        if (!submenu) return;

        item.addEventListener('mouseenter', function() {
            if (window.innerWidth <= 992) return;

            const rect = item.getBoundingClientRect();
            const subHeight = submenu.offsetHeight || submenu.scrollHeight;
            const viewportH = window.innerHeight;

            // 1. CÁLCULO DE CENTRADO IDEAL (Relativo al ítem)
            let desiredTop = (rect.height / 2) - (subHeight / 2);
            
            // 2. BLINDAJE DE TECHO (Clamping Superior)
            let absoluteTop = rect.top + desiredTop;
            if (absoluteTop < 15) {
                desiredTop = -rect.top + 15;
            }

            // 3. BLINDAJE DE SUELO (Clamping Inferior)
            absoluteTop = rect.top + desiredTop;
            if (absoluteTop + subHeight > viewportH - 15) {
                desiredTop = (viewportH - 15 - subHeight) - rect.top;
            }

            // 4. INYECCIÓN DE POSICIÓN SOBERANA (Vía Variable CSS Élite)
            submenu.style.setProperty('--el-submenu-offset-y', `${desiredTop}px`);
        });

        // Limpieza al salir
        item.addEventListener('mouseleave', function() {
            submenu.style.removeProperty('--el-submenu-offset-y');
        });
    });
});
