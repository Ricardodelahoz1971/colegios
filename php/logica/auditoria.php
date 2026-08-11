<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
// PHP/LOGICA/AUDITORIA.PHP - EL REGISTRO DE MEMORIA DEL SISTEMA

/**
 * Asegura que la tabla de auditoría exista en la base de datos.
 */
$db->exec("CREATE TABLE IF NOT EXISTS logs_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    actor_usuario_id INT,
    accion VARCHAR(100),
    objetivo_tipo VARCHAR(50),
    objetivo_id INT,
    detalles TEXT
)");

/**
 * Registra una acción administrativa en la base de datos.
 * 
 * @param PDO $db La conexión a la base de datos.
 * @param int|null $actor_id ID del usuario que realiza la acción (null si es el sistema).
 * @param string $accion Nombre de la acción (ej: 'PERMISOS_PERSONALIZADOS').
 * @param string $obj_tipo Tipo de objeto afectado (USUARIO, ROL, ESTUDIANTE, etc).
 * @param int $obj_id ID del objeto afectado.
 * @param string $detalles Descripción textual o JSON de los cambios.
 */
function registrar_accion(PDO $db, ?int $actor_id, string $accion, string $obj_tipo, int $obj_id, string $detalles): bool {
    // Si no hay actor_id, intentamos tomarlo de la sesión actual
    if ($actor_id === null && isset($_SESSION['usuario_id'])) {
        $actor_id = $_SESSION['usuario_id'];
    }

    $query = "INSERT INTO logs_auditoria (actor_usuario_id, accion, objetivo_tipo, objetivo_id, detalles) 
              VALUES (:actor, :accion, :tipo, :oid, :det)";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':actor', $actor_id, PDO::PARAM_INT);
    $stmt->bindValue(':accion', $accion, PDO::PARAM_STR);
    $stmt->bindValue(':tipo', $obj_tipo, PDO::PARAM_STR);
    $stmt->bindValue(':oid', $obj_id, PDO::PARAM_INT);
    $stmt->bindValue(':det', $detalles, PDO::PARAM_STR);
    
    return $stmt->execute();
}
?>
