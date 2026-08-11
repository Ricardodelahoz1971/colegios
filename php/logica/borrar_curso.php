<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/BORRAR_CURSO.PHP - PROCESADOR DE BAJA DE AULAS v1.1
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE PERMISOS
    if (!tiene_permiso('cursos')) { 
        throw new Exception("Acceso denegado: Rango insuficiente."); 
    }

    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception("Identificador de curso inválido.");
    }

    $stmt = $db->prepare('DELETE FROM cursos WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Aula/Curso retirado del sistema correctamente.']);
    } else {
        throw new Exception("Fallo en la operación de base de datos.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>

