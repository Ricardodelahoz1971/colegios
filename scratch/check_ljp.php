<?php
require_once 'php/db.php';
try {
    $stmt = $db->prepare("
        SELECT ca.id as carga_id, c.nombre_curso, e.nombre_especialidad 
        FROM carga_academica ca
        JOIN cursos c ON ca.curso_id = c.id
        JOIN especialidades e ON ca.especialidad_id = e.id
        WHERE ca.docente_id = (SELECT id FROM usuarios WHERE usuario = 'LJP')
    ");
    $stmt->execute();
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
