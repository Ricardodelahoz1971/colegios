<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

function test_dropdown($mi_rol, $user_id) {
    global $db;
    echo "=== TESTING ROLE $mi_rol, USER $user_id ===\n";
    
    // Obtener carga para el selector
    if (in_array($mi_rol, [1, 2, 3])) {
        // Directivos: ver carga completa de la institución
        $stmt_carga = $db->prepare("
            SELECT ca.curso_id, ca.especialidad_id, ca.docente_id, c.nombre_curso, e.nombre_especialidad, u.nombre as docente_nombre 
            FROM carga_academica ca
            JOIN cursos c ON ca.curso_id = c.id
            JOIN especialidades e ON ca.especialidad_id = e.id
            JOIN usuarios u ON ca.docente_id = u.id
            ORDER BY u.nombre ASC, e.nombre_especialidad ASC
        ");
        $stmt_carga->execute();
    } else {
        // Docente: ver carga propia
        $stmt_carga = $db->prepare("
            SELECT ca.curso_id, ca.especialidad_id, ca.docente_id, c.nombre_curso, e.nombre_especialidad, u.nombre as docente_nombre 
            FROM carga_academica ca
            JOIN cursos c ON ca.curso_id = c.id
            JOIN especialidades e ON ca.especialidad_id = e.id
            JOIN usuarios u ON ca.docente_id = u.id
            WHERE ca.docente_id = :uid
            ORDER BY e.nombre_especialidad ASC
        ");
        $stmt_carga->execute([':uid' => $user_id]);
    }
    
    $cargas = $stmt_carga->fetchAll();
    echo "Total options returned: " . count($cargas) . "\n";
    foreach ($cargas as $c) {
        if (in_array($mi_rol, [1, 2, 3])) {
            echo " - Value: {$c['especialidad_id']}-{$c['curso_id']}-{$c['docente_id']} | " . $c['docente_nombre'] . " | " . $c['nombre_especialidad'] . " - " . $c['nombre_curso'] . "\n";
        } else {
            echo " - Value: {$c['especialidad_id']}-{$c['curso_id']}-{$c['docente_id']} | " . $c['nombre_especialidad'] . " - " . $c['nombre_curso'] . "\n";
        }
    }
    echo "\n";
}

test_dropdown(1, 1);
test_dropdown(2, 2);
test_dropdown(3, 3);
test_dropdown(11, 3); // LJP is docente ID 3 (Jorge Pérez)
