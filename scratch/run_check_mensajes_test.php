<?php
// Simular la sesión del usuario 3 (LJP)
$_SESSION['usuario_id'] = 3;
$_SESSION['rol_id'] = 11;
$_SESSION['nombre_usuario'] = 'LJP';

ob_start();
include 'php/logica/check_mensajes.php';
$output = ob_get_clean();

echo "JSON output:\n";
echo $output . "\n";
