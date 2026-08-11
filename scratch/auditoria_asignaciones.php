<?php
$db = new PDO('sqlite:c:/xampp/htdocs/sistema_escolar/php/database/usuarios.db');
echo "--- ASIGNACIONES ACTIVAS ---\n";
$stmt = $db->query("SELECT a.id, a.prueba_id, a.curso_id, c.nombre_curso 
                    FROM eval_asignaciones a 
                    JOIN cursos c ON a.curso_id = c.id");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
