/**
 * ARES SELECT ENGINE v1.0
 * Protocolo de Unificación Híbrida - Vitrina 06
 * Convierte selectores nativos en componentes Premium en Escritorio.
 * Preserva la rueda nativa del SO en dispositivos móviles (Táctiles).
 */

const AresSelectEngine = {
    isDefinitiveCacheBusted: true,
    initialized: false,

    init() {
        if (this.initialized) return;
        this.initialized = true;

        // Detectar si es dispositivo táctil o pantalla pequeña (excluyendo coarse pointer de escritorio)
        const isMobile = window.matchMedia("(max-width: 991px)").matches || 
                         /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        // Si es móvil, ABORTAR MOTOR (La soberanía nativa domina por usabilidad)
        if (isMobile) {
            return;
        }

        this.scanAndTransform();

        // Mutuation Observer para detectar nuevos selects cargados por AJAX (ej. Modales)
        const observer = new MutationObserver((mutations) => {
            let shouldScan = false;
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) { // ELEMENT_NODE
                            if (node.matches && (node.matches('select'))) {
                                shouldScan = true;
                            } else if (node.querySelectorAll) {
                                if (node.querySelectorAll('select').length > 0) {
                                    shouldScan = true;
                                }
                            }
                        }
                    });
                }
            });
            if (shouldScan) this.scanAndTransform();
        });

        observer.observe(document.body, { childList: true, subtree: true });
    },

    scanAndTransform() {
        const selects = document.querySelectorAll('select:not([data-ares-initialized]):not(.swal2-select):not([data-ares-ignore="true"])');
        selects.forEach(select => {
            if (select.closest('.swal2-container') || select.closest('.swal2-popup')) {
                return;
            }
            this.transform(select);
        });
    },

    transform(select) {
        select.dataset.aresInitialized = 'true';
        
        // 1. Ocultar el select nativo, pero preservando su lógica de foco
        select.style.setProperty('display', 'none', 'important');
        select.style.setProperty('position', 'absolute', 'important');
        select.style.setProperty('width', '1px', 'important');
        select.style.setProperty('height', '1px', 'important');
        select.style.setProperty('padding', '0', 'important');
        select.style.setProperty('margin', '-1px', 'important');
        select.style.setProperty('overflow', 'hidden', 'important');
        select.style.setProperty('clip', 'rect(0, 0, 0, 0)', 'important');
        select.style.setProperty('border', '0', 'important');
        select.style.setProperty('opacity', '0', 'important');
        select.style.setProperty('visibility', 'hidden', 'important');
        select.style.setProperty('pointer-events', 'none', 'important');
        // 2. Envoltura
        const wrapper = document.createElement('div');
        wrapper.className = 'dropdown ares-select-wrapper position-relative';
        
        // Transferir clases de ancho (w-*) del select al wrapper
        Array.from(select.classList).forEach(cls => {
            if (cls.startsWith('w-')) {
                wrapper.classList.add(cls);
                select.classList.remove(cls); // Remover del original para evitar conflictos
            }
        });
        
        // Si no tenía ninguna clase de ancho, por defecto 100%
        if (!Array.from(wrapper.classList).some(cls => cls.startsWith('w-'))) {
            wrapper.classList.add('w-100');
        }

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        // 3. Botón Visual (El nuevo select falso)
        const btn = document.createElement('button');
        btn.type = 'button';
        
        // Mapeo perfecto de clases
        let hasEliteClass = false;
        const newClasses = Array.from(select.classList).map(cls => {
            if (cls === 'select-elite' || cls === 'form-select') {
                hasEliteClass = true;
                return 'select-elite-reborn';
            }
            if (cls === 'select-elite-sm') {
                hasEliteClass = true;
                return 'select-elite-reborn sm';
            }
            return cls;
        }).join(' ');
        
        btn.className = newClasses;
        if (!hasEliteClass) {
            btn.classList.add('select-elite-reborn');
        }
        // Añadir clases de flexbox premium para evitar que el ícono sea expulsado del botón
        btn.classList.add('dropdown-toggle', 'text-start', 'd-flex', 'justify-content-between', 'align-items-center', 'flex-nowrap');
        btn.dataset.bsToggle = 'dropdown';
        btn.dataset.bsDisplay = 'dynamic';
        btn.ariaExpanded = 'false';
        btn.disabled = select.disabled;

        // Remover clases de estilo del select nativo para evitar colisiones con el CSS de ui_kit.css
        select.classList.remove('select-elite', 'select-elite-sm', 'form-select');

        // Texto (Truncado) con min-width: 0 para que text-truncate funcione en flexbox
        const btnText = document.createElement('span');
        btnText.className = 'text-truncate pe-2 flex-grow-1 ares-select-text';
        this.updateButtonText(select, btnText);
        btn.appendChild(btnText);

        const btnIcon = document.createElement('i');
        btnIcon.className = 'bi bi-chevron-expand ms-1 opacity-50 flex-shrink-0';
        btn.appendChild(btnIcon);
        
        wrapper.appendChild(btn);

        // 4. Menú Desplegable Premium
        const menu = document.createElement('ul');
        menu.className = 'dropdown-menu dropdown-menu-elite shadow-lg animate__animated animate__fadeIn m-0';
        wrapper.appendChild(menu);

        // Llenar el menú la primera vez
        this.renderMenu(select, menu, btnText);



        // 5. Escuchar cambios en el Select Original (por si otro JS cambia su valor)
        select.addEventListener('change', (e) => {
            if (!e.detail || e.detail !== 'ares_trigger') {
                this.updateButtonText(select, btnText);
                this.syncActiveItem(select, menu);
            }
        });

        // 6. Observar cambios en los <option> y atributos (disabled) del select
        const optionsObserver = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'disabled') {
                    btn.disabled = select.disabled;
                } else {
                    this.renderMenu(select, menu, btnText);
                    this.updateButtonText(select, btnText);
                }
            });
        });
        optionsObserver.observe(select, { 
            childList: true, 
            characterData: true, 
            subtree: true,
            attributes: true,
            attributeFilter: ['disabled']
        });
    },

    updateButtonText(select, btnText) {
        const selectedOpt = select.options[select.selectedIndex];
        btnText.innerText = selectedOpt ? selectedOpt.text : 'Seleccione...';
    },

    syncActiveItem(select, menu) {
        const items = menu.querySelectorAll('.dropdown-item');
        items.forEach((item, idx) => {
            if (idx === select.selectedIndex) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    },

    renderMenu(select, menu, btnText) {
        menu.innerHTML = '';
        const options = Array.from(select.options);
        if (options.length === 0) {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.className = 'dropdown-item disabled text-muted';
            a.href = 'javascript:void(0)';
            a.innerText = 'Sin opciones';
            li.appendChild(a);
            menu.appendChild(li);
            return;
        }
        options.forEach((opt, idx) => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.className = 'dropdown-item';
            
            if (opt.disabled) a.classList.add('disabled', 'text-muted');
            if (opt.selected) a.classList.add('active');
            
            a.href = 'javascript:void(0)';
            a.innerText = opt.text;
            
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (opt.disabled) return;
                
                // Actualizar select original
                select.selectedIndex = idx;
                this.updateButtonText(select, btnText);
                this.syncActiveItem(select, menu);
                
                // Disparar evento Change para que los onchange nativos de PHP reaccionen
                const event = new CustomEvent('change', { bubbles: true, detail: 'ares_trigger' });
                select.dispatchEvent(event);
            });
            
            li.appendChild(a);
            menu.appendChild(li);
        });
    }
};

// Iniciar al cargar el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => AresSelectEngine.init());
} else {
    AresSelectEngine.init();
}
