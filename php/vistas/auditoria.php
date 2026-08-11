<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/VISTAS/AUDITORIA.PHP - BITÁCORA DE SEGURIDAD ELITE

// 1. SEGURIDAD: Solo ADMIN o DIRECTOR (o con permiso expreso)
if (!tiene_permiso('auditoria')) {
    echo "<div class='container p-5 text-center'><h4 class='text-danger'>Acceso Denegado</h4><p>No tiene privilegios para consultar la bitácora de seguridad.</p></div>";
    return;
}

// 2. CONSULTA MAESTRA CON JOIN PARA IDENTIFICAR ACTORES
$query = "
    SELECT l.*, u.nombre as actor_nombre, u.usuario as actor_user
    FROM logs_auditoria l
    LEFT JOIN usuarios u ON l.actor_usuario_id = u.id
    ORDER BY l.fecha DESC
";
$stmt_audit = $db->prepare($query);
$stmt_audit->execute();
$result = $stmt_audit;

// Función auxiliar para badges de acción
function get_badge_clase($accion) {
    switch ($accion) {
        case 'PERMISOS_PERSONALIZADOS': return 'bg-warning text-dark';
        case 'PERMISOS_ROL_ACTUALIZADOS': return 'bg-primary';
        case 'PERMISOS_RESTABLECIDOS': return 'bg-info text-dark';
        case 'TEST_LOG': return 'bg-secondary';
        default: return 'bg-dark';
    }
}
?>

<div class="container-fluid py-4">
    
    <!-- CABECERA UNIFICADA BOOTSTRAP 5 -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-7 text-center text-md-start border-start border-4 border-topbar-elite ps-4">
            <h4 class="h3 fw-bold mb-0 text-titulo-elite">Bitácora de Seguridad</h4>
            <p class="text-secondary mb-0 small">Historial sistemático de cambios críticos en el sistema.</p>
        </div>
        <div class="col-md-5">
            <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-end">
                <div class="input-group col-search-bar input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" class="input-elite border-start-0 border-end-0 buscador-dinamico" placeholder="Buscar por acción, usuario o detalle...">
                    <button class="btn btn-elite--outline btn-clear-search bg-white border-start-0 text-primary" type="button" title="Limpiar búsqueda">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE LOGS -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="table-responsive-elite table-scroll-elite">
            <table class="table-elite tabla-datos table-hover table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th class="py-3 ps-4 text-uppercase small fw-800 col-id-lg">Fecha y Hora</th>
                        <th class="py-3 text-uppercase small fw-800">Actor (Administrador)</th>
                        <th class="py-3 text-uppercase small fw-800 text-center">Acción</th>
                        <th class="py-3 text-uppercase small fw-800">Detalles del Evento</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php
                    $hay_registros = false;
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        $hay_registros = true;
                        $fecha = date("d/m/Y H:i:s", strtotime($row['fecha']));
                        $actor = $row['actor_nombre'] ? htmlspecialchars($row['actor_nombre']) . " <span class='text-muted small'>(@{$row['actor_user']})</span>" : "<i>Sistema / Desconocido</i>";
                        $badge = get_badge_clase($row['accion']);
                        
                        echo "<tr>";
                            echo "<td class='ps-4 fw-bold text-muted small'>" . $fecha . "</td>";
                            echo "<td><div class='fw-semibold text-dark small text-uppercase'>" . $actor . "</div></td>";
                            echo "<td class='text-center'><span class='badge {$badge} border px-2 fs-nano'>" . $row['accion'] . "</span></td>";
                            echo "<td><div class='text-secondary small lh-sm'>" . htmlspecialchars($row['detalles']) . " <br><small class='text-muted'>ID Objetivo ({$row['objetivo_tipo']}): #{$row['objetivo_id']}</small></div></td>";
                        echo "</tr>";
                    }

                    if (!$hay_registros) {
                        echo "<tr><td colspan='4' class='text-center py-5 text-muted'>No hay registros de actividad todavía.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



