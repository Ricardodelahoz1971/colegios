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

    if (!tiene_permiso('especialidades')) {
        throw new Exception("Acceso denegado: Rango académico insuficiente.");
    }

    $nombre = trim(filter_input(INPUT_POST, 'nombre_especialidad', FILTER_SANITIZE_STRING) ?? '');
    $area_id = filter_input(INPUT_POST, 'area_id', FILTER_VALIDATE_INT);
    $nivel_desde = filter_input(INPUT_POST, 'nivel_desde', FILTER_VALIDATE_INT) ?: 1;
    $nivel_hasta = filter_input(INPUT_POST, 'nivel_hasta', FILTER_VALIDATE_INT) ?: 11;

    if (empty($nombre)) {
        throw new Exception("El nombre de la materia es obligatorio.");
    }

    $check = $db->prepare('SELECT COUNT(*) FROM especialidades WHERE nombre_especialidad = :nom');
    $check->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $check->execute();
    if ((int)$check->fetchColumn() > 0) {
        throw new Exception("Esta materia ya se encuentra en el plan de estudios.");
    }

    $stmt = $db->prepare('INSERT INTO especialidades (nombre_especialidad, area_id, nivel_desde, nivel_hasta) VALUES (:nom, :area, :desde, :hasta)');
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':desde', $nivel_desde, PDO::PARAM_INT);
    $stmt->bindValue(':hasta', $nivel_hasta, PDO::PARAM_INT);
    
    if ($area_id === false || $area_id === null || $area_id === '') {
        $stmt->bindValue(':area', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':area', $area_id, PDO::PARAM_INT);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => '¡Materia registrada con éxito!']);
    } else {
        throw new Exception("Fallo al inscribir en la base de datos.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>