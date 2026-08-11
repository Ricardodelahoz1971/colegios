/**
 * ARES CANVAS ENGINE - NÚCLEO FÍSICO Y LÓGICO v2.0
 * Desarrollo en Sandbox Aislado - Estándar Élite
 * Arquitectura: 100% Vanilla JS (Cero dependencias)
 */

function initCanvasEngine() {
    const board = document.getElementById('ares-canvas-board');
    if (!board) return;
    if (board.dataset.engineInitialized === 'true') return;
    board.dataset.engineInitialized = 'true';
    const scrollArea = board.closest('.canvas-scroll-area');
    const tools = document.querySelectorAll('.tool-draggable');
    
    function getNodeType(node) {
        if (!node) return '';
        if (node.classList.contains('type-text')) return 'text';
        if (node.classList.contains('type-image')) return 'image';
        if (node.classList.contains('type-choice')) return 'choice';
        if (node.classList.contains('type-hotspot')) return 'hotspot';
        if (node.classList.contains('type-dropzone')) return 'dropzone';
        if (node.classList.contains('type-draggable')) return 'draggable';
        if (node.classList.contains('type-seq_block')) return 'seq_block';
        if (node.classList.contains('type-seq_slot')) return 'seq_slot';
        if (node.classList.contains('type-formula')) return 'formula';
        return '';
    }

    // Controles globales
    const btnGridToggle = document.getElementById('btn-grid-toggle');
    const btnSnapToggle = document.getElementById('btn-snap-toggle');
    const btnClearCanvas = document.getElementById('btn-clear-canvas');
    const btnModalCanvasSave = document.getElementById('btn-modal-canvas-save');
    const btnModalCanvasCancel = document.getElementById('btn-modal-canvas-cancel');
    const btnPreviewJson = document.getElementById('btn-preview-json');
    
    // Inspector de Elementos
    const inspectorDefault = document.getElementById('inspector-default-message');
    const inspectorForm = document.getElementById('inspector-form-container');
    const inpNodeId = document.getElementById('inp-node-id');
    const inpNodeType = document.getElementById('inp-node-type');
    const inpNodeX = document.getElementById('inp-node-x');
    const inpNodeY = document.getElementById('inp-node-y');
    const inpNodeW = document.getElementById('inp-node-w');
    const inpNodeH = document.getElementById('inp-node-h');
    const btnNodeFront = document.getElementById('btn-node-front');
    const btnNodeBack = document.getElementById('btn-node-back');
    const btnNodeLock = document.getElementById('btn-node-lock');
    const dynamicFields = document.getElementById('inspector-dynamic-fields');
    
    const svgConnections = document.getElementById('canvas-connections-svg');

    // Drawer del Inspector (Despliegue Lateral)
    const inspectorPanel = document.getElementById('canvas-inspector');
    const btnInspectorToggle = document.getElementById('btn-inspector-toggle');
    const inspectorTabIcon = document.getElementById('inspector-tab-icon');

    btnInspectorToggle.addEventListener('click', () => {
        const isOpen = inspectorPanel.classList.toggle('canvas-inspector--open');
        if (isOpen) {
            inspectorTabIcon.className = 'bi bi-chevron-right';
        } else {
            inspectorTabIcon.className = 'bi bi-chevron-left';
        }
    });

    // Configuración inicial del motor
    let gridSize = 20;
    let snapActive = true;
    let gridActive = true;
    
    let isDragging = false;
    let isResizing = false;
    let draggedNode = null;
    let selectedNode = null;
    
    // Coordenadas físicas
    let startX, startY;
    let initialLeft, initialTop;
    let initialWidth, initialHeight;
    let nodeCounter = 0;
    let groupCounter = 0;

    // Historial de Deshacer (Undo Stack)
    const undoStack = [];

    function saveState() {
        const state = compileCanvasJSON();
        // Evitar duplicados en el historial
        if (undoStack.length === 0 || undoStack[undoStack.length - 1] !== state) {
            undoStack.push(state);
            if (undoStack.length > 30) {
                undoStack.shift();
            }
        }
    }

    function trackInputUndo(input) {
        if (!input) return;
        input.addEventListener('focus', () => {
            saveState();
        });
    }

    // Inicializar malla visual activa
    scrollArea.classList.add('grid-active');

    // ==========================================
    // 1. GESTIÓN DEL LIENZO Y BOTONES GLOBALES
    // ==========================================
    
    btnGridToggle.addEventListener('click', () => {
        gridActive = !gridActive;
        if (gridActive) {
            scrollArea.classList.add('grid-active');
            scrollArea.classList.remove('grid-hidden');
            btnGridToggle.classList.add('active');
        } else {
            scrollArea.classList.remove('grid-active');
            scrollArea.classList.add('grid-hidden');
            btnGridToggle.classList.remove('active');
        }
    });

    btnSnapToggle.addEventListener('click', () => {
        snapActive = !snapActive;
        if (snapActive) {
            btnSnapToggle.classList.add('active');
        } else {
            btnSnapToggle.classList.remove('active');
        }
    });

    btnClearCanvas.addEventListener('click', async () => {
        const result = await Swal.fire({
            title: '¿Limpiar el lienzo?',
            text: 'Esta acción eliminará todos los elementos actuales del área de trabajo.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--el-primary)',
            cancelButtonColor: 'var(--el-text-muted)',
            confirmButtonText: 'Sí, limpiar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            saveState();
            // Eliminar todos los nodos excepto el SVG de conexiones
            const nodes = board.querySelectorAll('.canvas-node');
            nodes.forEach(node => node.remove());
            deselectNode();
            updateConnections();
            
            Swal.fire({
                title: 'Lienzo Limpio',
                text: 'El lienzo se ha restablecido con éxito.',
                icon: 'success',
                confirmButtonColor: 'var(--el-primary)'
            });
        }
    });

    // Escuchar atajo Control + Z para Deshacer
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
            const active = document.activeElement;
            if (active && (
                active.tagName === 'INPUT' || 
                active.tagName === 'TEXTAREA' || 
                active.isContentEditable
            )) {
                // Dejar que el navegador maneje el deshacer de texto nativo en inputs
                return;
            }

            e.preventDefault();
            if (undoStack.length > 0) {
                const previousState = undoStack.pop();
                if (window.initCanvasEditor) {
                    window.initCanvasEditor(previousState);
                }
            }
        }
    });

    const selectCanvasMaqueta = document.getElementById('select-canvas-maqueta');
    if (selectCanvasMaqueta) {
        selectCanvasMaqueta.addEventListener('change', async () => {
            const val = selectCanvasMaqueta.value;
            if (!val) return;

            // Si hay elementos, advertir de pérdida de datos
            const existingNodes = board.querySelectorAll('.canvas-node');
            if (existingNodes.length > 0) {
                const result = await Swal.fire({
                    title: '¿Cargar maqueta?',
                    text: 'Se eliminarán todos los elementos actuales del área de trabajo para cargar la maqueta seleccionada.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--el-primary)',
                    cancelButtonColor: 'var(--el-text-muted)',
                    confirmButtonText: 'Sí, cargar maqueta',
                    cancelButtonText: 'Cancelar'
                });

                if (!result.isConfirmed) {
                    selectCanvasMaqueta.value = '';
                    return;
                }
            }

            saveState();
            // Limpiar lienzo
            const nodes = board.querySelectorAll('.canvas-node');
            nodes.forEach(node => node.remove());
            deselectNode();

            // Cargar maqueta de forma genérica en la esquina superior izquierda (margen x=40, y=30)
            instantiateTemplate(val, 40, 30);

            // Actualizar conexiones e de-seleccionar
            deselectNode(true);
            updateConnections();
            selectCanvasMaqueta.value = '';

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Maqueta cargada con éxito',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        });
    }

    // ==========================================
    // 2. ARRASTRE DESDE LA BARRA DE HERRAMIENTAS
    // ==========================================
    
    tools.forEach(tool => {
        tool.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', tool.dataset.type);
            e.dataTransfer.effectAllowed = 'copy';
        });
    });

    board.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });

    board.addEventListener('drop', (e) => {
        e.preventDefault();
        const type = e.dataTransfer.getData('text/plain');
        if (!type) return;

        saveState();

        const rect = board.getBoundingClientRect();
        let x = e.clientX - rect.left;
        let y = e.clientY - rect.top;

        if (snapActive) {
            x = Math.round(x / gridSize) * gridSize;
            y = Math.round(y / gridSize) * gridSize;
        }

        // Si es una maqueta (template), la instanciamos con lógica grupal
        if (type.startsWith('template-')) {
            instantiateTemplate(type, x, y);
        } else {
            const createdNode = createCanvasNode(type, x, y);
            if (type === 'formula') {
                setTimeout(() => {
                    if (window.abrirModalFormulaPremium) {
                        window.abrirModalFormulaPremium('', createdNode);
                    }
                }, 100);
            }
        }
    });

    // ==========================================
    // 3. FACTORY Y CONSTRUCTOR DE NODOS
    // ==========================================
    
    function createCanvasNode(type, x, y, width = null, height = null, customId = null, group = null) {
        nodeCounter++;
        const node = document.createElement('div');
        node.className = `canvas-node type-${type}`;
        node.id = customId || `node_${nodeCounter}`;
        node.style.left = `${x}px`;
        node.style.top = `${y}px`;
        
        if (group) {
            node.dataset.group = group;
        }

        let innerContent = '';
        if (type === 'text') {
            const wVal = width || 220;
            const hVal = height || 100;
            node.style.width = `${wVal}px`;
            node.style.height = `${hVal}px`;
            innerContent = `<div class="canvas-node-content" contenteditable="true" spellcheck="false">Bloque de texto explicativo...</div>`;
        } else if (type === 'image') {
            const wVal = width || 200;
            const hVal = height || 160;
            node.style.width = `${wVal}px`;
            node.style.height = `${hVal}px`;
            // Imagen mock predefinida para simular circuito (Ruta corregida al directorio raíz relativo)
            innerContent = `<div class="canvas-node-content"><img src="../assets/modules/ares/default_placeholder.svg" alt="Diagrama"></div>`;
        } else if (type === 'choice') {
            const wVal = width || 44;
            const hVal = height || 44;
            node.style.width = `${wVal}px`;
            node.style.height = `${hVal}px`;
            node.style.minWidth = `${wVal}px`;
            node.style.minHeight = `${hVal}px`;
            
            const existingChoicesCount = board.querySelectorAll('.canvas-node.type-choice').length;
            const autoLabel = String.fromCharCode(65 + (existingChoicesCount % 26));
            
            node.dataset.label = autoLabel;
            node.dataset.value = `Opción ${autoLabel}`;
            innerContent = autoLabel;
        } else if (type === 'hotspot') {
            const wVal = width || 140;
            const hVal = height || 80;
            node.style.width = `${wVal}px`;
            node.style.height = `${hVal}px`;
            node.dataset.color = 'blue';
            innerContent = `<div class="canvas-node-content">ZONA CALIENTE</div>`;
        } else if (type === 'dropzone') {
            const wVal = width || 120;
            const hVal = height || 44;
            node.style.width = `${wVal}px`;
            node.style.height = `${hVal}px`;
            node.dataset.label = 'Hueco 1';
            node.dataset.valueCorrect = '';
            node.dataset.group = '';
            innerContent = `<div class="canvas-node-content">HUECO 1</div>`;
        } else if (type === 'draggable') {
            const wVal = width || 120;
            const hVal = height || 44;
            node.style.width = `${wVal}px`;
            node.style.height = `${hVal}px`;
            node.dataset.label = 'Etiqueta';
            innerContent = `<div class="canvas-node-content" contenteditable="true" spellcheck="false">Etiqueta</div>`;
        } else if (type === 'seq_block') {
            const wVal = width || 140;
            const hVal = height || 50;
            node.style.width = `${wVal}px`;
            node.style.height = `${hVal}px`;
            node.dataset.label = 'Paso';
            innerContent = `<div class="canvas-node-content" contenteditable="true" spellcheck="false">Evento o Paso...</div>`;
        } else if (type === 'seq_slot') {
            const wVal = width || 60;
            const hVal = height || 60;
            node.style.width = `${wVal}px`;
            node.style.height = `${hVal}px`;
            node.dataset.valueCorrect = '1';
            innerContent = `<div class="canvas-node-content">1</div>`;
        } else if (type === 'formula') {
            const wVal = width || 220;
            const hVal = height || 80;
            node.style.width = `${wVal}px`;
            node.style.height = `${hVal}px`;
            node.dataset.formula = '';
            innerContent = `<div class="canvas-node-content"><span class="canvas-formula-placeholder">Doble clic para definir fórmula</span></div>`;
        }

        node.innerHTML = innerContent;
        board.appendChild(node);

        // Registro de manejadores de foco/desenfoque para almacenar el estado del texto editable
        if (type === 'text' || type === 'draggable') {
            const contentDiv = node.querySelector('.canvas-node-content');
            if (contentDiv) {
                contentDiv.addEventListener('focus', () => {
                    saveState();
                });
                contentDiv.addEventListener('blur', () => {
                    saveState();
                });
            }
        }

        setupNodePhysics(node);
        setupNodeSelection(node);
        
        // Al instanciarse, seleccionarlo automáticamente
        selectNode(node);
        updateConnections();

        return node;
    }

    // ==========================================
    // 4. INSTANCIACIÓN DE MAQUETAS (TEMPLATES)
    // ==========================================
    
    function instantiateTemplate(type, x, y) {
        groupCounter++;
        const groupId = `maqueta_${groupCounter}`;

        if (type === 'template-choice') {
            const textNode = createCanvasNode('text', x, y, 700, 44, `txt_q_${groupCounter}`, groupId);
            textNode.querySelector('.canvas-node-content').innerHTML = 'Haga doble clic aquí para escribir el enunciado de la pregunta...';

            createCanvasNode('image', x, y + 60, 420, 320, `img_q_${groupCounter}`, groupId);

            const opts = ['A', 'B', 'C', 'D'];
            opts.forEach((opt, idx) => {
                const yPos = y + 60 + (idx * 80);
                const choiceNode = createCanvasNode('choice', x + 440, yPos, 44, 44, `ch_q_${groupCounter}_${opt}`, groupId);
                choiceNode.dataset.label = opt;
                choiceNode.dataset.value = `Opción ${opt}`;
                choiceNode.innerHTML = opt;

                const descNode = createCanvasNode('text', x + 494, yPos, 206, 44, `txt_q_${groupCounter}_desc_${opt}`, groupId);
                descNode.querySelector('.canvas-node-content').innerHTML = `Texto descriptivo de la opción ${opt}...`;
            });
            
            selectNode(textNode);
        } else if (type === 'template-match') {
            const matchTitle = createCanvasNode('text', x, y, 580, 44, `txt_m_${groupCounter}`, groupId);
            matchTitle.querySelector('.canvas-node-content').innerHTML = 'Maqueta de Apareamiento. Conecte los conceptos.';

            const cLeft1 = createCanvasNode('text', x, y + 60, 200, 44, `txt_ml_1_${groupCounter}`, `${groupId}_match_1`);
            cLeft1.querySelector('.canvas-node-content').innerHTML = 'Concepto Clave 1';
            const slot1 = createCanvasNode('choice', x + 220, y + 60, 44, 44, `ch_ml_1_${groupCounter}`, `${groupId}_match_1`);
            slot1.dataset.label = '1';
            slot1.innerHTML = '1';
            const cRight1 = createCanvasNode('text', x + 380, y + 60, 200, 44, `txt_mr_1_${groupCounter}`, `${groupId}_match_1`);
            cRight1.querySelector('.canvas-node-content').innerHTML = 'Respuesta Asociada 1';

            const cLeft2 = createCanvasNode('text', x, y + 120, 200, 44, `txt_ml_2_${groupCounter}`, `${groupId}_match_2`);
            cLeft2.querySelector('.canvas-node-content').innerHTML = 'Concepto Clave 2';
            const slot2 = createCanvasNode('choice', x + 220, y + 120, 44, 44, `ch_ml_2_${groupCounter}`, `${groupId}_match_2`);
            slot2.dataset.label = '2';
            slot2.innerHTML = '2';
            const cRight2 = createCanvasNode('text', x + 380, y + 120, 200, 44, `txt_mr_2_${groupCounter}`, `${groupId}_match_2`);
            cRight2.querySelector('.canvas-node-content').innerHTML = 'Respuesta Asociada 2';
            
            selectNode(matchTitle);
        } else if (type === 'template-hotspot') {
            const titleNode = createCanvasNode('text', x, y, 420, 50, `txt_h_title_${groupCounter}`, groupId);
            titleNode.querySelector('.canvas-node-content').innerHTML = 'Haga clic sobre el órgano indicado...';

            createCanvasNode('image', x, y + 60, 420, 320, `img_h_${groupCounter}`, groupId);

            const hs1 = createCanvasNode('hotspot', x + 50, y + 110, 100, 80, `hs_h_1_${groupCounter}`, groupId);
            hs1.dataset.color = 'blue';
            hs1.querySelector('.canvas-node-content').innerHTML = 'CEREBRO';

            const hs2 = createCanvasNode('hotspot', x + 240, y + 210, 100, 80, `hs_h_2_${groupCounter}`, groupId);
            hs2.dataset.color = 'blue';
            hs2.querySelector('.canvas-node-content').innerHTML = 'CORAZÓN';

            selectNode(titleNode);
        } else if (type === 'template-dragdrop') {
            const instructNode = createCanvasNode('text', x, y, 500, 60, `txt_dd_title_${groupCounter}`, groupId);
            instructNode.querySelector('.canvas-node-content').innerHTML = 'Complete la frase arrastrando la respuesta correcta:<br>La célula es la unidad _____(1)_____ y _____(2)_____ de la vida.';

            const dz1 = createCanvasNode('dropzone', x + 50, y + 80, 120, 44, `dz_dd_1_${groupCounter}`, groupId);
            dz1.dataset.label = 'Hueco 1';
            dz1.dataset.valueCorrect = `drag_lbl_estructural_${groupCounter}`;
            dz1.querySelector('.canvas-node-content').innerHTML = 'HUECO 1';

            const dz2 = createCanvasNode('dropzone', x + 200, y + 80, 120, 44, `dz_dd_2_${groupCounter}`, groupId);
            dz2.dataset.label = 'Hueco 2';
            dz2.dataset.valueCorrect = `drag_lbl_funcional_${groupCounter}`;
            dz2.querySelector('.canvas-node-content').innerHTML = 'HUECO 2';

            const drag1 = createCanvasNode('draggable', x + 50, y + 150, 120, 44, `drag_lbl_estructural_${groupCounter}`, groupId);
            drag1.querySelector('.canvas-node-content').innerHTML = 'Estructural';

            const drag2 = createCanvasNode('draggable', x + 190, y + 150, 120, 44, `drag_lbl_funcional_${groupCounter}`, groupId);
            drag2.querySelector('.canvas-node-content').innerHTML = 'Funcional';

            const drag3 = createCanvasNode('draggable', x + 330, y + 150, 120, 44, `drag_lbl_incorrecto_${groupCounter}`, groupId);
            drag3.querySelector('.canvas-node-content').innerHTML = 'Química';

            selectNode(instructNode);
        } else if (type === 'template-sequence') {
            const instructNode = createCanvasNode('text', x, y, 600, 60, `txt_seq_title_${groupCounter}`, groupId);
            instructNode.querySelector('.canvas-node-content').innerHTML = 'Ordene cronológicamente los siguientes eventos arrastrando los bloques a las ranuras:';

            for(let i=1; i<=4; i++) {
                const slot = createCanvasNode('seq_slot', x + (i-1)*100, y + 80, 60, 60, `slot_seq_${i}_${groupCounter}`, groupId);
                slot.dataset.valueCorrect = i;
                slot.querySelector('.canvas-node-content').innerHTML = i;
                
                const block = createCanvasNode('seq_block', x + (i-1)*160, y + 180, 140, 50, `block_seq_${i}_${groupCounter}`, groupId);
                block.querySelector('.canvas-node-content').innerHTML = `Evento ${i}`;
            }

            selectNode(instructNode);
        }

        updateConnections();
    }

    // ==========================================
    // 5. MOTOR FÍSICO: POINTER EVENTS (ARRUSTRE)
    // ==========================================
    
    function setupNodePhysics(node) {
        node.addEventListener('pointerdown', (e) => {
            // Ignorar clic si está en contenteditable activo
            if (e.target.closest('[contenteditable="true"]') && document.activeElement === e.target) return;
            // Ignorar si hace clic en tirador o microacciones
            if (e.target.closest('.canvas-node__resizer') || e.target.closest('.canvas-node__actions')) return;
            // Ignorar si está bloqueado
            if (node.classList.contains('canvas-node--locked')) return;

            // Liberar foco de cualquier control editable para reactivar el atajo global Ctrl+Z
            if (document.activeElement && document.activeElement !== document.body) {
                document.activeElement.blur();
            }

            saveState();
            isDragging = true;
            draggedNode = node;
            selectNode(node);

            startX = e.clientX;
            startY = e.clientY;

            initialLeft = parseInt(node.style.left || 0);
            initialTop = parseInt(node.style.top || 0);

            node.setPointerCapture(e.pointerId);
            e.stopPropagation();
        });

        node.addEventListener('pointermove', (e) => {
            if (!isDragging || draggedNode !== node) return;

            const dx = e.clientX - startX;
            const dy = e.clientY - startY;

            let newX = initialLeft + dx;
            let newY = initialTop + dy;

            // Limitar al lienzo superior/izquierdo
            if (newX < 0) newX = 0;
            if (newY < 0) newY = 0;

            if (snapActive) {
                newX = Math.round(newX / gridSize) * gridSize;
                newY = Math.round(newY / gridSize) * gridSize;
            }

            node.style.left = `${newX}px`;
            node.style.top = `${newY}px`;

            // Actualizar inspector en vivo
            inpNodeX.value = newX;
            inpNodeY.value = newY;

            updateConnections();
        });

        node.addEventListener('pointerup', (e) => {
            if (!isDragging || draggedNode !== node) return;

            isDragging = false;
            node.releasePointerCapture(e.pointerId);
            draggedNode = null;

            updateConnections();
        });
    }

    // ==========================================
    // 6. GESTIÓN DE SELECCIÓN E INSPECCIÓN AERO
    // ==========================================
    
    function setupNodeSelection(node) {
        node.addEventListener('click', (e) => {
            selectNode(node);
            e.stopPropagation();
        });
    }

    // Deseleccionar al hacer clic en el lienzo vacío
    board.addEventListener('click', (e) => {
        if (e.target === board || e.target === svgConnections || e.target.classList.contains('canvas-scroll-area')) {
            deselectNode();
        }
    });

    // Interceptar doble clic en fórmulas dentro del lienzo para editarlas
    board.addEventListener('dblclick', (e) => {
        const formulaNode = e.target.closest('.canvas-node.type-formula');
        if (formulaNode) {
            e.preventDefault();
            e.stopPropagation();
            const currentFormula = formulaNode.getAttribute('data-formula') || '';
            if (window.abrirModalFormulaPremium) {
                window.abrirModalFormulaPremium(currentFormula, formulaNode);
            }
            return;
        }

        const formulaEl = e.target.closest('.canvas-math-formula');
        if (formulaEl) {
            e.preventDefault();
            e.stopPropagation();
            const currentFormula = formulaEl.getAttribute('data-formula') || '';
            if (window.abrirModalFormulaPremium) {
                window.abrirModalFormulaPremium(currentFormula, formulaEl);
            }
        }
    });

    function selectNode(node) {
        if (selectedNode) {
            deselectNode(false);
        }

        selectedNode = node;
        node.classList.add('canvas-node--selected');

        // Construir barra de acciones flotantes
        createFloatingActions(node);

        // Construir tirador de redimensión si no está bloqueado
        if (!node.classList.contains('canvas-node--locked') && !node.classList.contains('type-choice')) {
            createResizer(node);
        }

        // Mostrar Inspector
        inspectorDefault.classList.add('d-none');
        inspectorForm.classList.remove('d-none');

        // Auto-desplegar panel lateral al seleccionar elemento
        inspectorPanel.classList.add('canvas-inspector--open');
        inspectorTabIcon.className = 'bi bi-chevron-right';

        // Llenar campos base
        inpNodeId.value = node.id;
        inpNodeType.value = getNodeType(node);
        inpNodeX.value = parseInt(node.style.left || 0);
        inpNodeY.value = parseInt(node.style.top || 0);
        inpNodeW.value = node.offsetWidth;
        inpNodeH.value = node.offsetHeight;

        // Actualizar botón de bloqueo
        if (node.classList.contains('canvas-node--locked')) {
            btnNodeLock.innerHTML = `<i class="bi bi-lock-fill"></i> Bloqueado`;
            btnNodeLock.classList.add('locked');
        } else {
            btnNodeLock.innerHTML = `<i class="bi bi-unlock"></i> Desbloqueado`;
            btnNodeLock.classList.remove('locked');
        }

        // Llenar campos específicos de tipo
        renderDynamicInspectorFields(node);
    }

    function deselectNode(hideInspector = true) {
        if (selectedNode) {
            selectedNode.classList.remove('canvas-node--selected');
            
            // Eliminar tirador y barra de acciones flotantes
            const resizer = selectedNode.querySelector('.canvas-node__resizer');
            if (resizer) resizer.remove();
            
            const actions = selectedNode.querySelector('.canvas-node__actions');
            if (actions) actions.remove();
        }

        selectedNode = null;

        if (hideInspector) {
            inspectorDefault.classList.remove('d-none');
            inspectorForm.classList.add('d-none');
            
            // Cerrar automáticamente panel lateral al deseleccionar
            inspectorPanel.classList.remove('canvas-inspector--open');
            inspectorTabIcon.className = 'bi bi-chevron-left';
        }
    }

    // ==========================================
    // 7. CONSTRUCTOR DE TIRADORES Y ACCIONES
    // ==========================================
    
    function createResizer(node) {
        if (node.querySelector('.canvas-node__resizer')) return;

        const resizer = document.createElement('div');
        resizer.className = 'canvas-node__resizer';
        node.appendChild(resizer);

        resizer.addEventListener('pointerdown', (e) => {
            // Liberar foco de cualquier control editable para reactivar el atajo global Ctrl+Z
            if (document.activeElement && document.activeElement !== document.body) {
                document.activeElement.blur();
            }

            saveState();
            isResizing = true;
            startX = e.clientX;
            startY = e.clientY;
            initialWidth = node.offsetWidth;
            initialHeight = node.offsetHeight;

            resizer.setPointerCapture(e.pointerId);
            e.stopPropagation();
        });

        resizer.addEventListener('pointermove', (e) => {
            if (!isResizing) return;

            const dx = e.clientX - startX;
            const dy = e.clientY - startY;

            let newWidth = initialWidth + dx;
            let newHeight = initialHeight + dy;

            // Límites mínimos
            if (newWidth < 60) newWidth = 60;
            if (newHeight < 40) newHeight = 40;

            if (snapActive) {
                newWidth = Math.round(newWidth / gridSize) * gridSize;
                newHeight = Math.round(newHeight / gridSize) * gridSize;
            }

            node.style.width = `${newWidth}px`;
            node.style.height = `${newHeight}px`;

            inpNodeW.value = newWidth;
            inpNodeH.value = newHeight;

            updateConnections();
        });

        resizer.addEventListener('pointerup', (e) => {
            if (!isResizing) return;
            isResizing = false;
            resizer.releasePointerCapture(e.pointerId);
            updateConnections();
        });
    }

    function createFloatingActions(node) {
        if (node.querySelector('.canvas-node__actions')) return;

        const actions = document.createElement('div');
        actions.className = 'canvas-node__actions';

        // Si es nodo de texto, agregar controles de formato enriquecido
        if (node.classList.contains('type-text')) {
            // Botón Negrita
            const btnBold = document.createElement('button');
            btnBold.className = 'btn-node-action';
            btnBold.dataset.tooltip = 'Negrita';
            btnBold.innerHTML = `<i class="bi bi-type-bold"></i>`;
            btnBold.addEventListener('pointerdown', (e) => {
                e.preventDefault();
                document.execCommand('bold', false, null);
            });

            // Botón Cursiva
            const btnItalic = document.createElement('button');
            btnItalic.className = 'btn-node-action';
            btnItalic.dataset.tooltip = 'Cursiva';
            btnItalic.innerHTML = `<i class="bi bi-type-italic"></i>`;
            btnItalic.addEventListener('pointerdown', (e) => {
                e.preventDefault();
                document.execCommand('italic', false, null);
            });

            // Botón Subrayado
            const btnUnderline = document.createElement('button');
            btnUnderline.className = 'btn-node-action';
            btnUnderline.dataset.tooltip = 'Subrayado';
            btnUnderline.innerHTML = `<i class="bi bi-type-underline"></i>`;
            btnUnderline.addEventListener('pointerdown', (e) => {
                e.preventDefault();
                document.execCommand('underline', false, null);
            });

            actions.appendChild(btnBold);
            actions.appendChild(btnItalic);
            actions.appendChild(btnUnderline);
        }

        // Botón Duplicar
        const btnDup = document.createElement('button');
        btnDup.className = 'btn-node-action';
        btnDup.dataset.tooltip = 'Duplicar elemento';
        btnDup.innerHTML = `<i class="bi bi-copy"></i>`;
        btnDup.addEventListener('click', (e) => {
            e.stopPropagation();
            duplicateNode(node);
        });

        // Botón Borrar
        const btnDel = document.createElement('button');
        btnDel.className = 'btn-node-action btn-node-action--danger';
        btnDel.dataset.tooltip = 'Eliminar elemento';
        btnDel.innerHTML = `<i class="bi bi-trash"></i>`;
        btnDel.addEventListener('click', (e) => {
            e.stopPropagation();
            deleteNode(node);
        });

        actions.appendChild(btnDup);
        actions.appendChild(btnDel);
        node.appendChild(actions);
    }

    function duplicateNode(node) {
        saveState();
        const type = getNodeType(node);
        const x = parseInt(node.style.left) + gridSize;
        const y = parseInt(node.style.top) + gridSize;
        const w = node.offsetWidth;
        const h = node.offsetHeight;
        
        const dup = createCanvasNode(type, x, y, w, h);
        
        // Copiar contenido
        if (type === 'text') {
            dup.querySelector('.canvas-node-content').innerHTML = node.querySelector('.canvas-node-content').innerHTML;
        } else if (type === 'image') {
            dup.querySelector('.canvas-node-content').innerHTML = node.querySelector('.canvas-node-content').innerHTML;
        } else if (type === 'choice') {
            dup.dataset.label = node.dataset.label;
            dup.dataset.value = node.dataset.value;
            dup.innerHTML = node.dataset.label;
        } else if (type === 'hotspot') {
            dup.dataset.color = node.dataset.color;
            dup.querySelector('.canvas-node-content').innerHTML = node.querySelector('.canvas-node-content').innerHTML;
        }
        
        if (node.dataset.group) {
            dup.dataset.group = node.dataset.group;
        }

        selectNode(dup);
        updateConnections();
    }

    function deleteNode(node) {
        saveState();
        node.remove();
        deselectNode();
        updateConnections();
    }

    // ==========================================
    // 8. INTERACCIÓN DE ENTRADAS DEL INSPECTOR
    // ==========================================
    
    inpNodeX.addEventListener('input', () => {
        if (!selectedNode) return;
        let val = parseInt(inpNodeX.value || 0);
        if (val < 0) val = 0;
        selectedNode.style.left = `${val}px`;
        updateConnections();
    });

    inpNodeY.addEventListener('input', () => {
        if (!selectedNode) return;
        let val = parseInt(inpNodeY.value || 0);
        if (val < 0) val = 0;
        selectedNode.style.top = `${val}px`;
        updateConnections();
    });

    inpNodeW.addEventListener('input', () => {
        if (!selectedNode) return;
        let val = parseInt(inpNodeW.value || 40);
        selectedNode.style.width = `${val}px`;
        if (selectedNode.classList.contains('type-choice')) {
            selectedNode.style.minWidth = `${val}px`;
        }
        updateConnections();
    });

    inpNodeH.addEventListener('input', () => {
        if (!selectedNode) return;
        let val = parseInt(inpNodeH.value || 40);
        selectedNode.style.height = `${val}px`;
        if (selectedNode.classList.contains('type-choice')) {
            selectedNode.style.minHeight = `${val}px`;
        }
        updateConnections();
    });

    // Cambiar z-index
    btnNodeFront.addEventListener('click', () => {
        if (!selectedNode) return;
        saveState();
        // z-index por defecto es 2. Frente es 40.
        selectedNode.style.zIndex = '40';
    });

    btnNodeBack.addEventListener('click', () => {
        if (!selectedNode) return;
        saveState();
        selectedNode.style.zIndex = '2';
    });

    // Bloquear/Desbloquear
    btnNodeLock.addEventListener('click', () => {
        if (!selectedNode) return;
        saveState();
        const isLocked = selectedNode.classList.contains('canvas-node--locked');
        
        if (isLocked) {
            // Desbloquear
            selectedNode.classList.remove('canvas-node--locked');
            btnNodeLock.innerHTML = `<i class="bi bi-unlock"></i> Desbloqueado`;
            btnNodeLock.classList.remove('locked');
            
            // Re-añadir tirador
            const type = getNodeType(selectedNode);
            if (type !== 'choice') {
                createResizer(selectedNode);
            }
            
            // Eliminar indicador visual de candado
            const indicator = selectedNode.querySelector('.canvas-node__lock-indicator');
            if (indicator) indicator.remove();
        } else {
            // Bloquear
            selectedNode.classList.add('canvas-node--locked');
            btnNodeLock.innerHTML = `<i class="bi bi-lock-fill"></i> Bloqueado`;
            btnNodeLock.classList.add('locked');
            
            // Eliminar resizer
            const resizer = selectedNode.querySelector('.canvas-node__resizer');
            if (resizer) resizer.remove();
            
            // Crear indicador visual de candado
            if (!selectedNode.querySelector('.canvas-node__lock-indicator')) {
                const indicator = document.createElement('div');
                indicator.className = 'canvas-node__lock-indicator';
                indicator.innerHTML = `<i class="bi bi-lock-fill"></i>`;
                selectedNode.appendChild(indicator);
            }
        }
    });

    // ==========================================
    // 9. CAMPOS DINÁMICOS DEL INSPECTOR POR TIPO
    // ==========================================
    
    function renderDynamicInspectorFields(node) {
        const type = getNodeType(node);
        dynamicFields.innerHTML = ''; // Limpiar campos previos

        if (type === 'formula') {
            const currentFormula = node.getAttribute('data-formula') || '';
            const html = `
                <div class="inspector-section">
                    <label class="inspector-label">Ecuación Científica (LaTeX)</label>
                    <input type="text" class="inspector-input" id="inp-formula-latex" value="${currentFormula.replace(/"/g, '&quot;')}" readonly placeholder="Ecuación vacía...">
                </div>
                <div class="inspector-section">
                    <button type="button" class="btn-inspector-action" id="btn-edit-node-formula">
                        <i class="bi bi-calculator me-1"></i> Abrir Editor de Fórmulas
                    </button>
                </div>
            `;
            dynamicFields.innerHTML = html;

            document.getElementById('btn-edit-node-formula').addEventListener('click', () => {
                if (window.abrirModalFormulaPremium) {
                    window.abrirModalFormulaPremium(node.getAttribute('data-formula') || '', node);
                }
            });
        } else if (type === 'text') {
            const html = `
                <div class="inspector-section">
                    <label class="inspector-label">Estilo de Texto Rápido</label>
                    <div class="d-flex gap-1" style="margin-block-end: 0.5rem;">
                        <button type="button" class="btn-inspector-action" id="btn-text-bold" style="height:36px; min-width:36px; padding:0; flex:none;" title="Negrita">
                            <i class="bi bi-type-bold"></i>
                        </button>
                        <button type="button" class="btn-inspector-action" id="btn-text-italic" style="height:36px; min-width:36px; padding:0; flex:none;" title="Cursiva">
                            <i class="bi bi-type-italic"></i>
                        </button>
                        <button type="button" class="btn-inspector-action" id="btn-text-underline" style="height:36px; min-width:36px; padding:0; flex:none;" title="Subrayado">
                            <i class="bi bi-type-underline"></i>
                        </button>
                        <button type="button" class="btn-inspector-action" id="btn-text-formula" style="height:36px; min-width:36px; padding:0; flex:none;" title="Insertar Fórmula LaTeX">
                            <span style="font-family: serif; font-weight: bold; font-style: italic;">fx</span>
                        </button>
                        <select class="inspector-input" id="sel-text-color" style="height:36px; font-size:0.75rem; padding:0 5px; flex:1;">
                            <option value="">Color por defecto</option>
                            <option value="var(--el-primary)">Naranja Institucional</option>
                            <option value="var(--el-dark)">Oscuro Principal</option>
                            <option value="rgb(40, 167, 69)">Verde Solución</option>
                            <option value="rgb(220, 53, 69)">Rojo Alerta</option>
                            <option value="rgb(0, 123, 255)">Azul Informativo</option>
                        </select>
                    </div>
                </div>
                <div class="inspector-section">
                    <label class="inspector-label">Instrucciones</label>
                    <div class="text-muted small italic p-2 bg-light rounded border" style="font-size: 0.75rem; line-height: 1.3;">
                        <i class="bi bi-info-circle me-1"></i> Escriba directamente sobre el texto en el lienzo para editarlo. Use los botones de arriba para aplicar formato.
                    </div>
                </div>
                <div class="inspector-section">
                    <label class="inspector-label">Grupo Relacionado (Opcional)</label>
                    <input type="text" class="inspector-input" id="inp-text-group" placeholder="maqueta_1">
                </div>
            `;
            dynamicFields.innerHTML = html;

            const contentDiv = node.querySelector('.canvas-node-content');

            // Asegurarnos de que el texto sea editable inline directamente
            contentDiv.setAttribute('contenteditable', 'true');
            contentDiv.setAttribute('spellcheck', 'false');

            // Función para sincronizar e iluminar los estados de los botones rápidos de formato
            const updateFormatButtonStates = () => {
                const isBold = document.queryCommandState('bold');
                const isItalic = document.queryCommandState('italic');
                const isUnderline = document.queryCommandState('underline');

                const btnBold = document.getElementById('btn-text-bold');
                const btnItalic = document.getElementById('btn-text-italic');
                const btnUnderline = document.getElementById('btn-text-underline');

                if (btnBold) {
                    btnBold.style.borderColor = isBold ? 'var(--el-primary)' : '';
                    btnBold.style.backgroundColor = isBold ? 'rgba(var(--el-primary-rgb), 0.1)' : '';
                    btnBold.style.color = isBold ? 'var(--el-primary)' : '';
                }
                if (btnItalic) {
                    btnItalic.style.borderColor = isItalic ? 'var(--el-primary)' : '';
                    btnItalic.style.backgroundColor = isItalic ? 'rgba(var(--el-primary-rgb), 0.1)' : '';
                    btnItalic.style.color = isItalic ? 'var(--el-primary)' : '';
                }
                if (btnUnderline) {
                    btnUnderline.style.borderColor = isUnderline ? 'var(--el-primary)' : '';
                    btnUnderline.style.backgroundColor = isUnderline ? 'rgba(var(--el-primary-rgb), 0.1)' : '';
                    btnUnderline.style.color = isUnderline ? 'var(--el-primary)' : '';
                }
            };

            // Escuchar cambios de selección dentro del elemento de texto
            contentDiv.addEventListener('keyup', updateFormatButtonStates);
            contentDiv.addEventListener('mouseup', updateFormatButtonStates);
            contentDiv.addEventListener('focus', updateFormatButtonStates);
            document.addEventListener('selectionchange', () => {
                if (document.activeElement === contentDiv) {
                    updateFormatButtonStates();
                }
            });

            // Lógica para botones de estilo enriquecido directo
            const applyStyle = (command) => {
                saveState();
                contentDiv.focus();
                
                const selection = window.getSelection();
                if (!selection.rangeCount) return;

                let range = selection.getRangeAt(0);
                
                // Si hay selección activa, guardamos y limpiamos espacios de la selección para evitar aplicar estilo al espacio
                if (!range.collapsed) {
                    let selText = range.toString();

                    let startAdjust = 0;
                    let endAdjust = 0;

                    // Contar cuántos espacios iniciales y finales hay en el string seleccionado
                    while (selText.startsWith(' ') || selText.startsWith('\u00A0') || selText.startsWith('\r') || selText.startsWith('\n')) {
                        startAdjust++;
                        selText = selText.substring(1);
                    }
                    while (selText.endsWith(' ') || selText.endsWith('\u00A0') || selText.endsWith('\r') || selText.endsWith('\n')) {
                        endAdjust++;
                        selText = selText.substring(0, selText.length - 1);
                    }

                    if (startAdjust > 0 || endAdjust > 0) {
                        try {
                            // Crear un nuevo rango basado en la búsqueda de texto real
                            let startContainer = range.startContainer;
                            let startOffset = range.startOffset;
                            let endContainer = range.endContainer;
                            let endOffset = range.endOffset;

                            // Si es el mismo contenedor de texto, el ajuste es directo
                            if (startContainer === endContainer && startContainer.nodeType === Node.TEXT_NODE) {
                                const newRange = document.createRange();
                                newRange.setStart(startContainer, startOffset + startAdjust);
                                newRange.setEnd(startContainer, endOffset - endAdjust);
                                selection.removeAllRanges();
                                selection.addRange(newRange);
                            } else {
                                // Rango complejo que abarca múltiples nodos
                                let walker = document.createTreeWalker(range.commonAncestorContainer, NodeFilter.SHOW_TEXT);
                                let nodes = [];
                                let node;
                                while (node = walker.nextNode()) {
                                    if (selection.containsNode(node, true)) {
                                        nodes.push(node);
                                    }
                                }

                                if (nodes.length > 0) {
                                    let newRange = document.createRange();
                                    
                                    // Ajustar el inicio
                                    let firstNode = nodes[0];
                                    let firstOffset = (firstNode === startContainer) ? startOffset : 0;
                                    let firstAdjust = 0;
                                    let firstText = firstNode.textContent.substring(firstOffset);
                                    while (firstText.startsWith(' ') || firstText.startsWith('\u00A0')) {
                                        firstAdjust++;
                                        firstText = firstText.substring(1);
                                    }
                                    newRange.setStart(firstNode, firstOffset + firstAdjust);

                                    // Ajustar el final
                                    let lastNode = nodes[nodes.length - 1];
                                    let lastOffset = (lastNode === endContainer) ? endOffset : lastNode.textContent.length;
                                    let lastAdjust = 0;
                                    let lastText = lastNode.textContent.substring(0, lastOffset);
                                    while (lastText.endsWith(' ') || lastText.endsWith('\u00A0')) {
                                        lastAdjust++;
                                        lastText = lastText.substring(0, lastText.length - 1);
                                    }
                                    newRange.setEnd(lastNode, lastOffset - lastAdjust);

                                    selection.removeAllRanges();
                                    selection.addRange(newRange);
                                }
                            }
                        } catch (err) {
                            // Silencio experimental
                        }
                    }
                }

                document.execCommand(command, false, null);
                updateFormatButtonStates();
                updateConnections();
            };

            ['btn-text-bold', 'btn-text-italic', 'btn-text-underline', 'btn-text-formula'].forEach(id => {
                const btn = document.getElementById(id);
                if (btn) btn.addEventListener('mousedown', e => e.preventDefault());
            });

            document.getElementById('btn-text-bold').addEventListener('click', (e) => {
                e.preventDefault();
                applyStyle('bold');
            });
            document.getElementById('btn-text-italic').addEventListener('click', (e) => {
                e.preventDefault();
                applyStyle('italic');
            });
            document.getElementById('btn-text-underline').addEventListener('click', (e) => {
                e.preventDefault();
                applyStyle('underline');
            });
            
            const btnFormula = document.getElementById('btn-text-formula');
            if (btnFormula) {
                btnFormula.addEventListener('click', async (e) => {
                    e.preventDefault();
                    contentDiv.focus();
                    
                    const { value: formula } = await Swal.fire({
                        title: 'Insertar Fórmula',
                        input: 'text',
                        inputLabel: 'Fórmula en formato LaTeX',
                        inputPlaceholder: 'Ej: E = mc^2',
                        showCancelButton: true,
                        confirmButtonText: 'Insertar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: 'var(--el-primary)'
                    });

                    if (formula) {
                        try {
                            if (typeof katex === 'undefined') {
                                Swal.fire('Error', 'El motor de fórmulas (KaTeX) no está disponible en este entorno.', 'error');
                                return;
                            }
                            const html = katex.renderToString(formula, { throwOnError: false });
                            contentDiv.focus();
                            document.execCommand('insertHTML', false, `<span class="canvas-math-formula" contenteditable="false" data-formula="${formula.replace(/"/g, '&quot;')}">${html}</span>&nbsp;`);
                            saveState();
                            updateConnections();
                        } catch(err) {
                            Swal.fire('Error', 'Fórmula LaTeX inválida', 'error');
                        }
                    }
                });
            }

            // Lógica de color de texto
            const selColor = document.getElementById('sel-text-color');
            selColor.value = contentDiv.style.color || '';
            selColor.addEventListener('change', () => {
                saveState();
                contentDiv.style.color = selColor.value;
            });

            const groupInput = document.getElementById('inp-text-group');
            trackInputUndo(groupInput);
            groupInput.value = node.dataset.group || '';
            groupInput.addEventListener('input', () => {
                node.dataset.group = groupInput.value.trim();
                updateConnections();
            });

        } else if (type === 'image') {
            const html = `
                <div class="inspector-section">
                    <label class="inspector-label">Cargar Imagen Local</label>
                    <input type="file" class="inspector-input pt-2" id="inp-image-file" accept="image/*">
                </div>
                <div class="inspector-section">
                    <label class="inspector-label">URL de Imagen Personalizada</label>
                    <input type="text" class="inspector-input" id="inp-image-src" placeholder="https://ejemplo.com/imagen.jpg">
                </div>
            `;
            dynamicFields.innerHTML = html;

            const fileInput = document.getElementById('inp-image-file');
            const input = document.getElementById('inp-image-src');
            const img = node.querySelector('img');
            trackInputUndo(input);

            input.value = img.src.includes('default_placeholder.svg') || img.src.includes('perseus.png') || img.src.includes('logo.ai') ? '' : img.src;

            fileInput.addEventListener('change', (e) => {
                saveState();
                const file = e.target.files[0];
                if (!file) return;

                // Validar tamaño máximo inicial de 10 MB para evitar saturación o cuelgues del navegador
                const maxRawSize = 10 * 1024 * 1024;
                if (file.size > maxRawSize) {
                    Swal.fire({
                        title: 'Archivo demasiado grande',
                        text: 'El tamaño de la imagen original supera el límite de 10 MB. Por favor use un archivo más ligero.',
                        icon: 'warning',
                        confirmButtonColor: 'var(--el-primary)'
                    });
                    fileInput.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = (evt) => {
                    const tempImg = new Image();
                    tempImg.onload = () => {
                        // Dimensiones de ajuste dinámico (máximo 800px)
                        const maxWidth = 800;
                        const maxHeight = 800;
                        let width = tempImg.width;
                        let height = tempImg.height;

                        if (width > maxWidth || height > maxHeight) {
                            if (width / height > maxWidth / maxHeight) {
                                height = Math.round((height * maxWidth) / width);
                                width = maxWidth;
                            } else {
                                width = Math.round((width * maxHeight) / height);
                                height = maxHeight;
                            }
                        }

                        // Lienzo virtual para re-dibujado y compresión
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');

                        // Rellenar fondo blanco para evitar silueta negra en transparencias (ej. PNG) al convertirlas a JPEG
                        ctx.fillStyle = 'rgb(255, 255, 255)';
                        ctx.fillRect(0, 0, width, height);

                        ctx.drawImage(tempImg, 0, 0, width, height);

                        // Comprimir a JPEG con calidad 0.75 para máxima ligereza en la base de datos
                        const compressedBase64 = canvas.toDataURL('image/jpeg', 0.75);

                        // Adaptar las dimensiones físicas del nodo al aspect ratio de la imagen cargada
                        const currentW = node.offsetWidth || 420;
                        const currentH = node.offsetHeight || 320;
                        const imageRatio = tempImg.width / tempImg.height;
                        const containerRatio = currentW / currentH;

                        let newW = currentW;
                        let newH = currentH;

                        if (imageRatio > containerRatio) {
                            newH = Math.round(currentW / imageRatio);
                        } else {
                            newW = Math.round(currentH * imageRatio);
                        }

                        node.style.width = `${newW}px`;
                        node.style.height = `${newH}px`;

                        // Asignar la imagen optimizada al elemento y limpiar input de URL
                        img.src = compressedBase64;
                        input.value = '';
                        updateConnections();
                    };
                    tempImg.onerror = () => {
                        Swal.fire({
                            title: 'Error de imagen',
                            text: 'No se pudo procesar la imagen correctamente.',
                            icon: 'error',
                            confirmButtonColor: 'var(--el-primary)'
                        });
                        fileInput.value = '';
                    };
                    tempImg.src = evt.target.result;
                };
                reader.readAsDataURL(file);
            });

            input.addEventListener('input', () => {
                const url = input.value.trim();
                if (url !== '') {
                    const tempImg = new Image();
                    tempImg.onload = () => {
                        // Adaptar las dimensiones físicas del nodo al aspect ratio
                        const currentW = node.offsetWidth || 420;
                        const currentH = node.offsetHeight || 320;
                        const imageRatio = tempImg.width / tempImg.height;
                        const containerRatio = currentW / currentH;

                        let newW = currentW;
                        let newH = currentH;

                        if (imageRatio > containerRatio) {
                            newH = Math.round(currentW / imageRatio);
                        } else {
                            newW = Math.round(currentH * imageRatio);
                        }

                        node.style.width = `${newW}px`;
                        node.style.height = `${newH}px`;

                        img.src = url;
                        fileInput.value = '';
                        updateConnections();
                    };
                    tempImg.src = url;
                }
            });

        } else if (type === 'choice') {
            const html = `
                <div class="inspector-section">
                    <label class="inspector-label">Etiqueta (A, B, C...)</label>
                    <input type="text" class="inspector-input" id="inp-choice-label" maxlength="3">
                </div>
                <div class="inspector-section">
                    <label class="inspector-label">Valor Opcional</label>
                    <input type="text" class="inspector-input" id="inp-choice-value">
                </div>
                <div class="inspector-section">
                    <label class="inspector-label font-bold text-primary">Grupo de Pregunta (Filtro Vectorial)</label>
                    <input type="text" class="inspector-input" id="inp-choice-group" placeholder="maqueta_1">
                </div>
                <div class="inspector-section">
                    <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded-3 border">
                        <span class="fw-bold small text-secondary">¿Es la Opción Correcta?</span>
                        <label class="switch-elite">
                            <input type="checkbox" id="inp-choice-correct" value="1">
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                </div>
            `;
            dynamicFields.innerHTML = html;

            const labelInput = document.getElementById('inp-choice-label');
            const valueInput = document.getElementById('inp-choice-value');
            const groupInput = document.getElementById('inp-choice-group');
            const correctInput = document.getElementById('inp-choice-correct');

            trackInputUndo(labelInput);
            trackInputUndo(valueInput);
            trackInputUndo(groupInput);
            correctInput.addEventListener('change', () => {
                saveState();
            });

            labelInput.value = node.dataset.label || 'A';
            valueInput.value = node.dataset.value || '';
            groupInput.value = node.dataset.group || '';
            correctInput.checked = node.dataset.isCorrect === 'true';

            labelInput.addEventListener('input', () => {
                let label = labelInput.value.toUpperCase() || '?';
                if (label.length > 3) {
                    label = label.slice(0, 3);
                    labelInput.value = label;
                }
                node.dataset.label = label;
                
                // Conservar la barra de acciones y el tirador si existen al reescribir HTML
                const actions = node.querySelector('.canvas-node__actions');
                const lockInd = node.querySelector('.canvas-node__lock-indicator');
                
                node.innerHTML = label;
                
                if (actions) node.appendChild(actions);
                if (lockInd) node.appendChild(lockInd);
            });

            valueInput.addEventListener('input', () => {
                node.dataset.value = valueInput.value;
            });

            groupInput.addEventListener('input', () => {
                node.dataset.group = groupInput.value.trim();
                updateConnections();
            });

            correctInput.addEventListener('change', () => {
                const isCorrect = correctInput.checked;
                node.dataset.isCorrect = isCorrect ? 'true' : 'false';
                
                if (isCorrect) {
                    node.classList.add('choice--correct');
                    
                    // Lógica de desmarcación automática para selección única por grupo (Soberanía de Grupo)
                    const group = node.dataset.group;
                    if (group && group.trim() !== '') {
                        const siblings = board.querySelectorAll('.canvas-node.type-choice');
                        siblings.forEach(sib => {
                            if (sib !== node && sib.dataset.group === group) {
                                sib.dataset.isCorrect = 'false';
                                sib.classList.remove('choice--correct');
                            }
                        });
                    }
                } else {
                    node.classList.remove('choice--correct');
                }
            });
            
        } else if (type === 'hotspot') {
            const html = `
                <div class="inspector-section">
                    <label class="inspector-label">Identificador de Zona</label>
                    <input type="text" class="inspector-input" id="inp-hotspot-label">
                </div>
                <div class="inspector-section">
                    <label class="inspector-label">Color del Filtro</label>
                    <select class="inspector-input" id="sel-hotspot-color">
                        <option value="blue">Azul Semitransparente</option>
                        <option value="red">Rojo Alerta</option>
                        <option value="green">Verde Solución</option>
                    </select>
                </div>
            `;
            dynamicFields.innerHTML = html;

            const labelInput = document.getElementById('inp-hotspot-label');
            const colorSelect = document.getElementById('sel-hotspot-color');
            const content = node.querySelector('.canvas-node-content');

            trackInputUndo(labelInput);
            colorSelect.addEventListener('change', () => {
                saveState();
            });

            labelInput.value = content.innerHTML === 'ZONA CALIENTE' ? '' : content.innerHTML;
            colorSelect.value = node.dataset.color || 'blue';

            labelInput.addEventListener('input', () => {
                content.innerHTML = labelInput.value.toUpperCase() || 'ZONA CALIENTE';
            });

            colorSelect.addEventListener('change', () => {
                node.dataset.color = colorSelect.value;
                if (colorSelect.value === 'red') {
                    node.style.backgroundColor = 'rgba(220, 53, 69, 0.15)';
                    node.style.borderColor = 'rgb(220, 53, 69)';
                } else if (colorSelect.value === 'green') {
                    node.style.backgroundColor = 'rgba(40, 167, 69, 0.15)';
                    node.style.borderColor = 'rgb(40, 167, 69)';
                } else {
                    node.style.backgroundColor = 'rgba(var(--el-primary-rgb), 0.08)';
                    node.style.borderColor = 'var(--el-primary)';
                }
            });
        } else if (type === 'dropzone') {
            const html = `
                <div class="inspector-section">
                    <label class="inspector-label">Etiqueta de Hueco</label>
                    <input type="text" class="inspector-input" id="inp-dropzone-label">
                </div>
                <div class="inspector-section">
                    <label class="inspector-label font-bold text-primary">ID de Etiqueta Correcta (Respuesta)</label>
                    <input type="text" class="inspector-input" id="inp-dropzone-correct" placeholder="id_de_la_etiqueta">
                </div>
                <div class="inspector-section">
                    <label class="inspector-label font-bold text-primary">Grupo de Pregunta (Filtro Vectorial)</label>
                    <input type="text" class="inspector-input" id="inp-dropzone-group" placeholder="maqueta_1">
                </div>
            `;
            dynamicFields.innerHTML = html;

            const labelInput = document.getElementById('inp-dropzone-label');
            const correctInput = document.getElementById('inp-dropzone-correct');
            const groupInput = document.getElementById('inp-dropzone-group');

            trackInputUndo(labelInput);
            trackInputUndo(correctInput);
            trackInputUndo(groupInput);

            labelInput.value = node.dataset.label || 'Hueco';
            correctInput.value = node.dataset.valueCorrect || '';
            groupInput.value = node.dataset.group || '';

            labelInput.addEventListener('input', () => {
                const val = labelInput.value.trim();
                node.dataset.label = val;
                const content = node.querySelector('.canvas-node-content');
                if (content) {
                    content.innerHTML = val || 'HUECO';
                }
            });

            correctInput.addEventListener('input', () => {
                node.dataset.valueCorrect = correctInput.value.trim();
            });

            groupInput.addEventListener('input', () => {
                node.dataset.group = groupInput.value.trim();
                updateConnections();
            });
        } else if (type === 'seq_slot') {
            const html = `
                <div class="inspector-section">
                    <label class="inspector-label font-bold text-primary">Orden Numérico (Posición)</label>
                    <input type="number" class="inspector-input" id="inp-seq-order" min="1" max="99">
                </div>
            `;
            dynamicFields.innerHTML = html;

            const orderInput = document.getElementById('inp-seq-order');
            trackInputUndo(orderInput);

            orderInput.value = node.dataset.valueCorrect || '1';

            orderInput.addEventListener('input', () => {
                const val = orderInput.value.trim();
                node.dataset.valueCorrect = val;
                const content = node.querySelector('.canvas-node-content');
                if (content) {
                    content.innerHTML = val || '?';
                }
            });
        } else if (type === 'draggable') {
            const html = `
                <div class="inspector-section">
                    <label class="inspector-label">Identificador de Etiqueta</label>
                    <div class="text-muted small italic p-2 bg-light rounded border" style="font-size: 0.75rem; line-height: 1.3;">
                        <i class="bi bi-info-circle me-1"></i> Escriba directamente sobre la etiqueta en el lienzo para editar su contenido.
                    </div>
                </div>
            `;
            dynamicFields.innerHTML = html;
            const contentDiv = node.querySelector('.canvas-node-content');
            if (contentDiv) {
                contentDiv.setAttribute('contenteditable', 'true');
                contentDiv.setAttribute('spellcheck', 'false');
            }
        }
    }

    // ==========================================
    // 10. DIBUJO DINÁMICO DE CONEXIONES SVG
    // ==========================================
    
    function updateConnections() {
        // Limpiar líneas previas del SVG, conservando <defs>
        const paths = svgConnections.querySelectorAll('path');
        paths.forEach(p => p.remove());

        const nodes = board.querySelectorAll('.canvas-node');
        const groupedNodes = {};

        // Agrupar elementos por data-group
        nodes.forEach(node => {
            const group = node.dataset.group;
            if (group && group.trim() !== '') {
                if (!groupedNodes[group]) {
                    groupedNodes[group] = [];
                }
                groupedNodes[group].push(node);
            }
        });

        // Trazar vectores de conexión entre elementos del mismo grupo
        Object.keys(groupedNodes).forEach(group => {
            const groupMembers = groupedNodes[group];
            
            // Encontrar el nodo "Padre/Maestro" (Texto Dinámico o Título)
            const masterNode = groupMembers.find(n => n.classList.contains('type-text')) || groupMembers[0];
            const childNodes = groupMembers.filter(n => n !== masterNode);

            if (!masterNode || childNodes.length === 0) return;

            // Centro del maestro
            const mX = parseInt(masterNode.style.left) + (masterNode.offsetWidth / 2);
            const mY = parseInt(masterNode.style.top) + (masterNode.offsetHeight / 2);

            childNodes.forEach(child => {
                // Centro del hijo
                const cX = parseInt(child.style.left) + (child.offsetWidth / 2);
                const cY = parseInt(child.style.top) + (child.offsetHeight / 2);

                // Dibujar una curva Bézier cuadrática de seda (Vitrina 06)
                const controlX = (mX + cX) / 2;
                // Curvatura sutil en el eje Y
                const controlY = mY - 20;

                const pathString = `M ${mX} ${mY} Q ${controlX} ${controlY} ${cX} ${cY}`;

                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', pathString);
                path.setAttribute('class', 'connection-line');
                // Flecha final en el extremo
                path.setAttribute('marker-end', 'url(#arrow)');
                
                svgConnections.appendChild(path);
            });
        });
    }

    // ==========================================
    // 11. EXPORTACIÓN SOBERANA A JSON Y PUENTE
    // ==========================================
    
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;')
                  .replace(/'/g, '&apos;');
    }

    function compileCanvasJSON() {
        const nodes = board.querySelectorAll('.canvas-node');
        const exportData = {
            canvas: { width: 2400, height: 1800, grid: gridSize },
            nodes: []
        };

        nodes.forEach(n => {
            const type = getNodeType(n);
            const details = {
                id: n.id,
                type: type,
                x: parseInt(n.style.left),
                y: parseInt(n.style.top),
                w: n.offsetWidth,
                h: n.offsetHeight,
                locked: n.classList.contains('canvas-node--locked'),
                group: n.dataset.group || null
            };

            if (type === 'text') {
                const contentEl = n.querySelector('.canvas-node-content');
                details.content = contentEl ? contentEl.innerHTML : '';
            } else if (type === 'image') {
                const imgEl = n.querySelector('img');
                details.src = imgEl ? imgEl.src : '';
            } else if (type === 'choice') {
                details.label = n.dataset.label || 'A';
                details.value_correct = n.dataset.value || '';
                details.is_correct = n.dataset.isCorrect === 'true';
            } else if (type === 'hotspot') {
                const contentEl = n.querySelector('.canvas-node-content');
                details.label = contentEl ? contentEl.innerHTML : 'ZONA CALIENTE';
                details.color = n.dataset.color || 'blue';
            } else if (type === 'dropzone') {
                details.label = n.dataset.label || 'Hueco';
                details.value_correct = n.dataset.valueCorrect || '';
                details.group = n.dataset.group || '';
            } else if (type === 'draggable') {
                const contentEl = n.querySelector('.canvas-node-content');
                details.label = contentEl ? contentEl.innerHTML : 'Etiqueta';
            } else if (type === 'formula') {
                details.formula = n.getAttribute('data-formula') || '';
            }

            exportData.nodes.push(details);
        });

        return JSON.stringify(exportData, null, 2);
    }

    if (btnPreviewJson) {
        btnPreviewJson.addEventListener('click', () => {
            const jsonString = compileCanvasJSON();
            Swal.fire({
                title: 'Código JSON Compilado',
                html: `<pre style="text-align:left; font-size:12px; background:var(--el-bg-neutral-light); padding:12px; border-radius:var(--el-radius-md); max-height:300px; overflow-y:auto; font-family:var(--el-font-institutional);">${escapeHtml(jsonString)}</pre>`,
                icon: 'success',
                confirmButtonColor: 'var(--el-primary)',
                width: 600
            });
        });
    }

    if (btnModalCanvasSave) {
        btnModalCanvasSave.addEventListener('click', () => {
            const jsonString = compileCanvasJSON();
            const parentTextarea = document.getElementById('canvas-json-data');
            if (parentTextarea) {
                parentTextarea.value = jsonString;
                parentTextarea.dispatchEvent(new Event('input', { bubbles: true }));
                parentTextarea.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            const modal = document.getElementById('modal-ares-canvas-fullscreen');
            if (modal) {
                modal.classList.remove('modal-canvas--open');
            }
            
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Diseño visual aplicado con éxito',
                html: 'Recuerde guardar el reactivo haciendo clic en <strong>GUARDAR EN BÓVEDA</strong>.',
                showConfirmButton: false,
                timer: 4500,
                timerProgressBar: true
            });
        });
    }

    if (btnModalCanvasCancel) {
        btnModalCanvasCancel.addEventListener('click', async () => {
            const result = await Swal.fire({
                title: '¿Descartar cambios?',
                text: 'Se perderán todas las modificaciones de dibujo no aplicadas al reactivo.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--el-danger)',
                cancelButtonColor: 'var(--el-text-muted)',
                confirmButtonText: 'Sí, descartar',
                cancelButtonText: 'Volver al editor'
            });
            
            if (result.isConfirmed) {
                const modal = document.getElementById('modal-ares-canvas-fullscreen');
                if (modal) {
                    modal.classList.remove('modal-canvas--open');
                }
            }
        });
    }

    // ==========================================
    // 12. INICIALIZADOR GLOBAL (EL PUENTE)
    // ==========================================
    
    window.initCanvasEditor = function(jsonData) {
        deselectNode(true);
        const nodes = board.querySelectorAll('.canvas-node');
        nodes.forEach(node => node.remove());
        
        let data = null;
        if (jsonData) {
            try {
                data = typeof jsonData === 'string' ? JSON.parse(jsonData) : jsonData;
            } catch (err) {
                // Silencio experimental
            }
        }
        
        if (data && data.nodes && Array.isArray(data.nodes)) {
            let maxCounter = 0;
            data.nodes.forEach(n => {
                const match = n.id.match(/\d+/);
                if (match) {
                    const val = parseInt(match[0]);
                    if (val > maxCounter) maxCounter = val;
                }
            });
            nodeCounter = maxCounter;
            
            data.nodes.forEach(n => {
                const node = createCanvasNode(n.type, n.x, n.y, n.w, n.h, n.id, n.group);
                if (n.locked) {
                    node.classList.add('canvas-node--locked');
                    const resizer = node.querySelector('.canvas-node__resizer');
                    if (resizer) resizer.remove();
                    
                    if (!node.querySelector('.canvas-node__lock-indicator')) {
                        const indicator = document.createElement('div');
                        indicator.className = 'canvas-node__lock-indicator';
                        indicator.innerHTML = `<i class="bi bi-lock-fill"></i>`;
                        node.appendChild(indicator);
                    }
                }
                
                if (n.type === 'text') {
                    const contentEl = node.querySelector('.canvas-node-content');
                    if (contentEl) contentEl.innerHTML = n.content || '';
                } else if (n.type === 'image') {
                    const imgEl = node.querySelector('img');
                    if (imgEl) imgEl.src = n.src || '../assets/modules/ares/default_placeholder.svg';
                } else if (n.type === 'choice') {
                    const labelTrunc = (n.label || 'A').slice(0, 3);
                    node.dataset.label = labelTrunc;
                    node.dataset.value = n.value_correct || '';
                    node.dataset.isCorrect = n.is_correct ? 'true' : 'false';
                    node.innerHTML = labelTrunc;
                    if (n.is_correct) {
                        node.classList.add('choice--correct');
                    }
                } else if (n.type === 'hotspot') {
                    const contentEl = node.querySelector('.canvas-node-content');
                    if (contentEl) contentEl.innerHTML = n.label || 'ZONA CALIENTE';
                    node.dataset.color = n.color || 'blue';
                    if (n.color === 'red') {
                        node.style.backgroundColor = 'rgba(220, 53, 69, 0.15)';
                        node.style.borderColor = 'rgb(220, 53, 69)';
                    } else if (n.color === 'green') {
                        node.style.backgroundColor = 'rgba(40, 167, 69, 0.15)';
                        node.style.borderColor = 'rgb(40, 167, 69)';
                    } else {
                        node.style.backgroundColor = 'rgba(var(--el-primary-rgb), 0.08)';
                        node.style.borderColor = 'var(--el-primary)';
                    }
                } else if (n.type === 'formula') {
                    node.setAttribute('data-formula', n.formula || '');
                    const contentEl = node.querySelector('.canvas-node-content');
                    if (contentEl) {
                        const formulaVal = n.formula || '';
                        if (formulaVal) {
                            if (typeof katex !== 'undefined') {
                                try {
                                    const html = katex.renderToString(formulaVal, { throwOnError: false });
                                    contentEl.innerHTML = `<span class="canvas-math-formula-rendered">${html}</span>`;
                                } catch (e) {
                                    contentEl.textContent = formulaVal;
                                }
                            } else {
                                contentEl.textContent = formulaVal;
                            }
                        } else {
                            contentEl.innerHTML = `<span class="canvas-formula-placeholder">Doble clic para definir fórmula</span>`;
                        }
                    }
                }
            });
        }
        
        deselectNode(true);
        updateConnections();
    };

    window.canvasSaveState = saveState;
    window.canvasUpdateConnections = updateConnections;

    // Trazar conexiones iniciales si existieran
    updateConnections();
}

if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(initCanvasEngine, 50);
} else {
    document.addEventListener('DOMContentLoaded', initCanvasEngine);
}
