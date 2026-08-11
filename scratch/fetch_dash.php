<?php
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['rol_id'] = 3;
$_SESSION['rol_nombre'] = 'Estudiante';
$_SESSION['nombre_usuario'] = 'mateo';
$_SESSION['nombre'] = 'Mateo Rodríguez';
$_SESSION['estudiante_id'] = 1;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/sistema_escolar/php/dashboard.php?p=aula_virtual_estudiante");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=" . session_id());
$output = curl_exec($ch);

$lines = explode("\n", $output);
for($i = max(0, 450); $i < min(count($lines), 480); $i++){
    echo ($i+1) . ": " . $lines[$i] . "\n";
}
