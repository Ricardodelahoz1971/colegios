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

    if (!tiene_permiso('especialidades')) { 
        throw new Exception("Acceso denegado: Rango insuficiente."); 
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    // 🛡️ VALIDACIONES PREVENTIVAS
    // 1. Carga Académica
    $stmt_ca = $db->prepare("SELECT COUNT(*) FROM carga_academica WHERE especialidad_id = ?");
    $stmt_ca->execute([$id]);
    if ((int)$stmt_ca->fetchColumn() > 0) {
        throw new Exception("No es posible eliminar la materia: Se encuentra asignada a uno o más cursos en la Carga Académica (Zulu).");
    }

    // 2. Docentes con esta especialidad
    $stmt_u = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE especialidad_id = ?");
    $stmt_u->execute([$id]);
    if ((int)$stmt_u->fetchColumn() > 0) {
        throw new Exception("No es posible eliminar la materia: Hay docentes vinculados a esta especialidad en el módulo de Personal.");
    }

    $stmt = $db->prepare('DELETE FROM especialidades WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Materia/Especialidad eliminada correctamente.']);
    } else {
        throw new Exception("Error en la bóveda de datos.");
    }

} catch (PDOException $pe) {
    $msg = $pe->getMessage();
    if ($pe->getCode() === '23000' || strpos($msg, '1451') !== false || strpos($msg, 'foreign key constraint') !== false) {
        $msg = 'Acción Bloqueada: La materia tiene actividades, preguntas o carga docente asociada y no puede eliminarse directamente.';
    }
    echo json_encode(['status' => 'error', 'message' => 'Error de Bóveda: ' . $msg]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>