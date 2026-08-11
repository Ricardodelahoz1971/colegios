<?php
$host = 'localhost';
$dbname = 'sistema_escolar';
$user = 'root';
$pass = '';

$pdo_maria = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
$pdo_maria->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo_maria->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo_maria->exec("USE `$dbname`");
$pdo_maria->exec("SET FOREIGN_KEY_CHECKS=0;");

$schema_sql = file_get_contents(__DIR__ . '/schema.sql');
$schema_sql = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $schema_sql);
$schema_sql = preg_replace('/CREATE TABLE sqlite_sequence[^;]+;/i', '', $schema_sql);
$schema_sql = str_replace('"estudiantes"', '`estudiantes`', $schema_sql);

// Fixes for TEXT PRIMARY KEY and UNIQUE which require lengths in MariaDB
$schema_sql = str_replace('clave TEXT PRIMARY KEY', 'clave VARCHAR(255) PRIMARY KEY', $schema_sql);
$schema_sql = str_replace('clave TEXT UNIQUE NOT NULL', 'clave VARCHAR(255) UNIQUE NOT NULL', $schema_sql);
$schema_sql = str_replace('nombre_rol TEXT UNIQUE NOT NULL', 'nombre_rol VARCHAR(255) UNIQUE NOT NULL', $schema_sql);
$schema_sql = str_replace('nombre_especialidad TEXT UNIQUE NOT NULL', 'nombre_especialidad VARCHAR(255) UNIQUE NOT NULL', $schema_sql);
$schema_sql = str_replace('nombre_area TEXT UNIQUE NOT NULL', 'nombre_area VARCHAR(255) UNIQUE NOT NULL', $schema_sql);
$schema_sql = str_replace('slug TEXT NOT NULL UNIQUE', 'slug VARCHAR(255) NOT NULL UNIQUE', $schema_sql);
$schema_sql = str_replace('clave TEXT NOT NULL UNIQUE', 'clave VARCHAR(255) NOT NULL UNIQUE', $schema_sql);
$schema_sql = str_replace('usuario TEXT NOT NULL UNIQUE', 'usuario VARCHAR(255) NOT NULL UNIQUE', $schema_sql);

// Fixes for CHECK constraint
$schema_sql = str_replace("complejidad TEXT CHECK(complejidad IN ('Baja', 'Media', 'Alta')) DEFAULT 'Media'", "complejidad ENUM('Baja', 'Media', 'Alta') DEFAULT 'Media'", $schema_sql);

$queries = explode(';', $schema_sql);
foreach ($queries as $q) {
    $q = trim($q);
    if ($q) {
        try {
            $pdo_maria->exec($q);
        } catch (Exception $e) {
            echo "Error al crear: " . $e->getMessage() . "\n";
        }
    }
}

$pdo_maria->exec("SET FOREIGN_KEY_CHECKS=1;");
echo "Estructura Base Migrada a MariaDB.\n";
?>
