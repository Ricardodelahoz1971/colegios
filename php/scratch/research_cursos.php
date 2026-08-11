<?php
declare(strict_types=1);
require 'db.php';
$stmt_res = $db->prepare("SELECT nombre_curso FROM cursos"); $stmt_res->execute(); $res = $stmt_res;
while($r = $res->fetchArray()){
    echo $r[0] . "\n";
}
?>

