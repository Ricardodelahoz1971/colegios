<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// 1. CONTROL DE ACCESO (Sistema Modular)
if (!tiene_permiso('cursos')) {
    echo "<div class='alert-soberania-elite animate__animated animate__headShake'>
            <div class='alert-soberania-icon'><i class='bi bi-shield-lock-fill'></i></div>
            <div class='alert-soberania-content'>
                <h4>Acceso Denegado</h4>
                <p>No posee los permisos académicos necesarios para entrar al listado de cursos.</p>
            </div>
          </div>";
    return;
}

$usuario_id = $_SESSION['usuario_id'];
$es_poderoso = in_array($_SESSION['rol_id'], [1, 2]); // Admin o Rector
$puede_gestionar = $es_poderoso; // Solo ellos pueden crear/editar/borrar

// 2. CONSULTAS BLINDADAS (v9.2 PDO Edition)
if ($es_poderoso) {
    $sql_admin = "SELECT c.*, u.nombre as nombre_tutor, (SELECT COUNT(*) FROM carga_academica WHERE curso_id = c.id) as total_materias FROM cursos c LEFT JOIN usuarios u ON c.tutor_id = u.id ORDER BY c.nombre_curso ASC";
    $stmt_stmt_c = $db->prepare($sql_admin); 
    $stmt_stmt_c->execute(); 
    $stmt_c = $stmt_stmt_c;
} else {
    $sql_docente = "SELECT DISTINCT c.*, u.nombre as nombre_tutor, (SELECT COUNT(*) FROM carga_academica WHERE curso_id = c.id) as total_materias 
                            FROM cursos c 
                            LEFT JOIN usuarios u ON c.tutor_id = u.id 
                            LEFT JOIN carga_academica ca ON c.id = ca.curso_id 
                            WHERE c.tutor_id = :uid OR ca.docente_id = :did 
                            ORDER BY c.nombre_curso ASC";
    $stmt_c = $db->prepare($sql_docente);
    $stmt_c->bindValue(':uid', $usuario_id, PDO::PARAM_INT);
    $stmt_c->bindValue(':did', $usuario_id, PDO::PARAM_INT);
    $stmt_c->execute();
}

