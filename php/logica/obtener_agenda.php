<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
/**
 * PHP/LOGICA/OBTENER_AGENDA.PHP
 * 
 * Motor de consulta asíncrona para la Agenda Académica.
 * Implementa filtrado inteligente basado en Roles (Estudiante, Profesor, Admin).
 * 
 * @author Ingeniería Élite v9.3
 * @version 1.1
 */

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';
require_once '../auth.php';

if (!tiene_permiso('agenda')) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Acceso denegado: Insuficiencia de privilegios registrados.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$rol_id = $_SESSION['rol_id'] ?? 0;
$identificacion = $_SESSION['identificacion'] ?? '';
session_write_close(); // Liberar candado de sesión para permitir concurrencia

// Consulta de Rol Blindada
$stmt_rol = $db->prepare("SELECT nombre_rol FROM roles WHERE id = :rid");
$stmt_rol->bindValue(':rid', $rol_id, PDO::PARAM_INT);
$stmt_rol->execute();
$fila_rol = $stmt_rol->fetch(PDO::FETCH_ASSOC);
$rol_nombre = $fila_rol['nombre_rol'] ?? '';

$curso_id = (int)($_GET['curso_id'] ?? 0); // Para Admin/Profesores

try {
    $where = "1=1";
    $params = [];

    // LÓGICA POR ROL
    if ($rol_nombre === 'Estudiante') {
        // Buscar el curso del estudiante mediante su ID de sesión único
        $estudiante_id = $_SESSION['estudiante_id'] ?? 0;
        $stmt_est = $db->prepare("SELECT curso_id FROM estudiantes WHERE id = :eid");
        $stmt_est->bindValue(':eid', $estudiante_id, PDO::PARAM_INT);
        $stmt_est->execute();
        $est_data = $stmt_est->fetch(PDO::FETCH_ASSOC);
        
        if (!$est_data) throw new Exception("Perfil de estudiante no encontrado para el usuario activo.");
        
        $where = "ae.curso_id = :cid";
        $params[':cid'] = $est_data['curso_id'];
    } 
    else if ($rol_nombre === 'Profesor') {
        $personal_id = $usuario_id;
        
        if ($curso_id > 0) {
            // Verificar que el profesor dicta en ese curso o sea el tutor
            $stmt_v = $db->prepare("SELECT COUNT(*) FROM cursos c 
                                     LEFT JOIN carga_academica ca ON c.id = ca.curso_id 
                                     WHERE c.id = :cid AND (ca.docente_id = :did OR c.tutor_id = :did)");
            $stmt_v->bindValue(':cid', $curso_id, PDO::PARAM_INT);
            $stmt_v->bindValue(':did', $personal_id, PDO::PARAM_INT);
            $stmt_v->execute();
            $v_dicta = (int)$stmt_v->fetchColumn();
            
            if ($v_dicta == 0 && !tiene_permiso('matricula')) {
                 throw new Exception("No tienes carga académica en este curso.");
            }
            $where = "ae.curso_id = :cid";
            $params[':cid'] = $curso_id;
        } else {
            // Ver todas las tareas creadas por este profesor
            $where = "ae.docente_id = :did";
            $params[':did'] = $personal_id;
        }
    } 
    else if ($curso_id > 0) {
        // Admin/Coordinador filtrando por curso
        $where = "ae.curso_id = :cid";
        $params[':cid'] = $curso_id;
    }

    $sql = "
        SELECT ae.*, c.nombre_curso, e.nombre_especialidad as materia_nom, u.nombre as docente_nom
        FROM agenda_escolar ae
        JOIN cursos c ON ae.curso_id = c.id
        LEFT JOIN especialidades e ON ae.especialidad_id = e.id
        LEFT JOIN usuarios u ON ae.docente_id = u.id
        WHERE $where
        ORDER BY ae.fecha_entrega ASC
    ";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();

    $tareas = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tareas[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $tareas]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
