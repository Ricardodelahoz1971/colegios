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
        throw new Exception("Acceso denegado: Rango académico insuficiente.");
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nombre = limpiar_texto_utf8(filter_input(INPUT_POST, 'nombre_rol', FILTER_DEFAULT) ?? '');

    if (empty($id) || empty($nombre)) {
        throw new Exception("Datos incompletos para actualizar el perfil.");
    }

    $check = $db->prepare('SELECT COUNT(*) FROM roles WHERE nombre_rol = :nom AND id != :id');
    $check->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->execute();
    if ((int)$check->fetchColumn() > 0) {
        throw new Exception("Ya existe otro perfil de usuario con ese nombre.");
    }

    $stmt = $db->prepare('UPDATE roles SET nombre_rol = :nom WHERE id = :id');
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => '¡Perfil de usuario actualizado con éxito!']);
    } else {
        throw new Exception("Error al modificar la bóveda de datos.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>