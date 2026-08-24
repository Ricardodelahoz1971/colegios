<?php
declare(strict_types=1);
// PHP/VISTAS/CONSTRUCTOR_PRUEBAS.PHP - ENSAMBLADOR DE EVALUACIONES v1.0
if (!tiene_permiso('evaluacion')) {
    echo "<div class='alert-soberania-elite animate__animated animate__headShake'>
            <div class='alert-soberania-icon'><i class='bi bi-shield-lock-fill'></i></div>
            <div class='alert-soberania-content'>
                <h4>Acceso Restringido</h4>
                <p>El constructor de pruebas requiere permisos de alta gestión académica.</p>
            </div>
          </div>";
    return;
}

// Carga de Materias
$mi_id = (int)$_SESSION['usuario_id'];
$stmt_m = $db->prepare("SELECT DISTINCT e.id, e.nombre_especialidad 
                        FROM especialidades e 
                        JOIN carga_academica ca ON e.id = ca.especialidad_id 
                        WHERE ca.docente_id = ? 
                        ORDER BY e.nombre_especialidad ASC");
$stmt_m->execute([$mi_id]);
$materias = $stmt_m->fetchAll();
if (tienen_rol(['Administrador', 'Coordinador'])) {
    $stmt_materias = $db->prepare("SELECT id, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC"); $stmt_materias->execute(); $materias = $stmt_materias->fetchAll();
}
?>

<!-- SortableJS Assets (Cargado en Dashboard) -->

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    <div class="row g-4" id="view-lista-pruebas">
        <!-- CABECERA DE MÓDULO -->
    <!-- CABECERA DE MÓDULO (Soberanía Élite) -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 header-module-elite mb-0">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">ENSAMBLADOR DE PRUEBAS</h4>
            <p class="subtitle-elite">Gestione y ensamble los instrumentos de evaluación institucional.</p>
        
            <nav class="breadcrumb-elite" aria-label="Ruta de navegación">
                <a href="javascript:void(0)" onclick="navegarModulo('inicio')" class="breadcrumb-link-elite"><i class="bi bi-house-door me-1"></i>Inicio</a>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-item-elite">Evaluación</span>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-current-elite">Creador de Exámenes</span>
            </nav></div>
        <div class="col-md-5 text-end">
            <button onclick="abrirConstructor()" class="btn-elite px-4">
                <i class="bi bi-plus-lg me-2"></i> NUEVA PRUEBA
            </button>
        </div>
    </div>

        <!-- LISTADO DE PRUEBAS EXISTENTES -->
        <div class="col-12">
            <div class="card card-elite border-0 shadow-sm overflow-hidden">
                <div class="table-responsive">
                    <table class="table-elite table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4 py-3 fs-nano text-uppercase fw-800">ID</th>
                                <th class="py-3 fs-nano text-uppercase fw-800">Título de la Prueba</th>
                                <th class="py-3 fs-nano text-uppercase fw-800">Materia</th>
                                <th class="py-3 fs-nano text-uppercase fw-800 text-center">Reactivos</th>
                                <th class="py-3 fs-nano text-uppercase fw-800 text-center">Tiempo</th>
                                <th class="py-3 fs-nano text-uppercase fw-800 text-center">Modalidad</th>
                                <th class="py-3 fs-nano text-uppercase fw-800 text-center">Estado</th>
                                <th class="pe-4 py-3 fs-nano text-uppercase fw-800 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="lista-pruebas-ares">
                            <!-- Carga AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODO CONSTRUCTOR (OCULTO POR DEFECTO) -->
    <div class="row g-4 d-none" id="view-constructor-ares">
        <div class="col-12">
            <div class="card card-elite border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <button class="btn-elite btn-elite--sm" onclick="volverALista()">
                        <i class="bi bi-arrow-left me-2"></i> VOLVER AL LISTADO
                    </button>
                    <div id="status-constructor">
                        <span class="badge-elite badge-elite--info">MODO CONSTRUCTOR</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-elite border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom p-4">
                    <h5 class="fw-bold mb-0 text-dark text-uppercase fs-nano">1. Identidad de la Prueba</h5>
                </div>
                    <form id="form-info-prueba" onsubmit="guardarCabeceraPrueba(event); return false;">
                        <input type="hidden" id="construct-prueba-id" value="0">
                        <div class="mb-3">
                            <label class="form-label fs-nano text-muted fw-bold text-uppercase">Título Institucional</label>
                            <input type="text" id="construct-titulo" class="input-elite" placeholder="ej: Primer Parcial de Física" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-nano text-muted fw-bold text-uppercase">Materia</label>
                            <select id="construct-materia" class="select-elite" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($materias as $m): ?>
                                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nombre_especialidad']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-nano text-muted fw-bold text-uppercase">Modalidad de Aplicación</label>
                            <select id="construct-modalidad" class="select-elite" required>
                                <option value="1">PLATAFORMA DIGITAL (Ares Online)</option>
                                <option value="2">EXAMEN FÍSICO (Híbrido / QR)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-nano text-muted fw-bold text-uppercase">Tiempo Límite (Minutos)</label>
                            <input type="number" id="construct-tiempo" class="input-elite" value="60" min="5" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-nano text-muted fw-bold text-uppercase">Instrucciones Generales</label>
                            <textarea id="construct-instrucciones" class="input-elite u-h-auto" rows="4" placeholder="Normas de la prueba..."></textarea>
                        </div>
                        <button type="submit" class="btn-elite w-100 mt-2 shadow-sm">
                            <i class="bi bi-save me-2"></i> GUARDAR CABECERA
                        </button>
                    </form>
                </div>
            </div>

            <!-- BUSCADOR DE REACTIVOS -->
            <div id="panel-reactivos-banco" class="card card-elite border-0 shadow-sm d-none">
                <div class="card-header bg-white border-bottom p-4 ares-sticky-header">
                    <h5 class="fw-bold mb-0 text-dark text-uppercase fs-nano">2. Banco de Reactivos</h5>
                </div>
                <div class="card-body p-3">
                    <div class="search-wrapper-elite mb-3">
                        <span class="search-icon-elite-fixed"><i class="bi bi-search"></i></span>
                        <input type="text" id="buscar-banco-construct" class="search-elite--wrapped" placeholder="Buscar reactivo..." onkeyup="filtrarBancoConstruct()">
                    </div>
                    <div id="banco-disponible" class="list-group list-group-flush">
                        <!-- Carga dinámica -->
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card card-elite border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark text-uppercase fs-nano">3. Lienzo de la Prueba (Estructura)</h5>
                    <div class="text-end">
                        <span class="text-muted small">Puntaje Total:</span>
                        <span class="fw-bold text-primary fs-5" id="total-peso">0.0</span> <span class="text-primary fw-bold">PTS</span>
                    </div>
                </div>
                <div class="card-body p-4 bg-light bg-opacity-50">
                    <div id="lienzo-prueba" class="list-group lienzo-drag-area">
                        <!-- Aquí caen las preguntas -->
                        <div class="text-center py-5 text-muted empty-lienzo">
                            <i class="bi bi-plus-circle-dotted fs-1 mb-3"></i>
                            <p>Arrastre reactivos aquí o haga clic en <i class="bi bi-plus"></i> en el banco.</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white p-4 border-top">
                    <button class="btn-elite px-5 shadow-sm" onclick="guardarEstructura()">
                        <i class="bi bi-check2-all me-2"></i> FINALIZAR ENSAMBLAJE
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="../js/constructor_pruebas.js" defer></script>
