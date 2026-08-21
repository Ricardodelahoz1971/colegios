<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
proteccion_extrema();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

if (!tiene_permiso('agenda')) {
    echo json_encode(['status' => 'error', 'message' => 'No tienes permisos']);
    exit;
}
session_write_close();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 1]]);

if ($id !== false && $id > 0) {
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