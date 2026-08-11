<?php
$db = new PDO('sqlite:C:/xampp/htdocs/sistema_escolar/php/database/usuarios.db');
$res = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
foreach($res as $r) {
    echo $r['name'] . "\n";
}
?>
