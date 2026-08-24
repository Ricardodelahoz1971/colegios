<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    if (!tiene_permiso('roles')) {
        throw new Exception("Acceso denegado: Rango insuficiente.");
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception("Identificador de rol inválido.");
    }

    $check = $db->prepare("SELECT nombre_rol FROM roles WHERE id = :id");
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->execute();
    $res = $check->fetch(PDO::FETCH_ASSOC);

    if ($res && ($res['nombre_rol'] === 'Administrador' || $res['nombre_rol'] === 'Director')) {
        throw new Exception("Este perfil está protegido por el protocolo del sistema.");
    }

    // 🛡️ VALIDACIÓN PREVENTIVA: Usuarios asignados al rol
    $stmt_u = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id = ?");
    $stmt_u->execute([$id]);
    if ((int)$stmt_u->fetchColumn() > 0) {
        throw new Exception("No es posible eliminar el rol: Existen usuarios institucionales asignados a este perfil. Reasigne su rol antes de continuar.");
    }

    $stmt = $db->prepare('DELETE FROM roles WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Perfil de usuario eliminado permanentemente.']);
    } else {
        throw new Exception("Error en la ejecución de base de datos.");
    }

} catch (PDOException $pe) {
    $msg = $pe->getMessage();
    if ($pe->getCode() === '23000' || strpos($msg, '1451') !== false || strpos($msg, 'foreign key constraint') !== false) {
        $msg = 'Acción Bloqueada: El rol está asignado a usuarios existentes y no puede eliminarse.';
    }
    echo json_encode(['status' => 'error', 'message' => 'Error de Bóveda: ' . $msg]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>