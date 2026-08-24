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

    if (!tiene_permiso('areas')) {
        throw new Exception("Acceso denegado: Rango académico insuficiente.");
    }

    $nombre = limpiar_texto_utf8(filter_input(INPUT_POST, 'nombre_area', FILTER_DEFAULT) ?? '');

    if (empty($nombre)) {
        throw new Exception("El nombre del área es obligatorio.");
    }

    $check = $db->prepare('SELECT COUNT(*) FROM areas WHERE nombre_area = :nom');
    $check->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $check->execute();
    if ((int)$check->fetchColumn() > 0) {
        throw new Exception("Esta área ya está registrada en el mapa institucional.");
    }

    $stmt = $db->prepare('INSERT INTO areas (nombre_area) VALUES (:nom)');
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => '¡Nueva Área integrada con éxito!']);
    } else {
        throw new Exception("Error al procesar el registro en la bóveda.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>