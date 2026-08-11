<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
proteccion_extrema();
// PHP/LOGICA/BORRAR_TAREA.PHP - ELIMINACIÓN DE COMPROMISO
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

if (!tiene_permiso('agenda')) {
    echo json_encode(['status' => 'error', 'message' => 'No tienes permisos']);
    exit;
}
session_write_close();

$id = $_POST['id'] ?? 0;

if ($id > 0) {
    // Validar propiedad si es profesor? Por ahora permitimos borrar el ID.
    $stmt = $db->prepare("DELETE FROM agenda_escolar WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Tarea eliminada']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
}
?>

