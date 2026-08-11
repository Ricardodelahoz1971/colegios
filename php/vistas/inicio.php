<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/VISTAS/INICIO.PHP - DASHBOARD DINÁMICO ÉLITE v2.0
$user_id = (int)$_SESSION['usuario_id'];
$mi_rol = (int)$_SESSION['rol_id'];
$mi_ident = $_SESSION['nombre_usuario'];

// 🛡️ TELEMETRÍA DE SALUD REAL (v1.0)
require_once __DIR__ . '/../logica/SistemaSalud.php';
$salud = SistemaSalud::obtenerDiagnostico($db);

// 🛡️ BLINDAJE DE MÉTRICAS (v9.2 PDO)
$stmt_est_count = $db->prepare("SELECT COUNT(*) FROM estudiantes");
$stmt_est_count->execute();
$count_estudiantes = (int)$stmt_est_count->fetchColumn() ?: 0;

// 2. MENSAJES NUEVOS (Optimización Atómica v2.0)
$unread_direct = 0;
$unread_groups = 0;

// Mensajes directos
$stmt_direct = $db->prepare("SELECT COUNT(*) FROM mensajes WHERE destinatario_id = :uid1 AND leido = 0 AND remitente_id != :uid2");
$stmt_direct->execute([':uid1' => $user_id, ':uid2' => $user_id]);
$unread_direct = (int)$stmt_direct->fetchColumn();

// Mensajes de grupos (Soberanía de un solo query)
$groups_sql = "";
$params_unread = [':uid_vc' => $user_id, ':uid_rem' => $user_id];

if ($mi_rol == 1 || $mi_rol == 2 || $mi_rol == 3 || $mi_rol == 17) {
    $groups_sql = "SELECT id FROM cursos";
} elseif ($mi_rol == 12 || $mi_rol == 5) {
    $groups_sql = "SELECT curso_id FROM estudiantes WHERE id = :est_id";
    $params_unread[':est_id'] = $_SESSION['estudiante_id'] ?? 0;
} elseif ($mi_rol == 11) {
    $groups_sql = "SELECT id FROM cursos WHERE tutor_id = :uid_tutor UNION SELECT curso_id FROM carga_academica WHERE docente_id = :uid_doc";
    $params_unread[':uid_tutor'] = $user_id;
    $params_unread[':uid_doc'] = $user_id;
}

