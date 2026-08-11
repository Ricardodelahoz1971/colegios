<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/BORRAR_CARGA.PHP - PROCESADOR DE BAJA DE CARGA ACADÉMICA v1.1
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE PERMISOS
    if (!tiene_permiso('zulu')) { 
        throw new Exception("Acceso denegado: Rango académico insuficiente."); 
    }

    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception("Identificador de carga académica inválido.");
    }

    $stmt = $db->prepare('DELETE FROM carga_academica WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Carga académica retirada correctamente.']);
    } else {
        throw new Exception("Error en la bóveda de datos.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>

