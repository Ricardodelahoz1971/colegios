<?php
$db = new PDO('sqlite:' . __DIR__ . '/../php/database/usuarios.db');
$res = $db->query('SELECT usuario, nombre, rol_id, estudiante_id FROM usuarios WHERE rol_id IN (5, 11) LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
