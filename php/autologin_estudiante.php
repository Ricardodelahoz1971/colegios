<?php
declare(strict_types=1);
session_start();
require_once 'db.php';
// Simular un autologin defectuoso
session_regenerate_id(true);
$_SESSION['usuario_id'] = 9;
$_SESSION['rol_id'] = 5;
$_SESSION['rol_nombre'] = 'Estudiante';
// NOTE: We INTENTIONALLY don't set estudiante_id
header("Location: dashboard.php?p=aula_virtual_estudiante");
exit();
