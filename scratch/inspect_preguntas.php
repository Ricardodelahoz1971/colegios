<?php
$db = new PDO('sqlite:c:\xampp\htdocs\sistema_escolar\php\database\usuarios.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

echo "=== QUESTIONS ===\n";
$stmt = $db->query("SELECT id, titulo, enunciado, materia_id, aprendizaje_id, evidencia_id FROM eval_preguntas WHERE id IN (991, 992, 993)");
print_r($stmt->fetchAll());

echo "\n=== MATERIAS / ESPECIALIDADES ===\n";
$stmt = $db->query("SELECT id, nombre_especialidad, area_id, disciplina_men FROM especialidades WHERE id IN (SELECT DISTINCT materia_id FROM eval_preguntas WHERE id IN (991, 992, 993))");
print_r($stmt->fetchAll());

echo "\n=== APRENDIZAJES (DBAS) ===\n";
$stmt = $db->query("SELECT id, area_id, grado, num_dba, enunciado FROM ares_catalogo_aprendizajes WHERE id IN (SELECT DISTINCT aprendizaje_id FROM eval_preguntas WHERE id IN (991, 992, 993))");
print_r($stmt->fetchAll());
?>
