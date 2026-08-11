<?php
require_once 'c:/xampp/htdocs/sistema_escolar/php/db.php';
$s=$db->query('SELECT * FROM usuario_permisos WHERE usuario_id = 2');
print_r($s->fetchAll(PDO::FETCH_ASSOC));
$s2=$db->query('SELECT permisos_custom FROM usuarios WHERE id = 2');
print_r($s2->fetch(PDO::FETCH_ASSOC));
?>
