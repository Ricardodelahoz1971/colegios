<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/GUARDAR_CARGA.PHP - PROCESADOR DE ASIGNACIÓN ACADÉMICA v1.1 (ELITE)
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE PERMISOS
    if (!tiene_permiso('zulu')) { 
        throw new Exception("Acceso denegado: Rango académico insuficiente."); 
    }

    $curso_id = filter_var($_POST['curso_id'] ?? '', FILTER_VALIDATE_INT);
    $especialidad_id = filter_var($_POST['especialidad_id'] ?? '', FILTER_VALIDATE_INT);
    $docente_id = filter_var($_POST['docente_id'] ?? '', FILTER_VALIDATE_INT);

    if (!$curso_id || !$especialidad_id || !$docente_id) {
        throw new Exception("Datos incompletos para procesar la asignación.");
    }

    // 🛡️ SOBERANÍA: Evitar duplicados de materia en el mismo curso
    $check = $db->prepare("SELECT COUNT(*) FROM carga_academica WHERE curso_id = :cur AND especialidad_id = :esp");
    $check->execute([':cur' => $curso_id, ':esp' => $especialidad_id]);
    
    if ((int)$check->fetchColumn() > 0) {
        throw new Exception("Esta materia ya está asignada a este curso.");
    }

    // 🛡️ ACCIÓN: Insertar asignación
    $stmt = $db->prepare('INSERT INTO carga_academica (curso_id, especialidad_id, docente_id) VALUES (:cur, :esp, :doc)');
    $stmt->bindValue(':cur', $curso_id, PDO::PARAM_INT);
    $stmt->bindValue(':esp', $especialidad_id, PDO::PARAM_INT);
    $stmt->bindValue(':doc', $docente_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => '¡Asignación académica guardada con éxito!']);
    } else {
        throw new Exception("Fallo en la operación de base de datos.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>

