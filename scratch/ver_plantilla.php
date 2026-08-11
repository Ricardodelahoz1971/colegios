<?php
require 'php/db.php';
$stmt = $db->query("SELECT id, nombre, contenido_html FROM formatos_matricula");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $r['id'] . " | Nombre: " . $r['nombre'] . "\n";
    echo "Contenido:\n" . htmlspecialchars($r['contenido_html']) . "\n\n====================================\n\n";
}
