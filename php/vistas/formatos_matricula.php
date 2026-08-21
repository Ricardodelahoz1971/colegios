<?php declare(strict_types=1);

require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();

if (!tiene_permiso('configuracion')) {
    echo '<div class="alert alert-danger">Acceso denegado: Rango académico insuficiente.</div>';
    return;
}

// Cargar formatos para el renderizado inicial
require_once __DIR__ . '/../db.php';
$stmt = $db->prepare("SELECT * FROM formatos_matricula ORDER BY tipo DESC, id ASC"); $stmt->execute();
$formatos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar rector para la vista previa
$rector_stmt = $db->prepare("SELECT nombre FROM usuarios WHERE rol_id = 3 LIMIT 1"); $rector_stmt->execute();
$rector_nombre = $rector_stmt->fetchColumn() ?: 'Rector Institucional';

// Cargar datos estéticos
$stmt_estetica = $db->prepare("SELECT clave, valor FROM ajustes_estetica"); $stmt_estetica->execute();
$cfg = $stmt_estetica->fetchAll(PDO::FETCH_KEY_PAIR);
$school_name = $cfg['school_name'] ?? 'SISTEMA ESCOLAR ÉLITE';
$school_motto = $cfg['school_motto'] ?? 'Excelencia en Gestión Educativa';
$school_logo = $cfg['school_logo'] ?? '';
if (!empty($school_logo) && strpos($school_logo, 'http') === false) { 
    $school_logo = '../' . $school_logo; 
}
if (empty($school_logo)) {
    $school_logo = 'perseus.png';
}

// Cargar año lectivo activo
$stmt_anio = $db->prepare("SELECT valor FROM configuracion_global WHERE clave = 'anio_lectivo_oficial' LIMIT 1"); $stmt_anio->execute();
$anio_lectivo = $stmt_anio ? ($stmt_anio->fetchColumn() ?: date('Y')) : date('Y');
?>

<!-- Quill Styles & Script -->
<link rel="stylesheet" href="../assets/libs/ql/quill.snow.min.css">
<script src="../assets/libs/ql/quill.js"></script>

