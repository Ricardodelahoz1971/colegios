<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
if (!tiene_permiso('especialidades')) {
    echo "<div class='alert-soberania-elite animate__animated animate__headShake'>
            <div class='alert-soberania-icon'><i class='bi bi-shield-lock-fill'></i></div>
            <div class='alert-soberania-content'>
                <h4>Acceso Denegado</h4>
                <p>No posee las credenciales académicas necesarias para gestionar las Especialidades Docentes.</p>
            </div>
          </div>";
    return;
}

// 1. Extraemos las áreas para el modal (v9.2 PDO)
$stmt_stmt_a = $db->prepare("SELECT * FROM areas ORDER BY nombre_area ASC"); $stmt_stmt_a->execute(); $stmt_a = $stmt_stmt_a;
$lista_areas = $stmt_a->fetchAll(PDO::FETCH_ASSOC);
$areas_json = htmlspecialchars(json_encode($lista_areas), ENT_QUOTES, 'UTF-8');

// 2. Extraemos las especialidades cruzadas (v9.2 PDO)
$stmt_e = $db->prepare("
    SELECT e.*, a.nombre_area 
    FROM especialidades e 
    LEFT JOIN areas a ON e.area_id = a.id 
    ORDER BY a.nombre_area ASC, e.nombre_especialidad ASC
");
$stmt_e->execute();
?>

<div class="container-fluid py-4">
    
    <!-- CABECERA DE SECCIÓN UNIFICADA BOOTSTRAP 5 -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 text-center text-md-start border-start border-4 border-topbar-elite ps-4">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">Especialidades Docentes (Materias)</h4>
            <p class="text-secondary mb-0 small">Defina las áreas de experticia y las cátedras que integran la oferta académica.</p>
            <nav class="breadcrumb-elite" aria-label="Ruta de navegación">
                <a href="javascript:void(0)" onclick="navegarModulo('inicio')" class="breadcrumb-link-elite"><i class="bi bi-house-door me-1"></i>Inicio</a>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-item-elite">Académico</span>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-current-elite">Asignaturas</span>
            </nav>
        </div>
        <div class="col-md-5">
            <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-md-end align-items-center">
                <div class="search-wrapper-elite flex-grow-1">
                    <span class="search-icon-elite-fixed"><i class="bi bi-search"></i></span>
                    <input type="text" class="search-elite--wrapped buscador-dinamico" placeholder="Filtrar materias...">
                </div>
                <button onclick='nuevaEspecialidad(<?php echo $areas_json; ?>)' class="btn-elite btn-elite--sm">
                    <i class="bi bi-plus-lg me-2"></i>
                    Crear Nueva
                </button>
            </div>
        </div>
    </div>

    <!-- LISTADO UNIFICADO ELITE -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="table-responsive-elite table-scroll-elite">
            <table class="table-elite tabla-datos table-hover table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th class="py-3 ps-4 text-uppercase small fw-800 col-id">ID</th>
                        <th class="py-3 text-uppercase small fw-800">Nombre de la Especialidad / Materia</th>
                        <th class="py-3 text-uppercase small fw-800 text-center">Área Responsable</th>
                        <th class="py-3 text-center text-uppercase small fw-800 col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php
                    $hay_registros = false;
                    while ($row = $stmt_e->fetch(PDO::FETCH_ASSOC)) {
                        $hay_registros = true;
                        echo "<tr>";
                            echo "<td class='ps-4 text-muted fw-bold small'>#" . $row['id'] . "</td>";
                            echo "<td><span class='fw-semibold text-dark fs-6'>" . htmlspecialchars($row['nombre_especialidad'], ENT_QUOTES, 'UTF-8') . "</span></td>";
                            echo "<td class='text-center'><span class='badge-elite badge-elite--primary'>" . htmlspecialchars($row['nombre_area'] ?? 'Sin Facultad', ENT_QUOTES, 'UTF-8') . "</span></td>";
                            echo "<td class='text-center'>
                                    <div class='d-flex justify-content-center gap-2'>
                                        <button class='btn-elite-icon' onclick='editarEspecialidad(" . $row['id'] . ", \"" . htmlspecialchars($row['nombre_especialidad'], ENT_QUOTES, 'UTF-8') . "\", \"" . ($row['area_id'] ?? '') . "\", " . $areas_json . ", " . ($row['nivel_desde'] ?? 1) . ", " . ($row['nivel_hasta'] ?? 11) . ")' title='Editar'>
                                            <i class='bi bi-pencil-square'></i>
                                        </button>
                                        <button class='btn-elite-icon btn-elite-icon--danger' onclick='borrarEspecialidad(" . $row['id'] . ", \"" . htmlspecialchars($row['nombre_especialidad'], ENT_QUOTES, 'UTF-8') . "\")' title='Eliminar'>
                                            <i class='bi bi-trash3'></i>
                                        </button>
                                    </div>
                                  </td>";
                        echo "</tr>";
                    }
                    if (!$hay_registros) {
                        echo "<tr>
                                <td colspan='4' class='py-5 text-center'>
                                    <div class='d-flex flex-column align-items-center justify-content-center py-4'>
                                        <!-- Premium Document SVG -->
                                        <svg width='100' height='100' viewBox='0 0 120 120' fill='none' class='mb-3 text-muted opacity-75'>
                                            <rect x='34' y='24' width='56' height='72' rx='8' fill='currentColor' class='opacity-10' />
                                            <rect x='30' y='20' width='56' height='72' rx='8' fill='currentColor' class='opacity-5' stroke='currentColor' stroke-width='2' />
                                            <line x1='40' y1='36' x2='65' y2='36' stroke='currentColor' stroke-width='3' stroke-linecap='round' class='text-primary' />
                                            <line x1='40' y1='48' x2='76' y2='48' stroke='currentColor' stroke-width='2' stroke-linecap='round' class='opacity-25' />
                                            <line x1='40' y1='60' x2='76' y2='60' stroke='currentColor' stroke-width='2' stroke-linecap='round' class='opacity-25' />
                                            <line x1='40' y1='72' x2='60' y2='72' stroke='currentColor' stroke-width='2' stroke-linecap='round' class='opacity-25' />
                                            <rect x='80' y='44' width='14' height='8' rx='2' fill='currentColor' class='text-danger opacity-75' />
                                            <rect x='82' y='64' width='12' height='8' rx='2' fill='currentColor' class='text-warning opacity-75' />
                                        </svg>
                                        <h5 class='fw-bold mb-1 text-uppercase text-primary small-letter-spacing'>Sin Especialidades Registradas</h5>
                                        <p class='text-secondary small mb-0'>Defina las especialidades y cátedras para estructurar la carga académica.</p>
                                    </div>
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
