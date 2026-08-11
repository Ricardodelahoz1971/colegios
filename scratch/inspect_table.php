<?php
try {
    $db = new PDO('sqlite:php/database/usuarios.db');
    $q = $db->query('PRAGMA table_info(usuarios)');
    foreach($q as $r) {
        echo $r['name'] . "\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
