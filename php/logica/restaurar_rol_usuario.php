<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/RESTAURAR_ROL_USUARIO.PHP - RESTABLECER SOBERANÍA v1.1 (ELITE)
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';
require_once 'auditoria.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE AUTORIDAD
    if (!tiene_permiso('personal')) {
        throw new Exception('Acceso denegado: No posee facultades para restaurar permisos.');
    }

    $usuario_id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

    if (!$usuario_id) {
        throw new Exception('Identificador de Usuario no especificado.');
    }

    $db->beginTransaction();

    // 1. Borrar todos los permisos individuales
    $stmt_del = $db->prepare("DELETE FROM usuario_permisos WHERE usuario_id = :usr");
    $stmt_del->bindValue(':usr', $usuario_id, PDO::PARAM_INT);
    $stmt_del->execute();

    // 2. Apagar el flag de personalización
    $stmt_upd = $db->prepare("UPDATE usuarios SET permisos_custom = 0 WHERE id = :usr");
    $stmt_upd->bindValue(':usr', $usuario_id, PDO::PARAM_INT);
    $stmt_upd->execute();

    // 3. REGISTRO DE AUDITORÍA
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

