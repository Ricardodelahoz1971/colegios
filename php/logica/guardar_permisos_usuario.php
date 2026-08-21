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
        throw new Exception('Acceso denegado: No posee facultades para alterar permisos personalizados.');
    }

    $usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
    $permisos_json = filter_input(INPUT_POST, 'permisos', FILTER_DEFAULT) ?? '[]';
    $permisos = json_decode($permisos_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($permisos)) {
        throw new Exception("El payload de permisos provisto no posee un formato JSON estructurado válido.");
    }

    if (!$usuario_id) {
        throw new Exception('Identificador de Usuario ausente.');
    }

    if ($usuario_id === (int)$_SESSION['usuario_id']) {
        throw new Exception('Seguridad: Está terminantemente prohibido alterar sus propios permisos.');
    }

    $sql_check_rol = "SELECT u.rol_id, r.nombre_rol FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE u.id = :uid";
    $check_rol = $db->prepare($sql_check_rol);
    $check_rol->bindValue(':uid', $usuario_id, PDO::PARAM_INT);
    $check_rol->execute();
    $res_target = $check_rol->fetch(PDO::FETCH_ASSOC);

    if ($res_target && $res_target['nombre_rol'] === 'Administrador') {
        throw new Exception("El Administrador posee facultades totales permanentes. El protocolo prohíbe la gestión individual.");
    }

    $jerarquia = [1 => 100, 3 => 90, 2 => 80];
    $mi_rol = (int)($_SESSION['rol_id'] ?? 0);
    $target_rol = $res_target ? (int)($res_target['rol_id'] ?? 0) : 0;
    $mi_peso = $jerarquia[$mi_rol] ?? 0;
    $target_peso = $jerarquia[$target_rol] ?? 0;

    if ($mi_rol !== 1 && $mi_peso <= $target_peso) {
        throw new Exception('Seguridad: No posee jerarquía suficiente para modificar permisos de este rango.');
    }

    $db->beginTransaction();

    $stmt_del = $db->prepare("DELETE FROM usuario_permisos WHERE usuario_id = :usr");
    $stmt_del->bindValue(':usr', $usuario_id, PDO::PARAM_INT);
    $stmt_del->execute();

    if (!empty($permisos)) {
        foreach ($permisos as $p_id) {
            $stmt_ins = $db->prepare("INSERT INTO usuario_permisos (usuario_id, permiso_id) VALUES (:usr, :p)");
            $stmt_ins->bindValue(':usr', $usuario_id, PDO::PARAM_INT);
            $stmt_ins->bindValue(':p', (int)$p_id, PDO::PARAM_INT);
            $stmt_ins->execute();
        }
    }

    $stmt_custom = $db->prepare("UPDATE usuarios SET permisos_custom = 1 WHERE id = :usr");
    $stmt_custom->bindValue(':usr', $usuario_id, PDO::PARAM_INT);
    $stmt_custom->execute();
    
    $detalles = "Asignación de Permisos Custom (Usuario ID: $usuario_id). Cantidad: " . count($permisos);
    registrar_accion($db, $_SESSION['usuario_id'], 'PERMISOS_PERSONALIZADOS', 'USUARIO', $usuario_id, $detalles);

    $db->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => '¡Permisos Custom asignados correctamente!'
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