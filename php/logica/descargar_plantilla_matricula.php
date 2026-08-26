<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../auth.php';
guardia_sesion();
session_write_close();

if (!tiene_permiso('matricula') && !tiene_permiso('estudiantes')) {
    http_response_code(403);
    exit();
}

// 36 Columnas oficiales del expediente de matrícula
$headers = [
    // 1. Estudiante Básico & Demográfico
    'tipo_documento',
    'identificacion',
    'tipo_sangre',
    'nombre',
    'apellido',
    'genero',
    'email',
    'celular',
    'fecha_nacimiento',
    'lugar_nacimiento',
    'nacionalidad',
    'direccion_estudiante',
    'folio_matricula',
    'colegio_anterior',
    'curso',
    'es_antiguo',

    // 2. Información del Padre
    'padre_nombre',
    'padre_tipo_documento',
    'padre_documento',
    'padre_documento_expedicion',
    'padre_nacionalidad',
    'padre_celular',
    'padre_telefono',
    'padre_direccion',
    'padre_profesion',
    'padre_email',

    // 3. Información de la Madre
    'madre_nombre',
    'madre_tipo_documento',
    'madre_documento',
    'madre_documento_expedicion',
    'madre_nacionalidad',
    'madre_celular',
    'madre_telefono',
    'madre_direccion',
    'madre_profesion',
    'madre_email'
];

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="plantilla_matricula_estudiantes.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Inyectar BOM UTF-8 para apertura correcta en Microsoft Excel
fprintf($output, "\xEF\xBB\xBF");

// Escribir estrictamente la fila de cabeceras sin filas de ejemplo
fputcsv($output, $headers, ';');

fclose($output);
exit();
