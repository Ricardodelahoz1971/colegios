<?php
declare(strict_types=1);
// PHP/VISTAS/AULA_VIRTUAL_GESTION.PHP - CENTRO DE RECURSOS v1.0
if (!tiene_permiso('aula_virtual')) {
    echo "<div class='container py-5 text-center'><h4 class='text-danger fw-bold'>ACCESO DENEGADO</h4></div>";
    return;
}

$mi_id = (int)$_SESSION['usuario_id'];
$ver_todo = tiene_permiso('matricula') || tiene_permiso('personal');

// Obtener Especialidades y Cursos del Docente
if ($ver_todo) {
    $stmt_especialidades = $db->prepare("SELECT id, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC"); $stmt_especialidades->execute(); $especialidades = $stmt_especialidades->fetchAll();
    $stmt_cursos = $db->prepare("SELECT id, nombre_curso FROM cursos ORDER BY nombre_curso ASC"); $stmt_cursos->execute(); $cursos = $stmt_cursos->fetchAll();
} else {
    $sql_e = "SELECT DISTINCT e.id, e.nombre_especialidad FROM especialidades e JOIN carga_academica ca ON e.id = ca.especialidad_id WHERE ca.docente_id = ? ORDER BY e.nombre_especialidad ASC";
    $stmt_e = $db->prepare($sql_e);
    $stmt_e->execute([$mi_id]);
    $especialidades = $stmt_e->fetchAll();

    $sql_c = "SELECT DISTINCT c.id, c.nombre_curso FROM cursos c JOIN carga_academica ca ON c.id = ca.curso_id WHERE ca.docente_id = ? ORDER BY c.nombre_curso ASC";
    $stmt_c = $db->prepare($sql_c);
    $stmt_c->execute([$mi_id]);
    $cursos = $stmt_c->fetchAll();
}
?>

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    <!-- MIGA DE PAN (BREADCRUMB ELITE) -->
    <div class="px-3 mb-3">
        <nav class="breadcrumb-elite" aria-label="Ruta de navegación">
            <a href="javascript:void(0)" onclick="navegarModulo('inicio')" class="breadcrumb-link-elite"><i class="bi bi-house-door me-1"></i>Inicio</a>
            <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
            <span class="breadcrumb-item-elite">Académico</span>
            <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
            <span class="breadcrumb-current-elite">Aula Virtual</span>
        </nav>
    </div>

    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 border-start border-4 border-primary ps-4">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">Aula Virtual</h4>
            <p class="text-secondary mb-0 small">Gestión institucional de recursos didácticos y materiales de apoyo.</p>
        </div>
        <div class="col-md-5 d-flex justify-content-md-end gap-2">
            <button class="btn-elite btn-elite--primary px-4" onclick="abrirModalRecurso()">
                <i class="bi bi-cloud-plus-fill me-2"></i>NUEVO RECURSO
            </button>
        </div>
    </div>

    <!-- PANEL DE FILTROS -->
    <div class="card card-elite card-elite--filters mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="filtro-materia" class="form-label fw-bold text-uppercase fs-nano text-muted">Materia / Especialidad</label>
                    <select id="filtro-materia" class="select-elite" onchange="cargarRecursos()">
                        <option value="0">Todas las Materias</option>
                        <?php foreach($especialidades as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre_especialidad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filtro-curso" class="form-label fw-bold text-uppercase fs-nano text-muted">Curso / Grupo</label>
                    <select id="filtro-curso" class="select-elite" onchange="cargarRecursos()">
                        <option value="0">Todos los Cursos</option>
                        <?php foreach($cursos as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre_curso']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- REJILLA DE RECURSOS -->
    <div id="grid-recursos" class="row g-4">
        <!-- Cargado vía AJAX -->
    </div>

    <!-- ESTADO VACÍO -->
    <div id="estado-vacio-aula" class="text-center py-5 d-none">
        <div class="display-1 text-muted opacity-25 mb-4">
            <i class="bi bi-folder-x"></i>
        </div>
        <h5 class="fw-bold text-muted">No se encontraron recursos</h5>
        <p class="text-secondary small">Inicie cargando materiales para sus estudiantes.</p>
    </div>
</div>

<!-- MODAL: GESTIÓN DE RECURSO -->
<div class="modal fade" id="modalRecurso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-elite border-0">
            <div class="modal-header border-bottom p-4">
                <h5 class="modal-title fw-bold" id="titulo-modal-recurso">NUEVO RECURSO DIDÁCTICO</h5>
            </div>
            <div class="modal-body p-4">
                <form id="form-recurso-aula" method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" id="recurso-id" name="id" value="0">
                    
                    <div class="mb-3">
                        <label for="titulo" class="form-label fw-bold text-uppercase fs-nano text-muted d-block mb-2">Título del Material</label>
                        <input type="text" id="titulo" name="titulo" class="input-elite" placeholder="Ej: Guía de Algoritmos v1" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="especialidad_id" class="form-label fw-bold text-uppercase fs-nano text-muted d-block mb-2">Materia</label>
                            <select id="especialidad_id" name="especialidad_id" class="select-elite w-100" required>
                                <option value="">Seleccione...</option>
                                <?php foreach($especialidades as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre_especialidad']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="curso_id" class="form-label fw-bold text-uppercase fs-nano text-muted d-block mb-2">Curso Destino</label>
                            <select id="curso_id" name="curso_id" class="select-elite w-100" required>
                                <option value="">Seleccione...</option>
                                <?php foreach($cursos as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre_curso']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="tipo_recurso" class="form-label fw-bold text-uppercase fs-nano text-muted d-block mb-2">Tipo de Recurso</label>
                            <select id="tipo_recurso" name="tipo_recurso" class="select-elite w-100" onchange="toggleInputsRecurso()" required>
                                <option value="PDF">Documento PDF</option>
                                <option value="LINK">Enlace Externo</option>
                                <option value="VIDEO">Video / Multimedia</option>
                                <option value="DOC">Archivo Editable</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="wrapper-url">
                            <label for="url_recurso" class="form-label fw-bold text-uppercase fs-nano text-muted d-block mb-2">URL / Enlace</label>
                            <input type="text" id="url_recurso" name="url_recurso" class="input-elite" placeholder="https:// o ruta local...">
                        </div>
                        <div class="col-md-6 d-none" id="wrapper-archivo">
                            <label for="archivo_recurso" class="form-label fw-bold text-uppercase fs-nano text-muted d-block mb-2">Seleccionar Archivo</label>
                            <input type="file" id="archivo_recurso" name="archivo_recurso" class="input-elite" accept=".pdf,.doc,.docx">
                        </div>
                    </div>

                    <div class="mb-3 d-none" id="wrapper-evaluativo">
                        <label class="form-label fw-bold text-uppercase fs-nano text-muted d-block mb-2">Evaluación y Calificaciones</label>
                        <label class="form-switch-elite" for="es_evaluativo">
                            <div class="form-switch-elite__container">
                                <input type="checkbox" id="es_evaluativo" name="es_evaluativo" value="1" class="form-switch-elite__input" onchange="toggleAulaAmbitoSelector()">
                                <span class="form-switch-elite__slider"></span>
                            </div>
                            <span class="form-switch-elite__label">
                                <span class="form-switch-elite__title">¿Actividad Calificable?</span>
                                <span class="form-switch-elite__subtitle">Crea una columna en caliente en el calificador.</span>
                            </span>
                        </label>
                        
                        <!-- Radios de Ámbito de Aula Virtual -->
                        <div class="mt-3 d-none" id="aula-ambito-container">
                            <label class="form-label fs-nano text-muted fw-bold text-uppercase mb-2 d-block">PROPÓSITO DE LA ACTIVIDAD</label>
                            <div class="mb-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="aula_ambito" id="aula-ambito-estandar" value="estandar" checked onchange="toggleAulaAmbitoRecuperacion()">
                                    <label class="form-check-label text-dark fs-nano fw-bold cursor-pointer" for="aula-ambito-estandar">
                                        ESTÁNDAR
                                    </label>
                                </div>
                                <div class="form-check form-check-inline ms-4">
                                    <input class="form-check-input" type="radio" name="aula_ambito" id="aula-ambito-recuperacion" value="recuperacion" onchange="toggleAulaAmbitoRecuperacion()">
                                    <label class="form-check-label text-dark fs-nano fw-bold cursor-pointer" for="aula-ambito-recuperacion">
                                        RECUPERACIÓN
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Selector dinámico de origen con data-ares-ignore="true" -->
                            <div class="mb-3 d-none" id="aula-contenedor-actividad-origen">
                                <label for="aula-act-origen" class="form-label fs-nano text-muted fw-bold text-uppercase">Actividad de Origen a Recuperar</label>
                                <select id="aula-act-origen" name="recupera_actividad_id" class="select-elite">
                                    <option value="">[Seleccionar actividad...]</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="fecha_inicio" class="form-label fw-bold text-uppercase fs-nano text-muted d-block mb-2">Fecha de Publicación</label>
                            <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" class="input-elite">
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_fin" class="form-label fw-bold text-uppercase fs-nano text-muted d-block mb-2">Fecha de Cierre (Opcional)</label>
                            <input type="datetime-local" id="fecha_fin" name="fecha_fin" class="input-elite">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="descripcion" class="form-label fw-bold text-uppercase fs-nano text-muted">Descripción Breve</label>
                        <textarea id="descripcion" name="descripcion" class="input-elite" rows="3" placeholder="Instrucciones para el estudiante..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <button type="button" class="btn-elite btn-elite--outline" data-bs-dismiss="modal">CANCELAR</button>
                        <button type="submit" id="btn-guardar-aula" class="btn-elite btn-elite--primary px-4">GUARDAR RECURSO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../js/aula_virtual_gestion.js" defer></script>
<?php /* MÓDULO PURIFICADO - CSS MIGRADO A /styles/modules/aula_virtual_gestion.css */ ?>
