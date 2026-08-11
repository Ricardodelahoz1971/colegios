<?php
$db = new PDO('sqlite:c:\xampp\htdocs\sistema_escolar\php\database\usuarios.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

echo "=== ALL QUESTIONS IN eval_preguntas ===\n";
$stmt = $db->query("SELECT id, docente_id, materia_id, tipo_id, titulo, enunciado FROM eval_preguntas");
print_r($stmt->fetchAll());
?>
