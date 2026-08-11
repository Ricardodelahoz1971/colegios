<?php
require 'php/db.php';
$stmt = $db->query("SELECT * FROM ajustes_estetica");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r['clave'] . ": " . $r['valor'] . "\n";
}
