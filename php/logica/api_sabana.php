<?php
declare(strict_types=1);

/**
 * 🏛️ MOTOR API DE SÁBANA DE CALIFICACIONES (PERSEUS ENGINE)
 * Consulta y Consolidación de Notas Académicas Élite - Vitrina 06 Standard
 * 
 * @author Ingeniería Élite v9.5
 * @version 3.0 (Strict Read-Only Architecture)
 */

ob_start();
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json; charset=utf-8');

require_once '../db.php';
require_once '../auth.php';
require_once '../security.php';
guardia_sesion();
    session_write_close();

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $mi_id = (int)($_SESSION['usuario_id'] ?? 0);
    if (!$mi_id) {
        throw new Exception('Sesión inválida o expirada. Acceso denegado.');
    }

    // Blindaje de Seguridad: Permiso obligatorio de evaluación
    if (!tiene_permiso('evaluacion')) {
        throw new Exception('Acceso denegado: No posee privilegios de evaluación.');
    }

    $accion = $_POST['accion'] ?? $_GET['accion'] ?? 'invalid';

    switch ($accion) {
        case 'cargar_consolidado_curso':
            $curso_id = (int)($_GET['curso_id'] ?? 0);
            $periodo_id = (int)($_GET['periodo_id'] ?? 1); // 1, 2, 3, 4

            if (!$curso_id) {
                throw new Exception('Parámetro incompleto (curso_id requerido).');
            }

            // Blindaje Transversal: Validar Soberanía de Carga Académica o Tutoría para No-Admins
            $es_admin = tienen_rol(['administrador', 'coordinador', 'director']);
            $es_tutor = false;
            if (!$es_admin) {
                // 1. ¿Es el Profesor Líder (Tutor) del curso?
                $stmt_tutor = $db->prepare("SELECT 1 FROM cursos WHERE id = ? AND tutor_id = ? LIMIT 1");
                $stmt_tutor->execute([$curso_id, $mi_id]);
                $es_tutor = (bool)$stmt_tutor->fetchColumn();

                if (!$es_tutor) {
                    // 2. ¿Es un Profesor Catedrático con asignación en el curso?
                    $stmt_verificar = $db->prepare("SELECT 1 FROM carga_academica WHERE curso_id = ? AND docente_id = ? LIMIT 1");
                    $stmt_verificar->execute([$curso_id, $mi_id]);
                    if (!$stmt_verificar->fetchColumn()) {
                        throw new Exception('Acceso denegado: Violación de privacidad transversal. No es el director del grupo ni imparte materias en este curso.');
                    }
                }
            }

            // 1. Obtener la escala de calificación institucional
            $stmt_esc = $db->prepare("SELECT * FROM eval_config_escala WHERE activo = :activo LIMIT 1");
            $stmt_esc->execute([':activo' => 1]);
            $config_escala = $stmt_esc->fetch(PDO::FETCH_ASSOC) ?: [
                'nota_minima' => 1.00,
                'nota_maxima' => 5.00,
                'nota_aprobacion' => 3.00,
                'rango_superior_min' => 4.60,
                'rango_alto_min' => 4.00,
                'rango_basico_min' => 3.00
            ];

            // 2. Establecer rangos de fechas dinámicos según el periodo seleccionado (Año Escolar Actual)
            $anio = date('Y');
            $fechas_periodo = [
                1 => ['inicio' => "$anio-01-01 00:00:00", 'fin' => "$anio-03-31 23:59:59"],
                2 => ['inicio' => "$anio-04-01 00:00:00", 'fin' => "$anio-06-30 23:59:59"],
                3 => ['inicio' => "$anio-07-01 00:00:00", 'fin' => "$anio-09-30 23:59:59"],
                4 => ['inicio' => "$anio-10-01 00:00:00", 'fin' => "$anio-12-31 23:59:59"]
            ];

            $rango = $fechas_periodo[$periodo_id] ?? $fechas_periodo[1];
            $f_inicio = $rango['inicio'];
            $f_fin = $rango['fin'];

            // 3. Obtener materias asignadas al curso via carga académica
            // Consultar el estado de privacidad transversal de la base de datos
            $stmt_priv = $db->prepare("SELECT valor FROM configuracion_global WHERE clave = 'privacidad_catedratico_sabana' LIMIT 1");
            $stmt_priv->execute();
            $modo_privacidad = $stmt_priv->fetchColumn() ?: 'estricto';

            if ($modo_privacidad === 'estricto' && !$es_admin && !$es_tutor) {
                // Filtrar materias estrictamente a las que dicta el docente en ese curso
                $stmt_mat = $db->prepare("
                    SELECT DISTINCT e.id, e.nombre_especialidad 
                    FROM especialidades e
                    JOIN carga_academica ca ON e.id = ca.especialidad_id
                    WHERE ca.curso_id = ? AND ca.docente_id = ?
                    ORDER BY e.nombre_especialidad ASC
                ");
                $stmt_mat->execute([$curso_id, $mi_id]);
            } else {
                // Carga normal para directivos/coordinadores o tutores
                $stmt_mat = $db->prepare("
                    SELECT DISTINCT e.id, e.nombre_especialidad 
                    FROM especialidades e
                    JOIN carga_academica ca ON e.id = ca.especialidad_id
                    WHERE ca.curso_id = ?
                    ORDER BY e.nombre_especialidad ASC
                ");
                $stmt_mat->execute([$curso_id]);
            }
            $materias = $stmt_mat->fetchAll(PDO::FETCH_ASSOC);

            // 🏛️ PLAN DE EVALUACIONES MÍNIMAS (AUDITORÍA INTEGRADORA)
            // 3.1 Obtener mínimos requeridos por dimensión
            $stmt_dims = $db->prepare("SELECT id, nombre, min_evaluaciones FROM ares_clases_nota WHERE estado = :estado");
            $stmt_dims->execute([':estado' => 1]);
            $min_evals_dimensiones = [];
            while ($row_d = $stmt_dims->fetch(PDO::FETCH_ASSOC)) {
                $min_evals_dimensiones[(int)$row_d['id']] = (int)$row_d['min_evaluaciones'];
            }

            // Preparar consultas para contar actividades del periodo
            $stmt_count_act = $db->prepare("
                SELECT a.clase_nota_id, COUNT(*) as cantidad 
                FROM ares_actividades a
                LEFT JOIN aula_recursos r ON r.actividad_vinculada_id = a.id
                WHERE a.curso_id = ? AND a.especialidad_id = ? 
                  AND a.fecha_registro BETWEEN ? AND ?
                  AND (r.id IS NULL OR (r.visibilidad = 1 AND (r.fecha_inicio IS NULL OR r.fecha_inicio = '' OR NOW() >= r.fecha_inicio)))
                  AND a.ambito = 'estandar'
                GROUP BY a.clase_nota_id
            ");
            
            $stmt_count_ex = $db->prepare("
                SELECT COUNT(DISTINCT asig.id) as cantidad
                FROM eval_asignaciones asig
                JOIN eval_pruebas p ON asig.prueba_id = p.id
                WHERE asig.curso_id = ? AND p.materia_id = ? 
                  AND asig.fecha_inicio BETWEEN ? AND ?
            ");

            foreach ($materias as &$mat) {
                $mat_id = (int)$mat['id'];
                
                // Contar actividades de clase
                $stmt_count_act->execute([$curso_id, $mat_id, $f_inicio, $f_fin]);
                $act_counts = $stmt_count_act->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
                
                // Contar exámenes (dimensión Saber = 1)
                $stmt_count_ex->execute([$curso_id, $mat_id, $f_inicio, $f_fin]);
                $ex_count = (int)$stmt_count_ex->fetchColumn();

                $conteo_dimensiones = [];
                $alertas = [];
                $cumple_plan = true;

                foreach ($min_evals_dimensiones as $dim_id => $min_requerido) {
                    $clase_act_count = $act_counts[$dim_id] ?? 0;
                    if ($dim_id === 1) {
                        $clase_act_count += $ex_count; // Sumar exámenes al Saber
                    }
                    $conteo_dimensiones[$dim_id] = $clase_act_count;

                    if ($clase_act_count < $min_requerido) {
                        $cumple_plan = false;
                        $dim_nombre = ($dim_id === 1 ? 'Saber' : ($dim_id === 2 ? 'Hacer' : 'Ser'));
                        $alertas[] = "{$dim_nombre}: requiere mín. {$min_requerido} (subidas: {$clase_act_count})";
                    }
                }

                $mat['conteo_dimensiones'] = $conteo_dimensiones;
                $mat['minimo_requerido'] = $min_evals_dimensiones;
                $mat['cumple_plan_evaluacion'] = $cumple_plan;
                $mat['alertas_plan'] = $alertas;
            }
            unset($mat);

            // 4. Obtener estudiantes del curso
            $stmt_est = $db->prepare("
                SELECT id, nombre, apellido 
                FROM estudiantes 
                WHERE curso_id = ? 
                ORDER BY apellido ASC, nombre ASC
            ");
            $stmt_est->execute([$curso_id]);
            $estudiantes = $stmt_est->fetchAll(PDO::FETCH_ASSOC);

            // 5. Cargar inasistencias por estudiante en este periodo
            $stmt_fallas = $db->prepare("
                SELECT estudiante_id, COUNT(*) as fallas 
                FROM asistencias 
                WHERE curso_id = ? AND estado = 'F' 
                  AND fecha BETWEEN ? AND ?
                GROUP BY estudiante_id
            ");
            $stmt_fallas->execute([$curso_id, substr($f_inicio, 0, 10), substr($f_fin, 0, 10)]);
            $fallas_map = $stmt_fallas->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

            // 6. Obtener pesos de las dimensiones evaluativas dinámicamente desde la base de datos
            $pesos_dimensiones = [];
            $stmt_pesos = $db->prepare("SELECT id, peso_global FROM ares_clases_nota WHERE estado = :estado");
            $stmt_pesos->execute([':estado' => 1]);
            while ($row_p = $stmt_pesos->fetch(PDO::FETCH_ASSOC)) {
                $pesos_dimensiones[(int)$row_p['id']] = (float)$row_p['peso_global'] / 100.0;
            }
            if (empty($pesos_dimensiones)) {
                $pesos_dimensiones = [
                    1 => 0.40, // Saber
                    2 => 0.40, // Hacer
                    3 => 0.20  // Ser
                ];
            }

            // 7. Cargar todas las calificaciones diarias de clase para este curso, materias y rango de periodo
            $stmt_calif_act = $db->prepare("
                SELECT cd.estudiante_id, a.especialidad_id, a.clase_nota_id, cd.calificacion, cd.nota_recuperacion
                FROM ares_calificaciones_desglose cd
                JOIN ares_actividades a ON cd.actividad_id = a.id
                LEFT JOIN aula_recursos r ON r.actividad_vinculada_id = a.id
                WHERE a.curso_id = ? 
                  AND a.fecha_registro BETWEEN ? AND ?
                  AND (r.id IS NULL OR (r.visibilidad = 1 AND (r.fecha_inicio IS NULL OR r.fecha_inicio = '' OR NOW() >= r.fecha_inicio)))
                  AND a.ambito = 'estandar'
            ");
            $stmt_calif_act->execute([$curso_id, $f_inicio, $f_fin]);
            $calif_act = $stmt_calif_act->fetchAll(PDO::FETCH_ASSOC);

            // 8. Cargar todas las calificaciones de exámenes en línea para este curso y periodo
            $stmt_calif_ex = $db->prepare("
                SELECT r.estudiante_id, p.materia_id as especialidad_id, 1 as clase_nota_id,
                       IFNULL(r.calificacion_manual, r.calificacion_automatica) as calificacion,
                       r.calificacion_recuperacion
                FROM eval_respuestas r
                JOIN eval_asignaciones asig ON r.asignacion_id = asig.id
                JOIN eval_pruebas p ON asig.prueba_id = p.id
                WHERE asig.curso_id = ? 
                  AND asig.fecha_inicio BETWEEN ? AND ?
            ");
            $stmt_calif_ex->execute([$curso_id, $f_inicio, $f_fin]);
            $calif_ex = $stmt_calif_ex->fetchAll(PDO::FETCH_ASSOC);

            // 9. Consolidar calificaciones en memoria en una matriz optimizada O(N) aplicando políticas
            // Estructura: $notas_consolidado[estudiante_id][materia_id][dimension_id][] = calificacion
            $notas_consolidado = [];
            
            $politica = CalculadoraNotas::obtenerPoliticaRecuperacion($db);
            $escala = CalculadoraNotas::obtenerEscala($db);
            $nota_aprobacion = $escala['nota_aprobacion'];

            foreach ($calif_act as $c) {
                $est_id = (int)$c['estudiante_id'];
                $mat_id = (int)$c['especialidad_id'];
                $dim_id = (int)$c['clase_nota_id'];
                $val = (float)$c['calificacion'];
                $recup = $c['nota_recuperacion'] !== null ? (float)$c['nota_recuperacion'] : null;

                // Aplicar política de recuperación
                $val_final = CalculadoraNotas::aplicarPoliticaRecuperacion($val, $recup, $politica, $nota_aprobacion);
                $notas_consolidado[$est_id][$mat_id][$dim_id][] = $val_final;
            }

            foreach ($calif_ex as $c) {
                $est_id = (int)$c['estudiante_id'];
                $mat_id = (int)$c['especialidad_id'];
                $dim_id = 1; // Exámenes siempre pertenecen a Saber (1)
                $val = (float)$c['calificacion'];
                $recup = $c['calificacion_recuperacion'] !== null ? (float)$c['calificacion_recuperacion'] : null;

                // Aplicar política de recuperación
                $val_final = CalculadoraNotas::aplicarPoliticaRecuperacion($val, $recup, $politica, $nota_aprobacion);
                $notas_consolidado[$est_id][$mat_id][$dim_id][] = $val_final;
            }

            // 10. Calcular la Nota Definitiva por Materia para cada Estudiante
            $matriz_estudiantes = [];
            foreach ($estudiantes as $est) {
                $est_id = (int)$est['id'];
                $notas_finales = [];

                foreach ($materias as $mat) {
                    $mat_id = (int)$mat['id'];
                    
                    // Si el estudiante tiene registros en esta materia
                    $materias_notas = $notas_consolidado[$est_id][$mat_id] ?? [];
                    
                    if (empty($materias_notas)) {
                        $notas_finales[$mat_id] = null; // Sin calificar
                        continue;
                    }

                    // Calcular el promedio por dimensión activa
                    $promedios_dim = [];
                    $suma_pesos_activos = 0.0;

                    foreach ($pesos_dimensiones as $dim_id => $peso) {
                        $dim_notas = $materias_notas[$dim_id] ?? [];
                        if (!empty($dim_notas)) {
                            $promedios_dim[$dim_id] = array_sum($dim_notas) / count($dim_notas);
                            $suma_pesos_activos += $peso;
                        }
                    }

                    if (empty($promedios_dim)) {
                        $notas_finales[$mat_id] = null;
                        continue;
                    }

                    // Calcular promedio ponderado con redistribución de dimensiones vacías
                    $nota_definitiva = 0.0;
                    foreach ($promedios_dim as $dim_id => $prom) {
                        $peso_original = $pesos_dimensiones[$dim_id];
                        $peso_ajustado = $peso_original / $suma_pesos_activos;
                        $nota_definitiva += $prom * $peso_ajustado;
                    }

                    // Redondear a dos decimales de precisión
                    $notas_finales[$mat_id] = round($nota_definitiva, 2);
                }

                $matriz_estudiantes[] = [
                    'id' => $est_id,
                    'nombre' => $est['nombre'],
                    'apellido' => $est['apellido'],
                    'fallas' => (int)($fallas_map[$est_id] ?? 0),
                    'calificaciones' => $notas_finales
                ];
            }

            // 🏛️ CAPA DE PRIVACIDAD TRANSVERSAL (SILO DE DATOS)
            // Si el usuario es un docente (no es administrador, coordinador o tutor del curso)
            // y la configuración global está en modo 'estricto', filtramos las calificaciones
            // a las que tiene acceso real en su carga académica.
            if (!$es_admin && !$es_tutor && $modo_privacidad === 'estricto') {
                $materias_permitidas = array_column($materias, 'id');
                $materias_permitidas = array_map('intval', $materias_permitidas);

                foreach ($matriz_estudiantes as &$estudiante) {
                    $estudiante['calificaciones'] = array_filter($estudiante['calificaciones'], function($materia_id) use ($materias_permitidas) {
                        return in_array((int)$materia_id, $materias_permitidas, true);
                    }, ARRAY_FILTER_USE_KEY);
                }
                unset($estudiante); // Romper referencia para evitar colisiones
            }

            $payload = [
                'escala' => $config_escala,
                'materias' => $materias,
                'estudiantes' => $matriz_estudiantes
            ];

            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $payload]);
            break;

        case 'cargar_analisis_colegio':
            $periodo_id = (int)($_GET['periodo_id'] ?? 1);

            // 1. Obtener la escala de calificación institucional
            $stmt_esc = $db->prepare("SELECT * FROM eval_config_escala WHERE activo = :activo LIMIT 1");
            $stmt_esc->execute([':activo' => 1]);
            $config_escala = $stmt_esc->fetch(PDO::FETCH_ASSOC) ?: [
                'nota_minima' => 1.00,
                'nota_maxima' => 5.00,
                'nota_aprobacion' => 3.00,
                'rango_superior_min' => 4.60,
                'rango_alto_min' => 4.00,
                'rango_basico_min' => 3.00
            ];
            $nota_aprobacion = (float)$config_escala['nota_aprobacion'];

            // 2. Fechas de Periodo
            $anio = date('Y');
            $fechas_periodo = [
                1 => ['inicio' => "$anio-01-01 00:00:00", 'fin' => "$anio-03-31 23:59:59"],
                2 => ['inicio' => "$anio-04-01 00:00:00", 'fin' => "$anio-06-30 23:59:59"],
                3 => ['inicio' => "$anio-07-01 00:00:00", 'fin' => "$anio-09-30 23:59:59"],
                4 => ['inicio' => "$anio-10-01 00:00:00", 'fin' => "$anio-12-31 23:59:59"]
            ];
            $rango = $fechas_periodo[$periodo_id] ?? $fechas_periodo[1];
            $f_inicio = $rango['inicio'];
            $f_fin = $rango['fin'];

            // 3. Cursos
            $stmt_c = $db->prepare("SELECT id, nombre_curso FROM cursos ORDER BY nombre_curso");
            $stmt_c->execute();
            $cursos_all = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
            
            // Extraer niveles únicos para el filtro
            $niveles = [];
            $cursos_map = [];
            foreach ($cursos_all as $c) {
                $c_id = (int)$c['id'];
                $nombre = $c['nombre_curso'];
                $cursos_map[$c_id] = $nombre;

                if (preg_match('/^([0-9]+[°º]?)/u', $nombre, $matches)) {
                    $lvl = $matches[1];
                } else {
                    $lvl = explode(' ', $nombre)[0];
                }
                if (!in_array($lvl, $niveles)) {
                    $niveles[] = $lvl;
                }
            }

            // 4. Pesos de las dimensiones
            $pesos_dimensiones = [];
            $stmt_pesos = $db->prepare("SELECT id, peso_global FROM ares_clases_nota WHERE estado = :estado");
            $stmt_pesos->execute([':estado' => 1]);
            while ($row_p = $stmt_pesos->fetch(PDO::FETCH_ASSOC)) {
                $pesos_dimensiones[(int)$row_p['id']] = (float)$row_p['peso_global'] / 100.0;
            }
            if (empty($pesos_dimensiones)) {
                $pesos_dimensiones = [1 => 0.40, 2 => 0.40, 3 => 0.20];
            }
            $politica = CalculadoraNotas::obtenerPoliticaRecuperacion($db);

            // 🏛️ QUERY AGREGADA OPTIMIZADA: Obtener promedios de actividades agrupados por estudiante y materia para evitar OOM
            $stmt_agg_act = $db->prepare("
                SELECT 
                    cd.estudiante_id, 
                    est.curso_id,
                    a.especialidad_id, 
                    a.clase_nota_id, 
                    AVG(
                        CASE 
                            WHEN ? = 'promedio' AND cd.nota_recuperacion IS NOT NULL AND cd.nota_recuperacion > cd.calificacion 
                                THEN (cd.calificacion + cd.nota_recuperacion) / 2.0
                            WHEN ? = 'tope_aprobacion' AND cd.nota_recuperacion IS NOT NULL AND cd.nota_recuperacion > cd.calificacion 
                                THEN GREATEST(cd.calificacion, LEAST(cd.nota_recuperacion, ?))
                            WHEN ? = 'reemplazo' AND cd.nota_recuperacion IS NOT NULL AND cd.nota_recuperacion > cd.calificacion 
                                THEN cd.nota_recuperacion
                            ELSE cd.calificacion
                        END
                    ) as promedio_dim
                FROM ares_calificaciones_desglose cd
                JOIN ares_actividades a ON cd.actividad_id = a.id
                JOIN estudiantes est ON cd.estudiante_id = est.id
                LEFT JOIN aula_recursos r ON r.actividad_vinculada_id = a.id
                WHERE a.fecha_registro BETWEEN ? AND ?
                  AND (r.id IS NULL OR (r.visibilidad = 1 AND (r.fecha_inicio IS NULL OR r.fecha_inicio = '' OR NOW() >= r.fecha_inicio)))
                  AND a.ambito = 'estandar'
                GROUP BY cd.estudiante_id, a.especialidad_id, a.clase_nota_id
            ");
            $stmt_agg_act->execute([$politica, $politica, $nota_aprobacion, $politica, $f_inicio, $f_fin]);
            $raw_acts = $stmt_agg_act->fetchAll(PDO::FETCH_ASSOC);

            // 🏛️ QUERY AGREGADA OPTIMIZADA: Obtener promedios de exámenes agrupados por estudiante y materia
            $stmt_agg_ex = $db->prepare("
                SELECT 
                    res.estudiante_id,
                    est.curso_id,
                    p.materia_id as especialidad_id,
                    1 as clase_nota_id,
                    AVG(
                        CASE 
                            WHEN ? = 'promedio' AND res.calificacion_recuperacion IS NOT NULL AND res.calificacion_recuperacion > IFNULL(res.calificacion_manual, res.calificacion_automatica) 
                                THEN (IFNULL(res.calificacion_manual, res.calificacion_automatica) + res.calificacion_recuperacion) / 2.0
                            WHEN ? = 'tope_aprobacion' AND res.calificacion_recuperacion IS NOT NULL AND res.calificacion_recuperacion > IFNULL(res.calificacion_manual, res.calificacion_automatica) 
                                THEN GREATEST(IFNULL(res.calificacion_manual, res.calificacion_automatica), LEAST(res.calificacion_recuperacion, ?))
                            WHEN ? = 'reemplazo' AND res.calificacion_recuperacion IS NOT NULL AND res.calificacion_recuperacion > IFNULL(res.calificacion_manual, res.calificacion_automatica) 
                                THEN res.calificacion_recuperacion
                            ELSE IFNULL(res.calificacion_manual, res.calificacion_automatica)
                        END
                    ) as promedio_dim
                FROM eval_respuestas res
                JOIN eval_asignaciones asig ON res.asignacion_id = asig.id
                JOIN eval_pruebas p ON asig.prueba_id = p.id
                JOIN estudiantes est ON res.estudiante_id = est.id
                WHERE asig.fecha_inicio BETWEEN ? AND ?
                GROUP BY res.estudiante_id, p.materia_id
            ");
            $stmt_agg_ex->execute([$politica, $politica, $nota_aprobacion, $politica, $f_inicio, $f_fin]);
            $raw_exs = $stmt_agg_ex->fetchAll(PDO::FETCH_ASSOC);

            // Consolidar promedios en memoria agrupados
            $notas_consolidado = [];
            $estudiante_cursos = [];

            foreach ($raw_acts as $row) {
                $est_id = (int)$row['estudiante_id'];
                $mat_id = (int)$row['especialidad_id'];
                $dim_id = (int)$row['clase_nota_id'];
                $promedio_dim = (float)$row['promedio_dim'];
                
                $notas_consolidado[$est_id][$mat_id][$dim_id] = $promedio_dim;
                $estudiante_cursos[$est_id] = (int)$row['curso_id'];
            }

            foreach ($raw_exs as $row) {
                $est_id = (int)$row['estudiante_id'];
                $mat_id = (int)$row['especialidad_id'];
                $dim_id = 1;
                $promedio_dim = (float)$row['promedio_dim'];

                // Si ya había promedio de actividades en la dimensión saber, promediar ambos
                if (isset($notas_consolidado[$est_id][$mat_id][$dim_id])) {
                    $notas_consolidado[$est_id][$mat_id][$dim_id] = ($notas_consolidado[$est_id][$mat_id][$dim_id] + $promedio_dim) / 2.0;
                } else {
                    $notas_consolidado[$est_id][$mat_id][$dim_id] = $promedio_dim;
                }
                $estudiante_cursos[$est_id] = (int)$row['curso_id'];
            }

            // Calcular definitivas finales a nivel de estudiante/materia
            $estudiante_promedios = [];
            $estudiante_perdidas = [];
            $materia_notas_global = [];

            foreach ($notas_consolidado as $est_id => $materias_data) {
                foreach ($materias_data as $mat_id => $dimensiones_notas) {
                    $nota_definitiva = 0.0;
                    $suma_pesos_activos = 0.0;
                    
                    foreach ($pesos_dimensiones as $dim_id => $peso) {
                        if (isset($dimensiones_notas[$dim_id])) {
                            $prom = $dimensiones_notas[$dim_id];
                            $nota_definitiva += $prom * $peso;
                            $suma_pesos_activos += $peso;
                        }
                    }

                    if ($suma_pesos_activos > 0) {
                        $nota_definitiva = round($nota_definitiva / $suma_pesos_activos, 2);
                        $estudiante_promedios[$est_id][] = $nota_definitiva;
                        $materia_notas_global[$mat_id][] = $nota_definitiva;

                        if ($nota_definitiva < $nota_aprobacion) {
                            $estudiante_perdidas[$est_id] = ($estudiante_perdidas[$est_id] ?? 0) + 1;
                        }
                    }
                }
            }

            // Agrupar KPIs de Cursos
            $curso_promedios_estudiantes = [];
            $curso_estudiantes_riesgo = [];
            $curso_total_perdidas = [];

            foreach ($estudiante_promedios as $est_id => $proms) {
                $c_id = $estudiante_cursos[$est_id];
                $student_avg = round(array_sum($proms) / count($proms), 2);
                $curso_promedios_estudiantes[$c_id][] = $student_avg;
                
                $perdidas = $estudiante_perdidas[$est_id] ?? 0;
                if ($student_avg < $nota_aprobacion || $perdidas >= 2) {
                    $curso_estudiantes_riesgo[$c_id] = ($curso_estudiantes_riesgo[$c_id] ?? 0) + 1;
                }
                $curso_total_perdidas[$c_id] = ($curso_total_perdidas[$c_id] ?? 0) + $perdidas;
            }

            $cursos_stats = [];
            foreach ($cursos_map as $c_id => $c_nombre) {
                $proms = $curso_promedios_estudiantes[$c_id] ?? [];
                $total_est = count($proms);
                if ($total_est > 0) {
                    $prom_curso = round(array_sum($proms) / $total_est, 2);
                    $riesgos = $curso_estudiantes_riesgo[$c_id] ?? 0;
                    $riesgo_pct = round(($riesgos / $total_est) * 100, 1);
                    $perdidas_total = $curso_total_perdidas[$c_id] ?? 0;

                    $cursos_stats[] = [
                        'id' => $c_id,
                        'nombre' => $c_nombre,
                        'promedio' => $prom_curso,
                        'total_estudiantes' => $total_est,
                        'estudiantes_riesgo' => $riesgos,
                        'riesgo_porcentaje' => $riesgo_pct,
                        'total_perdidas' => $perdidas_total
                    ];
                }
            }

            // Podio de Excelencia (Top 3)
            $podio = $cursos_stats;
            usort($podio, function($a, $b) {
                return $b['promedio'] <=> $a['promedio'];
            });
            $podio = array_slice($podio, 0, 3);

            // Semáforo de Cursos Críticos
            $semaforo = $cursos_stats;
            usort($semaforo, function($a, $b) {
                if ($b['riesgo_porcentaje'] === $a['riesgo_porcentaje']) {
                    return $b['total_perdidas'] <=> $a['total_perdidas'];
                }
                return $b['riesgo_porcentaje'] <=> $a['riesgo_porcentaje'];
            });

            // KPIs globales
            $todos_proms = [];
            $total_riesgo_global = 0;
            $total_estudiantes_con_notas = 0;
            foreach ($estudiante_promedios as $est_id => $proms) {
                $student_avg = array_sum($proms) / count($proms);
                $todos_proms[] = $student_avg;
                $perdidas = $estudiante_perdidas[$est_id] ?? 0;
                if ($student_avg < $nota_aprobacion || $perdidas >= 2) {
                    $total_riesgo_global++;
                }
                $total_estudiantes_con_notas++;
            }

            $promedio_colegio = !empty($todos_proms) ? round(array_sum($todos_proms) / count($todos_proms), 2) : 0.0;
            $total_aprobados_global = $total_estudiantes_con_notas - $total_riesgo_global;
            $aprobacion_pct = $total_estudiantes_con_notas > 0 ? round(($total_aprobados_global / $total_estudiantes_con_notas) * 100, 1) : 0.0;

            // Especialidades críticas
            $especialidades_stats = [];
            if (!empty($materia_notas_global)) {
                $stmt_mat_all = $db->prepare("SELECT id, nombre_especialidad FROM especialidades");
                $stmt_mat_all->execute();
                $mat_nombres = $stmt_mat_all->fetchAll(PDO::FETCH_KEY_PAIR);

                foreach ($materia_notas_global as $mat_id => $notas) {
                    $prom = round(array_sum($notas) / count($notas), 2);
                    $especialidades_stats[] = [
                        'id' => $mat_id,
                        'nombre' => $mat_nombres[$mat_id] ?? 'Especialidad ' . $mat_id,
                        'promedio' => $prom
                    ];
                }
                usort($especialidades_stats, function($a, $b) {
                    return $a['promedio'] <=> $b['promedio'];
                });
            }
            $criticas = array_slice($especialidades_stats, 0, 3);

            $payload = [
                'escala' => $config_escala,
                'kpis' => [
                    'promedio_colegio' => $promedio_colegio,
                    'aprobacion_porcentaje' => $aprobacion_pct,
                    'estudiantes_riesgo' => $total_riesgo_global,
                    'total_evaluados' => $total_estudiantes_con_notas
                ],
                'podio' => $podio,
                'semaforo' => $semaforo,
                'criticas' => $criticas,
                'niveles' => $niveles
            ];

            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $payload]);
            break;

        case 'cargar_analisis_nivel':
            $nivel_prefix = $_GET['nivel_prefix'] ?? '';
            $periodo_id = (int)($_GET['periodo_id'] ?? 1);

            if (empty($nivel_prefix)) {
                throw new Exception('Parámetro incompleto (nivel_prefix requerido).');
            }

            // 1. Obtener escala
            $stmt_esc = $db->prepare("SELECT * FROM eval_config_escala WHERE activo = :activo LIMIT 1");
            $stmt_esc->execute([':activo' => 1]);
            $config_escala = $stmt_esc->fetch(PDO::FETCH_ASSOC) ?: [
                'nota_minima' => 1.00,
                'nota_maxima' => 5.00,
                'nota_aprobacion' => 3.00,
                'rango_superior_min' => 4.60,
                'rango_alto_min' => 4.00,
                'rango_basico_min' => 3.00
            ];
            $nota_aprobacion = (float)$config_escala['nota_aprobacion'];

            // 2. Fechas
            $anio = date('Y');
            $fechas_periodo = [
                1 => ['inicio' => "$anio-01-01 00:00:00", 'fin' => "$anio-03-31 23:59:59"],
                2 => ['inicio' => "$anio-04-01 00:00:00", 'fin' => "$anio-06-30 23:59:59"],
                3 => ['inicio' => "$anio-07-01 00:00:00", 'fin' => "$anio-09-30 23:59:59"],
                4 => ['inicio' => "$anio-10-01 00:00:00", 'fin' => "$anio-12-31 23:59:59"]
            ];
            $rango = $fechas_periodo[$periodo_id] ?? $fechas_periodo[1];
            $f_inicio = $rango['inicio'];
            $f_fin = $rango['fin'];

            // 3. Obtener cursos en este nivel
            $stmt_c = $db->prepare("SELECT id, nombre_curso FROM cursos ORDER BY nombre_curso");
            $stmt_c->execute();
            $cursos_all = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
            $cursos_nivel = [];
            foreach ($cursos_all as $c) {
                $nombre = $c['nombre_curso'];
                
                if (preg_match('/^([0-9]+[°º]?)/u', $nombre, $matches)) {
                    $lvl = $matches[1];
                } else {
                    $lvl = explode(' ', $nombre)[0];
                }

                if ($lvl === $nivel_prefix) {
                    $cursos_nivel[] = $c;
                }
            }

            if (empty($cursos_nivel)) {
                throw new Exception("No se encontraron cursos paralelos en el nivel '$nivel_prefix'.");
            }

            $cursos_ids = array_map(function($c) { return (int)$c['id']; }, $cursos_nivel);
            $cursos_placeholders = implode(',', $cursos_ids);

            // 4. Especialidades activas en el nivel
            $stmt_mat = $db->prepare("
                SELECT DISTINCT e.id, e.nombre_especialidad
                FROM especialidades e
                JOIN carga_academica ca ON e.id = ca.especialidad_id
                WHERE ca.curso_id IN ($cursos_placeholders)
                ORDER BY e.nombre_especialidad ASC
            ");
            $stmt_mat->execute();
            $materias = $stmt_mat->fetchAll(PDO::FETCH_ASSOC);

            if (empty($materias)) {
                $stmt_mat = $db->prepare("SELECT id, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC");
                $stmt_mat->execute();
                $materias = $stmt_mat->fetchAll(PDO::FETCH_ASSOC);
            }

            // 5. Estudiantes del nivel
            $stmt_est = $db->prepare("
                SELECT id, curso_id, nombre, apellido
                FROM estudiantes
                WHERE curso_id IN ($cursos_placeholders)
            ");
            $stmt_est->execute();
            $estudiantes = $stmt_est->fetchAll(PDO::FETCH_ASSOC);

            if (empty($estudiantes)) {
                throw new Exception("No hay estudiantes matriculados en este nivel.");
            }

            // 6. Cargar calificaciones
            $stmt_calif_act = $db->prepare("
                SELECT cd.estudiante_id, a.especialidad_id, a.clase_nota_id, cd.calificacion, cd.nota_recuperacion
                FROM ares_calificaciones_desglose cd
                JOIN ares_actividades a ON cd.actividad_id = a.id
                LEFT JOIN aula_recursos r ON r.actividad_vinculada_id = a.id
                WHERE a.curso_id IN ($cursos_placeholders)
                  AND a.fecha_registro BETWEEN ? AND ?
                  AND (r.id IS NULL OR (r.visibilidad = 1 AND (r.fecha_inicio IS NULL OR r.fecha_inicio = '' OR NOW() >= r.fecha_inicio)))
            ");
            $stmt_calif_act->execute([$f_inicio, $f_fin]);
            $calif_act = $stmt_calif_act->fetchAll(PDO::FETCH_ASSOC);

            $stmt_calif_ex = $db->prepare("
                SELECT r.estudiante_id, p.materia_id as especialidad_id, 1 as clase_nota_id,
                       IFNULL(r.calificacion_manual, r.calificacion_automatica) as calificacion,
                       r.calificacion_recuperacion
                FROM eval_respuestas r
                JOIN eval_asignaciones asig ON r.asignacion_id = asig.id
                JOIN eval_pruebas p ON asig.prueba_id = p.id
                WHERE asig.curso_id IN ($cursos_placeholders)
                  AND asig.fecha_inicio BETWEEN ? AND ?
            ");
            $stmt_calif_ex->execute([$f_inicio, $f_fin]);
            $calif_ex = $stmt_calif_ex->fetchAll(PDO::FETCH_ASSOC);

            $notas_consolidado = [];
            
            $politica = CalculadoraNotas::obtenerPoliticaRecuperacion($db);
            $escala = CalculadoraNotas::obtenerEscala($db);
            $nota_aprobacion = $escala['nota_aprobacion'];

            foreach ($calif_act as $c) {
                $est_id = (int)$c['estudiante_id'];
                $mat_id = (int)$c['especialidad_id'];
                $dim_id = (int)$c['clase_nota_id'];
                $val = (float)$c['calificacion'];
                $recup = $c['nota_recuperacion'] !== null ? (float)$c['nota_recuperacion'] : null;

                $val_final = CalculadoraNotas::aplicarPoliticaRecuperacion($val, $recup, $politica, $nota_aprobacion);
                $notas_consolidado[$est_id][$mat_id][$dim_id][] = $val_final;
            }
            foreach ($calif_ex as $c) {
                $est_id = (int)$c['estudiante_id'];
                $mat_id = (int)$c['especialidad_id'];
                $dim_id = 1;
                $val = (float)$c['calificacion'];
                $recup = $c['calificacion_recuperacion'] !== null ? (float)$c['calificacion_recuperacion'] : null;

                $val_final = CalculadoraNotas::aplicarPoliticaRecuperacion($val, $recup, $politica, $nota_aprobacion);
                $notas_consolidado[$est_id][$mat_id][$dim_id][] = $val_final;
            }

            $pesos_dimensiones = [];
            $stmt_pesos = $db->prepare("SELECT id, peso_global FROM ares_clases_nota WHERE estado = :estado");
            $stmt_pesos->execute([':estado' => 1]);
            while ($row_p = $stmt_pesos->fetch(PDO::FETCH_ASSOC)) {
                $pesos_dimensiones[(int)$row_p['id']] = (float)$row_p['peso_global'] / 100.0;
            }
            if (empty($pesos_dimensiones)) {
                $pesos_dimensiones = [1 => 0.40, 2 => 0.40, 3 => 0.20];
            }

            // Definitivas por estudiante
            $estudiante_definitivas = [];
            foreach ($estudiantes as $est) {
                $est_id = (int)$est['id'];
                foreach ($materias as $mat) {
                    $mat_id = (int)$mat['id'];
                    $materias_notas = $notas_consolidado[$est_id][$mat_id] ?? [];
                    if (empty($materias_notas)) continue;

                    $promedios_dim = [];
                    $suma_pesos_activos = 0.0;
                    foreach ($pesos_dimensiones as $dim_id => $peso) {
                        $dim_notas = $materias_notas[$dim_id] ?? [];
                        if (!empty($dim_notas)) {
                            $promedios_dim[$dim_id] = array_sum($dim_notas) / count($dim_notas);
                            $suma_pesos_activos += $peso;
                        }
                    }

                    if (!empty($promedios_dim)) {
                        $nota_definitiva = 0.0;
                        foreach ($promedios_dim as $dim_id => $prom) {
                            $peso_original = $pesos_dimensiones[$dim_id];
                            $peso_ajustado = $peso_original / $suma_pesos_activos;
                            $nota_definitiva += $prom * $peso_ajustado;
                        }
                        $estudiante_definitivas[$est_id][$mat_id] = round($nota_definitiva, 2);
                    }
                }
            }

            // Promedios por Curso y Materia
            $curso_materia_promedios = [];
            foreach ($estudiantes as $est) {
                $est_id = (int)$est['id'];
                $curso_id = (int)$est['curso_id'];
                $defs = $estudiante_definitivas[$est_id] ?? [];
                foreach ($defs as $mat_id => $def) {
                    $curso_materia_promedios[$curso_id][$mat_id][] = $def;
                }
            }

            $comparativa = [];
            foreach ($materias as $mat) {
                $mat_id = (int)$mat['id'];
                $mat_nombre = $mat['nombre_especialidad'];
                $valores = [];
                $tiene_datos = false;
                
                foreach ($cursos_nivel as $c) {
                    $c_id = (int)$c['id'];
                    $notas_grupo = $curso_materia_promedios[$c_id][$mat_id] ?? [];
                    
                    if (!empty($notas_grupo)) {
                        $prom_grupo = round(array_sum($notas_grupo) / count($notas_grupo), 2);
                        $valores[$c_id] = $prom_grupo;
                        $tiene_datos = true;
                    } else {
                        $valores[$c_id] = null;
                    }
                }

                if ($tiene_datos) {
                    $comparativa[] = [
                        'id' => $mat_id,
                        'materia' => $mat_nombre,
                        'valores' => $valores
                    ];
                }
            }

            $payload = [
                'escala' => $config_escala,
                'cursos' => $cursos_nivel,
                'materias' => $materias,
                'comparativa' => $comparativa
            ];

            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $payload]);
            break;

        case 'obtener_desglose_materia':
            $estudiante_id = (int)($_GET['estudiante_id'] ?? 0);
            $materia_id = (int)($_GET['materia_id'] ?? 0);
            $periodo_id = (int)($_GET['periodo_id'] ?? 1);

            if (!$estudiante_id || !$materia_id) {
                throw new Exception('Parámetros incompletos (estudiante_id y materia_id requeridos).');
            }

            // Establecer rangos de fechas dinámicos según el periodo seleccionado (Año Escolar Actual)
            $anio = date('Y');
            $fechas_periodo = [
                1 => ['inicio' => "$anio-01-01 00:00:00", 'fin' => "$anio-03-31 23:59:59"],
                2 => ['inicio' => "$anio-04-01 00:00:00", 'fin' => "$anio-06-30 23:59:59"],
                3 => ['inicio' => "$anio-07-01 00:00:00", 'fin' => "$anio-09-30 23:59:59"],
                4 => ['inicio' => "$anio-10-01 00:00:00", 'fin' => "$anio-12-31 23:59:59"]
            ];

            $rango = $fechas_periodo[$periodo_id] ?? $fechas_periodo[1];
            $f_inicio = $rango['inicio'];
            $f_fin = $rango['fin'];

            // 1. Obtener detalles del estudiante y materia
            $stmt_est_info = $db->prepare("SELECT nombre, apellido FROM estudiantes WHERE id = ?");
            $stmt_est_info->execute([$estudiante_id]);
            $estudiante_info = $stmt_est_info->fetch(PDO::FETCH_ASSOC);

            $stmt_mat_info = $db->prepare("SELECT nombre_especialidad FROM especialidades WHERE id = ?");
            $stmt_mat_info->execute([$materia_id]);
            $materia_info = $stmt_mat_info->fetch(PDO::FETCH_ASSOC);

            if (!$estudiante_info || !$materia_info) {
                throw new Exception('Información no encontrada.');
            }

            // 2. Obtener lista detallada de actividades diarias con su calificación
            $stmt_acts = $db->prepare("
                SELECT a.titulo, cn.nombre as dimension, a.fecha_registro as fecha, cd.calificacion, cd.nota_recuperacion, 'actividad' as tipo
                FROM ares_calificaciones_desglose cd
                JOIN ares_actividades a ON cd.actividad_id = a.id
                JOIN ares_clases_nota cn ON a.clase_nota_id = cn.id
                WHERE cd.estudiante_id = ? 
                  AND a.especialidad_id = ?
                  AND a.fecha_registro BETWEEN ? AND ?
                ORDER BY a.fecha_registro ASC
            ");
            $stmt_acts->execute([$estudiante_id, $materia_id, $f_inicio, $f_fin]);
            $detalles_act = $stmt_acts->fetchAll(PDO::FETCH_ASSOC);

            // 3. Obtener lista detallada de exámenes con su calificación
            $stmt_exs = $db->prepare("
                SELECT p.titulo, 'Saber (Cognitivo)' as dimension, asig.fecha_inicio as fecha,
                       IFNULL(r.calificacion_manual, r.calificacion_automatica) as calificacion,
                       r.calificacion_recuperacion, 'examen' as tipo
                FROM eval_respuestas r
                JOIN eval_asignaciones asig ON r.asignacion_id = asig.id
                JOIN eval_pruebas p ON asig.prueba_id = p.id
                WHERE r.estudiante_id = ? 
                  AND p.materia_id = ?
                  AND asig.fecha_inicio BETWEEN ? AND ?
                ORDER BY asig.fecha_inicio ASC
            ");
            $stmt_exs->execute([$estudiante_id, $materia_id, $f_inicio, $f_fin]);
            $detalles_ex = $stmt_exs->fetchAll(PDO::FETCH_ASSOC);

            // Consolidar array ordenado de actividades por fecha
            $actividades_desglose = array_merge($detalles_act, $detalles_ex);
            usort($actividades_desglose, function($a, $b) {
                return strcmp($a['fecha'], $b['fecha']);
            });

            // Aplicar cálculo de políticas de recuperación
            $politica = CalculadoraNotas::obtenerPoliticaRecuperacion($db);
            $escala = CalculadoraNotas::obtenerEscala($db);
            $nota_aprobacion = $escala['nota_aprobacion'];

            foreach ($actividades_desglose as &$item) {
                $val = (float)$item['calificacion'];
                $recup = isset($item['nota_recuperacion']) && $item['nota_recuperacion'] !== null 
                    ? (float)$item['nota_recuperacion'] 
                    : (isset($item['calificacion_recuperacion']) && $item['calificacion_recuperacion'] !== null 
                        ? (float)$item['calificacion_recuperacion'] 
                        : null);

                $item['nota_recuperacion_final'] = $recup;
                $item['calificacion_final_calculada'] = CalculadoraNotas::aplicarPoliticaRecuperacion($val, $recup, $politica, $nota_aprobacion);
            }
            unset($item);

            $payload = [
                'estudiante' => $estudiante_info['apellido'] . ', ' . $estudiante_info['nombre'],
                'materia' => $materia_info['nombre_especialidad'],
                'desglose' => $actividades_desglose
            ];

            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $payload]);
            break;

        default:
            throw new Exception('Acción de motor inválida o no soportada.');
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
ob_end_flush();
