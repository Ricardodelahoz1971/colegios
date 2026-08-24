<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();

header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    if (!tiene_permiso('cursos')) { 
        throw new Exception("Acceso denegado: Rango insuficiente."); 
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    // 🛡️ VALIDACIONES PREVENTIVAS
    // 1. Estudiantes matriculados
    $stmt_e = $db->prepare("SELECT COUNT(*) FROM estudiantes WHERE curso_id = ?");
    $stmt_e->execute([$id]);
    if ((int)$stmt_e->fetchColumn() > 0) {
        throw new Exception("No es posible eliminar el curso: Tiene estudiantes matriculados asignados.");
    }

    // 2. Carga Académica vinculada
    $stmt_ca = $db->prepare("SELECT COUNT(*) FROM carga_academica WHERE curso_id = ?");
    $stmt_ca->execute([$id]);
    if ((int)$stmt_ca->fetchColumn() > 0) {
        throw new Exception("No es posible eliminar el curso: Tiene materias y docentes asignados en la Carga Académica.");
    }

    $stmt = $db->prepare('DELETE FROM cursos WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Aula/Curso retirado del sistema correctamente.']);
    } else {
        throw new Exception("Fallo en la operación de base de datos.");
    }

} catch (PDOException $pe) {
    $msg = $pe->getMessage();
    if ($pe->getCode() === '23000' || strpos($msg, '1451') !== false || strpos($msg, 'foreign key constraint') !== false) {
        $msg = 'Acción Bloqueada: El curso contiene registros dependientes (estudiantes, notas o carga horaria) y no puede eliminarse directamente.';
    }
    echo json_encode(['status' => 'error', 'message' => 'Error de Bóveda: ' . $msg]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>