<?php
require_once 'php/db.php';
try {
    $stmt = $db->prepare("
        SELECT mg.grupo_id, g.nombre, g.tipo 
        FROM miembros_grupo mg
        JOIN grupos g ON mg.grupo_id = g.id
        WHERE mg.usuario_id = 3
    ");
    $stmt->execute();
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
