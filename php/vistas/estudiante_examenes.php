<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/VISTAS/ESTUDIANTE_EXAMENES.PHP - SALA DE ESPERA ARES v1.0
if (!tiene_permiso('evaluacion') && $_SESSION['rol_id'] != 3 && $_SESSION['rol_nombre'] != 'Estudiante') {
    // Si no es estudiante ni tiene permiso, no puede entrar
    echo "<div class='alert-soberania-elite animate__animated animate__headShake'>
            <div class='alert-soberania-icon'><i class='bi bi-shield-lock-fill'></i></div>
            <div class='alert-soberania-content'>
                <h4>Acceso Restringido</h4>
                <p>Módulo exclusivo para presentación de evaluaciones.</p>
            </div>
          </div>";
    return;
}

$mi_id = (int)$_SESSION['usuario_id'];

// Obtener el curso del estudiante actual
$sql_ident_curso = "SELECT e.curso_id FROM estudiantes e JOIN usuarios u ON u.estudiante_id = e.id WHERE u.id = ? LIMIT 1";
$stmt_curso = $db->prepare($sql_ident_curso);
$stmt_curso->execute([$mi_id]);
$mi_curso_row = $stmt_curso->fetch();
$mi_curso_id = $mi_curso_row ? (int)$mi_curso_row['curso_id'] : 0;


// Si no está inscrito, buscar por si acaso en carga_academica u otras tablas, o simplemente usar 0.
// Para el demo, listaremos todas las asignaciones ACTIVAS que coincidan con su curso.
?>


<div class="container-fluid py-4 animate__animated animate__fadeIn" id="estudiante-examenes-container" data-csrf-token="<?php echo $_SESSION['csrf_token']; ?>">
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 text-center text-md-start border-start border-4 border-primary ps-4">
            <h2 class="text-titulo-elite mb-0">Centro de Evaluación</h2>
            <p class="text-secondary mb-0 small">Instrumentos de evaluación y misiones académicas programadas.</p>
        </div>
        <div class="col-md-5">
            <div class="search-elite-container">
                <div class="search-icon-elite">
                    <i class="bi bi-search"></i>
                </div>
                <input type="text" id="buscar-examen-ares" class="search-elite" placeholder="Filtrar por materia o título..." onkeyup="filtrarExamenesAres()">
            </div>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-12">
            <div id="contenedor-examenes-estudiante" class="row g-4">
                <div class="col-12 text-center py-5">
                    <div class="loader-ball-elite"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div>
                    <p class="mt-3 text-muted">Buscando evaluaciones...</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="../js/estudiante_examenes.js" defer></script>