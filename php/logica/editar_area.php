<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/EDITAR_AREA.PHP - PROCESADOR DE EDICIÓN DE ÁREAS v1.1
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE PERMISOS
    if (!tiene_permiso('areas')) { 
        throw new Exception("Acceso denegado: Rango académico insuficiente."); 
    }

    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nombre_area'] ?? '');

    if (empty($id) || empty($nombre)) {
        throw new Exception("Datos incompletos para procesar la edición.");
    }

    // 🛡️ SOBERANÍA: Validar duplicado excluyendo actual (Case Insensitive)
    $check = $db->prepare('SELECT COUNT(*) FROM areas WHERE nombre_area = :nom AND id != :id');
    $check->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->execute();
    
    if ((int)$check->fetchColumn() > 0) {
        throw new Exception("Ya existe otra área con ese nombre en el sistema.");
    }

    // Obtener nombre actual para detectar cambio exclusivo de mayúsculas/minúsculas
    $stmt_current = $db->prepare('SELECT nombre_area FROM areas WHERE id = :id');
    $stmt_current->execute([':id' => $id]);
    $current_name = $stmt_current->fetchColumn();

    $db->beginTransaction();

    if ($current_name && strcasecmp($current_name, $nombre) === 0 && $current_name !== $nombre) {
        $temp_name = $nombre . '_temp_' . uniqid();
        $stmt_temp = $db->prepare('UPDATE areas SET nombre_area = :temp WHERE id = :id');
        $stmt_temp->execute([':temp' => $temp_name, ':id' => $id]);
    }

    $stmt = $db->prepare('UPDATE areas SET nombre_area = :nom WHERE id = :id');
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $db->commit();
        echo json_encode(['status' => 'success', 'message' => '¡Área actualizada correctamente!']);
    } else {
        $db->rollBack();
        throw new Exception("Error al actualizar la bóveda de datos.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>

