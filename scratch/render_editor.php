<?php
session_start();
$_SESSION['usuario_id']=6;
$_SESSION['rol_id']=2;
$_GET['p']='editor_preguntas';
ob_start();
include 'php/dashboard.php';
$html = ob_get_clean();
file_put_contents('scratch/rendered_editor.html', $html);
