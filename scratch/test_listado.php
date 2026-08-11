<?php
$_SESSION['usuario_id'] = 3; // Forzamos ID para el test
$_POST['accion'] = 'listar';
try {
    include 'php/logica/api_pruebas.php';
} catch (Exception $e) {
    echo "\nERROR DETECTADO: " . $e->getMessage();
}
?>