if ($groups_sql) {
    $stmt_groups_unread = $db->prepare("
        SELECT COUNT(m.id) 
        FROM mensajes m
        LEFT JOIN vistas_canales vc ON m.grupo_id = vc.grupo_id AND vc.usuario_id = :uid_vc
        WHERE m.grupo_id IN ($groups_sql)
          AND m.id > IFNULL(vc.ultimo_mensaje_id, 0)
          AND m.remitente_id != :uid_rem
    ");
    $stmt_groups_unread->execute($params_unread);
    $unread_groups = (int)$stmt_groups_unread->fetchColumn();
}

$count_mensajes = $unread_direct + $unread_groups;
?>


<div class="container-max-elite py-4 px-3 animate__animated animate__fadeIn" id="inicio-container-master">
    
    <!-- BIENVENIDA ELITE -->
    <div class="row">
        <div class="col-12">
            <div class="welcome-section-elite">
                <h1 class="welcome-title-elite">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></h1>
                <p class="welcome-subtitle-elite">Resumen general del estado administrativo y académico del plantel.</p>
            </div>
        </div>
    </div>



    <!-- AREA DE TARJETAS (Metric System Elite) -->
    <div class="row g-3">
        <!-- ESTUDIANTES -->
        <div class="col-xl-4 col-lg-6 col-md-12">
            <div class="metric-card-elite variant-success">
                <div class="metric-header-elite">
                    <div class="metric-icon-box-elite">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <span class="metric-label-elite">Comunidad Estudiantil</span>
                </div>
                <div class="metric-value-elite" id="count-estudiantes"><?php echo number_format($count_estudiantes); ?></div>
                <div class="metric-footer-elite">
                    <span class="badge-elite badge-elite--success">
                        <i class="bi bi-shield-check me-1"></i> Cifra oficial registrada
                    </span>
                </div>
            </div>
        </div>

        <!-- MENSAJES -->
        <div class="col-xl-4 col-lg-6 col-md-12">
            <div class="metric-card-elite variant-danger">
                <div class="metric-header-elite">
                    <div class="metric-icon-box-elite">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    </div>
                    <span class="metric-label-elite">Radar de Mensajería</span>
                </div>
                <div class="metric-value-elite" id="count-mensajes"><?php echo number_format($count_mensajes); ?></div>
                <div class="metric-footer-elite">
                    <span class="badge-elite badge-elite--danger">
                        <i class="bi bi-chat-dots me-1"></i> <?php echo $count_mensajes === 1 ? 'Pendiente' : 'Pendientes'; ?> de lectura
                    </span>
                </div>
            </div>
        </div>

        <!-- ESTADO -->
        <div class="col-xl-4 col-lg-12 col-md-12">
            <div class="metric-card-elite variant-info">
                <div class="metric-header-elite">
                    <div class="metric-icon-box-elite">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    </div>
                    <span class="metric-label-elite">Salud del Sistema</span>
                </div>
                <div class="metric-value-elite d-flex align-items-center justify-content-between px-0">
                    <span class="badge-elite badge-elite--<?php echo str_replace('text-', '', $salud['color']); ?> shadow-sm">
                        <?php echo $salud['estado']; ?>
                    </span>
                    <div class="pulsar-status-elite <?php echo $salud['pulsar']; ?>"></div>
                </div>
                <div class="metric-footer-elite text-secondary d-flex flex-column gap-1 mt-2">
                    <div class="d-flex justify-content-between w-100">
                        <span>Latencia: <b class="text-dark"><?php echo $salud['detalles']['latencia'] ?? 'N/A'; ?></b></span>
                        <span>DB: <b class="text-dark"><?php echo $salud['db_size']; ?></b></span>
                    </div>
                    <?php if (!empty($salud['alertas'])): ?>
                        <div class="d-flex flex-wrap gap-1 align-items-start w-100">
                            <?php foreach ($salud['alertas'] as $alerta): ?>
                                <span class="badge-elite badge-elite--danger fs-nano">
                                    <i class="bi bi-exclamation-triangle me-1"></i><?php echo $alerta; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="d-flex w-100">
                            <span class="badge-elite badge-elite--success fs-nano">
                                <i class="bi bi-check-circle-fill me-1"></i> SISTEMAS NOMINALES
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 🛡️ SECCIÓN PERSEUS: AUDITORÍA DE COBERTURA CURRICULAR -->
    <?php if ($mi_rol == 1 || $mi_rol == 2 || $mi_rol == 3 || $mi_rol == 11): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card-elite card-perseus p-4 animate__animated animate__fadeInUp">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                    <div>
                        <h4 class="mb-1 text-primary fw-bold">
                            <i class="bi bi-shield-shaded me-2"></i>SISTEMA PERSEUS
                        </h4>
                        <p class="text-muted small mb-0">Auditoría de Cobertura Curricular MEN (DBA) en Tiempo Real.</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <?php 
                        // 1. Obtener la carga académica completa/específica
                        if (in_array($mi_rol, [1, 2, 3])) {
                            // Directivos: ver carga completa de la institución
                            $stmt_carga = $db->prepare("
                                SELECT ca.curso_id, ca.especialidad_id, ca.docente_id, c.nombre_curso, e.nombre_especialidad, u.nombre as docente_nombre 
                                FROM carga_academica ca
                                JOIN cursos c ON ca.curso_id = c.id
                                JOIN especialidades e ON ca.especialidad_id = e.id
                                JOIN usuarios u ON ca.docente_id = u.id
                                ORDER BY u.nombre ASC, e.nombre_especialidad ASC
                            ");
                            $stmt_carga->execute();
                        } else {
                            // Docente: ver carga propia
                            $stmt_carga = $db->prepare("
                                SELECT ca.curso_id, ca.especialidad_id, ca.docente_id, c.nombre_curso, e.nombre_especialidad, u.nombre as docente_nombre 
                                FROM carga_academica ca
                                JOIN cursos c ON ca.curso_id = c.id
                                JOIN especialidades e ON ca.especialidad_id = e.id
                                JOIN usuarios u ON ca.docente_id = u.id
                                WHERE ca.docente_id = :uid
                                ORDER BY e.nombre_especialidad ASC
                            ");
                            $stmt_carga->execute([':uid' => $user_id]);
                        }
                        $cargas = $stmt_carga->fetchAll(); ?>
<div id="perseus-cfg-bridge" data-perseus-cfg="<?php echo htmlspecialchars(json_encode([ 'cargas' => $cargas ?? [], 'miRol' => $mi_rol ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"></div><?php

                        // 2. Obtener cursos únicos correspondientes a la carga
                        if (in_array($mi_rol, [1, 2, 3])) {
                            $stmt_cursos = $db->prepare("
                                SELECT DISTINCT c.id, c.nombre_curso 
                                FROM carga_academica ca
                                JOIN cursos c ON ca.curso_id = c.id
                                ORDER BY c.nombre_curso ASC
                            ");
                            $stmt_cursos->execute();
                        } else {
                            $stmt_cursos = $db->prepare("
                                SELECT DISTINCT c.id, c.nombre_curso 
                                FROM carga_academica ca
                                JOIN cursos c ON ca.curso_id = c.id
                                WHERE ca.docente_id = :uid
                                ORDER BY c.nombre_curso ASC
                            ");
                            $stmt_cursos->execute([':uid' => $user_id]);
                        }
                        $cursos_d = $stmt_cursos->fetchAll();
                        ?>

                        <!-- Selector 1: El Curso -->
                        <select id="perseus-curso-selector" class="select-elite px-4 perseus-select-curso" onchange="filtrarDocentesPerseus()">
                            <option value="" disabled selected>Curso...</option>
                            <?php foreach ($cursos_d as $cur): ?>
                                <option value="<?php echo $cur['id']; ?>"><?php echo htmlspecialchars($cur['nombre_curso']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Selector 2: El Docente y Materia -->
                        <select id="perseus-docente-selector" class="select-elite px-4 perseus-select-docente" onchange="actualizarPerseus()">
                            <!-- Se poblará vía Javascript -->
                        </select>

                        <button class="btn-elite btn-elite--primary" onclick="actualizarPerseus()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>

                <div class="row align-items-center g-4">
                    <!-- INDICADOR VISUAL (EL SEMÁFORO) -->
                    <div class="col-lg-4 text-center border-end border-opacity-10">
                        <div class="position-relative d-inline-block">
                            <svg width="180" height="180" viewBox="0 0 100 100" class="perseus-ring">
                                <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(var(--el-primary-rgb), 0.1)" stroke-width="8"></circle>
                                <circle id="perseus-progress" cx="50" cy="50" r="45" fill="none" stroke="var(--el-primary)" stroke-width="8" 
                                        stroke-dasharray="283" stroke-dashoffset="283" stroke-linecap="round" class="perseus-progress-circle"></circle>
                            </svg>
                            <div class="position-absolute top-50 start-50 translate-middle text-center">
                                <div id="perseus-percent" class="h1 fw-black mb-0 text-primary">0%</div>
                                <div id="perseus-label" class="fs-nano text-uppercase fw-bold opacity-50">COBERTURA</div>
                            </div>
                        </div>
                    </div>

                    <!-- DETALLES TÉCNICOS -->
                    <div class="col-lg-8">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 rounded-4 bg-light border border-opacity-10 h-100">
                                    <h6 class="text-uppercase fs-nano fw-bold text-muted mb-2">Diagnóstico MEN</h6>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span id="dba-evaluados" class="h4 fw-bold mb-0">0</span>
                                        <span class="text-muted">de</span>
                                        <span id="dba-totales" class="h4 fw-bold mb-0">0</span>
                                        <span class="text-muted small">DBA evaluados</span>
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2 mt-3 pt-3 border-top border-opacity-10">
                                        <span id="evidencias-evaluadas" class="h4 fw-bold mb-0">0</span>
                                        <span class="text-muted">de</span>
                                        <span id="evidencias-totales" class="h4 fw-bold mb-0">0</span>
                                        <span class="text-muted small">Evidencias cubiertas</span>
                                    </div>
                                    <div class="small text-muted mt-1 italic">
                                        Precisión Curricular: <span id="evidencias-porcentaje">0</span>%
                                    </div>
                                    <div id="perseus-status-badge" class="mt-3">
                                        <span class="badge-elite badge-elite--neutral">SINCRONIZANDO...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 rounded-4 bg-light border border-opacity-10 h-100">
                                    <h6 class="text-uppercase fs-nano fw-bold text-muted mb-2">Acción Sugerida</h6>
                                    <p id="perseus-sugerencia" class="small text-muted mb-0">Cargando inteligencia académica...</p>
                                    <div id="perseus-btn-container" class="mt-3 d-none">
                                        <button class="btn-elite btn-elite--primary w-100 py-2" onclick="navegarModulo('editor_preguntas')">
                                            <i class="bi bi-plus-circle me-2"></i>Completar Saberes
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/inicio.js" defer></script>

    <?php endif; ?>
</div>
