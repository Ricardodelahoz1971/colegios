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

$id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? 0;
$titulo = limpiar_texto_utf8($_POST['titulo'] ?? '') ?? '';
$fecha  = limpiar_texto_utf8($_POST['fecha'] ?? '') ?? '';
$desc   = limpiar_texto_utf8($_POST['desc'] ?? '') ?? '';
$tipo   = limpiar_texto_utf8($_POST['tipo'] ?? '') ?? 'EVENTO';
$color  = limpiar_texto_utf8($_POST['color'] ?? '') ?? 'rgb(13, 202, 240)';

$titulo = trim($titulo);
$desc   = trim($desc);

if ($id <= 0 || $titulo === '' || $fecha === '') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ID, título y fecha son obligatorios.']);
    exit;
}

$stmt = $db->prepare("UPDATE cronograma SET titulo = :tit, descripcion = :des, tipo = :tip, color = :col, fecha = :fec WHERE id = :id");
$stmt->bindValue(':tit', $titulo, PDO::PARAM_STR);
$stmt->bindValue(':des', $desc, PDO::PARAM_STR);
$stmt->bindValue(':tip', $tipo, PDO::PARAM_STR);
$stmt->bindValue(':col', $color, PDO::PARAM_STR);
$stmt->bindValue(':fec', $fecha, PDO::PARAM_STR);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el evento en la base de datos.']);
}
exit;
?>