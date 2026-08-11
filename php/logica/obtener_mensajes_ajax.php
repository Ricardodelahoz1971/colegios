<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
// PHP/LOGICA/OBTENER_MENSAJES_AJAX.PHP - MOTOR ASÍNCRONO ÉLITE v2.5
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');
ob_start(); // Capturar cualquier salida accidental (v9.2)
session_start();
require_once '../db.php';
ob_clean();

if (!isset($_SESSION['usuario_id']) || !isset($_GET['chat_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit();
}

$user_id = (int)$_SESSION['usuario_id'];
// Liberar la sesión inmediatamente para permitir que el resto de la interfaz (SPA) siga funcionando
session_write_close(); 

$chat_con_id = (int)($_GET['chat_id'] ?? 0);
$chat_type = $_GET['chat_type'] ?? 'direct';

try {
    // 1. OBTENER INFORMACIÓN DEL CONTACTO Y ESTADO (v9.2 PDO)
    $chat_online = false;
    if ($chat_type === 'direct') {
        $stmt_u = $db->prepare("SELECT ultima_actividad FROM usuarios WHERE id = :uid");
        $stmt_u->bindValue(':uid', $chat_con_id, PDO::PARAM_INT);
        $stmt_u->execute();
        $chat_data = $stmt_u->fetch(PDO::FETCH_ASSOC);
        
        if ($chat_data && !empty($chat_data['ultima_actividad'])) {
            try {
                $last_act = strtotime($chat_data['ultima_actividad']);
                if ($last_act) {
                    $diff = time() - $last_act;
                    if ($diff <= 15) $chat_online = true;
                }
            } catch (Exception $e) {}
        }
        
        // Marcar como leídos (Directos) - Excluyendo los Urgentes (prioridad = 3), que requieren acuse manual
        $stmt_upd = $db->prepare("UPDATE mensajes SET leido = 1 WHERE remitente_id = :rid AND destinatario_id = :did AND grupo_id = 0 AND (prioridad IS NULL OR prioridad != 3)");
        $stmt_upd->bindValue(':rid', $chat_con_id, PDO::PARAM_INT);
        $stmt_upd->bindValue(':did', $user_id, PDO::PARAM_INT);
        $stmt_upd->execute();
    } else {
        // Si es Grupo, actualizar marca de "visto"
        $stmt_max = $db->prepare("SELECT MAX(id) FROM mensajes WHERE grupo_id = :gid");
        $stmt_max->bindValue(':gid', $chat_con_id, PDO::PARAM_INT);
        $stmt_max->execute();
        $ult_msg_id = (int)$stmt_max->fetchColumn();
        
        if ($ult_msg_id > 0) {
            $stmt_vistas = $db->prepare("REPLACE INTO vistas_canales (usuario_id, grupo_id, ultimo_mensaje_id) VALUES (:uid, :gid, :umid)");
            $stmt_vistas->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt_vistas->bindValue(':gid', $chat_con_id, PDO::PARAM_INT);
            $stmt_vistas->bindValue(':umid', $ult_msg_id, PDO::PARAM_INT);
            $stmt_vistas->execute();
        }
    }

    // 2. CARGAR HISTORIAL (v9.2 PDO)
    if ($chat_type === 'group') {
        $stmt_msgs = $db->prepare("SELECT m.*, u.nombre as remitente_nom FROM mensajes m 
                                   LEFT JOIN usuarios u ON m.remitente_id = u.id
                                   WHERE m.grupo_id = :gid 
                                   ORDER BY m.fecha_envio ASC");
        $stmt_msgs->bindValue(':gid', $chat_con_id, PDO::PARAM_INT);
    } else {
        $stmt_msgs = $db->prepare("SELECT * FROM mensajes 
                                   WHERE (remitente_id = :uid1 AND destinatario_id = :cid1 AND grupo_id = 0) 
                                   OR (remitente_id = :cid2 AND destinatario_id = :uid2 AND grupo_id = 0) 
                                   ORDER BY fecha_envio ASC");
        $stmt_msgs->bindValue(':uid1', $user_id, PDO::PARAM_INT);
        $stmt_msgs->bindValue(':uid2', $user_id, PDO::PARAM_INT);
        $stmt_msgs->bindValue(':cid1', $chat_con_id, PDO::PARAM_INT);
        $stmt_msgs->bindValue(':cid2', $chat_con_id, PDO::PARAM_INT);
    }
    
    $stmt_msgs->execute();

    $html = '';
    while ($m = $stmt_msgs->fetch(PDO::FETCH_ASSOC)) {
        $es_mio = ($m['remitente_id'] == $user_id);
        $clase_msg = $es_mio ? 'message-out justify-content-end' : 'message-in';
        $bubble_variant = $es_mio ? 'bubble-elite--out' : 'bubble-elite--in';
        
        $ticks = '';
        if ($es_mio) {
            $leido = (int)($m['leido'] ?? 0);
            $entregado = (int)($m['entregado'] ?? 0);
            $grupo_id = (int)($m['grupo_id'] ?? 0);
            
            if ($grupo_id > 0) {
                $tick_class = 'ticks-elite--sent';
                $num_ticks = 1;
            } else {
                if ($leido == 1) {
                    $tick_class = 'ticks-elite--read';
                    $num_ticks = 2;
                } elseif ($entregado == 1) {
                    $tick_class = 'ticks-elite--delivered';
                    $num_ticks = 2;
                } else {
                    $tick_class = 'ticks-elite--sent';
                    $num_ticks = 1;
                }
            }
            
            $ticks = '<span class="ticks-chat ms-1 '.$tick_class.'">';
            for ($i = 0; $i < $num_ticks; $i++) {
                $ticks .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="' . ($i > 0 ? 'tick-second' : 'tick-first') . '"><polyline points="3 12 8 17 19 6"></polyline></svg>';
            }
            $ticks .= '</span>';
        }

        $prio = (int)($m['prioridad'] ?? 1);
        $prio_class = $prio > 1 ? 'message-bubble-elite--priority-' . $prio : '';
        
        $priority_header = '';
        if ($prio === 2) {
            $priority_header = '<div class="priority-tag-elite priority-tag-elite--important"><i class="bi bi-info-circle-fill"></i> Importante</div>';
        } elseif ($prio === 3) {
            $priority_header = '<div class="priority-tag-elite priority-tag-elite--urgent">🚨 Urgente</div>';
        }

        $ack_footer = '';
        if ($prio === 3) {
            if (!$es_mio) {
                if (empty($m['fecha_lectura'])) {
                    $ack_footer = '
                    <button type="button" class="btn-acknowledge-elite" data-msg-id="' . $m['id'] . '">
                        <i class="bi bi-check-circle-fill"></i> Marcar como Entendido
                    </button>';
                } else {
                    $ack_footer = '
                    <div class="acknowledged-badge-elite">
                        <i class="bi bi-check-all"></i> Entendido (' . date('H:i d/m', strtotime($m['fecha_lectura'])) . ')
                    </div>';
                }
            } elseif (!empty($m['fecha_lectura'])) {
                $ack_footer = '
                <div class="acknowledged-badge-elite">
                    <i class="bi bi-check-all"></i> Entendido (' . date('H:i d/m', strtotime($m['fecha_lectura'])) . ')
                </div>';
            }
        }

        $html .= '
        <div class="message-group ' . ($es_mio ? 'message-out justify-content-end' : 'message-in') . ' mb-3 d-flex">
            <div class="message-bubble-elite position-relative ' . $prio_class . '">';
        
        if (!$es_mio && $chat_type === 'group') {
            $nom = htmlspecialchars($m['remitente_nom'] ?: 'Personal', ENT_QUOTES | ENT_IGNORE, 'UTF-8');
            $html .= '<small class="fw-bold text-primary d-block mb-1 fs-nano">' . $nom . '</small>';
        }

        $html .= $priority_header . '
                <p class="mb-1">' . htmlspecialchars($m['contenido'], ENT_QUOTES | ENT_IGNORE, 'UTF-8') . '</p>
                <div class="d-flex justify-content-end align-items-center mt-1">
                    <small class="opacity-75 fs-nano me-1">' . date('H:i', strtotime($m['fecha_envio'])) . '</small>
                    ' . $ticks . '
                </div>
                ' . $ack_footer . '
            </div>
        </div>';
    }

    echo json_encode(['status' => 'success', 'html' => $html, 'online' => $chat_online]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    // Conexión gestionada centralmente (v9.2)
}
exit();
?>

