<?php
declare(strict_types=1);
// PHP/LOGICA/AUTHCONTROLLER.PHP - EL GUARDIÁN DEL ACCESO v9.2
// Esta clase centraliza la lógica de autenticación y gestión de sesiones institucionales.

class AuthController {
    
    /**
     * Procesa el intento de inicio de sesión.
     */
    public static function login(PDO $db, string $username, string $password): array {
        // Validación de datos incompletos (Frontera de Seguridad)
        if (empty($username) && empty($password)) return ['status' => 'error', 'code' => 'campos_vacios'];
        if (empty($username)) return ['status' => 'error', 'code' => 'falta_usuario'];
        if (empty($password)) return ['status' => 'error', 'code' => 'falta_clave'];

        try {
            // Búsqueda unificada con normalización de mayúsculas y Prepared Statements
            $stmt = $db->prepare('
                SELECT u.*, r.nombre_rol 
                FROM usuarios u 
                JOIN roles r ON u.rol_id = r.id 
                WHERE UPPER(u.usuario) = UPPER(?)
            ');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                self::initSession($user);
                return ['status' => 'success', 'redirect' => 'dashboard.php'];
            }

            return ['status' => 'error', 'code' => 'credenciales_invalidas'];

        } catch (PDOException $e) {
            error_log("Error de Auth: " . $e->getMessage());
            return ['status' => 'error', 'code' => 'db_fail'];
        }
    }

    /**
     * Inicializa la sesión institucional y persiste la identidad del usuario.
     */
    private static function initSession(array $user): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Regenerar ID de sesión para prevenir Session Fixation (Seguridad Senior)
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nombre_usuario'] = $user['nombre'];
        $_SESSION['identificacion'] = $user['usuario']; 
        $_SESSION['rol_id'] = $user['rol_id'];
        $_SESSION['rol_nombre'] = $user['nombre_rol'];
        $_SESSION['last_activity'] = time();
        
        // Caso especial: Estudiantes (Vinculación Directa)
        if ((int)$user['rol_id'] === 5) {
            $_SESSION['estudiante_id'] = $user['estudiante_id'] ?? null;
        }
    }

    /**
     * Cierre de sesión seguro.
     */
    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_unset();
        session_destroy();
        header("Location: ../index.php");
        exit();
    }
}
?>
