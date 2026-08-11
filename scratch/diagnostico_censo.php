<?php
$db = new PDO('sqlite:c:/xampp/htdocs/sistema_escolar/php/database/usuarios.db');
echo "--- TABLA EVAL_RESPUESTAS ---\n";
print_r($db->query("PRAGMA table_info(eval_respuestas)")->fetchAll(PDO::FETCH_ASSOC));
?>
