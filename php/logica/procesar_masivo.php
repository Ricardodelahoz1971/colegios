<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();
proteccion_extrema();
session_start();
require_once '../db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Sesión expirada']);
    exit();
}

$remitente_id = (int)$_SESSION['usuario_id'];
$mi_rol = (int)($_SESSION['rol_id'] ?? 0);

if ($mi_rol != 1 && $mi_rol != 2 && $mi_rol != 3) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para realizar emisiones masivas.']);
    exit();
}

$grupos_json = filter_input(INPUT_POST, 'grupos', FILTER_DEFAULT, FILTER_REQUIRE_SCALAR) ?? '[]';
$grupos = json_decode($grupos_json, true);
$contenido = filter_input(INPUT_POST, 'contenido', FILTER_DEFAULT, FILTER_REQUIRE_SCALAR) ?? '';
$prioridad = filter_input(INPUT_POST, 'prioridad', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1, 'max_range' => 5]]) ?? 1;

if (empty($grupos) || empty($contenido)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios para el envío.']);
    exit();
}

$fecha_actual = date('Y-m-d H:i:s');
$exitos = 0;
$errores = 0;

foreach ($grupos as $grupo_id) {
    $gid = filter_var($grupo_id, FILTER_VALIDATE_INT);
    if ($gid === false || $gid <= 0) continue;

    $stmt = $db->prepare("INSERT INTO mensajes (remitente_id, destinatario_id, grupo_id, asunto, contenido, prioridad, fecha_envio) 
                          VALUES (:rem, 0, :grp, 'Comunicado Institucional', :cont, :prio, :fecha)");
    $stmt->bindValue(':rem', $remitente_id, PDO::PARAM_INT);
    $stmt->bindValue(':grp', $gid, PDO::PARAM_INT);
    $stmt->bindValue(':cont', $contenido, PDO::PARAM_STR);
    $stmt->bindValue(':prio', $prioridad, PDO::PARAM_INT);
    $stmt->bindValue(':fecha', $fecha_actual, PDO::PARAM_STR);

    if ($stmt->execute()) {
        $exitos++;
    } else {
        $errores++;
    }
}

header('Content-Type: application/json');
if ($exitos > 0) {
    echo json_encode([
        'status' => 'success', 
        'message' => "Comunicado enviado a $exitos cursos correctamente." . ($errores > 0 ? " ($errores fallaron)" : "")
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No se pudo enviar el comunicado a ningún grupo.']);
}
?>