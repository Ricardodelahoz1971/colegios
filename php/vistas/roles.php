<?php
declare(strict_types=1);
// --- CONTROL DE DAÑOS: Asegurarnos de que la tabla exista ---
$db->exec("CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT, 
    nombre_rol VARCHAR(255) UNIQUE NOT NULL
)");

// Si está vacía, le ponemos los 3 básicos (v9.2 PDO)
$check = (int)(function($db) { $s = $db->prepare("SELECT COUNT(*) FROM roles"); $s->execute(); return $s->fetchColumn(); })($db);
if ($check == 0) {
    $db->exec("INSERT INTO roles (nombre_rol) VALUES ('Administrador')");
    $db->exec("INSERT INTO roles (nombre_rol) VALUES ('Profesor')");
    $db->exec("INSERT INTO roles (nombre_rol) VALUES ('Estudiante')");
}
// --- FIN DEL CONTROL DE DAÑOS ---

// Ahora sí coordinamos el listado
$stmt_result = $db->prepare("SELECT * FROM roles"); $stmt_result->execute(); $result = $stmt_result;

// Cargamos TODOS los módulos disponibles para el panel de gestión
$stmt_stmt_p = $db->prepare("SELECT * FROM permisos"); $stmt_stmt_p->execute(); $stmt_p = $stmt_stmt_p;
$todos_permisos = $stmt_p->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    
    <!-- CABECERA ADAPTADA A BOOTSTRAP 5 -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 text-center text-md-start border-start border-4 border-topbar-elite ps-4">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">Jerarquía de Roles Escolar</h4>
            <p class="text-secondary mb-0 small">Gestione los niveles de acceso y permisos del sistema.</p>
            <nav class="breadcrumb-elite" aria-label="Ruta de navegación">
                <a href="javascript:void(0)" onclick="navegarModulo('inicio')" class="breadcrumb-link-elite"><i class="bi bi-house-door me-1"></i>Inicio</a>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-item-elite">Administración</span>
                <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
                <span class="breadcrumb-current-elite">Roles y Permisos</span>
            </nav>
        </div>
        <div class="col-md-5">
            <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-end">
                <div class="input-group col-search-bar input-group-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" class="input-elite border-start-0 border-end-0 buscador-dinamico" placeholder="Filtrar roles...">
                    <button class="btn btn-elite--outline btn-clear-search bg-white border-start-0 text-primary" type="button" title="Limpiar búsqueda">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <button onclick="nuevoRol()" class="btn-elite btn-elite--sm">
                    <i class="bi bi-shield-plus me-2"></i>
                    Nuevo Rol
                </button>
            </div>
        </div>
    </div>

    <!-- TABLA UNIFICADA ELITE -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="table-responsive-elite table-scroll-elite">
            <table class="table-elite tabla-datos table-hover table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th class="py-3 ps-4 text-uppercase small fw-800 col-id">ID</th>
                        <th class="py-3 text-uppercase small fw-800">Nombre del Perfil / Rol del Sistema</th>
                        <th class="py-3 text-center text-uppercase small fw-800 col-actions">Operaciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        $rol_id = $row['id'];
                        
                        $stmt_actual = $db->prepare("SELECT permiso_id FROM rol_permisos WHERE rol_id = :rid");
                        $stmt_actual->bindValue(':rid', $rol_id, PDO::PARAM_INT);
                        $stmt_actual->execute();
                        $permisos_actuales = $stmt_actual->fetchAll(PDO::FETCH_COLUMN);

                        echo "<tr>";
                            echo "<td class='ps-4 text-muted fw-bold small'>#" . $row['id'] . "</td>";
                            echo "<td><span class='fw-semibold text-dark text-uppercase small'>" . htmlspecialchars($row['nombre_rol']) . "</span></td>";
                            echo "<td class='text-center py-2'>
                                    <div class='d-flex justify-content-center gap-2'>
                                        <button class='btn-elite-icon' 
                                                onclick='gestionarPermisos(" . $rol_id . ", \"" . htmlspecialchars($row['nombre_rol'], ENT_QUOTES, 'UTF-8') . "\", " . htmlspecialchars(json_encode($permisos_actuales), ENT_QUOTES, 'UTF-8') . ", " . htmlspecialchars(json_encode($todos_permisos), ENT_QUOTES, 'UTF-8') . ")' 
                                                title='Permisos'>
                                            <i class='bi bi-shield-lock'></i>
                                        </button>
                                        <button class='btn-elite-icon' onclick='editarRol(" . $row['id'] . ", \"" . htmlspecialchars($row['nombre_rol'], ENT_QUOTES, 'UTF-8') . "\")' title='Editar'>
                                            <i class='bi bi-pencil-square'></i>
                                        </button>
                                        <button class='btn-elite-icon btn-elite-icon--danger' onclick='borrarRol(" . $row['id'] . ", \"" . htmlspecialchars($row['nombre_rol'], ENT_QUOTES, 'UTF-8') . "\")' title='Eliminar'>
                                            <i class='bi bi-trash3'></i>
                                        </button>
                                    </div>
                                  </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



