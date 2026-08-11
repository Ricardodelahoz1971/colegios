<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/GUARDAR_PERMISOS_ROL.PHP - GESTIÓN DE LEYES MAESTRAS v1.1 (ELITE)
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';
require_once 'auditoria.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE AUTORIDAD (Solo usuarios con permiso 'roles' o Admin)
    if (!tiene_permiso('roles')) {
        throw new Exception('Acceso denegado: No posee facultades para alterar leyes maestras.');
    }

    $rol_id = filter_var($_POST['rol_id'] ?? '', FILTER_VALIDATE_INT);
    $permisos_json = $_POST['permisos'] ?? '[]';
    $permisos = json_decode($permisos_json, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($permisos)) {
        throw new Exception("El payload de permisos provisto no posee un formato JSON estructurado válido.");
    }

    if (!$rol_id) {
        throw new Exception('Identificador de Rol ausente o inválido.');
    }

    // 🛡️ SOBERANÍA: No se pueden alterar los permisos del Administrador (ID 1) si no es por DB
    if ($rol_id == 1) {
        throw new Exception('El Perfil de Administrador posee facultades inamovibles por protocolo.');
    }

    $db->beginTransaction();

    // 1. LIMPIAR LEYES ANTERIORES PARA ESTE ROL (Tabula Rasa)
    $stmt_del = $db->prepare("DELETE FROM rol_permisos WHERE rol_id = :rol");
    $stmt_del->bindValue(':rol', $rol_id, PDO::PARAM_INT);
    $stmt_del->execute();

    // 2. INYECTAR NUEVAS LEYES
    if (!empty($permisos)) {
        foreach ($permisos as $p_id) {
            $stmt_ins = $db->prepare("INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (:rol, :p)");
            $stmt_ins->bindValue(':rol', $rol_id, PDO::PARAM_INT);
            $stmt_ins->bindValue(':p', (int)$p_id, PDO::PARAM_INT);
            $stmt_ins->execute();
        }
    }

    // 3. REGISTRO DE AUDITORÍA
    $detalles = "Sincronización de Leyes Maestras (Rol ID: $rol_id). Cantidad: " . count($permisos);
    registrar_accion($db, $_SESSION['usuario_id'], 'PERMISOS_ROL_ACTUALIZADOS', 'ROL', $rol_id, $detalles);

    $db->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => '¡Leyes maestras del perfil actualizadas correctamente!'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode([
        'status' => 'error', 
        'message' => 'Falla de Bóveda: ' . $e->getMessage()
    ]);
}
exit();
?>

