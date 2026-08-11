<?php
declare(strict_types=1);

// Simular la sesión para LJP (id = 1, rol_id = 11, docente)
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['rol_id'] = 11;
$_SESSION['rol_nombre'] = 'Docente';
$_SESSION['csrf_token'] = 'test_token';

$_GET['p'] = 'aplicacion_pruebas';

// Cambiar directorio de trabajo a php/
chdir(__DIR__ . '/../php');

// Capturar el output de dashboard.php
ob_start();
include 'dashboard.php';
$html = ob_get_clean();

// Guardar en un archivo temporal para inspección
file_put_contents(__DIR__ . '/full_rendered.html', $html);
echo "HTML rendered. Lines: " . count(explode("\n", $html)) . "\n";

// Buscar el texto '<script' y ver las líneas alrededor
$lines = explode("\n", $html);
foreach ($lines as $idx => $line) {
    if (strpos($line, 'pruebaIdProcesado') !== false || strpos($line, 'cargarAsignacionesProgramar') !== false) {
        echo "Found at line " . ($idx + 1) . ": " . trim($line) . "\n";
    }
}
