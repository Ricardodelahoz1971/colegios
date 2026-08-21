<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();
proteccion_extrema();
include '../db.php';
include '../auth.php';

$es_admin_cron = tiene_permiso('matricula') || tiene_permiso('personal');
if (!isset($_SESSION['usuario_id']) || !$es_admin_cron) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id !== false && $id !== null && $id > 0) {
    $stmt = $db->prepare("DELETE FROM cronograma WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    if ($stmt->execute()) {
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