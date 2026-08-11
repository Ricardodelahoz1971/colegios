<?php
require 'php/db.php';
$stmt = $db->query("SHOW TABLES");
while($r = $stmt->fetch(PDO::FETCH_NUM)) {
    echo $r[0] . "\n";
}
