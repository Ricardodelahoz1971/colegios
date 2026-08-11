<?php
require_once 'c:/xampp/htdocs/sistema_escolar/php/db.php';

// Permisos para Administrador (Todos)
$stmt = $db->query("SELECT id FROM permisos");
$todos_permisos = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Permisos para Coordinador (Gestión escolar básica)
// 'inicio', 'mensajeria', 'agenda', 'calendario', 'asistencia', 'evaluacion', 'cursos', 'khronos', 'zulu', 'aula_virtual', 'sabana_calificaciones'
// Más: 'personal', 'areas', 'especialidades', 'reporte_listas', 'matricula', etc.
$stmt_coord = $db->query("SELECT id FROM permisos WHERE clave IN ('inicio', 'mensajeria', 'agenda', 'calendario', 'asistencia', 'evaluacion', 'cursos', 'khronos', 'zulu', 'aula_virtual', 'sabana_calificaciones', 'personal', 'areas', 'especialidades', 'reporte_listas', 'matricula')");
$permisos_coord = $stmt_coord->fetchAll(PDO::FETCH_COLUMN);

// Limpiar tabla primero para estos roles para evitar duplicados
$db->exec("DELETE FROM rol_permisos WHERE rol_id IN (1, 2)");

// Inyectar Administrador (Rol 1)
$sql = "INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (?, ?)";
$stmt_insert = $db->prepare($sql);

foreach ($todos_permisos as $p) {
    $stmt_insert->execute([1, $p]);
}

// Inyectar Coordinador (Rol 2)
foreach ($permisos_coord as $p) {
    $stmt_insert->execute([2, $p]);
}

echo "Permisos base inyectados correctamente para Administrador (Rol 1) y Coordinador (Rol 2).\n";
?>
