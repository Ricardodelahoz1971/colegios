<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/GUARDAR_ESPECIALIDAD.PHP - PROCESADOR DE ALTA DE MATERIAS v1.1
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE PERMISOS
    if (!tiene_permiso('especialidades')) { 
        throw new Exception("Acceso denegado: Rango académico insuficiente."); 
    }

    $nombre = trim($_POST['nombre_especialidad'] ?? '');
    $area_id = filter_var($_POST['area_id'] ?? '', FILTER_VALIDATE_INT);

    if (empty($nombre)) {
        throw new Exception("El nombre de la materia es obligatorio.");
    }

    // 🛡️ SOBERANÍA: Evitar duplicados (Case Insensitive)
    $check = $db->prepare('SELECT COUNT(*) FROM especialidades WHERE nombre_especialidad = :nom');
    $check->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $check->execute();
    if ((int)$check->fetchColumn() > 0) {
        throw new Exception("Esta materia ya se encuentra en el plan de estudios.");
    }

    $nivel_desde = filter_var($_POST['nivel_desde'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
    $nivel_hasta = filter_var($_POST['nivel_hasta'] ?? 11, FILTER_VALIDATE_INT) ?: 11;

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

