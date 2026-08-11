<?php
declare(strict_types=1);
// PHP/VISTAS/ARES_CANVAS_LAB.PHP - LABORATORIO DE INTEGRACIÓN COMPLETA v3.0 (EDICIÓN SOBERANA)
if (!tiene_permiso('evaluacion')) {
    echo "<div class='alert-soberania-elite animate__animated animate__headShake'>
            <div class='alert-soberania-icon'><i class='bi bi-shield-lock-fill'></i></div>
            <div class='alert-soberania-content'>
                <h4>Acceso Restringido</h4>
                <p>El Laboratorio Ares es un área de alta seguridad y experimentación.</p>
            </div>
          </div>";
    return;
}

// Carga de Materias (Copiar lógica tradicional)
$mi_id = (int)$_SESSION['usuario_id'];
if (tienen_rol(['Administrador', 'Coordinador'])) {
    $stmt_materias = $db->prepare("SELECT id, nombre_especialidad, area_id, disciplina_men FROM especialidades ORDER BY nombre_especialidad ASC");
    $stmt_materias->execute();
    $materias = $stmt_materias->fetchAll();
} else {
    $stmt_m = $db->prepare("SELECT DISTINCT e.id, e.nombre_especialidad, e.area_id, e.disciplina_men 
                            FROM especialidades e 
                            JOIN carga_academica ca ON e.id = ca.especialidad_id 
                            WHERE ca.docente_id = ? 
                            ORDER BY e.nombre_especialidad ASC");
    $stmt_m->execute([$mi_id]);
    $materias = $stmt_m->fetchAll();
}
?>

<!-- Carga de la Hoja de Estilos Unificada del Laboratorio -->
<link rel="stylesheet" href="../styles/modules/ares_canvas_lab_editor.css?v=<?php echo time(); ?>">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    <div class="header-module-elite">
        <h4 class="h3 fw-bold mb-0 text-titulo-elite">Laboratorio Integrado Ares</h4>
        <p class="subtitle-elite">Espacio experimental aislado para la fusión de reactivos lineales y libre disposición visual.</p>
    </div>
    
    <div class="row g-4">
        <!-- LATERAL: EXPLORADOR -->
        <div class="col-xl-4 col-lg-5">
            <div class="card card-elite shadow-sm ares-panel-radius">
                <div class="card-header bg-white p-3 ares-sticky-header">
                    <div class="nav-elite-tabs mb-3 d-flex p-1 bg-light rounded-pill ares-h-44">
                        <button id="tab-mine" class="nav-link-elite active w-50 rounded-pill border-0" onclick="cambiarScope('mine')">MIS REACTIVOS (LAB)</button>
                        <button id="tab-universal" class="nav-link-elite w-50 rounded-pill border-0" onclick="cambiarScope('universal')">UNIVERSAL</button>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <select id="filtro-materia-banco" class="select-elite w-75 u-hidden" onchange="cargarBanco()">
                            <option value="">Todas las Materias</option>
                            <?php foreach ($materias as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nombre_especialidad']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button id="btn-crear-reactivo" class="btn-elite btn-elite--primary px-3 ms-auto ares-h-44" onclick="clickNuevoReactivo()">
                            <i class="bi bi-plus-lg fs-5"></i>
                        </button>
                    </div>
                    
                    <div class="search-wrapper-elite">
                        <span class="search-icon-elite-fixed"><i class="bi bi-search"></i></span>
                        <input type="text" id="buscar-pregunta" class="search-elite ares-h-44" placeholder="Filtrar reactivos..." onkeyup="filtrarBanco()">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="lista-reactivos" class="list-group list-group-flush"></div>
                </div>
            </div>
        </div>

        <!-- PRINCIPAL: LABORATORIO DE REACTIVOS -->
        <div class="col-xl-8 col-lg-7">
            <div class="card card-elite shadow-sm ares-panel-radius" id="card-editor-ares" data-user-id="<?php echo $mi_id; ?>" data-csrf="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" data-is-admin="<?php echo tienen_rol(['Administrador', 'Coordinador']) ? 'true' : 'false'; ?>">
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center ares-sticky-header">
                    <div>
                        <h5 class="fw-bold text-dark mb-0" id="editor-titulo">CONFIGURACIÓN INTEGRADA (CANVAS + LINEAL)</h5>
                        <p class="text-secondary small mb-0">Banco aislado para pruebas de guardado y dibujo SVG.</p>
                    </div>
                    <div id="badge-estado-editor">
                        <span class="badge-elite-pill badge-elite-pill--warning">SANDBOX ACTIVADO</span>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <!-- ESTADO INACTIVO / BIENVENIDA -->
                    <div id="editor-empty-state" class="text-center py-5 text-muted animate__animated animate__fadeIn">
                        <i class="bi bi-plus-circle fs-1 d-block mb-3 opacity-25"></i>
                        <p class="fs-nano fw-bold text-uppercase">Presione [+] o seleccione un reactivo para activar el laboratorio</p>
                    </div>

                    <form id="form-pregunta-ares" class="d-none">
                        <input type="hidden" id="pregunta-id" value="0">
                        <input type="hidden" id="es-propia" value="1">
                        
                        <div class="mb-4">
                            <label for="titulo_pregunta" class="form-label fw-bold text-uppercase fs-nano text-muted">Título Técnico del Reactivo</label>
                            <input type="text" id="titulo_pregunta" name="titulo_pregunta" class="input-elite ares-h-44" placeholder="Ej: Mapa Geográfico - Identificación Continental (Canvas)" required>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="d-flex flex-column">
                                    <label for="materia_id" class="form-label fw-bold text-uppercase fs-nano text-muted mb-2">Materia / Especialidad</label>
                                    <select class="select-elite" id="materia_id" name="materia_id" required>
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($materias as $m): ?>
                                            <option value="<?php echo $m['id']; ?>" data-area-id="<?php echo $m['area_id']; ?>" data-disciplina="<?php echo htmlspecialchars($m['disciplina_men'] ?? 'general'); ?>"><?php echo htmlspecialchars($m['nombre_especialidad']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="area-badge" class="mt-2 d-none">
                                        <span class="badge-elite badge-elite--primary rounded-pill py-2 px-3 fs-nano italic">
                                            <i class="bi bi-diagram-3-fill me-1"></i> Área MEN: <span id="area-name">---</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex flex-column">
                                    <label for="tipo_id" class="form-label fw-bold text-uppercase fs-nano text-muted mb-2">Tipo de Pregunta</label>
                                    <select id="tipo_id" name="tipo_id" class="select-elite" required></select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="d-flex flex-column">
                                    <label for="complejidad" class="form-label fw-bold text-uppercase fs-nano text-muted mb-2">Dificultad</label>
                                    <select id="complejidad" name="complejidad" class="select-elite">
                                        <option value="Baja">Baja</option>
                                        <option value="Media" selected>Media</option>
                                        <option value="Alta">Alta</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- PANEL DE ALINEACIÓN CURRICULAR MEN -->
                        <div class="p-4 bg-white rounded-4 border mb-4 shadow-sm ares-panel-men d-none" id="panel-alineacion-men">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-circle-elite me-2 bg-primary bg-opacity-10 text-primary">
                                    <i class="bi bi-diagram-3"></i>
                                </div>
                                <h6 class="fw-bold text-dark m-0 text-uppercase fs-nano">Alineación Curricular (Estándares MEN)</h6>
                            </div>
                            
                            <div class="ares-grid-men">
                                <div>
                                    <label class="ares-label-men">GRADO</label>
                                    <select class="select-elite" id="men-grado-id">
                                        <option value="">Grado...</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="ares-label-men">APRENDIZAJE / DBA</label>
                                    <div class="ares-custom-dropdown" id="dba_dropdown_wrapper">
                                        <button type="button" class="select-elite ares-dropdown-trigger text-truncate" id="dba_dropdown_trigger" onclick="toggleDropdown('aprendizaje_list')" disabled>
                                            -- Seleccionar DBA --
                                        </button>
                                        <div class="ares-dropdown-menu" id="aprendizaje_list">
                                            <div class="text-muted small italic p-3 text-center">Seleccione un grado para cargar los DBA...</div>
                                        </div>
                                    </div>
                                    <input type="hidden" id="aprendizaje_id" name="aprendizaje_id" value="">
                                </div>
                                <div>
                                    <label class="ares-label-men">EVIDENCIA A EVALUAR</label>
                                    <div class="ares-custom-dropdown" id="evidencia_dropdown_wrapper">
                                        <button type="button" class="select-elite ares-dropdown-trigger text-truncate" id="evidencia_dropdown_trigger" onclick="toggleDropdown('evidencia_list')" disabled>
                                            -- Seleccionar Evidencia --
                                        </button>
                                        <div class="ares-dropdown-menu" id="evidencia_list">
                                            <div class="text-muted small italic p-3 text-center">Seleccione un DBA para cargar las evidencias...</div>
                                        </div>
                                    </div>
                                    <input type="hidden" id="evidencia_id" name="evidencia_id" value="">
                                </div>
                            </div>

                            <div id="preview-dba" class="mt-2 rounded-3 border-start border-4 border-primary d-none ares-preview-box"></div>
                            <input type="hidden" id="men-area-id" name="area_id">
                        </div>

                        <!-- ENUNCIADO TEXTO (QUILL) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-uppercase fs-nano text-muted">Enunciado / Contexto</label>
                            <div id="editor-enunciado" class="bg-white rounded-3 ares-editor-box"></div>
                            <div class="text-muted small italic mt-2">
                                <i class="bi bi-info-circle me-1"></i> Formatee el texto base del examen de forma lineal con Quill.
                            </div>
                        </div>

                        <!-- PANEL DINÁMICO DE METADATOS Y DISEÑADOR -->
                        <div class="p-4 bg-light rounded-4 ares-border-dashed mb-4" id="contenedor-metadatos"></div>

                        <!-- BOTONES DE EDICIÓN -->
                        <div class="d-flex justify-content-end gap-2 pt-3 border-top" id="botones-editor">
                            <button type="button" class="btn-elite btn-elite--outline px-4 ares-h-44" onclick="cerrarLaboratorio()">CANCELAR</button>
                            <button type="submit" class="btn-elite btn-elite--primary px-5 shadow-sm ares-h-44" disabled>
                                <i class="bi bi-shield-check me-2"></i> GUARDAR EN BÓVEDA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL FLOTANTE DE PANTALLA COMPLETA: ARES VISUAL CANVAS (INTEGRACIÓN)
     ========================================================================== -->
<div class="modal-canvas-fullscreen" id="modal-ares-canvas-fullscreen">
    <div class="modal-canvas-header">
        <div class="d-flex align-items-center gap-2">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-palette-fill text-primary"></i> ARES VISUAL CANVAS (LIENZO DE EVALUACIÓN)
            </h5>
            <span class="badge-elite-pill badge-elite-pill--warning fs-nano">Entorno Gráfico v2.0</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn-elite btn-elite--outline px-4 ares-h-44" id="btn-modal-canvas-cancel" data-tooltip="Descartar cambios y salir">
                CANCELAR
            </button>
            <button type="button" class="btn-elite btn-elite--primary btn-modal-close" id="btn-modal-canvas-save" data-tooltip="Confirmar Diseño y Aplicar">
                <i class="bi bi-check-lg"></i> APLICAR AL EXAMEN
            </button>
        </div>
    </div>
    
    <div class="modal-canvas-body">
        <div class="canvas-lab-container">
            
            <!-- BARRA DE HERRAMIENTAS AERO GLASS (MINIMALISTA) -->
            <aside class="canvas-toolbar aero-glass">
                <div class="toolbar-header" data-tooltip="Caja de Herramientas">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="toolbar-body">
                    <div class="tool-draggable" draggable="true" data-type="text" data-tooltip="Texto Dinámico">
                        <i class="bi bi-fonts"></i>
                    </div>
                    <div class="tool-draggable" draggable="true" data-type="image" data-tooltip="Contenedor Multimedia">
                        <i class="bi bi-image"></i>
                    </div>
                    <div class="tool-draggable" draggable="true" data-type="choice" data-tooltip="Ranura de Opción (Choice)">
                        <i class="bi bi-ui-radios"></i>
                    </div>
                    <div class="tool-draggable" draggable="true" data-type="hotspot" data-tooltip="Zona Caliente (Hotspot)">
                        <i class="bi bi-pin-angle"></i>
                    </div>
                    <div class="tool-draggable" draggable="true" data-type="dropzone" data-tooltip="Hueco Receptor (Dropzone)">
                        <span class="dropzone-text-icon">|----|</span>
                    </div>
                    <div class="tool-draggable" draggable="true" data-type="draggable" data-tooltip="Etiqueta Arrastrable (Draggable)">
                        <i class="bi bi-grip-horizontal"></i>
                    </div>
                    <div class="tool-draggable" draggable="true" data-type="seq_block" data-tooltip="Bloque Secuencial">
                        <i class="bi bi-view-list"></i>
                    </div>
                    <div class="tool-draggable" draggable="true" data-type="seq_slot" data-tooltip="Ranura de Secuencia">
                        <span class="dropzone-text-icon">[ 1 ]</span>
                    </div>
                    <div class="tool-draggable tool-draggable--template" draggable="true" data-type="formula" data-tooltip="Editor de Ecuaciones (Fórmulas)">
                        <i class="bi bi-calculator"></i>
                    </div>
                </div>
                <div class="toolbar-footer" data-tooltip="Previsualizar Estructura JSON">
                    <button type="button" class="btn-elite btn-elite--primary w-100" id="btn-preview-json">
                        <i class="bi bi-code-slash"></i>
                    </button>
                </div>
            </aside>

            <!-- ÁREA DE TRABAJO (LIENZO CUADRICULADO) -->
            <main class="canvas-workspace">
                <div class="canvas-header">
                    <div class="canvas-header__info">
                        <h4 class="h6 fw-bold mb-0 text-dark">Lienzo Técnico de Disposición Visual</h4>
                    </div>
                    <div class="canvas-workspace-controls">
                        <select id="select-canvas-maqueta" class="select-elite select-canvas-maqueta ares-h-44 px-3" data-tooltip="Cargar una maqueta pedagógica pre-calculada">
                            <option value="">-- Cargar Maqueta --</option>
                            <option value="template-choice">Opción Múltiple con Imagen</option>
                            <option value="template-match">Apareamiento</option>
                            <option value="template-hotspot">Zona Activa (Hotspot)</option>
                            <option value="template-dragdrop">Completar Arrastrando (Drag & Drop)</option>
                            <option value="template-sequence">Secuenciación / Ordenamiento</option>
                        </select>
                        <button type="button" class="btn-workspace-action active" id="btn-grid-toggle" data-tooltip="Mostrar/Ocultar Malla">
                            <i class="bi bi-grid-3x3"></i>
                        </button>
                        <button type="button" class="btn-workspace-action active" id="btn-snap-toggle" data-tooltip="Activar/Desactivar Magnetismo">
                            <i class="bi bi-magnet"></i>
                        </button>
                        <button type="button" class="btn-workspace-action" id="btn-clear-canvas" data-tooltip="Limpiar Lienzo">
                            <i class="bi bi-trash"></i>
                        </button>
                        <button type="button" class="btn-workspace-action" id="btn-formula-helper" onclick="abrirModalFormulaPremium()" data-tooltip="Editor de Ecuaciones Científicas">
                            <i class="bi bi-calculator"></i>
                        </button>
                    </div>
                </div>
                
                <div class="canvas-scroll-area">
                    <div id="ares-canvas-board" class="canvas-board" data-grid-size="20">
                        <!-- Capa SVG para conexiones vectoriales -->
                        <svg class="canvas-board__connections" id="canvas-connections-svg">
                            <defs>
                                <marker id="arrow" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                                    <path d="M 0 0 L 10 5 L 0 10 z" fill="var(--el-primary)" />
                                </marker>
                            </defs>
                        </svg>
                        <!-- Los elementos aparecerán aquí dinámicamente -->
                    </div>
                </div>
            </main>

            <!-- PANEL DEL INSPECTOR SLIDING AERO DERECHO -->
            <aside class="canvas-inspector aero-glass" id="canvas-inspector">
                <button type="button" class="inspector-toggle-tab" id="btn-inspector-toggle" data-tooltip="Mostrar/Ocultar Inspector">
                    <i class="bi bi-chevron-left" id="inspector-tab-icon"></i>
                </button>

                <div class="inspector-header">
                    <i class="bi bi-sliders"></i> INSPECTOR
                </div>
                <div class="inspector-body" id="inspector-default-message">
                    <div class="inspector-empty-state">
                        <i class="bi bi-info-circle"></i>
                        <p>Seleccione un elemento del lienzo para configurar.</p>
                    </div>
                </div>
                <div class="inspector-body d-none" id="inspector-form-container">
                    <div class="inspector-section">
                        <label class="inspector-label">Identificador</label>
                        <input type="text" class="inspector-input" id="inp-node-id" readonly>
                    </div>
                    <div class="inspector-section">
                        <label class="inspector-label">Tipo</label>
                        <input type="text" class="inspector-input" id="inp-node-type" readonly>
                    </div>
                    
                    <div class="inspector-row">
                        <div class="inspector-section">
                            <label class="inspector-label">Posición X</label>
                            <input type="number" class="inspector-input" id="inp-node-x">
                        </div>
                        <div class="inspector-section">
                            <label class="inspector-label">Posición Y</label>
                            <input type="number" class="inspector-input" id="inp-node-y">
                        </div>
                    </div>

                    <div class="inspector-row">
                        <div class="inspector-section">
                            <label class="inspector-label">Ancho</label>
                            <input type="number" class="inspector-input" id="inp-node-w">
                        </div>
                        <div class="inspector-section">
                            <label class="inspector-label">Alto</label>
                            <input type="number" class="inspector-input" id="inp-node-h">
                        </div>
                    </div>

                    <div class="inspector-section">
                        <label class="inspector-label">Organizar Capas</label>
                        <div class="inspector-layer-actions">
                            <button type="button" class="btn-inspector-action" id="btn-node-front" data-tooltip="Traer al Frente">
                                <i class="bi bi-layer-forward"></i>
                            </button>
                            <button type="button" class="btn-inspector-action" id="btn-node-back" data-tooltip="Enviar al Fondo">
                                <i class="bi bi-layer-backward"></i>
                            </button>
                        </div>
                    </div>

                    <div class="inspector-section">
                        <label class="inspector-label">Estado de Bloqueo</label>
                        <button type="button" class="btn-inspector-action btn-inspector-action--toggle w-100" id="btn-node-lock" data-tooltip="Bloquear/Desbloquear Arrastre">
                            <i class="bi bi-unlock"></i> Desbloqueado
                        </button>
                    </div>

                    <div id="inspector-dynamic-fields"></div>
                </div>
            </aside>
            
        </div>
    </div>
</div>

<!-- Scripts unificados del Laboratorio -->
<script src="../js/modules/ares_canvas_lab_editor.js?v=<?php echo time(); ?>"></script>
<script src="../js/modules/ares_canvas_engine.js?v=<?php echo time(); ?>"></script>
