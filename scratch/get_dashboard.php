<?php
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['rol_id'] = 1;
ob_start();
$_GET['p'] = 'matriculados';
include 'C:/xampp/htdocs/sistema_escolar/php/dashboard.php';
$output = ob_get_clean();
file_put_contents('C:/xampp/htdocs/sistema_escolar/scratch/dashboard_output.html', $output);
