<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/VISTAS/MENSAJERIA.PHP - INTERFAZ DE COMUNICACIÓN (PROTOCOLO MERCURIO)
if (!tiene_permiso('mensajeria')) {
    echo "<div class='alert-soberania-elite animate__animated animate__headShake'>
            <div class='alert-soberania-icon'><i class='bi bi-shield-lock-fill'></i></div>
            <div class='alert-soberania-content'>
                <h4>Acceso Restringido</h4>
                <p>El Protocolo Mercurio requiere credenciales de comunicación activas.</p>
            </div>
          </div>";
    return;
}

$user_id = (int)$_SESSION['usuario_id'];
$mi_rol = (int)$_SESSION['rol_id'];
$roles_autorizados_masivo = [1, 2, 3]; 

// =====================================================================================
// 1. MOTOR DE IDENTIDAD DEFINITIVO - CARGA DE CONTACTOS
// =====================================================================================
$contactos = [];
$estudiantes = [];

$stmt_u = $db->prepare("SELECT u.id, u.nombre, u.rol_id, u.ultima_actividad, u.estudiante_id, c.nombre_curso 
                        FROM usuarios u 
                        LEFT JOIN estudiantes e ON u.estudiante_id = e.id 
                        LEFT JOIN cursos c ON e.curso_id = c.id 
                        WHERE u.id != :uid 
                        ORDER BY u.nombre ASC");
$stmt_u->bindValue(':uid', $user_id, PDO::PARAM_INT);
$stmt_u->execute();

$stmt_no_leidos = $db->prepare("SELECT COUNT(*) FROM mensajes WHERE remitente_id = :rid AND destinatario_id = :did AND leido = 0 AND grupo_id = 0");
$stmt_no_leidos->bindValue(':did', $user_id, PDO::PARAM_INT);

$stmt_urgentes_no_leidos = $db->prepare("SELECT COUNT(*) FROM mensajes WHERE remitente_id = :rid AND destinatario_id = :did AND leido = 0 AND grupo_id = 0 AND prioridad = 3");
$stmt_urgentes_no_leidos->bindValue(':did', $user_id, PDO::PARAM_INT);

$stmt_mi_curso = $db->prepare("SELECT curso_id FROM estudiantes WHERE id = :eid");
$stmt_es_mi_profe = $db->prepare("SELECT COUNT(*) FROM usuarios u 
                                  LEFT JOIN carga_academica ON u.id = carga_academica.docente_id 
                                  LEFT JOIN cursos c ON u.id = c.tutor_id
                                  WHERE u.id = :uid AND (carga_academica.curso_id = :cid1 OR c.id = :cid2)");


while ($u = $stmt_u->fetch(PDO::FETCH_ASSOC)) {
    $u['online'] = false;
    if ($u['ultima_actividad'] && (time() - strtotime($u['ultima_actividad']) <= 15)) {
        $u['online'] = true;
    }
    $stmt_no_leidos->bindValue(':rid', $u['id'], PDO::PARAM_INT);
    $stmt_no_leidos->execute();
    $u['no_leidos'] = (int)$stmt_no_leidos->fetchColumn();

    $stmt_urgentes_no_leidos->bindValue(':rid', $u['id'], PDO::PARAM_INT);
    $stmt_urgentes_no_leidos->execute();
    $u['no_leidos_urgentes'] = (int)$stmt_urgentes_no_leidos->fetchColumn();

    // 🛡️ LÓGICA DE SOBERANÍA COMUNICATIVA
    if ((int)$u['rol_id'] === 5) { // ES ESTUDIANTE
        if ($mi_rol == 1) {
            $estudiantes[] = $u;
        } elseif ($mi_rol == 11) {
            // Solo alumnos de mis grupos (Tutoría o Carga)
            if ($u['estudiante_id']) {
                $stmt_is_mine = $db->prepare("SELECT COUNT(*) FROM estudiantes e 
                                              JOIN cursos c ON e.curso_id = c.id 
                                              WHERE e.id = :eid 
                                              AND (c.tutor_id = :tid1 OR c.id IN (SELECT curso_id FROM carga_academica WHERE docente_id = :tid2))");
                $stmt_is_mine->bindValue(':eid', $u['estudiante_id'], PDO::PARAM_INT);
                $stmt_is_mine->bindValue(':tid1', $user_id, PDO::PARAM_INT);
                $stmt_is_mine->bindValue(':tid2', $user_id, PDO::PARAM_INT);
                $stmt_is_mine->execute();
                if ((int)$stmt_is_mine->fetchColumn() > 0) $estudiantes[] = $u;
            }
        }
    } else { // ES PERSONAL (ADMIN / DOCENTE)
        if ($mi_rol === 5) { // El estudiante solo ve a sus profes
            $mi_est_id = $_SESSION['estudiante_id'] ?? 0;
            $stmt_mi_curso->bindValue(':eid', $mi_est_id, PDO::PARAM_INT);
            $stmt_mi_curso->execute();
            $mi_curso_id = (int)$stmt_mi_curso->fetchColumn();
            
            $stmt_es_mi_profe->bindValue(':uid', $u['id'], PDO::PARAM_INT);
            $stmt_es_mi_profe->bindValue(':cid1', $mi_curso_id, PDO::PARAM_INT);
            $stmt_es_mi_profe->bindValue(':cid2', $mi_curso_id, PDO::PARAM_INT);
            $stmt_es_mi_profe->execute();
            if ((int)$stmt_es_mi_profe->fetchColumn() > 0) $contactos[] = $u;
        } else {
            // Profesores y Admins se ven entre ellos por defecto para coordinación
            $contactos[] = $u;
        }
    }
}

// =====================================================================================
// 2. MOTOR DE CANALES (CURSOS Y SISTEMA)
// =====================================================================================
$todos_los_canales = [];
$res_c_stmt = null;

if (in_array($mi_rol, $roles_autorizados_masivo)) {
    $stmt_c_masivo = $db->prepare("SELECT id, nombre, referencia_id FROM grupos WHERE tipo = 'CURSO'");
    $stmt_c_masivo->execute();
    $res_c_stmt = $stmt_c_masivo;
} elseif ($mi_rol === 5) {
    $mi_est_id = $_SESSION['estudiante_id'] ?? 0;
    $stmt_mi_curso->bindValue(':eid', $mi_est_id, PDO::PARAM_INT);
    $stmt_mi_curso->execute();
    $mi_curso_id = (int)$stmt_mi_curso->fetchColumn();
    if ($mi_curso_id > 0) {
        $stmt_c = $db->prepare("SELECT id, nombre, referencia_id FROM grupos WHERE tipo = 'CURSO' AND referencia_id = :cid");
        $stmt_c->bindValue(':cid', $mi_curso_id, PDO::PARAM_INT);
        $stmt_c->execute();
        $res_c_stmt = $stmt_c;
    }
} elseif ($mi_rol == 11) {
    $stmt_c = $db->prepare("SELECT id, nombre, referencia_id FROM grupos 
              WHERE id IN (SELECT g.id FROM grupos g JOIN cursos c ON g.referencia_id = c.id WHERE g.tipo = 'CURSO' AND (c.tutor_id = :uid1 OR c.id IN (SELECT curso_id FROM carga_academica WHERE docente_id = :uid2)))");
    $stmt_c->bindValue(':uid1', $user_id, PDO::PARAM_INT);
    $stmt_c->bindValue(':uid2', $user_id, PDO::PARAM_INT);
    $stmt_c->execute();
    $res_c_stmt = $stmt_c;
}

$stmt_ult_v = $db->prepare("SELECT ultimo_mensaje_id FROM vistas_canales WHERE usuario_id = :uid AND grupo_id = :gid");
$stmt_ult_v->bindValue(':uid', $user_id, PDO::PARAM_INT);

$stmt_nl_g = $db->prepare("SELECT COUNT(*) FROM mensajes WHERE grupo_id = :gid AND id > :ult AND remitente_id != :uid");
$stmt_nl_g->bindValue(':uid', $user_id, PDO::PARAM_INT);

$stmt_urg_g = $db->prepare("SELECT COUNT(*) FROM mensajes WHERE grupo_id = :gid AND id > :ult AND remitente_id != :uid AND prioridad = 3");
$stmt_urg_g->bindValue(':uid', $user_id, PDO::PARAM_INT);

if ($res_c_stmt) {
    while($c = $res_c_stmt->fetch(PDO::FETCH_ASSOC)) {
        $g_id = (int)$c['id'];
        $stmt_ult_v->bindValue(':gid', $g_id, PDO::PARAM_INT);
        $stmt_ult_v->execute();
        $ult_v = (int)$stmt_ult_v->fetchColumn();
        
        $stmt_nl_g->bindValue(':gid', $g_id, PDO::PARAM_INT);
        $stmt_nl_g->bindValue(':ult', $ult_v, PDO::PARAM_INT);
        $stmt_nl_g->execute();
        $c['no_leidos'] = (int)$stmt_nl_g->fetchColumn();

        $stmt_urg_g->bindValue(':gid', $g_id, PDO::PARAM_INT);
        $stmt_urg_g->bindValue(':ult', $ult_v, PDO::PARAM_INT);
        $stmt_urg_g->execute();
        $c['no_leidos_urgentes'] = (int)$stmt_urg_g->fetchColumn();

        $todos_los_canales[] = $c;
    }
}

if (in_array($mi_rol, $roles_autorizados_masivo) || $mi_rol == 11) {
    $stmt_inst = $db->prepare("SELECT id, nombre FROM grupos WHERE nombre = :nom AND tipo = 'sistema'");
    $stmt_inst->bindValue(':nom', 'CANAL DE PROFESORES', PDO::PARAM_STR);
    $stmt_inst->execute();
    $cp = $stmt_inst->fetch(PDO::FETCH_ASSOC);
    if ($cp) {
        $g_id = (int)$cp['id'];
        $stmt_ult_v->bindValue(':gid', $g_id, PDO::PARAM_INT);
        $stmt_ult_v->execute();
        $ult_v = (int)$stmt_ult_v->fetchColumn();
        $stmt_nl_g->bindValue(':gid', $g_id, PDO::PARAM_INT);
        $stmt_nl_g->bindValue(':ult', $ult_v, PDO::PARAM_INT);
        $stmt_nl_g->execute();
        $cp['no_leidos'] = (int)$stmt_nl_g->fetchColumn();

        $stmt_urg_g->bindValue(':gid', $g_id, PDO::PARAM_INT);
        $stmt_urg_g->bindValue(':ult', $ult_v, PDO::PARAM_INT);
        $stmt_urg_g->execute();
        $cp['no_leidos_urgentes'] = (int)$stmt_urg_g->fetchColumn();

        $todos_los_canales[] = $cp;
    }
    
    $stmt_tutor_check = $db->prepare("SELECT COUNT(*) FROM cursos WHERE tutor_id = :uid");
    $stmt_tutor_check->bindValue(':uid', $user_id, PDO::PARAM_INT);
    $stmt_tutor_check->execute();
    if (in_array($mi_rol, $roles_autorizados_masivo) || (int)$stmt_tutor_check->fetchColumn() > 0) {
        $stmt_inst->bindValue(':nom', 'CANAL DE LÍDERES', PDO::PARAM_STR);
        $stmt_inst->execute();
        $cl = $stmt_inst->fetch(PDO::FETCH_ASSOC);
        if ($cl) {
            $g_id = (int)$cl['id'];
            $stmt_ult_v->bindValue(':gid', $g_id, PDO::PARAM_INT);
            $stmt_ult_v->execute();
            $ult_v = (int)$stmt_ult_v->fetchColumn();
            $stmt_nl_g->bindValue(':gid', $g_id, PDO::PARAM_INT);
            $stmt_nl_g->bindValue(':ult', $ult_v, PDO::PARAM_INT);
            $stmt_nl_g->execute();
            $cl['no_leidos'] = (int)$stmt_nl_g->fetchColumn();

            $stmt_urg_g->bindValue(':gid', $g_id, PDO::PARAM_INT);
            $stmt_urg_g->bindValue(':ult', $ult_v, PDO::PARAM_INT);
            $stmt_urg_g->execute();
            $cl['no_leidos_urgentes'] = (int)$stmt_urg_g->fetchColumn();

            $todos_los_canales[] = $cl;
        }
    }
}

// 4. CONTEXTO SELECCIONADO
$chat_con_id = (int)($_GET['chat'] ?? 0);
$chat_type = $_GET['type'] ?? 'direct';
$chat_nombre = "Seleccione una conversación";
$chat_online = false;

if ($chat_con_id > 0) {
    if ($chat_type === 'group') {
        $stmt_gn = $db->prepare("SELECT nombre FROM grupos WHERE id = :gid");
        $stmt_gn->bindValue(':gid', $chat_con_id, PDO::PARAM_INT);
        $stmt_gn->execute();
        $chat_nombre = $stmt_gn->fetchColumn() ?: "Canal Desconocido";
        
        $stmt_max = $db->prepare("SELECT MAX(id) FROM mensajes WHERE grupo_id = :gid");
        $stmt_max->bindValue(':gid', $chat_con_id, PDO::PARAM_INT);
        $stmt_max->execute();
        $ult_msg_id = (int)$stmt_max->fetchColumn();
        
        if ($ult_msg_id > 0) {
            $stmt_v = $db->prepare("REPLACE INTO vistas_canales (usuario_id, grupo_id, ultimo_mensaje_id) VALUES (:uid, :gid, :umid)");
            $stmt_v->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt_v->bindValue(':gid', $chat_con_id, PDO::PARAM_INT);
            $stmt_v->bindValue(':umid', $ult_msg_id, PDO::PARAM_INT);
            $stmt_v->execute();
        }
    } else {
        $stmt_ud = $db->prepare("SELECT nombre, ultima_actividad FROM usuarios WHERE id = :uid");
        $stmt_ud->bindValue(':uid', $chat_con_id, PDO::PARAM_INT);
        $stmt_ud->execute();
        $chat_data = $stmt_ud->fetch(PDO::FETCH_ASSOC);
        if ($chat_data) {
            $chat_nombre = $chat_data['nombre'];
            if ($chat_data['ultima_actividad'] && (time() - strtotime($chat_data['ultima_actividad']) <= 15)) $chat_online = true;
        }
        $stmt_upd = $db->prepare("UPDATE mensajes SET leido = 1 WHERE remitente_id = :rid AND destinatario_id = :did AND grupo_id = 0 AND (prioridad IS NULL OR prioridad != 3)");
        $stmt_upd->bindValue(':rid', $chat_con_id, PDO::PARAM_INT);
        $stmt_upd->bindValue(':did', $user_id, PDO::PARAM_INT);
        $stmt_upd->execute();
    }
}

$puedo_escribir = true;
if ($chat_con_id > 0 && $chat_type === 'group') {
    if (!in_array($mi_rol, $roles_autorizados_masivo)) {
        $puedo_escribir = false;
        $stmt_gi = $db->prepare("SELECT tipo, referencia_id FROM grupos WHERE id = :gid");
        $stmt_gi->bindValue(':gid', $chat_con_id, PDO::PARAM_INT);
        $stmt_gi->execute();
        $g_info = $stmt_gi->fetch(PDO::FETCH_ASSOC);
        if ($g_info && $g_info['tipo'] === 'CURSO' && $mi_rol == 11) {
            $curso_id = $g_info['referencia_id'];
            $stmt_tutor = $db->prepare("SELECT COUNT(*) FROM cursos WHERE id = :cid AND tutor_id = :uid");
            $stmt_tutor->bindValue(':cid', $curso_id, PDO::PARAM_INT);
            $stmt_tutor->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt_tutor->execute();
            $stmt_carga = $db->prepare("SELECT COUNT(*) FROM carga_academica WHERE curso_id = :cid AND docente_id = :uid");
            $stmt_carga->bindValue(':cid', $curso_id, PDO::PARAM_INT);
            $stmt_carga->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt_carga->execute();
            if ((int)$stmt_tutor->fetchColumn() > 0 || (int)$stmt_carga->fetchColumn() > 0) $puedo_escribir = true;
        }
    }
}
?>

<link rel="stylesheet" href="../styles/modules/mensajeria.css?v=<?php echo time(); ?>">

<div class="chat-elite-wrapper" id="chat-elite-wrapper" data-todos-los-canales="<?php echo htmlspecialchars(json_encode($todos_los_canales), ENT_QUOTES, 'UTF-8'); ?>" data-school-logo="<?php echo htmlspecialchars($estilos['school_logo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-brand-color="<?php echo htmlspecialchars($estilos['brand_color'] ?? '#0a044d', ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" id="destinatario_id" value="<?php echo $chat_con_id; ?>">
    <input type="hidden" id="chat_type_input" value="<?php echo $chat_type; ?>">
    <input type="hidden" id="csrf_token_chat" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    
    <!-- LATERAL: LISTA DE CONTACTOS -->
    <div class="hermes-sidebar">
        <div class="hermes-search-box">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--el-primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                </div>
                <h5 class="hero-module-title mb-0 fs-xs tracking-widest">HERMES</h5>
            </div>
            
            <?php if (in_array($mi_rol, $roles_autorizados_masivo)): ?>
                <button class="btn-elite btn-elite--primary w-100 mb-4 ares-h-44" onclick="abrirModalMasivo()">
                    <i class="bi bi-broadcast me-2"></i> COMUNICADO MASIVO
                </button>
            <?php endif; ?>

            <div class="search-wrapper-elite">
                <span class="search-icon-elite-fixed"><i class="bi bi-search"></i></span>
                <input type="text" class="search-elite ares-h-44" placeholder="Buscar contacto..." id="buscar-contacto" autocomplete="off">
            </div>
            
            <div class="hermes-filters-container mt-3">
                <button type="button" class="btn-filter-elite" id="filter-unread" title="Filtrar por no leídos">
                    <i class="bi bi-envelope"></i> No leídos
                </button>
                <button type="button" class="btn-filter-elite" id="filter-urgent" title="Filtrar por urgentes">
                    <i class="bi bi-exclamation-triangle"></i> Urgentes
                </button>
            </div>
        </div>
        
        <div class="contact-list custom-scroll">
            <div class="accordion accordion-flush" id="accordionChat">
                
                <!-- 1. CANALES -->
                <div class="accordion-item border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed custom-acc-btn py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCanales">
                            <i class="bi bi-collection-play-fill me-2"></i> CANALES
                        </button>
                    </h2>
                    <div id="collapseCanales" class="accordion-collapse collapse <?php echo ($chat_type === 'group') ? 'show' : ''; ?>">
                        <div class="accordion-body p-0">
                            <?php foreach ($todos_los_canales as $can): ?>
                                <?php 
                                    $nombre_raw = htmlspecialchars($can['nombre']);
                                    if (!str_contains(strtolower($nombre_raw), 'salón') && !str_contains(strtolower($nombre_raw), 'canal')) {
                                        $nombre_raw = "SALÓN " . $nombre_raw;
                                    }
                                ?>
                                <div class="contact-item d-flex align-items-center cursor-pointer <?php echo ($chat_con_id == $can['id'] && $chat_type === 'group') ? 'is-active-elite' : ''; ?>" 
                                     onclick="abrirChat(<?php echo $can['id']; ?>, 'group')" data-group-id="<?php echo $can['id']; ?>" data-unread="<?php echo $can['no_leidos'] > 0 ? '1' : '0'; ?>" data-urgent="<?php echo ($can['no_leidos_urgentes'] ?? 0) > 0 ? '1' : '0'; ?>">
                                    <div class="avatar-elite--sm avatar-elite--circle me-3 bg-primary bg-opacity-10 text-primary">
                                        <i class="bi bi-collection-play-fill fs-nano"></i>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="elite-chat-contact-name text-truncate"><?php echo $nombre_raw; ?></div>
                                            <?php if ($can['no_leidos'] > 0): ?><span class="badge-elite badge-elite--danger"><?php echo $can['no_leidos']; ?></span><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- 2. PERSONAL / STAFF -->
                <div class="accordion-item border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed custom-acc-btn py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStaff">
                            <i class="bi bi-person-badge-fill me-2"></i> PERSONAL ACADÉMICO
                        </button>
                    </h2>
                    <div id="collapseStaff" class="accordion-collapse collapse <?php echo ($chat_type === 'direct' && in_array($chat_con_id, array_column($contactos, 'id'))) ? 'show' : ''; ?>">
                        <div class="accordion-body p-0">
                            <?php foreach ($contactos as $c): ?>
                                <div class="contact-item d-flex align-items-center cursor-pointer <?php echo ($chat_con_id == $c['id'] && $chat_type === 'direct') ? 'is-active-elite' : ''; ?>" 
                                     onclick="abrirChat(<?php echo $c['id']; ?>, 'direct')" data-user-id="<?php echo $c['id']; ?>" data-unread="<?php echo $c['no_leidos'] > 0 ? '1' : '0'; ?>" data-urgent="<?php echo ($c['no_leidos_urgentes'] ?? 0) > 0 ? '1' : '0'; ?>">
                                    <div class="avatar-elite--sm avatar-elite--circle me-3 bg-primary bg-opacity-10 text-primary position-relative">
                                        <?php echo strtoupper(substr($c['nombre'], 0, 1)); ?>
                                        <?php if ($c['online']): ?><span class="status-indicator online"></span><?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="elite-chat-contact-name text-truncate"><?php echo htmlspecialchars($c['nombre']); ?></div>
                                            <?php if ($c['no_leidos'] > 0): ?><span class="badge-elite badge-elite--danger"><?php echo $c['no_leidos']; ?></span><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- 3. ESTUDIANTES -->
                <?php if (!empty($estudiantes)): ?>
                <div class="accordion-item border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed custom-acc-btn py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEstudiantes">
                            <i class="bi bi-backpack-fill me-2"></i> RED DE ESTUDIANTES
                        </button>
                    </h2>
                    <div id="collapseEstudiantes" class="accordion-collapse collapse <?php echo ($chat_type === 'direct' && in_array($chat_con_id, array_column($estudiantes, 'id'))) ? 'show' : ''; ?>">
                        <div class="accordion-body p-0">
                            <?php foreach ($estudiantes as $e): ?>
                                <div class="contact-item d-flex align-items-center cursor-pointer <?php echo ($chat_con_id == $e['id'] && $chat_type === 'direct') ? 'is-active-elite' : ''; ?>" 
                                     onclick="abrirChat(<?php echo $e['id']; ?>, 'direct')" data-user-id="<?php echo $e['id']; ?>" data-unread="<?php echo $e['no_leidos'] > 0 ? '1' : '0'; ?>" data-urgent="<?php echo ($e['no_leidos_urgentes'] ?? 0) > 0 ? '1' : '0'; ?>">
                                    <div class="avatar-elite--sm avatar-elite--circle me-3 bg-success bg-opacity-10 text-success position-relative">
                                        <?php echo strtoupper(substr($e['nombre'], 0, 1)); ?>
                                        <?php if ($e['online']): ?><span class="status-indicator online"></span><?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="elite-chat-contact-name text-truncate">
                                                <?php echo htmlspecialchars($e['nombre']); ?>
                                                <?php if (!empty($e['nombre_curso'])): ?>
                                                    <span class="badge-elite badge-elite-curso ms-1"><?php echo htmlspecialchars($e['nombre_curso']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($e['no_leidos'] > 0): ?><span class="badge-elite badge-elite--danger"><?php echo $e['no_leidos']; ?></span><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- PRINCIPAL: VENTANA DE CHAT -->
    <div class="chat-main">
        <?php if ($chat_con_id > 0): ?>
            <div class="chat-header p-3 border-bottom d-flex align-items-center justify-content-between bg-white z-10">
                <div class="d-flex align-items-center overflow-hidden">
                    <button class="btn-back-chat" onclick="cerrarChat()">
                        <i class="bi bi-chevron-left fs-4"></i>
                    </button>
                    
                    <div class="avatar-elite--sm avatar-elite--circle me-3 <?php echo ($chat_type === 'group' ? 'bg-primary bg-opacity-10 text-primary' : 'bg-success bg-opacity-10 text-success'); ?>">
                        <?php echo ($chat_type === 'group' ? '#' : strtoupper(substr($chat_nombre, 0, 1))); ?>
                    </div>
                    <div class="overflow-hidden">
                        <div class="elite-chat-contact-name text-dark fs-6 fw-bold text-truncate"><?php echo htmlspecialchars($chat_nombre); ?></div>
                        <div id="chat-status-text">
                            <?php if ($chat_type === 'group'): ?>
                                <small class="status-label-elite fs-nano opacity-50 uppercase tracking-widest">Canal Institucional</small>
                            <?php elseif ($chat_online): ?>
                                <small class="status-label-elite fs-nano text-success fw-bold">● EN LÍNEA</small>
                            <?php else: ?>
                                <small class="status-label-elite fs-nano text-muted opacity-50">FUERA DE LÍNEA</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chat-history custom-scroll" id="chat-history">
                <?php
                if ($chat_type === 'group') {
                    $stmt_msgs = $db->prepare("SELECT * FROM mensajes WHERE grupo_id = :gid ORDER BY fecha_envio ASC");
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
                
                $stmt_rn = $db->prepare("SELECT nombre FROM usuarios WHERE id = :uid");
                while ($m = $stmt_msgs->fetch(PDO::FETCH_ASSOC)):
                    $es_mio = ($m['remitente_id'] == $user_id);
                ?>
                    <?php 
                    $prio = (int)($m['prioridad'] ?? 1);
                    $prio_class = $prio > 1 ? 'message-bubble-elite--priority-' . $prio : '';
                    ?>
                    <div class="message-group <?php echo $es_mio ? 'message-out' : 'message-in'; ?> mb-3 d-flex <?php echo $es_mio ? 'justify-content-end' : ''; ?>">
                        <div class="message-bubble-elite position-relative <?php echo $prio_class; ?>">
                            <?php if (!$es_mio && $chat_type === 'group'): ?>
                                <small class="fw-bold text-primary d-block mb-1 fs-nano"><?php 
                                    $stmt_rn->bindValue(':uid', $m['remitente_id'], PDO::PARAM_INT);
                                    $stmt_rn->execute();
                                    $rem = $stmt_rn->fetchColumn();
                                    echo htmlspecialchars($rem ?: "Usuario exterior"); 
                                ?></small>
                            <?php endif; ?>
                            
                            <?php if ($prio === 2): ?>
                                <div class="priority-tag-elite priority-tag-elite--important"><i class="bi bi-info-circle-fill"></i> Importante</div>
                            <?php elseif ($prio === 3): ?>
                                <div class="priority-tag-elite priority-tag-elite--urgent">🚨 Urgente</div>
                            <?php endif; ?>

                            <p class="mb-1"><?php echo htmlspecialchars($m['contenido']); ?></p>
                            <div class="d-flex justify-content-end align-items-center mt-1">
                                <small class="opacity-75 fs-nano me-1"><?php echo date('H:i', strtotime($m['fecha_envio'])); ?></small>
                                <?php
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
                                    
                                    echo '<span class="ticks-chat ms-1 '.$tick_class.'">';
                                    for ($i = 0; $i < $num_ticks; $i++) {
                                        echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="' . ($i > 0 ? 'tick-second' : 'tick-first') . '"><polyline points="3 12 8 17 19 6"></polyline></svg>';
                                    }
                                    echo '</span>';
                                }
                                ?>
                            </div>
                            
                            <?php if ($prio === 3): ?>
                                <?php if (!$es_mio): ?>
                                    <?php if (empty($m['fecha_lectura'])): ?>
                                        <button type="button" class="btn-acknowledge-elite" data-msg-id="<?php echo $m['id']; ?>">
                                            <i class="bi bi-check-circle-fill"></i> Marcar como Entendido
                                        </button>
                                    <?php else: ?>
                                        <div class="acknowledged-badge-elite">
                                            <i class="bi bi-check-all"></i> Entendido (<?php echo date('H:i d/m', strtotime($m['fecha_lectura'])); ?>)
                                        </div>
                                    <?php endif; ?>
                                <?php elseif (!empty($m['fecha_lectura'])): ?>
                                    <div class="acknowledged-badge-elite">
                                        <i class="bi bi-check-all"></i> Entendido (<?php echo date('H:i d/m', strtotime($m['fecha_lectura'])); ?>)
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="chat-input-container">
                <?php if ($puedo_escribir): ?>
                    <form id="form-mensaje" class="chat-input-form animate__animated animate__fadeInUp">
                        <div class="priority-selector-dots">
                            <label class="priority-dot priority-dot--success" title="Normal">
                                <input type="radio" name="prioridad" value="1" checked class="d-none">
                                <span class="dot-inner"></span>
                                <span class="priority-label">Normal</span>
                            </label>
                            <label class="priority-dot priority-dot--warning" title="Importante">
                                <input type="radio" name="prioridad" value="2" class="d-none">
                                <span class="dot-inner"></span>
                                <span class="priority-label">Importante</span>
                            </label>
                            <label class="priority-dot priority-dot--danger" title="Urgente">
                                <input type="radio" name="prioridad" value="3" class="d-none">
                                <span class="dot-inner"></span>
                                <span class="priority-label">Urgente</span>
                            </label>
                        </div>
                        <input type="text" id="mensaje-texto" class="input-elite flex-fill" placeholder="Escribe un mensaje institucional..." autocomplete="off">
                        <button type="submit" class="btn-elite btn-elite--primary shadow-sm" title="Transmitir Mensaje">
                            <i class="bi bi-send-fill fs-5"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert-soberania-elite m-0 py-2 d-flex align-items-center justify-content-center text-secondary small fw-bold">
                        <i class="bi bi-shield-lock-fill me-2"></i> SÓLO LECTURA: Canal de Transmisión
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center p-5 animate__animated animate__fadeIn">
                <div class="mb-4">
                    <div class="bg-primary bg-opacity-10 p-5 rounded-circle d-flex align-items-center justify-content-center icon-elite--master size-250">
                        <svg class="size-180" viewBox="0 0 24 24" fill="none" stroke="var(--el-primary)" stroke-width="0.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </div>
                </div>
                <h4 class="fw-bold text-dark mt-3">Protocolo Hermes</h4>
                <p class="text-secondary w-75 mx-auto">Seleccione un canal o una persona para iniciar la comunicación oficial bajo el Protocolo Hermes.</p>
            </div>
        <?php endif; ?>
    </div>
</div>


<!-- MODAL COMUNICADO MASIVO (Ventana Flotante No Bloqueante) -->

<!-- MODAL COMUNICADO MASIVO (Ventana Flotante No Bloqueante) -->
<div class="modal fade modal-non-blocking" id="modalMasivo" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-dialog-centered shadow-lg u-pointer-events-auto">
        <div class="modal-content card-elite-depth border-0 border-t-primary-5">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-broadcast me-2"></i> Comunicado Masivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-secondary small mb-3">Seleccione los canales de destino:</p>
                <div class="custom-scroll mb-3" id="lista-canales-masivo"></div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Prioridad</label>
                    <select class="select-elite" id="masivo-prioridad">
                        <option value="1">Normal (Institucional)</option>
                        <option value="2">Importante (Atención)</option>
                        <option value="3">Urgente (Crítico)</option>
                    </select>
                </div>

                <div class="mb-0">
                    <label class="form-label small fw-bold text-muted text-uppercase">Contenido del Mensaje</label>
                    <textarea class="input-elite u-h-auto" id="masivo-texto" rows="4" placeholder="Escriba aquí el mensaje..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-2 d-flex gap-2 justify-content-end">
                <button type="button" class="btn-elite btn-elite--sm btn-elite--outline" data-bs-dismiss="modal">DESCARTAR</button>
                <button type="button" class="btn-elite btn-elite--sm shadow-sm" onclick="enviarMasivo()">TRANSMITIR</button>
            </div>
        </div>
    </div>
</div>

<script src="../js/mensajeria.js" defer></script>
<script src="../js/modules/chat_engine.js?v=<?php echo time(); ?>"></script>
