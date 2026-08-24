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

    $id = filter_var($input['id'] ?? filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? null, FILTER_VALIDATE_INT);
    $nombre = trim(limpiar_texto_utf8($input['nombre'] ?? filter_input(INPUT_POST, 'nombre', FILTER_DEFAULT) ?? ''));
    $user = strtoupper(trim(limpiar_texto_utf8($input['user'] ?? filter_input(INPUT_POST, 'user', FILTER_DEFAULT) ?? '')));
    $pass = (string)($input['pass'] ?? filter_input(INPUT_POST, 'pass', FILTER_DEFAULT) ?? '');
    $rol = filter_var($input['rol'] ?? filter_input(INPUT_POST, 'rol', FILTER_VALIDATE_INT) ?? null, FILTER_VALIDATE_INT);
    $esp = filter_var($input['esp'] ?? filter_input(INPUT_POST, 'esp', FILTER_VALIDATE_INT) ?? null, FILTER_VALIDATE_INT);

    if ((int)$rol !== 11) {
        $esp = null;
    }

    if ($id === null || $id === false || $nombre === '' || $user === '' || $rol === null || $rol === false) {
        throw new Exception('Faltan datos obligatorios para actualizar el perfil.');
    }

    if ((int)$rol === 12 || (int)$rol === 5) {
        throw new Exception('Operación Inválida: La gestión de estudiantes debe realizarse desde el módulo de Matrícula.');
    }

    if ($id === 1) {
        $rol = 1;
    }

    // Validar que el usuario no esté en uso por otro registro
    $stmt_check = $db->prepare('SELECT id FROM usuarios WHERE UPPER(usuario) = UPPER(:usr) AND id != :id');
    $stmt_check->bindValue(':usr', $user, PDO::PARAM_STR);
    $stmt_check->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt_check->execute();
    if ($stmt_check->fetch()) {
        throw new Exception('El nombre de usuario "' . $user . '" ya pertenece a otro miembro del personal.');
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

} catch (PDOException $pe) {
    $msg = $pe->getMessage();
    if ($pe->getCode() === '23000' || strpos($msg, '1062') !== false || strpos($msg, 'Duplicate entry') !== false) {
        $msg = 'El nombre de usuario ya está asignado a otra cuenta. Elija uno diferente.';
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
exit();
?>