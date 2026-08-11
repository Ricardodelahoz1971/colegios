<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

echo "=== TABLAS EXISTENTES ===\n";
$tbls = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tbls as $t) echo "  - $t\n";

echo "\n=== VERIFICACIÓN CRÍTICA ===\n";
$criticas = ['ajustes_estetica', 'usuarios', 'roles', 'permisos', 'rol_permisos',
             'configuracion_global', 'mensajes', 'vistas_canales', 'carga_academica', 'cursos'];
foreach ($criticas as $t) {
    echo in_array($t, $tbls) ? "  ✅ $t\n" : "  ❌ FALTA: $t\n";
}

echo "\n=== SCHEMA VERSION ===\n";
try {
    $v = $db->query("SELECT valor FROM configuracion_global WHERE clave='schema_version'")->fetchColumn();
    echo "  schema_version = $v\n";
} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== CONTEO ajustes_estetica ===\n";
try {
    $c = $db->query("SELECT COUNT(*) FROM ajustes_estetica")->fetchColumn();
    echo "  Registros: $c\n";
} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}
