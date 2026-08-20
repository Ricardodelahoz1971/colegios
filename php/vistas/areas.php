<?php
declare(strict_types=1);
if (!tiene_permiso('areas')) {
    echo "<div class='alert-soberania-elite animate__animated animate__headShake'>
            <div class='alert-soberania-icon'><i class='bi bi-shield-lock-fill'></i></div>
            <div class='alert-soberania-content'>
                <h4>Acceso Denegado</h4>
                <p>No posee las credenciales académicas necesarias para gestionar las Áreas de Conocimiento.</p>
            </div>
          </div>";
    return;
}

// --- INFRAESTRUCTURA (Ejecución Única) ---
$db->exec("CREATE TABLE IF NOT EXISTS areas (id INT PRIMARY KEY AUTO_INCREMENT, nombre_area VARCHAR(255) UNIQUE NOT NULL)");

// --- CONSULTA BLINDADA (v9.2 PDO) ---
$stmt = $db->prepare("SELECT * FROM areas ORDER BY nombre_area ASC");
$stmt->execute();
?>

<div class="container-fluid py-4">
    
    <!-- CABECERA DE SECCIÓN UNIFICADA -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 text-center text-md-start border-start border-4 border-topbar-elite ps-4">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">Áreas de Conocimiento</h4>
            <p class="text-secondary mb-0 small">Gestión de las facultades y divisiones académicas del plantel.</p>
        </div>
        <div class="col-md-5">
            <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-md-end align-items-center">
                <div class="search-wrapper-elite flex-grow-1">
                    <span class="search-icon-elite-fixed"><i class="bi bi-search"></i></span>
                    <label for="buscador-areas" class="visually-hidden">Filtrar áreas de conocimiento</label>
                    <input type="text" id="buscador-areas" class="search-elite--wrapped buscador-dinamico" placeholder="Filtrar áreas...">
                </div>
                <button onclick="nuevaArea()" class="btn-elite btn-elite--sm">
                    <i class="bi bi-plus-lg me-2"></i>
                    Crear Área
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
                        <th class="py-3 text-uppercase small fw-800">Nombre del Área de Conocimiento</th>
                        <th class="py-3 text-center text-uppercase small fw-800 col-actions-sm">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php
                    $hay_registros = false;
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $hay_registros = true;
                        echo "<tr>";
                            echo "<td class='ps-4 text-muted fw-bold small'>#" . $row['id'] . "</td>";
                            echo "<td><span class='fw-semibold text-dark'>" . htmlspecialchars($row['nombre_area'], ENT_QUOTES, 'UTF-8') . "</span></td>";
                            echo "<td class='text-center p-2'>
                                    <div class='d-flex justify-content-center gap-2'>
                                        <button class='btn-elite-icon' onclick='editarArea(" . $row['id'] . ", \"" . htmlspecialchars($row['nombre_area'], ENT_QUOTES, 'UTF-8') . "\")' title='Editar'>
                                            <i class='bi bi-pencil-square'></i>
                                        </button>
                                        <button class='btn-elite-icon btn-elite-icon--danger' onclick='borrarArea(" . $row['id'] . ", \"" . htmlspecialchars($row['nombre_area'], ENT_QUOTES, 'UTF-8') . "\")' title='Eliminar'>
                                            <i class='bi bi-trash3'></i>
                                        </button>
                                    </div>
                                  </td>";
                        echo "</tr>";
                    }
                    if (!$hay_registros) {
                        echo "<tr>
                                <td colspan='3' class='py-5 text-center'>
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
                                        <h5 class='fw-bold mb-1 text-uppercase text-primary small-letter-spacing'>Sin Áreas Registradas</h5>
                                        <p class='text-secondary small mb-0'>No se encontraron divisiones académicas. Comience creando una nueva para organizar las asignaturas.</p>
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
