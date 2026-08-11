<?php
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['rol'] = 'administrador';
$_SESSION['nombre'] = 'Admin';
$_SESSION['csrf_token'] = 'dummy_token';

// Capturar el HTML
ob_start();
$_GET['p'] = 'editor_preguntas';
$pagina = 'editor_preguntas';
require 'dashboard.php';
$html = ob_get_clean();

file_put_contents('rendered.html', $html);
echo "Rendered length: " . strlen($html);
?>
