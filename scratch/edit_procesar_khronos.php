<?php
$file = 'C:\\xampp\\htdocs\\sistema_escolar\\php\\logica\\procesar_khronos.php';
$content = file_get_contents($file);

$target1 = 'function getTiempos(PDO $db, int $hora_num): array {
    $stmt_kh = $db->prepare("SELECT clave, valor FROM ajustes_estetica WHERE clave LIKE \'khronos%\'");
    $stmt_kh->execute();
    $stmt = $stmt_kh;
    $cfg = []; 
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) $cfg[$r[\'clave\']] = $r[\'valor\'];
    
    $h_ini = $cfg[\'khronos_inicio\'] ?? \'07:00\';
    $dur = (int)($cfg[\'khronos_duracion\'] ?? 55);
    $d1h = (int)($cfg[\'khronos_descanso_h\'] ?? 0);
    $d1m = (int)($cfg[\'khronos_descanso_m\'] ?? 0);
    $d2h = (int)($cfg[\'khronos_descanso2_h\'] ?? 0);
    $d2m = (int)($cfg[\'khronos_descanso2_m\'] ?? 0);';

$replace1 = 'function getTiempos(PDO $db, int $hora_num, int $curso_id = 0): array {
    $jornada = \'Mañana\';
    if ($curso_id > 0) {
        $stmt_j = $db->prepare("SELECT jornada FROM cursos WHERE id = ?");
        $stmt_j->execute([$curso_id]);
        $jornada = $stmt_j->fetchColumn() ?: \'Mañana\';
    }

    $stmt_kh = $db->prepare("SELECT clave, valor FROM ajustes_estetica WHERE clave LIKE \'khronos%\'");
    $stmt_kh->execute();
    $stmt = $stmt_kh;
    $cfg = []; 
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) $cfg[$r[\'clave\']] = $r[\'valor\'];

    $obtener_cfg = function($clave, $jornada) use ($cfg) {
        $suffix = strtolower(str_replace([\' \', \'á\', \'é\', \'í\', \'ó\', \'ú\'], [\'\', \'a\', \'e\', \'i\', \'o\', \'u\'], $jornada));
        $clave_jornada = $clave . \'_\' . $suffix;
        if (isset($cfg[$clave_jornada]) && $cfg[$clave_jornada] !== \'\') {
            return $cfg[$clave_jornada];
        }
        return $cfg[$clave] ?? null;
    };
    
    $h_ini = $obtener_cfg(\'khronos_inicio\', $jornada) ?? \'07:00\';
    $dur = (int)($obtener_cfg(\'khronos_duracion\', $jornada) ?? 55);
    $d1h = (int)($obtener_cfg(\'khronos_descanso_h\', $jornada) ?? 0);
    $d1m = (int)($obtener_cfg(\'khronos_descanso_m\', $jornada) ?? 0);
    $d2h = (int)($obtener_cfg(\'khronos_descanso2_h\', $jornada) ?? 0);
    $d2m = (int)($obtener_cfg(\'khronos_descanso2_m\', $jornada) ?? 0);';

$target2 = '        [$ini, $fin] = getTiempos($db, $hora);';
$replace2 = '        [$ini, $fin] = getTiempos($db, $hora, $curso_id);';

$target3 = '    case \'swap\':
        $id_a = (int)$_POST[\'id_a\']; $id_b = (int)$_POST[\'id_b\'];
        $dia_a = $_POST[\'dia_a\']; $hora_a = (int)$_POST[\'hora_a\'];
        $dia_b = $_POST[\'dia_b\']; $hora_b = (int)$_POST[\'hora_b\'];
        [$ini_a, $fin_a] = getTiempos($db, $hora_a);
        [$ini_b, $fin_b] = getTiempos($db, $hora_b);';

$replace3 = '    case \'swap\':
        $id_a = (int)$_POST[\'id_a\']; $id_b = (int)$_POST[\'id_b\'];
        $dia_a = $_POST[\'dia_a\']; $hora_a = (int)$_POST[\'hora_a\'];
        $dia_b = $_POST[\'dia_b\']; $hora_b = (int)$_POST[\'hora_b\'];
        
        $stmt_c_a = $db->prepare("SELECT curso_id FROM khronos_horarios WHERE id = ?");
        $stmt_c_a->execute([$id_a]);
        $curso_id_a = (int)$stmt_c_a->fetchColumn();

        [$ini_a, $fin_a] = getTiempos($db, $hora_a, $curso_id_a);
        [$ini_b, $fin_b] = getTiempos($db, $hora_b, $curso_id_a);';

$content = str_replace(str_replace("\r\n", "\n", $target1), str_replace("\r\n", "\n", $replace1), str_replace("\r\n", "\n", $content));
$content = str_replace(str_replace("\r\n", "\n", $target2), str_replace("\r\n", "\n", $replace2), str_replace("\r\n", "\n", $content));
$content = str_replace(str_replace("\r\n", "\n", $target3), str_replace("\r\n", "\n", $replace3), str_replace("\r\n", "\n", $content));

file_put_contents($file, $content);
echo "Ediciones aplicadas en procesar_khronos.php\n";
