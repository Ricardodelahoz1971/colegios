<?php
require 'php/db.php';
echo "--- COLUMNS OF estudiantes ---\n";
$stmt = $db->query("DESCRIBE estudiantes");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r['Field'] . " (" . $r['Type'] . ")\n";
}

echo "\n--- COLUMNS OF estudiantes_datos_adicionales ---\n";
$stmt = $db->query("DESCRIBE estudiantes_datos_adicionales");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r['Field'] . " (" . $r['Type'] . ")\n";
}
