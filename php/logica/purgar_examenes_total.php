<?php
declare(strict_types=1);
// PHP/LOGICA/PURGAR_EXAMENES_TOTAL.PHP - PROTOCOLO DE LIMPIEZA SOBERANA v2.0
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../db.php';
require_once '../auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado: Inicie sesión.']);
    exit();
}

if ((int)($_SESSION['rol_id'] ?? 0) !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado: Se requiere nivel Administrador.']);
    exit();
}

try {
    $db->beginTransaction();

    // 1. Limpiar respuestas de alumnos
    $db->prepare("DELETE FROM eval_respuestas")->execute();
    
    // 2. Limpiar incidentes de seguridad
    $db->prepare("DELETE FROM eval_incidentes")->execute();

    // 3. Limpiar asignaciones (programaciones)
    $db->prepare("DELETE FROM eval_asignaciones")->execute();

    // 4. Limpiar vinculaciones (items de pruebas)
    $db->prepare("DELETE FROM eval_pruebas_items")->execute();

    $db->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => "Bóveda purgada exitosamente. Todas las restricciones de integridad han sido removidas.",
        'details' => "Se han liberado todos los reactivos y exámenes para su gestión total."
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Error en la purga atómica: ' . $e->getMessage()]);
}
?>

