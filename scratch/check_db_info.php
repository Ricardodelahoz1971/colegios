<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

$cnt_carga = $db->query("SELECT COUNT(*) FROM carga_academica")->fetchColumn();
echo "Carga academica: $cnt_carga\n";

if ($cnt_carga > 0) {
    $carga = $db->query("SELECT ca.id, c.nombre_curso, e.nombre_especialidad, ca.docente_id FROM carga_academica ca JOIN cursos c ON ca.curso_id = c.id JOIN especialidades e ON ca.especialidad_id = e.id LIMIT 10")->fetchAll();
    print_r($carga);
}
