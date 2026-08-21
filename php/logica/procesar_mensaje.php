<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    $remitente_id = (int)$_SESSION['usuario_id'];
    $destinatario_id = (int)(filter_input(INPUT_POST, 'destinatario_id', FILTER_VALIDATE_INT) ?? 0);
    $chat_type = filter_input(INPUT_POST, 'chat_type', FILTER_SANITIZE_STRING) ?? 'direct';
    $asunto = filter_input(INPUT_POST, 'asunto', FILTER_SANITIZE_STRING) ?? 'Sin asunto';
    $contenido = trim(filter_input(INPUT_POST, 'contenido', FILTER_SANITIZE_STRING) ?? '');
    $prioridad = (int)(filter_input(INPUT_POST, 'prioridad', FILTER_VALIDATE_INT) ?? 1);

    if (empty($contenido)) {
        throw new Exception('El contenido del mensaje no puede estar vacío.');
    }

    $grupo_id = ($chat_type === 'group') ? $destinatario_id : 0;
    $real_dest_id = ($chat_type === 'group') ? 0 : $destinatario_id;

    $fecha_actual = date('Y-m-d H:i:s');

    $stmt = $db->prepare("INSERT INTO mensajes (remitente_id, destinatario_id, grupo_id, asunto, contenido, prioridad, fecha_envio) 
                          VALUES (:rem, :dest, :grupo, :asu, :cont, :prio, :fecha)");
    $stmt->bindValue(':rem', $remitente_id, PDO::PARAM_INT);
    $stmt->bindValue(':dest', $real_dest_id, PDO::PARAM_INT);
    $stmt->bindValue(':grupo', $grupo_id, PDO::PARAM_INT);
    $stmt->bindValue(':asu', $asunto, PDO::PARAM_STR);
    $stmt->bindValue(':cont', $contenido, PDO::PARAM_STR);
    $stmt->bindValue(':prio', $prioridad, PDO::PARAM_INT);
    $stmt->bindValue(':fecha', $fecha_actual, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => '¡Mensaje transmitido con éxito!']);
    } else {
        throw new Exception("Fallo en la sincronización del canal de comunicación.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>