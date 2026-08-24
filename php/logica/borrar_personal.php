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

    $mi_id = (int)($_SESSION['user_id'] ?? 0);
    if ($mi_id > 0 && $id === $mi_id) {
        throw new Exception('No puede eliminarse a sí mismo mientras mantiene su sesión activa.');
    }

    // 🏛️ TRANSACCIÓN ATÓMICA DE BAJA INSTITUCIONAL
    $db->beginTransaction();

    // 1. Desvincular dirección de grupo / tutoría en cursos
    $stmt_t = $db->prepare("UPDATE cursos SET tutor_id = NULL WHERE tutor_id = ?");
    $stmt_t->execute([$id]);

    // 2. Desvincular asignaciones de carga académica (Zulu Engine)
    $stmt_ca = $db->prepare("DELETE FROM carga_academica WHERE docente_id = ?");
    $stmt_ca->execute([$id]);

    // 3. Desvincular bloques de horarios (Khronos Engine)
    try {
        $stmt_h = $db->prepare("DELETE FROM horarios WHERE docente_id = ?");
        $stmt_h->execute([$id]);
    } catch (Throwable $th) {}

    // 4. Eliminar usuario institucional
    $stmt = $db->prepare('DELETE FROM usuarios WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $db->commit();

    echo json_encode(['status' => 'success', 'message' => '¡Personal dado de baja y dependencias liberadas!']);

} catch (PDOException $pe) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $msg = $pe->getMessage();
    if ($pe->getCode() === '23000' || strpos($msg, '1451') !== false || strpos($msg, 'foreign key constraint') !== false) {
        $msg = 'Acción Bloqueada: El usuario posee actividades evaluativas o notas vinculadas y no puede ser eliminado directamente.';
    }
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error de Bóveda: ' . $msg
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error de Bóveda: ' . $e->getMessage()
    ]);
}
?>