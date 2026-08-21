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

    if (!tiene_permiso('estudiantes')) {
        throw new Exception('Permisos insuficientes para esta operación.');
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null || $id <= 0) {
        throw new Exception('ID de estudiante no válido.');
    }

    $db->beginTransaction();

    $stmt_usr = $db->prepare('DELETE FROM usuarios WHERE estudiante_id = ?');
    $stmt_usr->execute([$id]);

    $stmt_add = $db->prepare('DELETE FROM estudiantes_datos_adicionales WHERE estudiante_id = ?');
    $stmt_add->execute([$id]);

    $stmt = $db->prepare('DELETE FROM estudiantes WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('No se encontró el estudiante especificado.');
    }

    $db->commit();
    echo json_encode(['status' => 'success', 'message' => '¡Baja académica procesada correctamente!']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error de Bóveda: ' . $e->getMessage()
    ]);
}
?>