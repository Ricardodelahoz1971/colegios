<?php
declare(strict_types=1);
// Mock session
$_SESSION['usuario_id'] = 2; // Asumir un ID válido
$_SESSION['rol_id'] = 3;
$_GET['accion'] = 'listar_mis_examenes';

try {
    require_once '../db.php';
    
    $mi_id = 2; // Hardcode para probar
    
    $stmt_curso = $db->prepare("SELECT e.curso_id FROM estudiantes e JOIN usuarios u ON u.estudiante_id = e.id WHERE u.id = ? LIMIT 1");
    $stmt_curso->execute([$mi_id]);
    $mi_curso_row = $stmt_curso->fetch();
    $mi_curso_id = $mi_curso_row ? (int)$mi_curso_row['curso_id'] : 0;
    
    echo "Curso ID: " . $mi_curso_id . "\n";

    $filtro_curso = "WHERE a.curso_id = ?";
    $params = [$mi_curso_id];

    $sql = "SELECT a.id as asignacion_id, a.fecha_inicio, a.fecha_fin, a.clave_acceso, 
            p.titulo, p.duracion_minutos, p.total_preguntas, e.nombre_especialidad as materia
            FROM eval_asignaciones a
            JOIN eval_pruebas p ON a.prueba_id = p.id
            JOIN especialidades e ON p.materia_id = e.id
            $filtro_curso
            ORDER BY a.fecha_inicio DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    print_r($asignaciones);
} catch (Exception $e) {
    echo "ERROR CATCH: " . $e->getMessage();
} catch (Error $err) {
    echo "ERROR FATAL: " . $err->getMessage();
}

