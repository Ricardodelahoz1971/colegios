<?php
declare(strict_types=1);

// Simulamos los parámetros de GET
$_GET['docente_id'] = 11;
$_GET['materia_id'] = 8;
$_GET['curso_id'] = 3;

echo "--- TEST PERSEUS API ---\n";
try {
    ob_start();
    include __DIR__ . '/../php/api_perseus.php';
    $response = ob_get_clean();
    echo $response;
} catch (Exception $e) {
    echo "FALLO: " . $e->getMessage();
}
