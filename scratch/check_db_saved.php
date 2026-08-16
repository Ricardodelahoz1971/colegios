<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    $stmt = $db->prepare("SELECT contenido_html, configuracion_json FROM formatos_matricula WHERE id = 30");
    $stmt->execute();
    $formato = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($formato) {
        echo "=== HTML EN BD ===\n";
        echo $formato['contenido_html'] . "\n\n";
        echo "=== JSON EN BD ===\n";
        echo $formato['configuracion_json'] . "\n";
    } else {
        echo "No se encontró el formato 30.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
