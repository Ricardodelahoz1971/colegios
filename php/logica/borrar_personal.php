<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/BORRAR_PERSONAL.PHP - MOTOR DE BAJA DE PERSONAL v2.1
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE AUTORIDAD
    if (!tiene_permiso('personal')) {
        throw new Exception('Permisos insuficientes para esta operación.');
    }

    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        throw new Exception('ID de personal no proporcionado.');
    }

    // REGLA DE PROTECCIÓN: El superusuario principal no puede ser eliminado
    if ($id == 1) {
        throw new Exception('El superusuario principal del sistema es inamovible.');
    }

    // 1. ELIMINAR DE LA BASE DE DATOS
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

