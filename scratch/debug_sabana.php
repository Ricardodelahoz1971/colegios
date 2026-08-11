<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/auth.php';

// Mock session to simulate Admin
$_SESSION = [
    'usuario_id' => 1,
    'rol_nombre' => 'Administrador',
    'nombre_usuario' => 'admin_test',
    'rol_id' => 1,
    'roles' => ['administrador']
];

$_GET = [
    'accion' => 'cargar_consolidado_curso',
    'curso_id' => 6, // 1B
    'periodo_id' => 1
];

// Include api_sabana.php and capture raw output
ob_start();
try {
    chdir(__DIR__ . '/../php/logica');
    require 'api_sabana.php';
} catch (Throwable $e) {
    echo json_encode(['status' => 'exception', 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
$output = ob_get_clean();

echo "Raw Output:\n" . $output . "\n";
