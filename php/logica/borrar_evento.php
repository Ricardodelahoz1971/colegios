<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
proteccion_extrema();
// PHP/LOGICA/BORRAR_EVENTO.PHP - ELIMINACIÓN DE EVENTOS v1.0
include '../db.php';
include '../auth.php';

$es_admin_cron = tiene_permiso('matricula') || tiene_permiso('personal');
if (!isset($_SESSION['usuario_id']) || !$es_admin_cron) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $stmt = $db->prepare("DELETE FROM cronograma WHERE id = ?");
    if ($stmt->execute([$id])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el evento.']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ID de evento no válido.']);
}
exit;
?>

