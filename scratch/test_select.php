<?php
require_once __DIR__ . '/../php/db.php';
$nombre = 'Matemáticas y ciencias'; // Lowercase c

$check = $db->prepare('SELECT COUNT(*) FROM areas WHERE nombre_area = :nom');
$check->bindValue(':nom', $nombre, PDO::PARAM_STR);
$check->execute();
$count = (int)$check->fetchColumn();

echo "SELECT count for '$nombre': " . $count . "\n";

try {
    $stmt = $db->prepare('INSERT INTO areas (nombre_area) VALUES (:nom)');
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->execute();
    echo "INSERT succeeded!\n";
} catch (Exception $e) {
    echo "INSERT failed: " . $e->getMessage() . "\n";
}
