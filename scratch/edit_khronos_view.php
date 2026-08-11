<?php
$file = 'C:\\xampp\\htdocs\\sistema_escolar\\php\\vistas\\khronos.php';
$content = file_get_contents($file);

$target1 = '// 1. CARGAR CONFIGURACIÓN MAESTRA
$stmt_c = $db->prepare("SELECT * FROM ajustes_estetica WHERE clave LIKE \'khronos%\' OR clave = \'brand_color\'");
$stmt_c->execute();
$cfg = [];
while ($row = $stmt_c->fetch(PDO::FETCH_ASSOC)) { $cfg[$row[\'clave\']] = $row[\'valor\']; }

// 2. PARÁMETROS DE TIEMPO
$h_inicio = $cfg[\'khronos_inicio\'] ?? \'07:00\';
$h_duracion = (int)($cfg[\'khronos_duracion\'] ?? 55);
$h_max = (int)($cfg[\'khronos_max_horas\'] ?? 7);
$dias_lab = explode(\',\', $cfg[\'khronos_dias\'] ?? \'lun,mar,mie,jue,vie\');
$desc1_h = (int)($cfg[\'khronos_descanso_h\'] ?? 0);
$desc1_m = (int)($cfg[\'khronos_descanso_m\'] ?? 0);
$desc2_h = (int)($cfg[\'khronos_descanso2_h\'] ?? 0);
$desc2_m = (int)($cfg[\'khronos_descanso2_m\'] ?? 0);

// 3. SELECCIÓN DE CURSO (Contexto)
$curso_id = (int)($_GET[\'curso_id\'] ?? 0);
$stmt_cursos_res = $db->prepare("SELECT id, nombre_curso, nivel_id FROM cursos ORDER BY nombre_curso ASC");
$stmt_cursos_res->execute();
$cursos_res = $stmt_cursos_res;

// 4. CARGA ACADÉMICA DEL CURSO (Si hay seleccionado)
$carga_pendiente = [];
if ($curso_id > 0) {
    $stmt_sel = $db->prepare("SELECT nivel_id FROM cursos WHERE id = :cid");
    $stmt_sel->bindValue(\':cid\', $curso_id, PDO::PARAM_INT);
    $stmt_sel->execute();
    $curso_sel = $stmt_sel->fetch(PDO::FETCH_ASSOC);
    $nivel_id = $curso_sel[\'nivel_id\'] ?? 0;';

$replace1 = '// 3. SELECCIÓN DE CURSO (Contexto)
$curso_id = (int)($_GET[\'curso_id\'] ?? 0);

$jornada_curso = \'Mañana\';
$nivel_id = 0;
if ($curso_id > 0) {
    $stmt_sel = $db->prepare("SELECT nivel_id, jornada FROM cursos WHERE id = :cid");
    $stmt_sel->bindValue(\':cid\', $curso_id, PDO::PARAM_INT);
    $stmt_sel->execute();
    $curso_sel = $stmt_sel->fetch(PDO::FETCH_ASSOC);
    $nivel_id = (int)($curso_sel[\'nivel_id\'] ?? 0);
    $jornada_curso = $curso_sel[\'jornada\'] ?? \'Mañana\';
}

// 1. CARGAR CONFIGURACIÓN MAESTRA
$stmt_c = $db->prepare("SELECT * FROM ajustes_estetica WHERE clave LIKE \'khronos%\' OR clave = \'brand_color\'");
$stmt_c->execute();
$cfg = [];
while ($row = $stmt_c->fetch(PDO::FETCH_ASSOC)) { $cfg[$row[\'clave\']] = $row[\'valor\']; }

$obtener_cfg = function($clave, $jornada) use ($cfg) {
    $suffix = strtolower(str_replace([\' \', \'á\', \'é\', \'í\', \'ó\', \'ú\'], [\'\', \'a\', \'e\', \'i\', \'o\', \'u\'], $jornada));
    $clave_jornada = $clave . \'_\' . $suffix;
    if (isset($cfg[$clave_jornada]) && $cfg[$clave_jornada] !== \'\') {
        return $cfg[$clave_jornada];
    }
    return $cfg[$clave] ?? null;
};

// 2. PARÁMETROS DE TIEMPO RESUELTOS POR JORNADA
$h_inicio = $obtener_cfg(\'khronos_inicio\', $jornada_curso) ?? \'07:00\';
$h_duracion = (int)($obtener_cfg(\'khronos_duracion\', $jornada_curso) ?? 55);
$h_max = (int)($obtener_cfg(\'khronos_max_horas\', $jornada_curso) ?? 7);
$dias_lab = explode(\',\', $obtener_cfg(\'khronos_dias\', $jornada_curso) ?? \'lun,mar,mie,jue,vie\');
$desc1_h = (int)($obtener_cfg(\'khronos_descanso_h\', $jornada_curso) ?? 0);
$desc1_m = (int)($obtener_cfg(\'khronos_descanso_m\', $jornada_curso) ?? 0);
$desc2_h = (int)($obtener_cfg(\'khronos_descanso2_h\', $jornada_curso) ?? 0);
$desc2_m = (int)($obtener_cfg(\'khronos_descanso2_m\', $jornada_curso) ?? 0);

$stmt_cursos_res = $db->prepare("SELECT id, nombre_curso, nivel_id, jornada FROM cursos ORDER BY nombre_curso ASC");
$stmt_cursos_res->execute();
$cursos_res = $stmt_cursos_res;

// 4. CARGA ACADÉMICA DEL CURSO (Si hay seleccionado)
$carga_pendiente = [];
if ($curso_id > 0) {';

$target2 = '                    $stmt_cursos = $db->prepare("SELECT id, nombre_curso, nivel_id FROM cursos ORDER BY nombre_curso ASC");
                    $stmt_cursos->execute();
                    while($c = $stmt_cursos->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $c[\'id\']; ?>" <?php echo ($curso_id == $c[\'id\']) ? \'selected\' : \'\'; ?>>
                            <?php echo htmlspecialchars($c[\'nombre_curso\']); ?>
                        </option>';

$replace2 = '                    $stmt_cursos = $db->prepare("SELECT id, nombre_curso, nivel_id, jornada FROM cursos ORDER BY nombre_curso ASC");
                    $stmt_cursos->execute();
                    while($c = $stmt_cursos->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $c[\'id\']; ?>" <?php echo ($curso_id == $c[\'id\']) ? \'selected\' : \'\'; ?>>
                            <?php echo htmlspecialchars($c[\'nombre_curso\'] . \' (\' . ($c[\'jornada\'] ?? \'Mañana\') . \')\'); ?>
                        </option>';

// Reemplazar de forma normalizada para ignorar retornos de carro de Windows/Linux
$content = str_replace(str_replace("\r\n", "\n", $target1), str_replace("\r\n", "\n", $replace1), str_replace("\r\n", "\n", $content));
$content = str_replace(str_replace("\r\n", "\n", $target2), str_replace("\r\n", "\n", $replace2), str_replace("\r\n", "\n", $content));

file_put_contents($file, $content);
echo "Ediciones aplicadas en khronos.php\n";
