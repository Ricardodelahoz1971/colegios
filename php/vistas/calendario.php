<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/VISTAS/CALENDARIO.PHP - CRONOGRAMA INSTITUCIONAL ÉLITE v1.1
// Blindaje contra acceso directo
if (!isset($db)) {
    header("Location: ../dashboard.php?p=calendario");
    exit;
}

if (!function_exists('tiene_permiso') || !tiene_permiso('cronograma')) {
    echo "<div class='container py-5 text-center'><h4 class='text-danger fw-bold'>ACCESO DENEGADO</h4><p>No tienes privilegios para ver el cronograma.</p></div>";
    return;
}

$es_admin_cron = tiene_permiso('matricula') || tiene_permiso('personal');
?>

<div class="container-fluid py-4" id="calendario-container" data-es-admin-cron="<?php echo $es_admin_cron ? 'true' : 'false'; ?>" data-csrf-token="<?php echo $_SESSION['csrf_token']; ?>">
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 text-center text-md-start border-start border-4 border-topbar-elite ps-4">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">Cronograma Institucional</h4>
            <p class="text-secondary mb-0 small">Seguimiento de eventos, festivos y actividades especiales.</p>
            <nav class="breadcrumb-elite" aria-label="Ruta de navegación">
                <a href="javascript:void(0)" onclick="navegarModulo('inicio')" class="breadcrumb-link-elite"><i class="bi bi-house-door me-1"></i>Inicio</a>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-item-elite">Agenda</span>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-current-elite">Cronograma Escolar</span>
            </nav>
        </div>
        <div class="col-md-5 text-end d-flex justify-content-end align-items-center gap-2">
            <select id="month-selector-label" class="select-elite w-fixed-140" onchange="saltarAMesElite(this.value)">
                <option value="0">Enero</option>
                <option value="1">Febrero</option>
                <option value="2">Marzo</option>
                <option value="3">Abril</option>
                <option value="4">Mayo</option>
                <option value="5">Junio</option>
                <option value="6">Julio</option>
                <option value="7">Agosto</option>
                <option value="8">Septiembre</option>
                <option value="9">Octubre</option>
                <option value="10">Noviembre</option>
                <option value="11">Diciembre</option>
            </select>
            <?php if ($es_admin_cron): ?>
                <button onclick="nuevoEvento()" class="btn-elite btn-elite--sm">
                    <i class="bi bi-calendar-plus me-2"></i>Evento
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-lg border-0 rounded-4 overflow-hidden glass-card-elite">
        <div class="calendar-header-elite p-4 d-flex justify-content-between align-items-center shadow">
            <button class="btn btn-link p-0 calendar-btn-nav-elite" onclick="prevMonth()"><i class="bi bi-chevron-left fs-4"></i></button>
            <div class="text-center">
                <h4 id="calendar-month-year" class="mb-0 fw-bold text-uppercase tracking-wider">Mes Año</h4>
                <small class="opacity-75 fw-medium">Cronograma de Actividades</small>
            </div>
            <button class="btn btn-link p-0 calendar-btn-nav-elite" onclick="nextMonth()"><i class="bi bi-chevron-right fs-4"></i></button>
        </div>

        <!-- Vista Mensual (Única) -->
        <div id="monthly-view-container" class="calendar-body-elite p-0">
            <div class="calendar-weekdays-elite border-bottom bg-light">
                <div class="fw-bold text-uppercase fs-nano py-2">Dom</div>
                <div class="fw-bold text-uppercase fs-nano py-2">Lun</div>
                <div class="fw-bold text-uppercase fs-nano py-2">Mar</div>
                <div class="fw-bold text-uppercase fs-nano py-2">Mié</div>
                <div class="fw-bold text-uppercase fs-nano py-2">Jue</div>
                <div class="fw-bold text-uppercase fs-nano py-2">Vie</div>
                <div class="fw-bold text-uppercase fs-nano py-2">Sáb</div>
            </div>
            <div id="calendar-days" class="calendar-days-elite">
                <!-- Se llena con JS -->
            </div>
        </div>
    </div>
</div>

<script src="../js/calendario.js" defer></script>
