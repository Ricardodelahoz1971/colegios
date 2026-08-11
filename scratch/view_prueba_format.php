<?php
require 'php/db.php';
$stmt = $db->prepare('SELECT contenido_html, configuracion_json FROM formatos_matricula WHERE id = ?');
$stmt->execute([25]);
$r = $stmt->fetch();
echo "HTML:\n" . $r['contenido_html'] . "\n\n";
echo "JSON:\n" . $r['configuracion_json'] . "\n";
