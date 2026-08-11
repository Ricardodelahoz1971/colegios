<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/EDITAR_ESPECIALIDAD.PHP - PROCESADOR DE EDICIÓN DE MATERIAS v1.1
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE PERMISOS
    if (!tiene_permiso('especialidades')) { 
        throw new Exception("Acceso denegado: Rango académico insuficiente."); 
    }

    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nombre_especialidad'] ?? '');
    $area_id = filter_var($_POST['area_id'] ?? '', FILTER_VALIDATE_INT);

    if (empty($id) || empty($nombre)) {
        throw new Exception("Datos insuficientes para procesar la actualización.");
    }

    // 🛡️ SOBERANÍA: Validar nombre único excluyendo el id actual (Case Insensitive)
    $check = $db->prepare('SELECT COUNT(*) FROM especialidades WHERE nombre_especialidad = :nom AND id != :id');
    $check->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->execute();
    
    if ((int)$check->fetchColumn() > 0) {
        throw new Exception("Esta materia ya está registrada con otro identificador.");
    }

    // Obtener nombre actual para detectar cambio exclusivo de mayúsculas/minúsculas
    $stmt_current = $db->prepare('SELECT nombre_especialidad FROM especialidades WHERE id = :id');
    $stmt_current->execute([':id' => $id]);
    $current_name = $stmt_current->fetchColumn();

    $db->beginTransaction();

    if ($current_name && strcasecmp($current_name, $nombre) === 0 && $current_name !== $nombre) {
        $temp_name = $nombre . '_temp_' . uniqid();
        $stmt_temp = $db->prepare('UPDATE especialidades SET nombre_especialidad = :temp WHERE id = :id');
        $stmt_temp->execute([':temp' => $temp_name, ':id' => $id]);
    }

    $nivel_desde = filter_var($_POST['nivel_desde'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
    $nivel_hasta = filter_var($_POST['nivel_hasta'] ?? 11, FILTER_VALIDATE_INT) ?: 11;

    $stmt = $db->prepare('UPDATE especialidades SET nombre_especialidad = :nom, area_id = :area, nivel_desde = :desde, nivel_hasta = :hasta WHERE id = :id');
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':desde', $nivel_desde, PDO::PARAM_INT);
    $stmt->bindValue(':hasta', $nivel_hasta, PDO::PARAM_INT);
    
    if ($area_id === false || $area_id === null || $area_id === '') {
        $stmt->bindValue(':area', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':area', $area_id, PDO::PARAM_INT);
    }
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $db->commit();
        echo json_encode(['status' => 'success', 'message' => '¡Materia/Especialidad actualizada en el plan académico!']);
    } else {
        $db->rollBack();
        throw new Exception("Error al modificar la bóveda de datos.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>

