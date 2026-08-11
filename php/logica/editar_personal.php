<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/EDITAR_PERSONAL.PHP - ACTUALIZADOR DE IDENTIDAD v1.1 (ELITE)
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE AUTORIDAD
    if (!tiene_permiso('personal')) {
        throw new Exception('Acceso denegado o privilegios insuficientes.');
    }

    $id = $_POST['id'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $user = strtoupper(trim($_POST['user'] ?? ''));
    $pass = $_POST['pass'] ?? '';
    $rol = $_POST['rol'] ?? '';
    $esp = $_POST['esp'] ?? '';

    // Solo a los docentes (rol_id = 11) se les asigna una especialidad/materia
    if ((int)$rol !== 11) {
        $esp = '';
    }

    if (empty($id) || empty($nombre) || empty($user) || empty($rol)) {
        throw new Exception('Faltan datos obligatorios para actualizar el perfil.');
    }

    // BLOQUEO DE SEGURIDAD ELITE: Impedir rol Estudiante desde Personal
    if ((int)$rol === 12) {
        throw new Exception('Operación Inválida: No se puede asignar el rol de Estudiante desde este módulo.');
    }

    // 1. REGLA DE PROTECCIÓN: El superadministrador (ID 1) mantiene su rol 1
    if ($id == 1) {
        $rol = 1;
    }

    // 2. CONSTRUIR SQL DINÁMICO (Contraseña opcional)
    if (!empty($pass)) {
        $pass_cripto = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE usuarios SET nombre = :nom, usuario = :usr, password = :pwd, rol_id = :rol, especialidad_id = :esp WHERE id = :id');
        $stmt->bindValue(':pwd', $pass_cripto, PDO::PARAM_STR);
    } else {
        $stmt = $db->prepare('UPDATE usuarios SET nombre = :nom, usuario = :usr, rol_id = :rol, especialidad_id = :esp WHERE id = :id');
    }

    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':usr', $user, PDO::PARAM_STR);
    $stmt->bindValue(':rol', (int)$rol, PDO::PARAM_INT);
    
    if ($esp === '' || $esp === null || $esp === '0') {
        $stmt->bindValue(':esp', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':esp', (int)$esp, PDO::PARAM_INT);
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

