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

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $nombre = trim(limpiar_texto_utf8($input['nombre'] ?? filter_input(INPUT_POST, 'nombre', FILTER_DEFAULT) ?? ''));
    $user = strtoupper(trim(limpiar_texto_utf8($input['user'] ?? filter_input(INPUT_POST, 'user', FILTER_DEFAULT) ?? '')));
    $pass = (string)($input['pass'] ?? filter_input(INPUT_POST, 'pass', FILTER_DEFAULT) ?? '');
    $rol = filter_var($input['rol'] ?? filter_input(INPUT_POST, 'rol', FILTER_VALIDATE_INT) ?? 0, FILTER_VALIDATE_INT);
    $esp = limpiar_texto_utf8($input['esp'] ?? filter_input(INPUT_POST, 'esp', FILTER_DEFAULT) ?? '');

    if ($rol === false) {
        $rol = 0;
    }

    if ($rol !== 11) {
        $esp = '';
    }

    if (empty($nombre) || empty($user) || empty($pass) || empty($rol)) {
        throw new Exception('Faltan datos obligatorios para registrar al personal.');
    }

    $stmt_rol_check = $db->prepare("SELECT nombre_rol FROM roles WHERE id = :rol_id");
    $stmt_rol_check->bindValue(':rol_id', $rol, PDO::PARAM_INT);
    $stmt_rol_check->execute();
    $rol_nombre = strtolower($stmt_rol_check->fetchColumn() ?: '');

    if (strpos($rol_nombre, 'estudiante') !== false || strpos($rol_nombre, 'alumno') !== false) {
        throw new Exception('Operación Inválida: La matriculación de estudiantes debe realizarse desde el módulo de Matrícula.');
    }

    $stmt_check = $db->prepare('SELECT id FROM usuarios WHERE UPPER(usuario) = UPPER(:usr)');
    $stmt_check->bindValue(':usr', $user, PDO::PARAM_STR);
    $stmt_check->execute();
    if ($stmt_check->fetch()) {
        throw new Exception('El nombre de usuario "'.$user.'" ya se encuentra registrado en el sistema.');
    }

    $pass_segura = password_hash($pass, PASSWORD_BCRYPT);
    
    $stmt = $db->prepare('INSERT INTO usuarios (nombre, usuario, password, rol_id, especialidad_id) VALUES (:nom, :usr, :pass, :rol, :esp)');
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':usr', $user, PDO::PARAM_STR);
    $stmt->bindValue(':pass', $pass_segura, PDO::PARAM_STR);
    $stmt->bindValue(':rol', $rol, PDO::PARAM_INT);
    
    if ($esp === '' || $esp === null) {
        $stmt->bindValue(':esp', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':esp', (int)$esp, PDO::PARAM_INT);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => '¡Personal registrado y credenciales activadas!']);
    } else {
        throw new Exception('Fallo crítico al registrar en la base de datos.');
    }

} catch (PDOException $pe) {
    $msg = $pe->getMessage();
    if ($pe->getCode() === '23000' || strpos($msg, '1062') !== false || strpos($msg, 'Duplicate entry') !== false) {
        $msg = 'El nombre de usuario ya se encuentra registrado. Por favor ingrese uno diferente.';
    }
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error de Bóveda: ' . $msg
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error de Bóveda: ' . $e->getMessage()
    ]);
}
?>