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

    if (!tiene_permiso('personal')) {
        throw new Exception('Acceso denegado o privilegios insuficientes.');
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nombre = trim((string)filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $user = strtoupper(trim((string)filter_input(INPUT_POST, 'user', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? ''));
    $pass = (string)filter_input(INPUT_POST, 'pass', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $rol = filter_input(INPUT_POST, 'rol', FILTER_VALIDATE_INT);
    $esp = filter_input(INPUT_POST, 'esp', FILTER_VALIDATE_INT);

    if ((int)$rol !== 11) {
        $esp = null;
    }

    if ($id === null || $id === false || $nombre === '' || $user === '' || $rol === null || $rol === false) {
        throw new Exception('Faltan datos obligatorios para actualizar el perfil.');
    }

    if ((int)$rol === 12) {
        throw new Exception('Operación Inválida: No se puede asignar el rol de Estudiante desde este módulo.');
    }

    if ($id === 1) {
        $rol = 1;
    }

    if (!empty($pass)) {
        $pass_cripto = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE usuarios SET nombre = :nom, usuario = :usr, password = :pwd, rol_id = :rol, especialidad_id = :esp WHERE id = :id');
        $stmt->bindValue(':pwd', $pass_cripto, PDO::PARAM_STR);
    } else {
        $stmt = $db->prepare('UPDATE usuarios SET nombre = :nom, usuario = :usr, rol_id = :rol, especialidad_id = :esp WHERE id = :id');
    }

    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':usr', $user, PDO::PARAM_STR);
    $stmt->bindValue(':rol', $rol, PDO::PARAM_INT);
    
    if ($esp === null || $esp === 0) {
        $stmt->bindValue(':esp', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':esp', $esp, PDO::PARAM_INT);
    }
    
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => '¡Perfil institucional actualizado correctamente!']);
    } else {
        throw new Exception('Fallo crítico al actualizar en la bóveda de datos.');
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error de Bóveda: ' . $e->getMessage()
    ]);
}
exit();
?>