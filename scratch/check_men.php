<?php
$db = new PDO('sqlite:C:/xampp/htdocs/sistema_escolar/php/database/usuarios.db');
$res = $db->query("SELECT name, sql FROM sqlite_master WHERE type='table'");
foreach($res as $r) {
    if (stripos($r['sql'], 'men') !== false || stripos($r['sql'], 'competenc') !== false || stripos($r['sql'], 'estandar') !== false) {
        echo "\n---\nTable: {$r['name']}\nSQL: {$r['sql']}\n";
    }
}
?>
