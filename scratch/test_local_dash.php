<?php
$_SESSION['estudiante_id'] = 1;
$_SESSION['rol_id'] = 3;
$_GET['p'] = 'aula_virtual_estudiante';

ob_start();
include 'c:/xampp/htdocs/sistema_escolar/php/dashboard.php';
$html = ob_get_clean();

$lines = explode("\n", $html);
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], 'renderRecursos') !== false || strpos($lines[$i], 'Sin descripci') !== false || $i > 460 && $i < 480) {
        echo ($i+1) . ": " . $lines[$i] . "\n";
    }
}
