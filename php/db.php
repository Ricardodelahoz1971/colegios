<?php
declare(strict_types=1);
// PHP/DB.PHP - EL CEREBRO DE LA CONEXIÓN v10.0 (MARIADB EDITION)
date_default_timezone_set('America/Bogota');

try {
    // Cargar variables de entorno desde .env
    $env_file = __DIR__ . '/../.env';
    $env_vars = [];
    if (file_exists($env_file)) {
        $env_vars = parse_ini_file($env_file);
    }

    $host = $env_vars['DB_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $env_vars['DB_NAME'] ?? $_ENV['DB_NAME'] ?? 'sistema_escolar';
    $user = $env_vars['DB_USER'] ?? $_ENV['DB_USER'] ?? 'root';
    $pass = $env_vars['DB_PASS'] ?? $_ENV['DB_PASS'] ?? '';

    // Auto-creación de la base de datos si no existe (Entorno Local/Ficticio)
    $pdo_setup = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo_setup->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_setup->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Conexión principal
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    die("Error fatal: No se pudo conectar a la base de datos escolar MariaDB.");
}

// --- MOTOR DE INTEGRIDAD Y EVOLUCIÓN ÉLITE ---
try {
    $stmt_v = $db->prepare("SELECT valor FROM configuracion_global WHERE clave = 'schema_version'"); $stmt_v->execute();
    $schema_version = $stmt_v ? (int)$stmt_v->fetchColumn() : 0;
} catch (Exception $e) {
    $schema_version = 0;
}

if ($schema_version < 99) {
    include_once __DIR__ . '/logica/db_integridad.php';
    // db_integridad actualiza la versión al finalizar
}

// Auto-despliegue de la tabla de periodos académicos si no existe
try {
    $stmt = $db->prepare("SELECT 1 FROM eval_periodos_academicos LIMIT 1"); $stmt->execute();
} catch (Exception $e) {
    $sql_migracion = @file_get_contents(__DIR__ . '/../database/crear_periodos.sql');
    if ($sql_migracion !== false) {
        $db->exec($sql_migracion);
    }
}

// Auto-despliegue de la tabla de recesos escolares si no existe
try {
    $stmt = $db->prepare("SELECT 1 FROM recesos_escolares LIMIT 1"); $stmt->execute();
} catch (Exception $e) {
    $sql_recesos = @file_get_contents(__DIR__ . '/../database/crear_recesos.sql');
    if ($sql_recesos !== false) {
        $db->exec($sql_recesos);
    }
}

include_once __DIR__ . '/logica/calculadora_notas.php';
?>
