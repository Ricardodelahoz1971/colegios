<?php
require_once 'php/db.php';
header('Content-Type: text/plain');
try {
    // Buscar eventos en esa fecha
    $stmt = $db->prepare("SELECT * FROM cronograma WHERE fecha = '2026-06-12'");
    $stmt->execute();
    $events = $stmt->fetchAll();
    echo "Eventos encontrados en 2026-06-12: " . count($events) . "\n";
    print_r($events);

    // Borrarlos
    $del = $db->prepare("DELETE FROM cronograma WHERE fecha = '2026-06-12'");
    $del->execute();
    echo "Eliminados con éxito.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
