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

    if (!tiene_permiso('areas')) { 
        throw new Exception("Acceso denegado: Rango insuficiente."); 
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    // 🛡️ VALIDACIÓN PREVENTIVA
    $stmt_m = $db->prepare("SELECT COUNT(*) FROM especialidades WHERE area_id = ?");
    $stmt_m->execute([$id]);
    if ((int)$stmt_m->fetchColumn() > 0) {
        throw new Exception("No es posible eliminar el área: Tiene materias/especialidades asociadas. Debe reasignar o eliminar primero las materias.");
    }

    $stmt = $db->prepare('DELETE FROM areas WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Área eliminada del mapa institucional.']);
    } else {
        throw new Exception("Error en la bóveda de datos.");
    }

} catch (PDOException $pe) {
    $msg = $pe->getMessage();
    if ($pe->getCode() === '23000' || strpos($msg, '1451') !== false || strpos($msg, 'foreign key constraint') !== false) {
        $msg = 'Acción Bloqueada: El área contiene materias o datos vinculados y no puede eliminarse directamente.';
    }
    echo json_encode(['status' => 'error', 'message' => 'Error de Bóveda: ' . $msg]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>