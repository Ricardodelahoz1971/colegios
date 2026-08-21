<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';
require_once 'auditoria.php';

try {
    proteccion_extrema();

    if (!tiene_permiso('personal')) {
        throw new Exception('Acceso denegado: No posee facultades para restaurar permisos.');
    }

    $usuario_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$usuario_id) {
        throw new Exception('Identificador de Usuario no especificado.');
    }

    $db->beginTransaction();

    $stmt_del = $db->prepare("DELETE FROM usuario_permisos WHERE usuario_id = :usr");
    $stmt_del->bindValue(':usr', $usuario_id, PDO::PARAM_INT);
    $stmt_del->execute();

    $stmt_upd = $db->prepare("UPDATE usuarios SET permisos_custom = 0 WHERE id = :usr");
    $stmt_upd->bindValue(':usr', $usuario_id, PDO::PARAM_INT);
    $stmt_upd->execute();

    registrar_accion($db, $_SESSION['usuario_id'], 'PERMISOS_RESTABLECIDOS', 'USUARIO', $usuario_id, 'Se eliminaron permisos individuales para volver a heredar del Rol.');

    $db->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => '¡Permisos personalizados eliminados! El usuario ahora hereda de su Rol.'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Falla de Bóveda: ' . $e->getMessage()]);
}
exit();
?>