<div class="formatos-container animate__animated animate__fadeIn" id="formatos-container" data-school-name="<?php echo htmlspecialchars($school_name, ENT_QUOTES, 'UTF-8'); ?>" data-school-motto="<?php echo htmlspecialchars($school_motto, ENT_QUOTES, 'UTF-8'); ?>" data-school-logo="<?php echo htmlspecialchars($school_logo, ENT_QUOTES, 'UTF-8'); ?>" data-school-anio="<?php echo htmlspecialchars((string)$anio_lectivo, ENT_QUOTES, 'UTF-8'); ?>" data-colegio-nit="<?php echo htmlspecialchars($cfg['colegio_nit'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-colegio-resolucion="<?php echo htmlspecialchars($cfg['colegio_resolucion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    
    <div class="nav-elite-header-container mb-4">
        <ul class="nav nav-pills-elite" id="formatos-tabs" role="tablist">
            <li class="nav-item-elite">
                <button class="nav-link-elite active" id="tab-lista-btn" data-bs-toggle="pill" data-bs-target="#tab-lista" type="button" role="tab">
                    <i class="bi bi-file-earmark-text me-2"></i>Plantillas y Formatos
                </button>
            </li>
            <li class="nav-item-elite">
                <button class="nav-link-elite" id="tab-editor-btn" data-bs-toggle="pill" data-bs-target="#tab-editor" type="button" role="tab" onclick="prepararNuevoFormato()">
                    <i class="bi bi-file-earmark-plus me-2"></i>Diseñador de Plantillas
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <!-- TAB 1: LISTADO DE PLANTILLAS -->
        <div class="tab-pane fade show active" id="tab-lista" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Formatos de Matrícula Configurados</h3>
                <span class="badge-elite-conteo"><?php echo count($formatos); ?> Formatos</span>
            </div>

            <div class="formats-grid">
                <?php foreach ($formatos as $f): 
                    $tipo_class = htmlspecialchars((string)($f['tipo'] === 'predisenado' ? 'predesigned' : 'custom'), ENT_QUOTES, 'UTF-8');
                    $tipo_label = htmlspecialchars((string)($f['tipo'] === 'predisenado' ? 'Prediseñado' : 'Personalizado'), ENT_QUOTES, 'UTF-8');
                    $f_id = (int)$f['id'];
                ?>
                    <div class="format-card d-flex flex-column justify-content-between" data-id="<?php echo htmlspecialchars((string)$f_id, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="format-card__header">
                            <span class="format-card__badge format-card__badge--<?php echo htmlspecialchars($tipo_class, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($tipo_label, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <h4 class="format-card__title"><?php echo htmlspecialchars($f['nombre'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p class="format-card__desc"><?php echo htmlspecialchars($f['descripcion'] ?? 'Sin descripción disponible.', ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="mt-2 small text-muted">
                                Margen Superior: <strong><?php echo htmlspecialchars((string)$f['margen_superior'], ENT_QUOTES, 'UTF-8'); ?>mm</strong> | Margen Inferior: <strong><?php echo htmlspecialchars((string)$f['margen_inferior'], ENT_QUOTES, 'UTF-8'); ?>mm</strong>
                            </div>
                        </div>

                        <div class="format-card__actions d-flex gap-2 mt-3 pt-3 border-top">
                            <button class="btn-elite btn-elite--sm btn-elite--primary flex-grow-1" onclick="editarFormato(<?php echo htmlspecialchars((string)$f_id, ENT_QUOTES, 'UTF-8'); ?>)">
                                <i class="bi bi-pencil-square me-1"></i> Editar
                            </button>

                            <?php if ($f['tipo'] !== 'predisenado'): ?>
                                <button class="btn-elite btn-elite--sm btn-elite--danger flex-grow-1" onclick="eliminarFormato(<?php echo htmlspecialchars((string)$f_id, ENT_QUOTES, 'UTF-8'); ?>)">
                                    <i class="bi bi-trash3 me-1"></i> Borrar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TAB 2: EDITOR DE PLANTILLAS -->
        <div class="tab-pane fade" id="tab-editor" role="tabpanel">
            <form id="form-formato" onsubmit="guardarFormato(event)" class="h-100">
                <div class="d-flex flex-column bg-light border rounded-4 overflow-hidden ares-min-height">
                    
                    <!-- HEADER APP (ESTÁTICA INMÓVIL) -->
                    <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-shrink-0 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <input type="hidden" id="formato-id" value="0">
                            <div>
                                <h5 class="m-0 fw-bold text-dark" id="editor-title-label">Crear Formato de Matrícula</h5>
                                <span class="fs-nano text-muted text-uppercase">Entorno Gráfico V2.0 (Ares Paper)</span>
                            </div>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn-elite btn-elite--outline" onclick="abrirCatalogoVariables()">
                                <i class="bi bi-tag-fill me-1 text-primary"></i> Insertar Campo...
                            </button>
                            <button type="button" class="btn-elite btn-elite--outline" onclick="abrirModalAjustesFormato()">
                                <i class="bi bi-gear me-1"></i> Ajuste
                            </button>
                            <button type="button" class="btn-elite btn-elite--ghost btn-elite--icon-only" onclick="abrirReferenciaCatalogo()" title="Referencia Oficial de Variables">
                                <i class="bi bi-journal-code"></i>
                            </button>
                            <button type="button" class="btn-elite btn-elite--outline" onclick="cancelarEdicion()">Cancelar</button>
                            <div class="btn-group">
                                <button type="button" class="btn-elite btn-elite--primary btn-elite--icon-only shadow-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Guardar Plantilla">
                                    <i class="bi bi-floppy"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <button type="button" class="dropdown-item py-2" onclick="guardarFormato(event, true)">
                                            <i class="bi bi-box-arrow-right me-2 text-success"></i> Guardar y Salir
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item py-2" onclick="guardarFormato(event, false)">
                                            <i class="bi bi-save me-2 text-primary"></i> Guardar
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- MAIN LAYOUT (CANVAS PANTALLA COMPLETA) -->
                    <div class="d-flex flex-grow-1 overflow-hidden position-relative">
                        <!-- CANVAS WORKSPACE -->
                        <div class="flex-grow-1 ares-workspace overflow-auto p-4 p-md-5">
                            <div class="ares-paper-sheet mx-auto canvas-builder-container" id="canvas-builder">
                                <div class="canvas-empty-state text-muted text-center py-5" id="canvas-empty-state">
                                    <div class="icon-circle-elite bg-primary bg-opacity-10 text-primary mx-auto mb-3 ares-icon-lg">
                                        <i class="bi bi-layout-text-window"></i>
                                    </div>
                                    <p class="mt-2 mb-0 fw-bold text-uppercase fs-nano">Lienzo Técnico A4</p>
                                    <p class="small text-muted mt-1">Arrastre los bloques desde el panel izquierdo hacia este documento</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden inputs para settings -->
                <input type="hidden" id="formato-nombre" value="" required>
                <input type="hidden" id="formato-descripcion" value="">
                <input type="hidden" id="formato-margen-superior" value="20">
                <input type="hidden" id="formato-margen-inferior" value="20">
                <input type="hidden" id="formato-margen-izquierdo" value="20">
                <input type="hidden" id="formato-margen-derecho" value="20">
                <input type="hidden" id="formato-cabecera-mm" value="50">
                <input type="hidden" id="formato-tipo-documento" value="matricula">
                <input type="hidden" id="formato-tamano-lienzo" value="carta">
            </form>
        </div>
    </div>
</div>

<!-- Modal de Configuración de Componente -->
<div class="modal fade" id="modalConfigBloque" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-uppercase text-primary">Configuración del Componente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-config-bloque" onsubmit="guardarAjustesBloque(event)">
                    <input type="hidden" id="config-uid" value="">
                    
                    <!-- Configuración para Logo -->
                    <div id="config-section-logo" class="d-none">
                        <div class="mb-3">
                            <label for="config-logo-width" class="form-label small fw-bold text-uppercase d-flex justify-content-between">
                                <span>Ancho del Logo (px)</span>
                                <span id="config-logo-width-value">120px</span>
                            </label>
                            <input type="range" id="config-logo-width" class="form-range" min="80" max="240" step="5" value="120" oninput="document.getElementById('config-logo-width-value').textContent = this.value + 'px'; if (activeConfigNode) { const img = activeConfigNode.querySelector('.ares-logo-cabecera'); if (img) img.setAttribute('width', this.value); }">
                        </div>
                    </div>

                    <!-- Configuración para Título -->
                    <div id="config-section-titulo" class="d-none">
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label for="config-titulo-size" class="form-label small fw-bold text-uppercase">Tamaño (pt)</label>
                                <input type="number" id="config-titulo-size" class="input-elite" min="8" max="72" value="20" onchange="if (activeConfigNode) { const h = activeConfigNode.querySelector('.ares-titulo-cabecera, h3, h2, .ares-lema-cabecera, p, .block-content-texto'); if (h) h.style.fontSize = this.value + 'pt'; if (typeof autoAjustarAnchoBloqueTexto === 'function') autoAjustarAnchoBloqueTexto(activeConfigNode); }">
                            </div>
                            <div class="col-6 d-none">
                                <label for="config-titulo-align" class="form-label small fw-bold text-uppercase">Alineación</label>
                                <select id="config-titulo-align" class="input-elite">
                                    <option value="center" selected>Centrado</option>
                                    <option value="left">Izquierda</option>
                                    <option value="right">Derecha</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración para Metadatos -->
                    <div id="config-section-metadatos" class="d-none">
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label for="config-metadatos-size" class="form-label small fw-bold text-uppercase">Tamaño (pt)</label>
                                <input type="number" id="config-metadatos-size" class="input-elite" min="8" max="72" value="16" onchange="if (activeConfigNode) { const h = activeConfigNode.querySelector('h4'); if (h) h.style.fontSize = this.value + 'pt'; if (typeof autoAjustarAnchoBloqueTexto === 'function') autoAjustarAnchoBloqueTexto(activeConfigNode); }">
                            </div>
                            <div class="col-6 d-none">
                                <label for="config-metadatos-align" class="form-label small fw-bold text-uppercase">Alineación</label>
                                <select id="config-metadatos-align" class="input-elite">
                                    <option value="center" selected>Centrado</option>
                                    <option value="left">Izquierda</option>
                                    <option value="right">Derecha</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración para Tablas / Fichas / Calificaciones -->
                    <div id="config-section-tablas" class="d-none">
                        <div class="mb-3">
                            <label for="config-diseno" class="form-label small fw-bold text-uppercase">Diseño de la Tabla</label>
                            <select id="config-diseno" class="input-elite">
                                <option value="elite">Tabla Estilo Élite (Primario)</option>
                                <option value="minimalist">Minimalista (Bordes Delgados)</option>
                                <option value="classic">Clásico Académico</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="config-filtro" class="form-label small fw-bold text-uppercase">Filtrar Notas</label>
                            <select id="config-filtro" class="input-elite">
                                <option value="todas">Mostrar todas las asignaturas</option>
                                <option value="perdidas">Mostrar únicamente materias perdidas</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase d-block">Columnas a Mostrar</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="col-materia" value="materia" checked>
                                <label class="form-check-label small" for="col-materia">Materia</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="col-docente" value="docente" checked>
                                <label class="form-check-label small" for="col-docente">Docente</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="col-definitiva" value="definitiva" checked>
                                <label class="form-check-label small" for="col-definitiva">Nota Final</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="col-estado" value="estado" checked>
                                <label class="form-check-label small" for="col-estado">Estado</label>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración para Firmas -->
                    <div id="config-section-firmas" class="d-none">
                        <div class="mb-3">
                            <label for="config-firmas-columnas" class="form-label small fw-bold text-uppercase">Número de Firmantes</label>
                            <select id="config-firmas-columnas" class="input-elite">
                                <option value="3">3 Firmas (Estudiante, Acudiente y Rector)</option>
                                <option value="4">4 Firmas (Estudiante, Acudiente, Rector y Secretaria)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Configuración para Línea -->
                    <div id="config-section-linea" class="d-none">
                        <div class="mb-3">
                            <label for="config-linea-grosor" class="form-label small fw-bold text-uppercase d-flex justify-content-between">
                                <span>Grosor de la Línea (pt)</span>
                                <span id="config-linea-grosor-value">1.5 pt</span>
                            </label>
                            <input type="number" id="config-linea-grosor" class="form-control input-elite" min="0.5" max="12" step="0.5" value="1.5" oninput="document.getElementById('config-linea-grosor-value').textContent = this.value + ' pt'; if (activeConfigNode) { activeConfigNode.dataset.height = this.value + 'pt'; const lineaGrafica = activeConfigNode.querySelector('.ares-linea-grafica'); if (lineaGrafica) { lineaGrafica.style['height'] = this.value + 'pt'; } }">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn-elite btn-elite--outline" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-elite btn-elite--primary">Aplicar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Ajustes de Página (Formato) -->
<div class="modal fade" id="modalAjustesFormato" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-uppercase text-dark"><i class="bi bi-gear me-2 text-primary"></i>Ajustes de Página</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="modal-formato-nombre" class="form-label small fw-bold text-uppercase">Nombre de la Plantilla</label>
                    <input type="text" id="modal-formato-nombre" class="input-elite" placeholder="Ej: Carné Oficial Estudiantil" onkeyup="document.getElementById('formato-nombre').value = this.value">
                </div>
                <div class="mb-3">
                    <label for="modal-formato-descripcion" class="form-label small fw-bold text-uppercase">Descripción Corta</label>
                    <input type="text" id="modal-formato-descripcion" class="input-elite" placeholder="Propósito de la plantilla" onkeyup="document.getElementById('formato-descripcion').value = this.value">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label for="modal-formato-tipo" class="form-label small fw-bold text-uppercase">Tipo de Documento</label>
                        <select id="modal-formato-tipo" class="input-elite" onchange="document.getElementById('formato-tipo-documento').value = this.value; actualizarFiltroCatalogoContextual(this.value);">
                            <option value="matricula">Matrícula Académica</option>
                            <option value="carne">Carné Escolar (ID)</option>
                            <option value="certificado">Certificado de Curso / Estudio</option>
                            <option value="constancia">Constancia / Paz y Salvo</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label for="modal-formato-tamano" class="form-label small fw-bold text-uppercase">Tamaño de Lienzo</label>
                        <select id="modal-formato-tamano" class="input-elite" onchange="document.getElementById('formato-tamano-lienzo').value = this.value; cambiarTamanoLienzoBuilder(this.value);">
                            <option value="carta">Carta (8.5" x 11")</option>
                            <option value="media_carta">Media Carta (8.5" x 5.5")</option>
                            <option value="carne_v">Carné CR-80 Vertical (54x86mm)</option>
                            <option value="carne_h">Carné CR-80 Horizontal (86x54mm)</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-3">
                        <label for="modal-formato-margen-superior" class="form-label small fw-bold text-uppercase">Sup. (mm)</label>
                        <input type="number" id="modal-formato-margen-superior" class="input-elite" min="0" max="100" onchange="document.getElementById('formato-margen-superior').value = this.value; if(typeof actualizarZonaSeguraLienzo === 'function') actualizarZonaSeguraLienzo();">
                    </div>
                    <div class="col-3">
                        <label for="modal-formato-margen-inferior" class="form-label small fw-bold text-uppercase">Inf. (mm)</label>
                        <input type="number" id="modal-formato-margen-inferior" class="input-elite" min="0" max="100" onchange="document.getElementById('formato-margen-inferior').value = this.value; if(typeof actualizarZonaSeguraLienzo === 'function') actualizarZonaSeguraLienzo();">
                    </div>
                    <div class="col-3">
                        <label for="modal-formato-margen-izquierdo" class="form-label small fw-bold text-uppercase">Izq. (mm)</label>
                        <input type="number" id="modal-formato-margen-izquierdo" class="input-elite" min="0" max="100" onchange="document.getElementById('formato-margen-izquierdo').value = this.value; if(typeof actualizarZonaSeguraLienzo === 'function') actualizarZonaSeguraLienzo();">
                    </div>
                    <div class="col-3">
                        <label for="modal-formato-margen-derecho" class="form-label small fw-bold text-uppercase">Der. (mm)</label>
                        <input type="number" id="modal-formato-margen-derecho" class="input-elite" min="0" max="100" onchange="document.getElementById('formato-margen-derecho').value = this.value; if(typeof actualizarZonaSeguraLienzo === 'function') actualizarZonaSeguraLienzo();">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label for="modal-formato-cabecera-mm" class="form-label small fw-bold text-uppercase">Cabecera (mm)</label>
                        <input type="number" id="modal-formato-cabecera-mm" class="input-elite" min="10" max="150" value="50" onchange="document.getElementById('formato-cabecera-mm').value = this.value; if(typeof actualizarZonesUI === 'function') { actualizarZonesUI(); }">
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button type="button" class="btn-elite btn-elite--primary" data-bs-dismiss="modal">Aplicar Ajustes</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Catálogo de Campos (Pestañas Élite) -->
<div class="modal fade" id="modalCatalogoVariables" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="icon-circle-elite bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-grid-3x3-gap-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-uppercase text-dark mb-0">Catálogo de Campos Dinámicos</h5>
                        <p class="text-secondary small mb-0">Seleccione el campo que desea inyectar en el documento</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="nav nav-pills-elite mb-4" id="catalogoTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link-elite active" id="tab-expediente" data-bs-toggle="pill" data-bs-target="#panel-expediente" type="button" role="tab" aria-controls="panel-expediente" aria-selected="true">
                            <i class="bi bi-person-badge me-2"></i>Expediente del Alumno
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link-elite" id="tab-padres" data-bs-toggle="pill" data-bs-target="#panel-padres" type="button" role="tab" aria-controls="panel-padres" aria-selected="false">
                            <i class="bi bi-people-fill me-2"></i>Padres / Acudientes
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link-elite" id="tab-institucion" data-bs-toggle="pill" data-bs-target="#panel-institucion" type="button" role="tab" aria-controls="panel-institucion" aria-selected="false">
                            <i class="bi bi-building me-2"></i>Institución y Folio
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link-elite" id="tab-bloques" data-bs-toggle="pill" data-bs-target="#panel-bloques" type="button" role="tab" aria-controls="panel-bloques" aria-selected="false">
                            <i class="bi bi-box me-2"></i>Bloques y Estructuras
                        </button>
                    </li>
                </ul>
                <div class="tab-content" id="catalogoTabsContent">
                    <!-- TAB 1: EXPEDIENTE DEL ALUMNO -->
                    <div class="tab-pane fade show active" id="panel-expediente" role="tabpanel" aria-labelledby="tab-expediente">
                        <div class="ares-catalogo-grid">
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_nombre', 'Nombre Estudiante')">
                                <i class="bi bi-person-fill text-primary"></i>
                                <div><strong>Nombre Completo</strong><span>Nombre y Apellido del alumno</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_documento', 'Documento')">
                                <i class="bi bi-card-text text-primary"></i>
                                <div><strong>Documento Identidad</strong><span>N° Identificación oficial</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_tipo_documento', 'Tipo Doc.')">
                                <i class="bi bi-journal-text text-primary"></i>
                                <div><strong>Tipo Documento</strong><span>TI, CC, RC, etc.</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_rh', 'RH / Sangre')">
                                <i class="bi bi-droplet-fill text-danger"></i>
                                <div><strong>Grupo Sanguíneo / RH</strong><span>Factor RH del alumno</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_genero', 'Género')">
                                <i class="bi bi-gender-ambiguous text-info"></i>
                                <div><strong>Género / Sexo</strong><span>Masculino / Femenino</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_celular', 'Celular')">
                                <i class="bi bi-telephone-fill text-success"></i>
                                <div><strong>Teléfono / Celular</strong><span>Contacto directo del alumno</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_email', 'Correo')">
                                <i class="bi bi-envelope-fill text-warning"></i>
                                <div><strong>Correo Electrónico</strong><span>Email personal alumno</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_fecha_nacimiento', 'F. Nacimiento')">
                                <i class="bi bi-calendar-date text-primary"></i>
                                <div><strong>Fecha Nacimiento</strong><span>Día / Mes / Año nacimiento</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_edad', 'Edad Alumno')">
                                <i class="bi bi-calendar-event text-secondary"></i>
                                <div><strong>Edad Calculada</strong><span>Edad actual en años</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_lugar_nacimiento', 'Lugar Nac.')">
                                <i class="bi bi-geo-alt text-danger"></i>
                                <div><strong>Lugar Nacimiento</strong><span>Municipio / Ciudad orig.</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_nacionalidad', 'Nacionalidad Alum.')">
                                <i class="bi bi-globe text-info"></i>
                                <div><strong>Nacionalidad</strong><span>País de origen</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_colegio_anterior', 'Colegio Ant.')">
                                <i class="bi bi-mortarboard text-primary"></i>
                                <div><strong>Colegio Anterior</strong><span>Institución de procedencia</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_direccion', 'Dir. Estudiante')">
                                <i class="bi bi-house text-success"></i>
                                <div><strong>Dirección Residencia</strong><span>Ubicación domiciliaria</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_foto', 'Foto Estudiante')">
                                <i class="bi bi-person-bounding-box text-primary"></i>
                                <div><strong>Fotografía Estudiante</strong><span>Foto digital 3x4 del alumno</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('estudiante_folio', 'Folio Matrícula')">
                                <i class="bi bi-file-earmark-binary text-dark"></i>
                                <div><strong>Número Folio</strong><span>N° Folio libro matrícula</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: PADRES Y ACUDIENTES -->
                    <div class="tab-pane fade" id="panel-padres" role="tabpanel" aria-labelledby="tab-padres">
                        <div class="ares-catalogo-grid">
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('padre_nombre', 'Nombre Padre')">
                                <i class="bi bi-person-badge text-primary"></i>
                                <div><strong>Nombre del Padre</strong><span>Nombre completo padre</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('padre_documento', 'Doc. Padre')">
                                <i class="bi bi-card-heading text-primary"></i>
                                <div><strong>Documento Padre</strong><span>Cédula de ciudadanía padre</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('padre_documento_expedicion', 'Exp. Doc. Padre')">
                                <i class="bi bi-geo-alt text-primary"></i>
                                <div><strong>Lugar Exp. Padre</strong><span>Lugar expedición cédula padre</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('padre_celular', 'Celular Padre')">
                                <i class="bi bi-phone text-success"></i>
                                <div><strong>Celular Padre</strong><span>Teléfono móvil del padre</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('padre_profesion', 'Profesión Padre')">
                                <i class="bi bi-briefcase text-secondary"></i>
                                <div><strong>Profesión / Ocupación</strong><span>Oficio o labor del padre</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('padre_email', 'Email Padre')">
                                <i class="bi bi-envelope text-warning"></i>
                                <div><strong>Correo Padre</strong><span>Email de contacto padre</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('madre_nombre', 'Nombre Madre')">
                                <i class="bi bi-person-badge-fill text-danger"></i>
                                <div><strong>Nombre de la Madre</strong><span>Nombre completo madre</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('madre_documento', 'Doc. Madre')">
                                <i class="bi bi-card-heading text-danger"></i>
                                <div><strong>Documento Madre</strong><span>Cédula de ciudadanía madre</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('madre_documento_expedicion', 'Exp. Doc. Madre')">
                                <i class="bi bi-geo-alt-fill text-danger"></i>
                                <div><strong>Lugar Exp. Madre</strong><span>Lugar expedición cédula madre</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('madre_celular', 'Celular Madre')">
                                <i class="bi bi-phone-fill text-success"></i>
                                <div><strong>Celular Madre</strong><span>Teléfono móvil de la madre</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('madre_profesion', 'Profesión Madre')">
                                <i class="bi bi-briefcase-fill text-secondary"></i>
                                <div><strong>Profesión / Ocupación</strong><span>Oficio o labor de la madre</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('madre_email', 'Email Madre')">
                                <i class="bi bi-envelope-fill text-warning"></i>
                                <div><strong>Correo Madre</strong><span>Email de contacto madre</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: INSTITUCIÓN Y FOLIO -->
                    <div class="tab-pane fade" id="panel-institucion" role="tabpanel" aria-labelledby="tab-institucion">
                        <div class="ares-catalogo-grid">
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('rector_nombre', 'Rector')">
                                <i class="bi bi-award-fill text-primary"></i>
                                <div><strong>Nombre del Rector(a)</strong><span>Representante legal</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('colegio_nit', 'NIT Colegio')">
                                <i class="bi bi-file-earmark-ruled text-danger"></i>
                                <div><strong>NIT del Colegio</strong><span>Registro Tributario</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('colegio_resolucion', 'Resolución Colegio')">
                                <i class="bi bi-patch-check text-success"></i>
                                <div><strong>Resolución Oficial</strong><span>Licencia de funcionamiento</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('logo', 'Logo Colegio', true)">
                                <i class="bi bi-file-image text-info"></i>
                                <div><strong>Logo Institución</strong><span>Insignia o escudo escolar</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('titulo_colegio', 'Nombre del Colegio', true)">
                                <i class="bi bi-building text-success"></i>
                                <div><strong>Nombre del Colegio</strong><span>Encabezado institucional con formato</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('lema_colegio', 'Lema Institucional', true)">
                                <i class="bi bi-quote text-warning"></i>
                                <div><strong>Lema Institucional</strong><span>Slogan del plantel, con formato</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('qr_estudiante', 'Código QR Validación', true)">
                                <i class="bi bi-qr-code-scan text-dark"></i>
                                <div><strong>Código QR Institucional</strong><span>Validación de carné / expediente</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: BLOQUES Y ESTRUCTURAS -->
                    <div class="tab-pane fade" id="panel-bloques" role="tabpanel" aria-labelledby="tab-bloques">
                        <div class="ares-catalogo-grid">
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('curso_asignado', 'Curso Asignado')">
                                <i class="bi bi-book text-info"></i>
                                <div><strong>Curso / Grado</strong><span>Grado de matriculación</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('jornada_escolar', 'Jornada')">
                                <i class="bi bi-clock text-info"></i>
                                <div><strong>Jornada Escolar</strong><span>Jornada del grupo (Mañana/Tarde/Noche)</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('fecha_registro', 'Fecha Registro')">
                                <i class="bi bi-calendar-event text-primary"></i>
                                <div><strong>Fecha Registro (Fija)</strong><span>Fecha matrícula inamovible</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('fecha_impresion', 'Fecha Impresión')">
                                <i class="bi bi-calendar-check text-success"></i>
                                <div><strong>Fecha Impresión (Actual)</strong><span>Fecha del día de hoy</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('anio_lectivo', 'Año Lectivo')">
                                <i class="bi bi-calendar3 text-warning"></i>
                                <div><strong>Año Lectivo</strong><span>Solo año de la matrícula</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('texto', 'Párrafo de Texto', true)">
                                <i class="bi bi-textarea-t text-primary"></i>
                                <div><strong>Párrafo de Texto</strong><span>Cuadro de texto libre</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('foto_estudiante', 'Foto Estudiante', true)">
                                <i class="bi bi-person-square text-primary"></i>
                                <div><strong>Foto del Estudiante</strong><span>Bloque de foto 3x4 oficial</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('texto_certificacion', 'Texto Certificación', true)">
                                <i class="bi bi-file-earmark-check text-info"></i>
                                <div><strong>Bloque Certificación</strong><span>Redacción oficial de certificado</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('metadatos', 'Metadatos Formato', true)">
                                <i class="bi bi-card-text text-secondary"></i>
                                <div><strong>Metadatos Formato</strong><span>Cabecera técnica formato</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('ficha', 'Ficha Estudiante', true)">
                                <i class="bi bi-table text-primary"></i>
                                <div><strong>Ficha Estudiante</strong><span>Tabla filiatoria completa</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('calificaciones', 'Tabla Notas', true)">
                                <i class="bi bi-card-checklist text-danger"></i>
                                <div><strong>Tabla de Notas</strong><span>Cuadro calificaciones</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('firmas', 'Sección Firmas', true)">
                                <i class="bi bi-pen text-dark"></i>
                                <div><strong>Sección Firmas</strong><span>Firmas rector y acudiente</span></div>
                            </div>
                            <div class="ares-var-card" onclick="seleccionarVariableCatalogo('linea', 'Línea Divisoria', true)">
                                <i class="bi bi-dash-lg text-primary"></i>
                                <div><strong>Línea Divisoria</strong><span>Línea gráfica decorativa</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex justify-content-between align-items-center">
                <button type="button" class="btn-elite btn-elite--ghost btn-sm" onclick="abrirReferenciaCatalogo()">
                    <i class="bi bi-journal-code me-1"></i> Ver Referencia Completa de Claves
                </button>
                <button type="button" class="btn-elite btn-elite--primary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Referencia Completa de Claves -->
<div class="modal fade" id="modalReferenciaCatalogo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="icon-circle-elite bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-journal-code fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-uppercase text-dark mb-0">Referencia Oficial de Variables</h5>
                        <p class="text-secondary small mb-0">Listado completo de claves disponibles para usar en plantillas y contratos</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- GRUPO: INSTITUCIN -->
                <h6 class="fw-bold text-uppercase text-info border-bottom pb-1 mb-3"><i class="bi bi-building me-2"></i>Institucional</h6>
                <table class="tabla-referencia-elite mb-4">
                    <thead><tr><th>Clave</th><th>Nombre</th><th>Uso / Descripción</th></tr></thead>
                    <tbody>
                        <tr><td><code>[colegio_nombre]</code></td><td>Nombre del Colegio</td><td>Nombre oficial de la institución educativa</td></tr>
                        <tr><td><code>[colegio_lema]</code></td><td>Lema Institucional</td><td>Slogan o frase representativa del plantel</td></tr>
                        <tr><td><code>[colegio_nit]</code></td><td>NIT del Colegio</td><td>Número de Identificación Tributaria del colegio</td></tr>
                        <tr><td><code>[colegio_resolucion]</code></td><td>Resolución Oficial</td><td>Número de licencia o resolución de funcionamiento</td></tr>
                    </tbody>
                </table>
                <!-- GRUPO: ESTUDIANTE -->
                <h6 class="fw-bold text-uppercase text-primary border-bottom pb-1 mb-3"><i class="bi bi-person-badge me-2"></i>Estudiante</h6>
                <table class="tabla-referencia-elite mb-4">
                    <thead><tr><th>Clave</th><th>Nombre</th><th>Uso / Descripción</th></tr></thead>
                    <tbody>
                        <tr><td><code>[estudiante_nombre]</code></td><td>Nombre Completo</td><td>Nombre y apellido del estudiante matriculado</td></tr>
                        <tr><td><code>[estudiante_documento]</code></td><td>Número Documento</td><td>Número de identificación oficial del alumno</td></tr>
                        <tr><td><code>[estudiante_tipo_documento]</code></td><td>Tipo Documento</td><td>TI, CC, RC, CE u otro tipo de documento</td></tr>
                        <tr><td><code>[estudiante_rh]</code></td><td>Grupo Sanguíneo / RH</td><td>Factor RH del estudiante (ej: O+, A-)</td></tr>
                        <tr><td><code>[estudiante_genero]</code></td><td>Género</td><td>Género del alumno (Masculino / Femenino)</td></tr>
                        <tr><td><code>[estudiante_email]</code></td><td>Correo Electrónico</td><td>Email de contacto del estudiante</td></tr>
                        <tr><td><code>[estudiante_celular]</code></td><td>Teléfono / Celular</td><td>Número móvil del estudiante</td></tr>
                        <tr><td><code>[estudiante_direccion]</code></td><td>Dirección Residencia</td><td>Dirección domiciliaria del alumno</td></tr>
                        <tr><td><code>[estudiante_folio]</code></td><td>Folio de Matrícula</td><td>Código único de folio (ej: 2026-0001)</td></tr>
                    </tbody>
                </table>
                <!-- GRUPO: PADRE -->
                <h6 class="fw-bold text-uppercase text-success border-bottom pb-1 mb-3"><i class="bi bi-people-fill me-2"></i>Padre / Acudiente</h6>
                <table class="tabla-referencia-elite mb-4">
                    <thead><tr><th>Clave</th><th>Nombre</th><th>Uso / Descripción</th></tr></thead>
                    <tbody>
                        <tr><td><code>[padre_nombre]</code></td><td>Nombre del Padre</td><td>Nombre completo del padre o acudiente</td></tr>
                        <tr><td><code>[padre_documento]</code></td><td>Documento del Padre</td><td>Cédula de ciudadanía del padre</td></tr>
                        <tr><td><code>[padre_documento_expedicion]</code></td><td>Lugar Exp. Padre</td><td>Municipio o lugar de expedición del documento del padre</td></tr>
                        <tr><td><code>[padre_celular]</code></td><td>Celular del Padre</td><td>Teléfono móvil del padre</td></tr>
                        <tr><td><code>[padre_email]</code></td><td>Correo del Padre</td><td>Email de contacto del padre</td></tr>
                    </tbody>
                </table>
                <!-- GRUPO: MADRE -->
                <h6 class="fw-bold text-uppercase text-danger border-bottom pb-1 mb-3"><i class="bi bi-people-fill me-2"></i>Madre / Acudiente</h6>
                <table class="tabla-referencia-elite mb-4">
                    <thead><tr><th>Clave</th><th>Nombre</th><th>Uso / Descripción</th></tr></thead>
                    <tbody>
                        <tr><td><code>[madre_nombre]</code></td><td>Nombre de la Madre</td><td>Nombre completo de la madre o acudiente</td></tr>
                        <tr><td><code>[madre_documento]</code></td><td>Documento de la Madre</td><td>Cédula de ciudadanía de la madre</td></tr>
                        <tr><td><code>[madre_documento_expedicion]</code></td><td>Lugar Exp. Madre</td><td>Municipio o lugar de expedición del documento de la madre</td></tr>
                        <tr><td><code>[madre_celular]</code></td><td>Celular de la Madre</td><td>Teléfono móvil de la madre</td></tr>
                        <tr><td><code>[madre_email]</code></td><td>Correo de la Madre</td><td>Email de contacto de la madre</td></tr>
                    </tbody>
                </table>
                <!-- GRUPO: ACADMICO -->
                <h6 class="fw-bold text-uppercase text-warning border-bottom pb-1 mb-3"><i class="bi bi-mortarboard-fill me-2"></i>Académico</h6>
                <table class="tabla-referencia-elite mb-2">
                    <thead><tr><th>Clave</th><th>Nombre</th><th>Uso / Descripción</th></tr></thead>
                    <tbody>
                        <tr><td><code>[curso_asignado]</code></td><td>Curso / Grado</td><td>Grado o sección en que el alumno fue matriculado</td></tr>
                        <tr><td><code>[jornada_escolar]</code></td><td>Jornada Escolar</td><td>Jornada del grupo o curso (Mañana / Tarde / Noche / Única)</td></tr>
                        <tr><td><code>[fecha_registro]</code></td><td>Fecha Registro (Inamovible)</td><td>Fecha real en que el estudiante se matriculó originalmente (dd/mm/aaaa)</td></tr>
                        <tr><td><code>[fecha_impresion]</code></td><td>Fecha Impresión (Actual)</td><td>Fecha del día de hoy (momento de la impresión) (dd/mm/aaaa)</td></tr>
                        <tr><td><code>[anio_lectivo]</code></td><td>Año Lectivo</td><td>Año escolar correspondiente extraído de la fecha de registro (aaaa)</td></tr>
                        <tr><td><code>[rector_nombre]</code></td><td>Nombre del Rector(a)</td><td>Nombre del representante legal de la institución</td></tr>
                    </tbody>
                </table>
                <div class="nota-uso-elite d-flex align-items-start gap-2 mt-3 p-3 rounded-3">
                    <i class="bi bi-info-circle-fill fs-5 mt-1 flex-shrink-0 text-primary"></i>
                    <div class="small">
                        <strong>Nota de uso:</strong> Puede escribir las claves directamente entre corchetes en cualquier bloque de texto de la plantilla (ej: <code>[colegio_nombre]</code>) y el sistema las reemplazará automáticamente al imprimir. Si la clave no existe en el sistema, se conservará literalmente en el documento.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-elite btn-elite--primary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- Scripts de Lógica del Editor y AJAX -->


<script>
    document.documentElement.style.setProperty('--el-font-institutional', "'<?php echo htmlspecialchars($cfg['school_font'] ?? 'Montserrat', ENT_QUOTES, 'UTF-8'); ?>', sans-serif");
    window.SCHOOL_INFO = {
        name: <?php echo json_encode($school_name); ?>,
        motto: <?php echo json_encode($school_motto); ?>,
        logo: <?php echo json_encode($school_logo); ?>,
        nit: <?php echo json_encode($cfg['colegio_nit'] ?? ''); ?>,
        resolucion: <?php echo json_encode($cfg['colegio_resolucion'] ?? ''); ?>
    };
    window.BLOCK_CONFIG = <?php
        require_once __DIR__ . '/../logica/formatos_bloques_config.php';
        echo json_encode(obtenerConfiguracionBloques($cfg));
    ?>;
</script>
<script src="../js/modules/formatos_matricula_builder.js?v=<?php echo time(); ?>"></script>
<script src="../js/formatos_matricula.js?v=<?php echo time(); ?>" defer></script>




