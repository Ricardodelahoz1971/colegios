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

    if (!tiene_permiso('roles')) {
        throw new Exception('Acceso denegado: No posee facultades para alterar leyes maestras.');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Payload JSON inválido.');
    }

    $rol_id = filter_var($input['rol_id'] ?? null, FILTER_VALIDATE_INT);
    $permisos = $input['permisos'] ?? [];

    if (!is_array($permisos)) {
        throw new Exception("El payload de permisos provisto no posee un formato JSON estructurado válido.");
    }

    if (!$rol_id) {
        throw new Exception('Identificador de Rol ausente o inválido.');
    }

    if ($rol_id == 1) {
        throw new Exception('El Perfil de Administrador posee facultades inamovibles por protocolo.');
    }

    $db->beginTransaction();

    $stmt_del = $db->prepare("DELETE FROM rol_permisos WHERE rol_id = :rol");
    $stmt_del->bindValue(':rol', $rol_id, PDO::PARAM_INT);
    $stmt_del->execute();

    if (!empty($permisos)) {
        $stmt_ins = $db->prepare("INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (:rol, :p)");
        foreach ($permisos as $p_id) {
            $permiso_id = filter_var($p_id, FILTER_VALIDATE_INT);
            if ($permiso_id === false) {
                throw new Exception('Identificador de permiso inválido en el payload.');
            }
            $stmt_ins->bindValue(':rol', $rol_id, PDO::PARAM_INT);
            $stmt_ins->bindValue(':p', $permiso_id, PDO::PARAM_INT);
            $stmt_ins->execute();
        }
    }

    $detalles = "Sincronización de Leyes Maestras (Rol ID: $rol_id). Cantidad: " . count($permisos);
    registrar_accion($db, $_SESSION['usuario_id'], 'PERMISOS_ROL_ACTUALIZADOS', 'ROL', $rol_id, $detalles);

    $db->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => '¡Leyes maestras del perfil actualizadas correctamente!'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode([
        'status' => 'error', 
        'message' => 'Falla de Bóveda: ' . $e->getMessage()
    ]);
}
exit();
?>