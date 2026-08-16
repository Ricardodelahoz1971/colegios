<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    // 1. Consultar formatos
    $stmt = $db->query("SELECT id, nombre, tipo, margen_superior, margen_izquierdo, tamano_lienzo FROM formatos_matricula ORDER BY id DESC LIMIT 5");
    echo "=== FORMATOS ===\n";
    while ($f = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$f['id']} | Nombre: {$f['nombre']} | Tipo: {$f['tipo']} | Margen Superior: {$f['margen_superior']} | Margen Izquierdo: {$f['margen_izquierdo']} | Lienzo: {$f['tamano_lienzo']}\n";
    }

    // 2. Consultar estudiantes
    $stmt_est = $db->query("SELECT id, nombre, apellido FROM estudiantes LIMIT 5");
    echo "\n=== ESTUDIANTES ===\n";
    while ($e = $stmt_est->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$e['id']} | Nombre: {$e['nombre']} {$e['apellido']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
