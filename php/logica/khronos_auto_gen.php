<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
include '../db.php';
header('Content-Type: application/json');

$curso_id = (int)($_GET['curso_id'] ?? 0);
if (!$curso_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID de curso no válido']);
    exit();
}

// 2. OBTENER NIVEL DEL CURSO Y JORNADA
$stmt_nv = $db->prepare("SELECT nivel_id, jornada FROM cursos WHERE id = ?");
$stmt_nv->execute([$curso_id]);
$curso_data = $stmt_nv->fetch(PDO::FETCH_ASSOC);
$nivel_id = (int)($curso_data['nivel_id'] ?? 0);
$jornada_curso = $curso_data['jornada'] ?? 'Mañana';

// 1. CARGAR CONFIGURACIÓN GLOBAL
$stmt_c_kh = $db->prepare("SELECT * FROM ajustes_estetica WHERE clave LIKE 'khronos%'");
$stmt_c_kh->execute();
$stmt_c = $stmt_c_kh;
$cfg = [];
while ($row = $stmt_c->fetch(PDO::FETCH_ASSOC)) {
    $cfg[$row['clave']] = $row['valor'];
}

$obtener_cfg = function($clave, $jornada) use ($cfg) {
    $suffix = strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['', 'a', 'e', 'i', 'o', 'u'], $jornada));
    $clave_jornada = $clave . '_' . $suffix;
    if (isset($cfg[$clave_jornada]) && $cfg[$clave_jornada] !== '') {
        return $cfg[$clave_jornada];
    }
    return $cfg[$clave] ?? null;
};

$h_inicio = $obtener_cfg('khronos_inicio', $jornada_curso) ?? '07:00';
$h_duracion = (int)($obtener_cfg('khronos_duracion', $jornada_curso) ?? 55);
$h_max = (int)($obtener_cfg('khronos_max_horas', $jornada_curso) ?? 7);
$dias_lab = explode(',', $obtener_cfg('khronos_dias', $jornada_curso) ?? 'Lun,Mar,Mie,Jue,Vie');
$fatiga_max = (int)($obtener_cfg('khronos_max_diario_materia', $jornada_curso) ?? 2);

$desc1_h = (int)($obtener_cfg('khronos_descanso_h', $jornada_curso) ?? 0);
$desc1_m = (int)($obtener_cfg('khronos_descanso_m', $jornada_curso) ?? 0);
$desc2_h = (int)($obtener_cfg('khronos_descanso2_h', $jornada_curso) ?? 0);
$desc2_m = (int)($obtener_cfg('khronos_descanso2_m', $jornada_curso) ?? 0);

// Preparar consultas que se ejecutarán dentro de bucles (Estrategia Pro)
$stmt_plan = $db->prepare("SELECT intensidad_horaria FROM zulu_plan_maestro WHERE especialidad_id = ? AND (nivel_nombre = ? OR nivel_nombre = 'GLOBAL') LIMIT 1");
$stmt_asig = $db->prepare("SELECT COUNT(*) FROM khronos_horarios WHERE curso_id = ? AND especialidad_id = ?");
$stmt_hoy = $db->prepare("SELECT COUNT(*) FROM khronos_horarios WHERE curso_id = ? AND dia_semana = ? AND especialidad_id = ?");
$stmt_ocu = $db->prepare("SELECT COUNT(*) FROM khronos_horarios WHERE curso_id = ? AND dia_semana = ? AND hora_numero = ?");
$stmt_col = $db->prepare("SELECT COUNT(*) FROM khronos_horarios WHERE docente_id = ? AND dia_semana = ? AND hora_numero = ? AND curso_id != ?");
$stmt_pre = $db->prepare("SELECT especialidad_id FROM khronos_horarios WHERE curso_id = ? AND dia_semana = ? AND hora_numero = ?");

$materias_pendientes = [];
$sql_carga = "SELECT ca.especialidad_id, ca.docente_id FROM carga_academica ca WHERE ca.curso_id = ?";
$stmt_p = $db->prepare($sql_carga);
$stmt_p->execute([$curso_id]);

while($cp = $stmt_p->fetch(PDO::FETCH_ASSOC)) {
    // Buscar intensidad específica
    $stmt_plan->execute([$cp['especialidad_id'], $nivel_id]);
    $int = (int)($stmt_plan->fetchColumn() ?: 4);

    $stmt_asig->execute([$curso_id, $cp['especialidad_id']]);
    $asignadas = (int)$stmt_asig->fetchColumn();
    
    $cp['faltantes'] = $int - $asignadas;
    if ($cp['faltantes'] > 0) $materias_pendientes[] = $cp;
}

if (empty($materias_pendientes)) {
    echo json_encode(['status' => 'info', 'message' => 'No hay carga académica pendiente.']);
    exit();
}

// 3. MOTOR DE ASIGNACIÓN EQUILIBRADA (Shuffle-Mode)
$asignaciones = 0;
$intentos_max = 200; 

usort($materias_pendientes, function($a, $b) { return $b['faltantes'] - $a['faltantes']; });

