<?php
require_once 'php/db.php';
try {
    echo "--- VISTAS CANALES PARA USUARIO 3, GRUPO 16 ---\n";
    $stmt = $db->prepare("SELECT * FROM vistas_canales WHERE usuario_id = 3 AND grupo_id = 16");
    $stmt->execute();
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\n--- MENSAJES PARA GRUPO 16 ---\n";
    $stmt2 = $db->prepare("SELECT id, remitente_id, contenido, fecha_envio FROM mensajes WHERE grupo_id = 16 ORDER BY id DESC LIMIT 5");
    $stmt2->execute();
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
