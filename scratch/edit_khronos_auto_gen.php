<?php
$file = 'C:\\xampp\\htdocs\\sistema_escolar\\php\\logica\\khronos_auto_gen.php';
$content = file_get_contents($file);

$target1 = '// 1. CARGAR CONFIGURACIÓN GLOBAL
$stmt_c_kh = $db->prepare("SELECT * FROM ajustes_estetica WHERE clave LIKE \'khronos%\'");
$stmt_c_kh->execute();
$stmt_c = $stmt_c_kh;
$cfg = [];
while ($row = $stmt_c->fetch(PDO::FETCH_ASSOC)) {
    $cfg[$row[\'clave\']] = $row[\'valor\'];
}

$h_inicio = $cfg[\'khronos_inicio\'] ?? \'07:00\';
$h_duracion = (int)($cfg[\'khronos_duracion\'] ?? 55);
$h_max = (int)($cfg[\'khronos_max_horas\'] ?? 7);
$dias_lab = explode(\',\', $cfg[\'khronos_dias\'] ?? \'Lun,Mar,Mie,Jue,Vie\');
$fatiga_max = (int)($cfg[\'khronos_max_diario_materia\'] ?? 2);

$desc1_h = (int)($cfg[\'khronos_descanso_h\'] ?? 0);
$desc1_m = (int)($cfg[\'khronos_descanso_m\'] ?? 0);
$desc2_h = (int)($cfg[\'khronos_descanso2_h\'] ?? 0);
$desc2_m = (int)($cfg[\'khronos_descanso2_m\'] ?? 0);

// 2. OBTENER NIVEL DEL CURSO Y CARGA PENDIENTE
$stmt_nv = $db->prepare("SELECT nivel_id FROM cursos WHERE id = ?");
$stmt_nv->execute([$curso_id]);
$nivel_id = $stmt_nv->fetchColumn();';

$replace1 = '// 2. OBTENER NIVEL DEL CURSO Y JORNADA
$stmt_nv = $db->prepare("SELECT nivel_id, jornada FROM cursos WHERE id = ?");
$stmt_nv->execute([$curso_id]);
$curso_data = $stmt_nv->fetch(PDO::FETCH_ASSOC);
$nivel_id = (int)($curso_data[\'nivel_id\'] ?? 0);
$jornada_curso = $curso_data[\'jornada\'] ?? \'Mañana\';

// 1. CARGAR CONFIGURACIÓN GLOBAL
$stmt_c_kh = $db->prepare("SELECT * FROM ajustes_estetica WHERE clave LIKE \'khronos%\'");
$stmt_c_kh->execute();
$stmt_c = $stmt_c_kh;
$cfg = [];
while ($row = $stmt_c->fetch(PDO::FETCH_ASSOC)) {
    $cfg[$row[\'clave\']] = $row[\'valor\'];
}

$obtener_cfg = function($clave, $jornada) use ($cfg) {
    $suffix = strtolower(str_replace([\' \', \'á\', \'é\', \'í\', \'ó\', \'ú\'], [\'\', \'a\', \'e\', \'i\', \'o\', \'u\'], $jornada));
    $clave_jornada = $clave . \'_\' . $suffix;
    if (isset($cfg[$clave_jornada]) && $cfg[$clave_jornada] !== \'\') {
        return $cfg[$clave_jornada];
    }
    return $cfg[$clave] ?? null;
};

$h_inicio = $obtener_cfg(\'khronos_inicio\', $jornada_curso) ?? \'07:00\';
$h_duracion = (int)($obtener_cfg(\'khronos_duracion\', $jornada_curso) ?? 55);
$h_max = (int)($obtener_cfg(\'khronos_max_horas\', $jornada_curso) ?? 7);
$dias_lab = explode(\',\', $obtener_cfg(\'khronos_dias\', $jornada_curso) ?? \'Lun,Mar,Mie,Jue,Vie\');
$fatiga_max = (int)($obtener_cfg(\'khronos_max_diario_materia\', $jornada_curso) ?? 2);

$desc1_h = (int)($obtener_cfg(\'khronos_descanso_h\', $jornada_curso) ?? 0);
$desc1_m = (int)($obtener_cfg(\'khronos_descanso_m\', $jornada_curso) ?? 0);
$desc2_h = (int)($obtener_cfg(\'khronos_descanso2_h\', $jornada_curso) ?? 0);
$desc2_m = (int)($obtener_cfg(\'khronos_descanso2_m\', $jornada_curso) ?? 0);';

$target2 = 'function asignarHora(PDO $db, array $m, string $dia, int $hora, array $cfg, int $curso_id): void {
    $h_ini_base = $cfg[\'khronos_inicio\'] ?? \'07:00\';
    $dur = (int)($cfg[\'khronos_duracion\'] ?? 55);
    $d1h = (int)($cfg[\'khronos_descanso_h\'] ?? 0);
    $d1m = (int)($cfg[\'khronos_descanso_m\'] ?? 0);
    $d2h = (int)($cfg[\'khronos_descanso2_h\'] ?? 0);
    $d2m = (int)($cfg[\'khronos_descanso2_m\'] ?? 0);';

$replace2 = 'function asignarHora(PDO $db, array $m, string $dia, int $hora, array $cfg, int $curso_id): void {
    $stmt_j = $db->prepare("SELECT jornada FROM cursos WHERE id = ?");
    $stmt_j->execute([$curso_id]);
    $jornada_curso = $stmt_j->fetchColumn() ?: \'Mañana\';

    $obtener_cfg = function($clave, $jornada) use ($cfg) {
        $suffix = strtolower(str_replace([\' \', \'á\', \'é\', \'í\', \'ó\', \'ú\'], [\'\', \'a\', \'e\', \'i\', \'o\', \'u\'], $jornada));
        $clave_jornada = $clave . \'_\' . $suffix;
        if (isset($cfg[$clave_jornada]) && $cfg[$clave_jornada] !== \'\') {
            return $cfg[$clave_jornada];
        }
        return $cfg[$clave] ?? null;
    };

    $h_ini_base = $obtener_cfg(\'khronos_inicio\', $jornada_curso) ?? \'07:00\';
    $dur = (int)($obtener_cfg(\'khronos_duracion\', $jornada_curso) ?? 55);
    $d1h = (int)($obtener_cfg(\'khronos_descanso_h\', $jornada_curso) ?? 0);
    $d1m = (int)($obtener_cfg(\'khronos_descanso_m\', $jornada_curso) ?? 0);
    $d2h = (int)($obtener_cfg(\'khronos_descanso2_h\', $jornada_curso) ?? 0);
    $d2m = (int)($obtener_cfg(\'khronos_descanso2_m\', $jornada_curso) ?? 0);';

$content = str_replace(str_replace("\r\n", "\n", $target1), str_replace("\r\n", "\n", $replace1), str_replace("\r\n", "\n", $content));
$content = str_replace(str_replace("\r\n", "\n", $target2), str_replace("\r\n", "\n", $replace2), str_replace("\r\n", "\n", $content));

file_put_contents($file, $content);
echo "Ediciones aplicadas en khronos_auto_gen.php\n";
