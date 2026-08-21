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

    if (!tiene_permiso('personal')) {
        throw new Exception('Permisos insuficientes para esta operación.');
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($id === null || $id === false || $id <= 0) {
        throw new Exception('ID de personal no proporcionado o inválido.');
    }

    if ($id === 1) {
        throw new Exception('El superusuario principal del sistema es inamovible.');
    }

    $stmt = $db->prepare('DELETE FROM usuarios WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => '¡Baja institucional confirmada!']);
    } else {
        throw new Exception('Fallo crítico al eliminar de la base de datos.');
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error de Bóveda: ' . $e->getMessage()
    ]);
}
?>