<?php
session_start();
$_SESSION['identificacion'] = 'admin';
$_SESSION['rol_nombre'] = 'Director General';
$_SESSION['rol_id'] = 1;

require_once 'c:\xampp\htdocs\sistema_escolar\php\db.php';
require_once 'c:\xampp\htdocs\sistema_escolar\php\auth.php';

echo "Tiene permiso 'personal': " . (tiene_permiso('personal') ? 'SI' : 'NO') . "\n";

// Function from dashboard.php for moduloAutorizadoMovil
function moduloAutorizadoMovil(string $modulo, int $rol): bool {
    $rol_nombre = strtolower($_SESSION['rol_nombre'] ?? '');
    if ($rol_nombre === 'docente') {
        $modulos_permitidos = ['inicio', 'agenda', 'calendario', 'mensajeria', 'calificar_pruebas', 'editor_preguntas', 'constructor_pruebas', 'constructor_actividades', 'aplicacion_pruebas', 'asistencia', 'cursos', 'zulu', 'khronos', 'aula_virtual_gestion', 'sabana_calificaciones'];
        return in_array($modulo, $modulos_permitidos);
    }
    if ($rol_nombre === 'estudiante') {
        $modulos_permitidos = ['inicio', 'agenda', 'calendario', 'mensajeria', 'estudiante_examenes', 'aula_virtual_estudiante'];
        return in_array($modulo, $modulos_permitidos);
    }
    return true;
}

echo "Modulo autorizado movil 'personal': " . (moduloAutorizadoMovil('personal', 1) ? 'SI' : 'NO') . "\n";
?>
