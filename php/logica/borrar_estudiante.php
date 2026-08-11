<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();
// PHP/LOGICA/BORRAR_ESTUDIANTE.PHP - PROCESADOR DE BAJA ELITE v1.2
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE AUTORIDAD
    if (!tiene_permiso('estudiantes')) {
        throw new Exception('Permisos insuficientes para esta operación.');
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID de estudiante no válido.');
    }

    // 1. ELIMINAR REGISTROS RELACIONADOS EN OTRAS TABLAS PARA EVITAR RESTRICCIONES DE FOREIGN KEY
    // Eliminar cuenta de usuario relacionada
    $stmt_usr = $db->prepare('DELETE FROM usuarios WHERE estudiante_id = ?');
    $stmt_usr->execute([$id]);

    // Eliminar datos adicionales relacionados
    $stmt_add = $db->prepare('DELETE FROM estudiantes_datos_adicionales WHERE estudiante_id = ?');
    $stmt_add->execute([$id]);

    // 2. ELIMINAR DE LA TABLA PRINCIPAL DE ESTUDIANTES
    $stmt = $db->prepare('DELETE FROM estudiantes WHERE id = ?');
    
    if ($stmt->execute([$id])) {
        echo json_encode(['status' => 'success', 'message' => '¡Baja académica procesada correctamente!']);
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