// Lista de docentes para el modal (v9.2 PDO)
$docentes_json = "[]";
if ($puede_gestionar) {
    $stmt_d = $db->prepare("SELECT u.id, u.nombre FROM usuarios u 
                            LEFT JOIN roles r ON u.rol_id = r.id 
                            WHERE LOWER(r.nombre_rol) LIKE '%profesor%' 
                            OR LOWER(r.nombre_rol) LIKE '%docente%' 
                            OR u.especialidad_id IS NOT NULL 
                            ORDER BY u.nombre ASC");
    $stmt_d->execute();
    $lista_docentes = $stmt_d->fetchAll(PDO::FETCH_ASSOC);
    $docentes_json = htmlspecialchars(json_encode($lista_docentes), ENT_QUOTES, 'UTF-8');
}
?>

<div class="container-fluid py-4">
    
    <!-- CABECERA DE SECCIÓN UNIFICADA -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 text-center text-md-start border-start border-4 border-topbar-elite ps-4">
            <?php 
                $es_admin_r = in_array($_SESSION['rol_id'], [1, 2, 10, 20]);
                $titulo_v = $es_admin_r ? 'Gestión de Cursos' : 'Mis Grupos Académicos';
                $desc_v = $es_admin_r ? 'Administre la oferta académica institucional de forma eficiente.' : 'Panel central de sus grupos y grados asignados.';
            ?>
            <h4 class="h3 fw-bold mb-0 text-titulo-elite"><?php echo $titulo_v; ?></h4>
            <p class="text-secondary mb-0 small"><?php echo $desc_v; ?></p>
            <nav class="breadcrumb-elite" aria-label="Ruta de navegación">
                <a href="javascript:void(0)" onclick="navegarModulo('inicio')" class="breadcrumb-link-elite"><i class="bi bi-house-door me-1"></i>Inicio</a>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-item-elite">Académico</span>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-current-elite">Cursos</span>
            </nav>
        </div>
        <div class="col-md-5">
            <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-md-end align-items-center">
                <div class="search-wrapper-elite flex-grow-1">
                    <span class="search-icon-elite"><i class="bi bi-search"></i></span>
                    <input type="text" class="search-elite--wrapped buscador-dinamico" placeholder="Filtrar cursos...">
                </div>
                <?php if ($puede_gestionar): ?>
                    <button onclick='nuevoCurso(<?php echo $docentes_json; ?>)' class="btn-elite btn-elite--sm">
                        <i class="bi bi-plus-lg me-2"></i>
                        Nuevo Curso
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="table-responsive-elite table-scroll-elite">
            <table class="table-elite table-hover table-sm align-middle mb-0 tabla-datos">
                <thead>
                    <tr>
                        <th class="py-3 ps-4 text-uppercase small fw-800 col-id">ID</th>
                        <th class="py-3 text-uppercase small fw-800">Curso</th>
                        <th class="py-3 text-uppercase small fw-800 text-center">Jornada</th>
                        <th class="py-3 text-uppercase small fw-800 text-center">Rol en el Grupo</th>
                        <th class="py-3 text-uppercase small fw-800 text-center">Jefe de Grupo</th>
                        <th class="py-3 pe-4 text-uppercase small fw-800 text-center col-actions">Operaciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php
                    $hay_registros = false;
                    while ($row = $stmt_c->fetch(PDO::FETCH_ASSOC)) {
                        $hay_registros = true;
                        $c_id = $row['id'];
                        $mi_uid = (int)$_SESSION['usuario_id'];
                        $es_admin_r = in_array($_SESSION['rol_id'], [1, 2, 10, 20]);
                        $soy_tutor = ($mi_uid === (int)$row['tutor_id']);
                        $tiene_tutor = ($row['tutor_id'] !== null);
                        $total_materias = (int)($row['total_materias'] ?? 0);
                        
                        // Lógica de Insignia Élite: Unificada para Admin y Docente
                        if ($es_admin_r) {
                            if ($tiene_tutor) {
                                $badge_rol = "<span class='badge-elite badge-elite--info'>DIRECTOR_DE_GRUPO</span>";
                            } else {
                                $badge_rol = "<span class='badge-elite badge-pulse-danger-elite'><i class='bi bi-exclamation-circle me-1'></i>SIN TUTOR</span>";
                            }
                        } else {
                            if ($soy_tutor) {
                                $badge_rol = "<span class='badge-elite badge-elite--info shadow-sm'>DIRECTOR_DE_GRUPO</span>";
                            } else {
                                $badge_rol = "<span class='badge-elite badge-elite--neutral'>DOCENTE_CATEDRÁTICO</span>";
                            }
                        }

                        echo "<tr>";
                            echo "<td class='ps-4 text-muted fw-bold small'>#" . $c_id . "</td>";
                            echo "<td><span class='fw-semibold text-dark fs-6'>" . htmlspecialchars($row['nombre_curso'], ENT_QUOTES, 'UTF-8') . "</span></td>";
                            echo "<td class='text-center'><span class='badge-elite badge-elite--neutral'>" . htmlspecialchars(mb_strtoupper((string)($row['jornada'] ?? 'Mañana'), 'UTF-8'), ENT_QUOTES, 'UTF-8') . "</span></td>";
                            echo "<td class='text-center'>" . $badge_rol . "</td>";
                            echo "<td class='text-center'>" . ($row['nombre_tutor'] ? "<span class='badge-elite badge-elite--info'>" . htmlspecialchars($row['nombre_tutor'], ENT_QUOTES, 'UTF-8') . "</span>" : "<span class='text-danger small fw-semibold'><i class='bi bi-person-x me-1'></i>Sin tutor asignado</span>") . "</td>";
                            echo "<td class='pe-4 text-center'>";
                                echo "<div class='d-flex justify-content-center gap-2'>";
                                    echo "<button class='btn-elite-icon' onclick='imprimirLista(" . $c_id . ")' title='Lista de Estudiantes'>
                                            <i class='bi bi-printer'></i>
                                          </button>";
                                    if ($puede_gestionar) {
                                        if ($total_materias === 0) {
                                            echo "<button class='btn-elite-icon btn-elite-icon--danger-pulse' onclick='navegarModulo(\"carga\", \"id=" . $c_id . "\")' title='¡Carga académica pendiente! Haz clic para asignar materias'>
                                                    <i class='bi bi-book'></i>
                                                  </button>";
                                        } else {
                                            echo "<button class='btn-elite-icon' onclick='navegarModulo(\"carga\", \"id=" . $c_id . "\")' title='Carga Académica (" . $total_materias . " asignaturas)'>
                                                    <i class='bi bi-book'></i>
                                                  </button>";
                                        }
                                        echo "<button class='btn-elite-icon' onclick='editarCurso(" . $c_id . ", \"" . htmlspecialchars($row['nombre_curso'], ENT_QUOTES, 'UTF-8') . "\", \"" . ($row['tutor_id'] ?? '') . "\", \"" . htmlspecialchars($row['jornada'] ?? 'Mañana', ENT_QUOTES, 'UTF-8') . "\", " . $docentes_json . ")' title='Editar'>
                                                <i class='bi bi-pencil-square'></i>
                                              </button>
                                              <button class='btn-elite-icon btn-elite-icon--danger' onclick='borrarCurso(" . $c_id . ", \"" . htmlspecialchars($row['nombre_curso'], ENT_QUOTES, 'UTF-8') . "\")' title='Eliminar'>
                                                <i class='bi bi-trash3'></i>
                                              </button>";
                                    }
                                echo "</div>";
                            echo "</td>";
                        echo "</tr>";
                    }
                    if (!$hay_registros) {
                        echo "<tr>
                                <td colspan='6' class='py-5 text-center'>
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
                                        <h5 class='fw-bold mb-1 text-uppercase text-primary small-letter-spacing'>Sin Cursos Activos</h5>
                                        <p class='text-secondary small mb-0'>No hay grados o grupos escolares creados en este periodo académico.</p>
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

<script src="../js/cursos.js" defer></script>
