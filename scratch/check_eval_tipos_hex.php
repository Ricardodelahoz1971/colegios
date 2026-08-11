<?php
$db = new PDO('sqlite:c:/xampp/htdocs/sistema_escolar/php/database/usuarios.db');
foreach($db->query('SELECT * FROM eval_tipos')->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo $r['id'] . ' | ' . bin2hex($r['slug']) . ' | ' . $r['slug'] . "\n";
}
