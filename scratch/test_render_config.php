<?php
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['rol_id'] = 1;

function generar_csrf_token() {
    return 'dummy_token';
}
function tiene_permiso() {
    return true;
}

require_once 'C:\\xampp\\htdocs\\sistema_escolar\\php\\db.php';

// Capturar el output
ob_start();
include 'C:\\xampp\\htdocs\\sistema_escolar\\php\\vistas\\configuracion.php';
$html = ob_get_clean();

// Buscar el tag de khronos_inicio
$lines = explode("\n", $html);
foreach ($lines as $line) {
    if (strpos($line, 'khronos_inicio') !== false && strpos($line, '<input') !== false) {
        echo "HTML GENERADO:\n" . trim($line) . "\n";
    }
}
