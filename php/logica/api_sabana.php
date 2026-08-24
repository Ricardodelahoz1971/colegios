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

    if (!tiene_permiso('evaluacion')) {
        throw new Exception('Acceso denegado: No posee privilegios de evaluación.');
    }

    $accion = limpiar_texto_utf8($_POST['accion'] ?? '') ?? limpiar_texto_utf8($_GET['accion'] ?? '') ?? 'invalid';

    switch ($accion) {
        case 'cargar_consolidado_curso':
            $curso_id = (int)(filter_input(INPUT_GET, 'curso_id', FILTER_VALIDATE_INT) ?? 0);
            $periodo_id = (int)(filter_input(INPUT_GET, 'periodo_id', FILTER_VALIDATE_INT) ?? 1);

            if (!$curso_id) {
                throw new Exception('Parámetro incompleto (curso_id requerido).');
            }

            $es_admin = tienen_rol(['administrador', 'coordinador', 'director']);
            $es_tutor = false;
            if (!$es_admin) {
                $stmt_tutor = $db->prepare("SELECT 1 FROM cursos WHERE id = ? AND tutor_id = ? LIMIT 1");
                $stmt_tutor->execute([$curso_id, $mi_id]);
                $es_tutor = (bool)$stmt_tutor->fetchColumn();

                if (!$es_tutor) {
                    $stmt_verificar = $db->prepare("SELECT 1 FROM carga_academica WHERE curso_id = ? AND docente_id = ? LIMIT 1");
                    $stmt_verificar->execute([$curso_id, $mi_id]);
                    if (!$stmt_verificar->fetchColumn()) {
                        throw new Exception('Acceso denegado: Violación de privacidad transversal. No es el director del grupo ni imparte materias en este curso.');
                    }
                }
            }

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

            $stmt_priv = $db->prepare("SELECT valor FROM configuracion_global WHERE clave = 'privacidad_catedratico_sabana' LIMIT 1");
            $stmt_priv->execute();
            $modo_privacidad = $stmt_priv->fetchColumn() ?: 'estricto';

            if ($modo_privacidad === 'estricto' && !$es_admin && !$es_tutor) {
                $stmt_mat = $db->prepare("
                    SELECT DISTINCT e.id, e.nombre_especialidad 
                    FROM especialidades e
                    JOIN carga_academica ca ON e.id = ca.especialidad_id
                    WHERE ca.curso_id = ? AND ca.docente_id = ?
                    ORDER BY e.nombre_especialidad ASC
                ");
                $stmt_mat->execute([$curso_id, $mi_id]);
            } else {
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

            $stmt_dims = $db->prepare("SELECT id, nombre, min_evaluaciones FROM ares_clases_nota WHERE estado = :estado");
            $stmt_dims->execute([':estado' => 1]);
            $min_evals_dimensiones = [];
            while ($row_d = $stmt_dims->fetch(PDO::FETCH_ASSOC)) {
                $min_evals_dimensiones[(int)$row_d['id']] = (int)$row_d['min_evaluaciones'];
            }

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
                
                $stmt_count_act->execute([$curso_id, $mat_id, $f_inicio, $f_fin]);
                $act_counts = $stmt_count_act->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
                
                $stmt_count_ex->execute([$curso_id, $mat_id, $f_inicio, $f_fin]);
                $ex_count = (int)$stmt_count_ex->fetchColumn();

                $conteo_dimensiones = [];
                $alertas = [];
                $cumple_plan = true;

                foreach ($min_evals_dimensiones as $dim_id => $min_requerido) {
                    $clase_act_count = $act_counts[$dim_id] ?? 0;
                    if ($dim_id === 1) {
                        $clase_act_count += $ex_count;
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

            $stmt_est = $db->prepare("
                SELECT id, nombre, apellido 
                FROM estudiantes 
                WHERE curso_id = ? 
                ORDER BY apellido ASC, nombre ASC
            ");
            $stmt_est->execute([$curso_id]);
            $estudiantes = $stmt_est->fetchAll(PDO::FETCH_ASSOC);

            $stmt_fallas = $db->prepare("
                SELECT estudiante_id, COUNT(*) as fallas 
                FROM asistencias 
                WHERE curso_id = ? AND estado = 'F' 
                  AND fecha BETWEEN ? AND ?
                GROUP BY estudiante_id
            ");
            $stmt_fallas->execute([$curso_id, substr($f_inicio, 0, 10), substr($f_fin, 0, 10)]);
            $fallas_map = $stmt_fallas->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

            $pesos_dimensiones = [];
            $stmt_pesos = $db->prepare("SELECT id, peso_global FROM ares_clases_nota WHERE estado = :estado");
            $stmt_pesos->execute([':estado' => 1]);
            while ($row_p = $stmt_pesos->fetch(PDO::FETCH_ASSOC)) {
                $pesos_dimensiones[(int)$row_p['id']] = (float)$row_p['peso_global'] / 100.0;
            }
            if (empty($pesos_dimensiones)) {
                $pesos_dimensiones = [
                    1 => 0.40,
                    2 => 0.40,
                    3 => 0.20
                ];
            }

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

            $matriz_estudiantes = [];
            foreach ($estudiantes as $est) {
                $est_id = (int)$est['id'];
                $notas_finales = [];

                foreach ($materias as $mat) {
                    $mat_id = (int)$mat['id'];
                    
                    $materias_notas = $notas_consolidado[$est_id][$mat_id] ?? [];
                    
                    if (empty($materias_notas)) {
                        $notas_finales[$mat_id] = null;
                        continue;
                    }

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

                    $nota_definitiva = 0.0;
                    foreach ($promedios_dim as $dim_id => $prom) {
                        $peso_original = $pesos_dimensiones[$dim_id];
                        $peso_ajustado = $peso_original / $suma_pesos_activos;
                        $nota_definitiva += $prom * $peso_ajustado;
                    }

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

            if (!$es_admin && !$es_tutor && $modo_privacidad === 'estricto') {
                $materias_permitidas = array_column($materias, 'id');
                $materias_permitidas = array_map('intval', $materias_permitidas);

                foreach ($matriz_estudiantes as &$estudiante) {
                    $estudiante['calificaciones'] = array_filter($estudiante['calificaciones'], function($materia_id) use ($materias_permitidas) {
                        return in_array((int)$materia_id, $materias_permitidas, true);
                    }, ARRAY_FILTER_USE_KEY);
                }
                unset($estudiante);
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
            $periodo_id = (int)(filter_input(INPUT_GET, 'periodo_id', FILTER_VALIDATE_INT) ?? 1);

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

            $stmt_c = $db->prepare("SELECT id, nombre_curso FROM cursos ORDER BY nombre_curso");
            $stmt_c->execute();
            $cursos_all = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
            
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

                if (isset($notas_consolidado[$est_id][$mat_id][$dim_id])) {
                    $notas_consolidado[$est_id][$mat_id][$dim_id] = ($notas_consolidado[$est_id][$mat_id][$dim_id] + $promedio_dim) / 2.0;
                } else {
                    $notas_consolidado[$est_id][$mat_id][$dim_id] = $promedio_dim;
                }
                $estudiante_cursos[$est_id] = (int)$row['curso_id'];
            }

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

            $podio = $cursos_stats;
            usort($podio, function($a, $b) {
                return $b['promedio'] <=> $a['promedio'];
            });
            $podio = array_slice($podio, 0, 3);

            $semaforo = $cursos_stats;
            usort($semaforo, function($a, $b) {
                if ($b['riesgo_porcentaje'] === $a['riesgo_porcentaje']) {
                    return $b['total_perdidas'] <=> $a['total_perdidas'];
                }
                return $b['riesgo_porcentaje'] <=> $a['riesgo_porcentaje'];
            });

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

            $promedio_global = !empty($todos_proms) ? round(array_sum($todos_proms) / count($todos_proms), 2) : 0;
            $tasa_riesgo_global = $total_estudiantes_con_notas > 0 ? round(($total_riesgo_global / $total_estudiantes_con_notas) * 100, 1) : 0;

            $payload = [
                'escala' => $config_escala,
                'niveles' => $niveles,
                'cursos' => $cursos_stats,
                'podio' => $podio,
                'semaforo' => $semaforo,
                'kpis' => [
                    'promedio_global' => $promedio_global,
                    'total_estudiantes' => $total_estudiantes_con_notas,
                    'estudiantes_riesgo' => $total_riesgo_global,
                    'tasa_riesgo' => $tasa_riesgo_global
                ]
            ];

            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $payload]);
            break;

        default:
            throw new Exception('Acción no válida.');
    }
} catch (Throwable $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}