<?php
declare(strict_types=1);
// PHP/VISTAS/AULA_VIRTUAL_ESTUDIANTE.PHP - STUDENT COCKPIT v1.0
$estudiante_id = (int)($_SESSION['estudiante_id'] ?? 0);

if ($estudiante_id === 0) {
    echo "<div class='container py-5 text-center'><h4 class='text-danger fw-bold'>SESIÓN NO VÁLIDA</h4></div>";
    return;
}

// RECUPERACIÓN SOBERANA DE NOMBRE
$stmt_n = $db->prepare("SELECT nombre FROM estudiantes WHERE id = ?");
$stmt_n->execute([$estudiante_id]);
$nombre_est = $stmt_n->fetchColumn() ?: ($_SESSION['nombre'] ?? 'Estudiante');
?>

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    <!-- BANNER DE BIENVENIDA -->
    <div class="card card-elite mb-4 border-0 shadow-none overflow-hidden bg-primary bg-opacity-10">
        <div class="card-body p-4 d-flex align-items-center justify-content-between">
            <div>
                <h4 class="h2 fw-bold text-primary mb-1">¡Hola, <?= explode(' ', $nombre_est)[0] ?>!</h4>
                <p class="text-secondary mb-0">Explora tus materiales de estudio y mantente al día con tus materias.</p>
            </div>
            <div class="d-none d-md-block opacity-25 banner-icon-elite">
                <svg width="140" height="140" viewBox="0 0 24 24" fill="none" stroke="var(--el-primary)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    <path d="M12 6h4"></path>
                    <path d="M12 10h4"></path>
                    <path d="M12 14h4"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- SECCIÓN: MIS MATERIAS (Biblioteca Principal) -->

    <div>
        <div class="d-flex align-items-center mb-4 border-start border-4 border-accent ps-3">
            <h5 class="fw-bold mb-0 text-titulo-elite">Mural de Recursos</h5>
        </div>
        <div id="estudiante-recursos" class="row g-4">
            <!-- Cargado vía AJAX -->
        </div>
    </div>
</div>

<!-- MODAL: RESUMEN DE PENDIENTES (Smart Alert) -->
<div class="modal fade" id="modalResumenPendientes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-elite border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 p-4 bg-primary bg-opacity-10">
                <div class="d-flex align-items-center">
                    <div class="icon-materia-circle bg-white text-primary rounded-circle shadow-sm me-3">
                        <i class="bi bi-bell-fill fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-primary mb-0 text-uppercase">HAY TRABAJO PENDIENTE, <?= explode(' ', $nombre_est)[0] ?></h5>
                        <p class="text-secondary small mb-0">Resumen de materiales sin revisar</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="lista-pendientes-resumen" class="mb-4">
                    <!-- Cargado dinámicamente -->
                </div>
                <button class="btn-elite btn-elite--primary w-100 py-3" data-bs-dismiss="modal">
                    <i class="bi bi-rocket-takeoff-fill me-2"></i>EMPEZAR AHORA
                </button>
            </div>
        </div>
    </div>
</div>

<?php /* MÓDULO PURIFICADO - CSS MIGRADO A /styles/modules/aula_virtual_estudiante.css */ ?>

<script src="../js/aula_virtual_estudiante.js" defer></script>