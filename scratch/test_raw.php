<?php
declare(strict_types=1);

// Simular la sesión para LJP (id = 1, rol_id = 11, docente)
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['rol_id'] = 11;
$_SESSION['rol_nombre'] = 'Docente';
$_SESSION['csrf_token'] = 'test_token';

$_GET['p'] = 'aplicacion_pruebas';
$_GET['raw'] = '1';

// Capturar el output de dashboard.php
ob_start();
include __DIR__ . '/../php/dashboard.php';
$html = ob_get_clean();

echo "HTML LENGTH: " . strlen($html) . "\n";
echo "FIRST 500 CHARS:\n";
echo substr($html, 0, 500) . "\n";
echo "LAST 500 CHARS:\n";
echo substr($html, -500) . "\n";

// Guardar en un archivo temporal para inspección
file_put_contents(__DIR__ . '/raw_output.html', $html);
echo "Raw output saved to scratch/raw_output.html\n";
