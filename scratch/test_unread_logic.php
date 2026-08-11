<?php
require_once 'php/db.php';
try {
    // 1. Simular envío de mensaje de Admin (remitente_id = 1) a Salón 2B (grupo_id = 16)
    echo "Simulando envío de mensaje nuevo de Admin a Salón 2B...\n";
    $stmt = $db->prepare("INSERT INTO mensajes (remitente_id, destinatario_id, grupo_id, contenido, prioridad, fecha_envio) VALUES (1, 0, 16, 'Mensaje de prueba de Admin para LJP', 1, NOW())");
    $stmt->execute();
    $new_msg_id = $db->lastInsertId();
    echo "Mensaje insertado con ID: {$new_msg_id}\n";

    // 2. Ejecutar la lógica de check_mensajes.php simulando la sesión del usuario 3 (LJP)
    $user_id = 3;
    
    // Obtener los grupos del usuario 3
    $mis_grupos = [];
    $stmt_g = $db->prepare("SELECT grupo_id FROM miembros_grupo WHERE usuario_id = ?");
    $stmt_g->execute([$user_id]);
    while($g = $stmt_g->fetch(PDO::FETCH_ASSOC)) {
        $mis_grupos[] = (int)$g['grupo_id'];
    }

    // Contar no leídos para grupos
    $unread_groups = 0;
    $unread_per_group = [];
    $stmt_ult = $db->prepare("SELECT ultimo_mensaje_id FROM vistas_canales WHERE usuario_id = ? AND grupo_id = ?");
    $stmt_qty = $db->prepare("SELECT COUNT(*) FROM mensajes WHERE grupo_id = ? AND id > ? AND remitente_id != ?");

    foreach ($mis_grupos as $gid) {
        $stmt_ult->execute([$user_id, $gid]);
        $ult_visto = (int)$stmt_ult->fetchColumn() ?: 0;
        $stmt_qty->execute([$gid, $ult_visto, $user_id]);
        $qty = (int)$stmt_qty->fetchColumn();
        if ($qty > 0) {
            $unread_groups += $qty;
            $unread_per_group[$gid] = $qty;
        }
    }

    echo "\n--- RESULTADO DE MENSAJES SIN LEER PARA LJP (Usuario 3) ---\n";
    echo "Grupos en los que es miembro: " . implode(", ", $mis_grupos) . "\n";
    echo "Último visto en grupo 16 (Salón 2B): " . $ult_visto . "\n";
    echo "Conteo sin leer por grupo:\n";
    print_r($unread_per_group);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
