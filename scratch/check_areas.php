<?php
include_once __DIR__ . '/../php/db.php';
$res = $db->query("SELECT * FROM areas")->fetchAll(PDO::FETCH_ASSOC);
foreach($res as $r) {
    echo "ID: {$r['id']} | Área: {$r['nombre_area']}\n";
}
