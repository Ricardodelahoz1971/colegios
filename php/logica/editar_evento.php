<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
proteccion_extrema();
// PHP/LOGICA/EDITAR_EVENTO.PHP - ACTUALIZACIÓN DE EVENTOS v1.0
include '../db.php';
include '../auth.php';

$es_admin_cron = tiene_permiso('matricula') || tiene_permiso('personal');
if (!isset($_SESSION['usuario_id']) || !$es_admin_cron) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$fecha  = isset($_POST['fecha']) ? $_POST['fecha'] : '';
$desc   = isset($_POST['desc']) ? trim($_POST['desc']) : '';
$tipo   = isset($_POST['tipo']) ? $_POST['tipo'] : 'EVENTO';
$color  = isset($_POST['color']) ? $_POST['color'] : 'rgb(13, 202, 240)';

if ($id <= 0 || !$titulo || !$fecha) {
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

