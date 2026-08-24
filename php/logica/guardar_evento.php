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

$titulo = limpiar_texto_utf8($_POST['titulo'] ?? '');
$fecha = limpiar_texto_utf8($_POST['fecha'] ?? '');
$desc = limpiar_texto_utf8($_POST['desc'] ?? '');
$tipo = limpiar_texto_utf8($_POST['tipo'] ?? '');
$color = limpiar_texto_utf8($_POST['color'] ?? '');

$titulo = $titulo !== null ? trim($titulo) : '';
$fecha = $fecha !== null ? trim($fecha) : '';
$desc = $desc !== null ? trim($desc) : '';
$tipo = $tipo !== null ? trim($tipo) : 'EVENTO';
$color = $color !== null ? trim($color) : 'rgb(13, 202, 240)';

if (!$titulo || !$fecha) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Título y fecha son obligatorios.']);
    exit;
}

$stmt = $db->prepare("INSERT INTO cronograma (titulo, descripcion, tipo, color, fecha, creado_por) 
                      VALUES (:tit, :des, :tip, :col, :fec, :usr)");
$stmt->bindValue(':tit', $titulo, PDO::PARAM_STR);
$stmt->bindValue(':des', $desc, PDO::PARAM_STR);
$stmt->bindValue(':tip', $tipo, PDO::PARAM_STR);
$stmt->bindValue(':col', $color, PDO::PARAM_STR);
$stmt->bindValue(':fec', $fecha, PDO::PARAM_STR);
$stmt->bindValue(':usr', $_SESSION['usuario_id'], PDO::PARAM_INT);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error al guardar el evento en la base de datos.']);
}
exit;
?>