<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/GUARDAR_PERSONAL.PHP - MOTOR DE IDENTIDAD SEGURO v2.1
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE AUTORIDAD
    if (!tiene_permiso('personal')) {
        throw new Exception('Acceso denegado o privilegios insuficientes.');
    }

    $nombre = e($_POST['nombre'] ?? '');
    $user = strtoupper(e($_POST['user'] ?? ''));
    $pass = $_POST['pass'] ?? '';
    $rol = filter_var($_POST['rol'] ?? 0, FILTER_VALIDATE_INT);
    $esp = $_POST['esp'] ?? '';

    // Solo a los docentes (rol_id = 11) se les asigna una especialidad/materia
    if ($rol !== 11) {
        $esp = '';
    }

    if (empty($nombre) || empty($user) || empty($pass) || empty($rol)) {
        throw new Exception('Faltan datos obligatorios para registrar al personal.');
    }

    // BLOQUEO DE SEGURIDAD ELITE: Impedir rol Estudiante desde Personal de manera dinámica
    $stmt_rol_check = $db->prepare("SELECT nombre_rol FROM roles WHERE id = ?");
    $stmt_rol_check->execute([$rol]);
    $rol_nombre = strtolower($stmt_rol_check->fetchColumn() ?: '');

    if (strpos($rol_nombre, 'estudiante') !== false || strpos($rol_nombre, 'alumno') !== false) {
        throw new Exception('Operación Inválida: La matriculación de estudiantes debe realizarse desde el módulo de Matrícula.');
    }

    // 1. VALIDAR SI EL USUARIO YA EXISTE (Bóveda de Identidad)
    $stmt_check = $db->prepare('SELECT id FROM usuarios WHERE UPPER(usuario) = UPPER(:usr)');
    $stmt_check->bindValue(':usr', $user, PDO::PARAM_STR);
    $stmt_check->execute();
    if ($stmt_check->fetch()) {
        throw new Exception('El nombre de usuario "'.$user.'" ya se encuentra en uso.');
    }

    // 2. ENCRIPTAR LA CONTRASEÑA
    $pass_segura = password_hash($pass, PASSWORD_BCRYPT);
    
    // 3. INSERTAR EN LA BASE DE DATOS
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

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error de Bóveda: ' . $e->getMessage()
    ]);
}
?>

