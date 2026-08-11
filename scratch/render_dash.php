<?php
$_SESSION['estudiante_id'] = 1; // dummy
$_SESSION['rol_id'] = 5;
$_GET['p'] = 'aula_virtual_estudiante';
ob_start();
include 'c:/xampp/htdocs/sistema_escolar/php/dashboard.php';
$html = ob_get_clean();

$lines = explode("\n", $html);
for ($i = max(0, 460); $i < min(count($lines), 480); $i++) {
    echo ($i + 1) . ": " . $lines[$i] . "\n";
}
