<?php
declare(strict_types=1);

require_once __DIR__ . '/../../php/db.php';

echo "Iniciando limpieza de entidades HTML en base de datos...\n";

// 1. Áreas
$stmt = $db->prepare("SELECT id, nombre_area FROM areas");
$stmt->execute();
$areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$upd_area = $db->prepare("UPDATE areas SET nombre_area = :nom WHERE id = :id");
$c_areas = 0;
foreach ($areas as $a) {
    $dec = html_entity_decode($a['nombre_area'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($dec !== $a['nombre_area']) {
        $upd_area->execute([':nom' => $dec, ':id' => $a['id']]);
        $c_areas++;
    }
}
echo "Áreas corregidas: $c_areas\n";

// 2. Especialidades
$stmt = $db->prepare("SELECT id, nombre_especialidad FROM especialidades");
$stmt->execute();
$esps = $stmt->fetchAll(PDO::FETCH_ASSOC);
$upd_esp = $db->prepare("UPDATE especialidades SET nombre_especialidad = :nom WHERE id = :id");
$c_esp = 0;
foreach ($esps as $e) {
    $dec = html_entity_decode($e['nombre_especialidad'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($dec !== $e['nombre_especialidad']) {
        $upd_esp->execute([':nom' => $dec, ':id' => $e['id']]);
        $c_esp++;
    }
}
echo "Especialidades corregidas: $c_esp\n";

// 3. Cursos
$stmt = $db->prepare("SELECT id, nombre_curso FROM cursos");
$stmt->execute();
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$upd_cur = $db->prepare("UPDATE cursos SET nombre_curso = :nom WHERE id = :id");
$c_cur = 0;
foreach ($cursos as $c) {
    $dec = html_entity_decode($c['nombre_curso'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($dec !== $c['nombre_curso']) {
        $upd_cur->execute([':nom' => $dec, ':id' => $c['id']]);
        $c_cur++;
    }
}
echo "Cursos corregidos: $c_cur\n";

// 4. Roles
$stmt = $db->prepare("SELECT id, nombre_rol FROM roles");
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
$upd_rol = $db->prepare("UPDATE roles SET nombre_rol = :nom WHERE id = :id");
$c_rol = 0;
foreach ($roles as $r) {
    $dec = html_entity_decode($r['nombre_rol'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($dec !== $r['nombre_rol']) {
        $upd_rol->execute([':nom' => $dec, ':id' => $r['id']]);
        $c_rol++;
    }
}
echo "Roles corregidos: $c_rol\n";

echo "✅ Limpieza de entidades HTML finalizada con éxito.\n";
