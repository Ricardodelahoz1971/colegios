<?php
declare(strict_types=1);

// Configurar $_GET para simular la petición web
$_GET['estudiante_id'] = 9;
$_GET['formato_id'] = 30;

// Simular entorno e iniciar sesión para evitar errores
$_SESSION = [
    'usuario_id' => 1,
    'usuario_rol' => 'coordinador',
    'permisos' => ['estudiantes', 'configuracion']
];

// Definir de forma dummy la función tiene_permiso
if (!function_exists('tiene_permiso')) {
    function tiene_permiso(string $p): bool {
        return true;
    }
}

// Leer imprimir_matricula.php
$code = file_get_contents(__DIR__ . '/../imprimir_matricula.php');

// Reemplazar __DIR__ por el directorio de la aplicación para resolver las inclusiones
$code = str_replace("__DIR__", "dirname(__DIR__)", $code);

// Eliminar strict_types y validaciones
$code = str_replace("declare(strict_types=1);", "//", $code);
$code = str_replace("require_once dirname(__DIR__) . '/php/auth.php';", "//", $code);
$code = str_replace("guardia_sesion();", "//", $code);
$code = str_replace("session_write_close();", "//", $code);
$code = str_replace("if (!tiene_permiso('estudiantes')) {", "if (false) {", $code);

// Evaluar el código limpio
ob_start();
eval('?>' . $code);
$html = ob_get_clean();

// Guardar en archivo
file_put_contents(__DIR__ . '/preview_result.html', $html);
echo "Preview HTML renderizado y guardado con éxito en scratch/preview_result.html\n";
