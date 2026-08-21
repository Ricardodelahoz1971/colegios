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

    $nombre = trim(filter_input(INPUT_POST, 'nombre_rol', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');

    if (empty($nombre)) {
        throw new Exception("El nombre del perfil es obligatorio.");
    }

    $check = $db->prepare('SELECT COUNT(*) FROM roles WHERE nombre_rol = :nom');
    $check->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $check->execute();
    if ((int)$check->fetchColumn() > 0) {
        throw new Exception("Este perfil de usuario ya existe en el sistema.");
    }

    $stmt = $db->prepare('INSERT INTO roles (nombre_rol) VALUES (:nom)');
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => '¡Nuevo Perfil Escolar creado con éxito!']);
    } else {
        throw new Exception("Error al guardar en la bóveda de datos.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>