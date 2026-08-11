<?php
session_start();
$_SESSION['usuario_id'] = 9;
$_SESSION['rol_id'] = 5;
$_SESSION['estudiante_id'] = 1;
$_GET['action'] = 'listar_estudiante';

ob_start();
require_once __DIR__ . '/../php/logica/api_aula.php';
$output = ob_get_clean();
echo "OUTPUT:\n";
var_dump($output);
