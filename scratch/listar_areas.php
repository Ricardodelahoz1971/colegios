<?php
include 'php/db.php';
$stmt = $db->query("SELECT * FROM areas");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "[{$row['id']}] {$row['nombre_area']}\n";
}
