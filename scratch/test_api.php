<?php 
session_start(); 
$_SESSION['usuario_id']=6; 
$_SESSION['rol_id']=2; 
$_GET['docente_id']=6; 
$_GET['materia_id']=4; 
$_GET['curso_id']=1; 
require __DIR__ . '/../php/api_perseus.php';
