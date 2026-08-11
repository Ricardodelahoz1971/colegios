<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/AUTH.PHP - EL CENTINELA DE ACCESO MODULAR
require_once __DIR__ . '/security.php';

/**
 * Función Maestra: ¿Tiene permiso este usuario para este módulo?
 * @param string $clave_modulo La clave técnica (ej: 'matricula')
 * @return bool Verdadero si tiene permiso
 */
function tiene_permiso(string $clave_modulo): bool {
    global $db;

    // 1. PROTOCOLO DE SUPERUSUARIO AUTÉNTICO (Rol Administrador con ID 1)
    if (isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === 1) {
        return true;
    }

    // 2. ¿ES UNA CUENTA CON SOBERANÍA INDIVIDUAL?
    if (!isset($_SESSION['usuario_id'])) return false;
    $usuario_id = $_SESSION['usuario_id'];

    // Consultamos si el usuario ha sido personalizado por el Administrador.
    $query_c = "SELECT permisos_custom, rol_id FROM usuarios WHERE id = :usr";
    $stmt_c = $db->prepare($query_c);
    $stmt_c->bindValue(':usr', $usuario_id, PDO::PARAM_INT);
    $stmt_c->execute();
    $res_c = $stmt_c->fetch(PDO::FETCH_ASSOC);
    
    if (!$res_c) return false;

    $custom = (int)($res_c['permisos_custom'] ?? 0);
    $rol_id = (int)($res_c['rol_id'] ?? 0);

    // 3. CONSULTA SEGÚN EL MODELO (Soberanía vs Herencia de Rol)
    if ($custom === 1) {
        // MODELO DE SOBERANÍA: Solo importa lo que diga su tabla individual.
        $query_u = "
            SELECT COUNT(*) as cuenta 
            FROM usuario_permisos up
            JOIN permisos p ON up.permiso_id = p.id
            WHERE up.usuario_id = :usr AND p.clave = :clave
        ";
        $stmt_u = $db->prepare($query_u);
        $stmt_u->bindValue(':usr', $usuario_id, PDO::PARAM_INT);
        $stmt_u->bindValue(':clave', $clave_modulo, PDO::PARAM_STR);
        $stmt_u->execute();
        $res_u = $stmt_u->fetch(PDO::FETCH_ASSOC);
        return (($res_u['cuenta'] ?? 0) > 0);
    } else {
        // MODELO DE HERENCIA: Se rige por las leyes de rango (Rol).
        if (!$rol_id) return false;

        $query_r = "
            SELECT COUNT(*) as cuenta 
            FROM rol_permisos rp
            JOIN permisos p ON rp.permiso_id = p.id
            WHERE rp.rol_id = :rol AND p.clave = :clave
        ";
        $stmt_r = $db->prepare($query_r);
        $stmt_r->bindValue(':rol', $rol_id, PDO::PARAM_INT);
        $stmt_r->bindValue(':clave', $clave_modulo, PDO::PARAM_STR);
        $stmt_r->execute();
        $res_r = $stmt_r->fetch(PDO::FETCH_ASSOC);
        return (($res_r['cuenta'] ?? 0) > 0);
    }
}

/**
 * Verifica si el usuario actual posee uno de los roles indicados.
 * @param array $roles_permitidos Lista de nombres de roles o IDs (ej: ['administrador', 20])
 */
function tienen_rol(array $roles_permitidos): bool {
    if (!isset($_SESSION['rol_nombre']) && !isset($_SESSION['rol_id'])) return false;
    
    foreach ($roles_permitidos as $rol) {
        if (is_numeric($rol)) {
            if ((int)$_SESSION['rol_id'] === (int)$rol) return true;
        } else {
            if (strtolower($_SESSION['rol_nombre'] ?? '') === strtolower($rol)) return true;
        }
    }
    
    // Fallback para administradores legítimos por ID de Rol
    if (isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === 1) {
        return true;
    }

    return false;
}
?>
