<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();

ob_start();
require_once '../db.php';
require_once '../auth.php';
ob_clean();

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

try {
    proteccion_extrema();

    $user_id = (int)$_SESSION['usuario_id'];
    $mensaje_id = filter_input(INPUT_POST, 'mensaje_id', FILTER_VALIDATE_INT);

    if ($mensaje_id === false || $mensaje_id === null || $mensaje_id <= 0) {
        throw new Exception('Identificador de mensaje no válido.');
    }

    $stmt_check = $db->prepare("SELECT destinatario_id, leido, prioridad FROM mensajes WHERE id = :id");
    $stmt_check->bindValue(':id', $mensaje_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $msg = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$msg) {
        throw new Exception('El mensaje solicitado no existe.');
    }

    if ((int)$msg['destinatario_id'] !== $user_id) {
        throw new Exception('No tiene permisos para firmar este mensaje.');
    }

    if ((int)$msg['prioridad'] !== 3) {
        throw new Exception('Solo se pueden firmar acuses de mensajes urgentes.');
    }

    $fecha_actual = date('Y-m-d H:i:s');
    $stmt_upd = $db->prepare("UPDATE mensajes SET leido = 1, fecha_lectura = :fecha WHERE id = :id");
    $stmt_upd->bindValue(':fecha', $fecha_actual, PDO::PARAM_STR);
    $stmt_upd->bindValue(':id', $mensaje_id, PDO::PARAM_INT);

    if ($stmt_upd->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Acuse de recibo registrado correctamente.',
            'fecha' => date('H:i d/m', strtotime($fecha_actual))
        ]);
    } else {
        throw new Exception('Error al actualizar el acuse de recibo.');
    }

} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit();
?>