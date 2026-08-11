<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
proteccion_extrema();
// PHP/LOGICA/RESETEAR_INTENTO_INDIVIDUAL.PHP - BISTURÍ DE RESETS v1.0
require_once '../db.php';
require_once '../auth.php';

header('Content-Type: application/json');

// 🛡️ Solo personal autorizado con permiso de evaluación puede resetear
if (!tiene_permiso('evaluacion')) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado: Se requieren permisos de evaluación.']);
    exit();
}
    session_write_close();

$id_entrega = (int)($_POST['entrega_id'] ?? 0);

if ($id_entrega <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de entrega no válido.']);
    exit();
}

try {
    // 1. Obtener datos de la entrega para limpiar incidentes y verificar propiedad (Evitar IDOR)
    $mi_id = (int)$_SESSION['usuario_id'];
    $stmt_info = $db->prepare("
        SELECT r.asignacion_id, r.estudiante_id 
        FROM eval_respuestas r
        JOIN eval_asignaciones a ON r.asignacion_id = a.id
        WHERE r.id = ? AND a.docente_id = ?
    ");
    $stmt_info->execute([$id_entrega, $mi_id]);
    $info = $stmt_info->fetch();

    if (!$info) {
        throw new Exception("Operación no autorizada o entrega no encontrada.");
    }

    $db->beginTransaction();

    // 2. Eliminar incidentes del Centinela para este intento específico
    // Nota: Los incidentes se vinculan por asignacion y estudiante
    $stmt_inc = $db->prepare("DELETE FROM eval_incidentes WHERE asignacion_id = ? AND estudiante_id = ?");
    $stmt_inc->execute([$info['asignacion_id'], $info['estudiante_id']]);

    // 3. Eliminar la respuesta/intento
    $stmt_res = $db->prepare("DELETE FROM eval_respuestas WHERE id = ?");
    $stmt_res->execute([$id_entrega]);

    $db->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => 'Intento reseteado. El estudiante puede presentar el examen nuevamente.'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Error en el bisturí: ' . $e->getMessage()]);
}

