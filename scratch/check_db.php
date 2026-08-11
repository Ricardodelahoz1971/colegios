<?php
require 'php/db.php';
$stmt = $db->query('SELECT id, nombre, contenido_html, configuracion_json FROM formatos_matricula');
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $r['id'] . "\n";
    echo "NAME: " . $r['nombre'] . "\n";
    echo "HTML: " . substr($r['contenido_html'], 0, 500) . "...\n";
    echo "JSON: " . substr($r['configuracion_json'], 0, 500) . "...\n\n";
}
