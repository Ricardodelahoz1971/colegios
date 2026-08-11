<?php
// Simular la sesión para evitar errores de tiene_permiso
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['rol_id'] = 1; // Admin
$_SESSION['rol_nombre'] = 'Administrador';

// Definir variables GET para simular la petición
$_GET['accion'] = 'tipos';

// Capturar la salida de api_preguntas.php
ob_start();
include_once __DIR__ . '/../php/logica/api_preguntas.php';
$output = ob_get_clean();

echo "🤖 RESPUESTA OBTENIDA DE api_preguntas.php?accion=tipos:\n";
echo $output . "\n";