foreach ($materias_pendientes as &$m) {
    $intentos = 0;
    while ($m['faltantes'] > 0 && $intentos < $intentos_max) {
        $intentos++;
        shuffle($dias_lab);
        $dia_asignado = false;

        foreach ($dias_lab as $dia) {
            if ($dia_asignado) break;

            // REGLA FATIGA
            $stmt_hoy->execute([$curso_id, $dia, $m['especialidad_id']]);
            $hoy = (int)$stmt_hoy->fetchColumn();
            if ($hoy >= $fatiga_max) continue;

            for ($i = 1; $i <= $h_max; $i++) {
                // ¿Hueco ocupado?
                $stmt_ocu->execute([$curso_id, $dia, $i]);
                if ((int)$stmt_ocu->fetchColumn() > 0) continue;

                // ¿Docente ocupado en otro curso?
                $stmt_col->execute([$m['docente_id'], $dia, $i, $curso_id]);
                if ((int)$stmt_col->fetchColumn() > 0) continue;

                // REGLA ÉLITE: Anti-Repetición Post-Recreo
                if ($i == $desc1_h + 1 || $i == $desc2_h + 1) {
                    $stmt_pre->execute([$curso_id, $dia, ($i-1)]);
                    if ((int)$stmt_pre->fetchColumn() == $m['especialidad_id']) continue;
                }

                $es_bloque_doble = false;
                if ($m['faltantes'] >= 2 && ($hoy + 2) <= $fatiga_max) {
                    $prox_i = $i + 1;
                    $puedo_doble = true;
                    if ($prox_i == $desc1_h + 1 || $prox_i == $desc2_h + 1) $puedo_doble = false;
                    if ($prox_i > $h_max) $puedo_doble = false;
                    
                    if ($puedo_doble) {
                        $stmt_ocu->execute([$curso_id, $dia, $prox_i]);
                        $stmt_col->execute([$m['docente_id'], $dia, $prox_i, $curso_id]);
                        if ((int)$stmt_ocu->fetchColumn() > 0 || (int)$stmt_col->fetchColumn() > 0) $puedo_doble = false;
                    }
                    if ($puedo_doble) $es_bloque_doble = true;
                }

                asignarHora($db, $m, $dia, $i, $cfg, $curso_id);
                $asignaciones++;
                $m['faltantes']--;
                $dia_asignado = true;

                if ($es_bloque_doble) {
                    asignarHora($db, $m, $dia, $i + 1, $cfg, $curso_id);
                    $asignaciones++;
                    $m['faltantes']--;
                }
                break; 
            }
        }
    }
}

function asignarHora(PDO $db, array $m, string $dia, int $hora, array $cfg, int $curso_id): void {
    $stmt_j = $db->prepare("SELECT jornada FROM cursos WHERE id = ?");
    $stmt_j->execute([$curso_id]);
    $jornada_curso = $stmt_j->fetchColumn() ?: 'Mañana';

    $obtener_cfg = function($clave, $jornada) use ($cfg) {
        $suffix = strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['', 'a', 'e', 'i', 'o', 'u'], $jornada));
        $clave_jornada = $clave . '_' . $suffix;
        if (isset($cfg[$clave_jornada]) && $cfg[$clave_jornada] !== '') {
            return $cfg[$clave_jornada];
        }
        return $cfg[$clave] ?? null;
    };

    $h_ini_base = $obtener_cfg('khronos_inicio', $jornada_curso) ?? '07:00';
    $recesos_raw = $obtener_cfg('khronos_recesos', $jornada_curso);
    $recesos_lista = [];
    if (!empty($recesos_raw)) {
        $decoded = json_decode((string)$recesos_raw, true);
        if (is_array($decoded)) {
            $recesos_lista = $decoded;
        }
    }
    if (empty($recesos_lista)) {
        $d1h = (int)($obtener_cfg('khronos_descanso_h', $jornada_curso) ?? 0);
        $d1m = (int)($obtener_cfg('khronos_descanso_m', $jornada_curso) ?? 0);
        if ($d1h > 0 && $d1m > 0) $recesos_lista[] = ['bloque' => $d1h, 'duracion' => $d1m];
        $d2h = (int)($obtener_cfg('khronos_descanso2_h', $jornada_curso) ?? 0);
        $d2m = (int)($obtener_cfg('khronos_descanso2_m', $jornada_curso) ?? 0);
        if ($d2h > 0 && $d2m > 0) $recesos_lista[] = ['bloque' => $d2h, 'duracion' => $d2m];
    }

    [$h, $min] = explode(':', $h_ini_base);
    $t_ini = '';
    $t_fin = '';
    for($j=1; $j<=$hora; $j++) {
        $t_ini = sprintf("%02d:%02d", $h, $min);
        $total = $min + $dur;
        $h += floor($total/60);
        $min = $total % 60;
        $t_fin = sprintf("%02d:%02d", $h, $min);

        foreach ($recesos_lista as $rec) {
            if ((int)($rec['bloque'] ?? 0) === $j) {
                $dur_rec = (int)($rec['duracion'] ?? 0);
                $total_d = $min + $dur_rec;
                $h += floor($total_d/60);
                $min = $total_d % 60;
            }
        }
    }

    $stmt = $db->prepare("INSERT INTO khronos_horarios (curso_id, docente_id, especialidad_id, dia_semana, hora_numero, hora_inicio, hora_fin) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $m['docente_id'], $m['especialidad_id'], $dia, $hora, $t_ini, $t_fin]);
}

if ($asignaciones > 0) {
    echo json_encode(['status' => 'success', 'message' => "Se han distribuido $asignaciones horas equitativamente."]);
} else {
    echo json_encode(['status' => 'info', 'message' => 'No se pudieron asignar más horas.']);
}
?>
