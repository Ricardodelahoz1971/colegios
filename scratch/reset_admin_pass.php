<?php
require_once 'php/db.php';
header('Content-Type: text/plain');
try {
    $new_hash = password_hash('1234', PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE usuario = 'admin'");
    $stmt->execute([$new_hash]);
    echo "Contraseña de ADMIN actualizada con éxito a '1234'.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
