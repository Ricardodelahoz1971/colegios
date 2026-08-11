<?php
$db = new PDO('sqlite:' . __DIR__ . '/../php/database/usuarios.db');
$profes = $db->query('SELECT id, usuario, nombre, rol_id FROM usuarios WHERE rol_id = 11 LIMIT 5')->fetchAll();
print_r($profes);



