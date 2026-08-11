<?php
declare(strict_types=1);
ob_start();
session_start();
require_once '../db.php';

if (!isset($_SESSION['usuario_id'])) {
    if (ob_get_length()) ob_clean();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No session']);
    exit();
}

try {
    $user_id = (int)$_SESSION['usuario_id'];
    $mi_rol = (int)$_SESSION['rol_id'];
    $mi_ident = $_SESSION['nombre_usuario'];
    session_write_close(); 

    // --- RASTREO DE PRESENCIA (RADAR) ---
    try {
        $db->prepare("UPDATE usuarios SET ultima_actividad = CURRENT_TIMESTAMP WHERE id = ?")->execute([$user_id]);
    } catch (Throwable $e) {}

    // 1. MARCAR COMO ENTREGADOS
    try {
        $db->prepare("UPDATE mensajes SET entregado = 1 WHERE destinatario_id = ? AND entregado = 0")->execute([$user_id]);
    } catch (Throwable $e) {}

    // 2. DETECTAR TODOS LOS CANALES QUE EL USUARIO TIENE ACCESO (Sincronizado con mensajeria.php)
    $mis_grupos = [];
    
    // a) Grupos por membresía explícita (Solo canales de comunicación: CURSO o sistema)
    $stmt_g = $db->prepare("SELECT mg.grupo_id FROM miembros_grupo mg JOIN grupos g ON mg.grupo_id = g.id WHERE mg.usuario_id = ? AND g.tipo IN ('CURSO', 'sistema')");
    $stmt_g->execute([$user_id]);
    while($g = $stmt_g->fetch(PDO::FETCH_ASSOC)) {
        $mis_grupos[] = (int)$g['grupo_id'];
    }
    
    // b) Canales de Curso según Rol
    $roles_autorizados_masivo = [1, 2, 3];
    if (in_array($mi_rol, $roles_autorizados_masivo)) {
        // Admins ven todos los cursos
        $stmt_c = $db->prepare("SELECT id FROM grupos WHERE tipo = :tipo");
        $stmt_c->execute([':tipo' => 'CURSO']);
        while($c = $stmt_c->fetch(PDO::FETCH_ASSOC)) {
            $mis_grupos[] = (int)$c['id'];
        }
    } elseif ($mi_rol === 5) {
        // Estudiantes ven el curso de su matrícula
        $stmt_mc = $db->prepare("SELECT curso_id FROM estudiantes WHERE id = (SELECT estudiante_id FROM usuarios WHERE id = ?)");
        $stmt_mc->execute([$user_id]);
        $mi_curso_id = (int)$stmt_mc->fetchColumn();
        if ($mi_curso_id > 0) {
            $stmt_c = $db->prepare("SELECT id FROM grupos WHERE tipo = 'CURSO' AND referencia_id = ?");
            $stmt_c->execute([$mi_curso_id]);
            while($c = $stmt_c->fetch(PDO::FETCH_ASSOC)) {
                $mis_grupos[] = (int)$c['id'];
            }
        }
    } elseif ($mi_rol == 11) {
        // Docentes ven cursos donde son tutores o tienen carga académica
        $stmt_c = $db->prepare("SELECT id FROM grupos 
                  WHERE id IN (SELECT g.id FROM grupos g JOIN cursos c ON g.referencia_id = c.id WHERE g.tipo = 'CURSO' AND (c.tutor_id = ? OR c.id IN (SELECT curso_id FROM carga_academica WHERE docente_id = ?)))");
        $stmt_c->execute([$user_id, $user_id]);
        while($c = $stmt_c->fetch(PDO::FETCH_ASSOC)) {
            $mis_grupos[] = (int)$c['id'];
        }
    }

    // c) Canales de Sistema (Canal de Profesores, Canal de Líderes)
    if (in_array($mi_rol, $roles_autorizados_masivo) || $mi_rol == 11) {
        $stmt_inst = $db->prepare("SELECT id FROM grupos WHERE nombre = 'CANAL DE PROFESORES' AND tipo = 'sistema'");
        $stmt_inst->execute();
        $cp_id = $stmt_inst->fetchColumn();
        if ($cp_id) {
            $mis_grupos[] = (int)$cp_id;
        }
        
        $stmt_tutor_check = $db->prepare("SELECT COUNT(*) FROM cursos WHERE tutor_id = ?");
        $stmt_tutor_check->execute([$user_id]);
        $es_tutor = (int)$stmt_tutor_check->fetchColumn() > 0;
        
        if (in_array($mi_rol, $roles_autorizados_masivo) || $es_tutor) {
            $stmt_inst = $db->prepare("SELECT id FROM grupos WHERE nombre = 'CANAL DE LÍDERES' AND tipo = 'sistema'");
            $stmt_inst->execute();
            $cl_id = $stmt_inst->fetchColumn();
            if ($cl_id) {
                $mis_grupos[] = (int)$cl_id;
            }
        }
    }

    $mis_grupos = array_unique($mis_grupos);

    // 3. CONTAR NO LEÍDOS
    $stmt_ud = $db->prepare("SELECT COUNT(*) FROM mensajes WHERE destinatario_id = ? AND leido = 0 AND remitente_id != ? AND grupo_id = 0");
    $stmt_ud->execute([$user_id, $user_id]);
    $unread_direct = (int)$stmt_ud->fetchColumn();

    // Conteo de mensajes sin leer agrupados por remitente (contacto directo)
    $unread_per_user = [];
    $stmt_uu = $db->prepare("SELECT remitente_id, COUNT(*) as qty FROM mensajes WHERE destinatario_id = ? AND leido = 0 AND grupo_id = 0 GROUP BY remitente_id");
    $stmt_uu->execute([$user_id]);
    while($row = $stmt_uu->fetch(PDO::FETCH_ASSOC)) {
        $unread_per_user[(int)$row['remitente_id']] = (int)$row['qty'];
    }

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

    $unread_total = $unread_direct + $unread_groups;

    // 4. NOTIFICACIONES
    $notificaciones = [];
    
    // a) Mensajes directos sin leer
    $sql_notif = "SELECT m.*, COALESCE(u.nombre, CONCAT(e.nombre, ' ', e.apellido)) as remitente_nom, NULL as grupo_nom
                  FROM mensajes m 
                  LEFT JOIN usuarios u ON m.remitente_id = u.id 
                  LEFT JOIN estudiantes e ON m.remitente_id = e.id
                  WHERE m.destinatario_id = ? AND m.leido = 0 AND m.grupo_id = 0";
    $stmt_n = $db->prepare($sql_notif);
    $stmt_n->execute([$user_id]);
    $raw_notif = $stmt_n->fetchAll(PDO::FETCH_ASSOC);

    // b) Mensajes de grupo sin leer
    $stmt_g_msgs = $db->prepare("SELECT m.*, u.nombre as remitente_nom, g.nombre as grupo_nom
                                 FROM mensajes m 
                                 JOIN usuarios u ON m.remitente_id = u.id 
                                 JOIN grupos g ON m.grupo_id = g.id
                                 WHERE m.grupo_id = ? AND m.id > ? AND m.remitente_id != ?");
    
    foreach ($mis_grupos as $gid) {
        $stmt_ult->execute([$user_id, $gid]);
        $ult_visto = (int)$stmt_ult->fetchColumn() ?: 0;
        $stmt_g_msgs->execute([$gid, $ult_visto, $user_id]);
        while ($row = $stmt_g_msgs->fetch(PDO::FETCH_ASSOC)) {
            $raw_notif[] = $row;
        }
    }

    // Ordenar por fecha de envío descendente
    usort($raw_notif, function($a, $b) {
        return strcmp($b['fecha_envio'], $a['fecha_envio']);
    });

    // Limitar a las 5 más recientes
    $raw_notif = array_slice($raw_notif, 0, 5);

    foreach ($raw_notif as $row) {
        $grupo_id = (int)($row['grupo_id'] ?? 0);
        if ($grupo_id > 0) {
            $nombre_canal = $row['grupo_nom'];
            if (str_contains(strtolower($nombre_canal), 'canal de ')) {
                $tag = str_replace('CANAL DE ', '', strtoupper($nombre_canal));
            } else {
                $tag = strtoupper($nombre_canal);
            }
            $remitente = "[" . $tag . "] " . $row['remitente_nom'];
            $tipo = 'group';
            $chat_id = $grupo_id;
        } else {
            $remitente = $row['remitente_nom'];
            $tipo = 'direct';
            $chat_id = (int)$row['remitente_id'];
        }

        $notificaciones[] = [
            'id' => (int)$row['id'],
            'remitente' => $remitente,
            'contenido' => mb_substr($row['contenido'] ?? '', 0, 40, 'UTF-8') . '...',
            'prioridad' => (int)$row['prioridad'],
            'fecha' => date('H:i', strtotime($row['fecha_envio'])),
            'tipo' => $tipo,
            'chat_id' => $chat_id
        ];
    }

    // 5. USUARIOS ONLINE
    $online_ids = [];
    try {
        $online_stmt_prepared = $db->prepare("SELECT id FROM usuarios WHERE ultima_actividad IS NOT NULL AND TIMESTAMPDIFF(SECOND, ultima_actividad, NOW()) <= 60");
        $online_stmt_prepared->execute();
        $online_stmt = $online_stmt_prepared;
        if ($online_stmt) {
            while ($row = $online_stmt->fetch(PDO::FETCH_ASSOC)) $online_ids[] = (int)$row['id'];
        }
    } catch (Throwable $e) {}

    if (ob_get_length()) ob_clean();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Content-Type: application/json');
    echo json_encode([
        'unread_count' => (int)$unread_total,
        'notificaciones' => $notificaciones,
        'unread_per_group' => $unread_per_group,
        'unread_per_user' => $unread_per_user,
        'online_ids' => $online_ids
    ]);

} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
exit();
