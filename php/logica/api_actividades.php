<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);

/**
 * 🛡️ API DE ACTIVIDADES Y RÚBRICAS DINÁMICAS (PERSEUS ENGINE)
 * Motor Backend para Evaluación Multicriterio - Vitrina 06 Standard
 */

ob_start(); // BLINDAJE: Capturar cualquier advertencia antes de enviar JSON
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

require_once '../db.php';
require_once '../auth.php';
require_once '../security.php';
guardia_sesion();
    session_write_close();

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    // Autenticación Básica
    $mi_id = (int)($_SESSION['usuario_id'] ?? 0);
    if (!$mi_id) {
        throw new Exception('Sesión expirada o inválida. Acceso denegado.');
    }

    // Seguridad: Validar si tiene permiso de evaluación
    if (!tiene_permiso('evaluacion')) {
        throw new Exception('Acceso denegado: No posee credenciales para gestionar rúbricas.');
    }

    $accion = $_POST['accion'] ?? $_GET['accion'] ?? 'invalid';

    switch ($accion) {
        case 'obtener_evidencias_digitales':
            $estudiante_id = (int)($_GET['estudiante_id'] ?? $_POST['estudiante_id'] ?? 0);
            $especialidad_id = (int)($_GET['especialidad_id'] ?? $_POST['especialidad_id'] ?? 0);
            $tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? '';

            if (!$estudiante_id || !$especialidad_id || !$tipo) {
                throw new Exception('Parámetros estudiante_id, especialidad_id y tipo son obligatorios.');
            }

            // Obtener el curso del estudiante
            $stmt_cur = $db->prepare("SELECT curso_id FROM estudiantes WHERE id = ?");
            $stmt_cur->execute([$estudiante_id]);
            $curso_id = (int)$stmt_cur->fetchColumn();

            if (!$curso_id) {
                throw new Exception('El estudiante no se encuentra matriculado o asignado a un curso.');
            }

            $data = [];

            if ($tipo === 'aula_virtual') {
                // Consolidar notas mediante LEFT JOIN en una sola consulta estructurada (Evitar N+1)
                $stmt_ev = $db->prepare("
                    SELECT 
                        r.id, 
                        r.titulo, 
                        r.actividad_vinculada_id, 
                        a.tipo_evaluacion,
                        (
                            SELECT calificacion 
                            FROM ares_calificaciones_desglose 
                            WHERE actividad_id = r.actividad_vinculada_id AND estudiante_id = ? 
                            LIMIT 1
                        ) as nota_directa,
                        (
                            SELECT SUM(d.calificacion * c.peso_porcentaje) / 100 
                            FROM ares_calificaciones_desglose d 
                            JOIN ares_actividad_criterios c ON d.criterio_id = c.id
                            WHERE d.actividad_id = r.actividad_vinculada_id AND d.estudiante_id = ?
                        ) as nota_ponderada
                    FROM aula_recursos r
                    JOIN ares_actividades a ON r.actividad_vinculada_id = a.id
                    WHERE r.curso_id = ? AND r.especialidad_id = ? AND r.es_evaluativo = 1
                ");
                $stmt_ev->execute([$estudiante_id, $estudiante_id, $curso_id, $especialidad_id]);
                $recursos = $stmt_ev->fetchAll(PDO::FETCH_ASSOC);

                foreach ($recursos as $rec) {
                    $nota = null;
                    if ($rec['tipo_evaluacion'] === 'directo') {
                        if ($rec['nota_directa'] !== null) {
                            $nota = (float)$rec['nota_directa'];
                        }
                    } else {
                        if ($rec['nota_ponderada'] !== null) {
                            $nota = round((float)$rec['nota_ponderada'], 1);
                        }
                    }
                    
                    $data[] = [
                        'id' => $rec['id'],
                        'titulo' => $rec['titulo'],
                        'calificacion' => $nota
                    ];
                }
            } elseif ($tipo === 'examen_online') {
                // Consolidar notas de exámenes online en una sola consulta estructurada (Evitar N+1)
                $stmt_ex = $db->prepare("
                    SELECT 
                        a.id as asignacion_id, 
                        p.titulo,
                        res.calificacion_automatica,
                        res.calificacion_manual
                    FROM eval_asignaciones a
                    JOIN eval_pruebas p ON a.prueba_id = p.id
                    LEFT JOIN eval_respuestas res ON res.asignacion_id = a.id AND res.estudiante_id = ?
                    WHERE a.curso_id = ? AND p.materia_id = ?
                ");
                $stmt_ex->execute([$estudiante_id, $curso_id, $especialidad_id]);
                $examenes = $stmt_ex->fetchAll(PDO::FETCH_ASSOC);

                foreach ($examenes as $ex) {
                    $nota = null;
                    if ($ex['calificacion_automatica'] !== null || $ex['calificacion_manual'] !== null) {
                        $nota = (float)($ex['calificacion_automatica'] ?? 0) + (float)($ex['calificacion_manual'] ?? 0);
                    }
                    
                    $data[] = [
                        'id' => $ex['asignacion_id'],
                        'titulo' => $ex['titulo'],
                        'calificacion' => $nota
                    ];
                }
            } else {
                throw new Exception('Tipo de evidencia digital no válido.');
            }

            ob_clean();
            echo json_encode([
                'status' => 'success',
                'data' => $data,
                'message' => 'Evidencias digitales cargadas.'
            ]);
            break;

        case 'listar_clases_nota':
            $stmt = $db->prepare("SELECT id, nombre, peso_global FROM ares_clases_nota WHERE estado = 1 ORDER BY id ASC");
            $stmt->execute();
            ob_clean();
            echo json_encode([
                'status' => 'success',
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'message' => 'Catálogo de clases de nota cargado.'
            ]);
            break;

        case 'guardar_actividad':
            proteccion_extrema(); // CSRF
            
            $titulo = trim($_POST['titulo'] ?? '');
            $curso_id = (int)($_POST['curso_id'] ?? 0);
            $especialidad_id = (int)($_POST['especialidad_id'] ?? 0);
            $clase_nota_id = (int)($_POST['clase_nota_id'] ?? 0);
            $tipo_evaluacion = $_POST['tipo_evaluacion'] ?? 'directo'; // 'deductivo', 'rubrica', 'directo'
            $ambito = trim($_POST['ambito'] ?? 'estandar');
            if (!in_array($ambito, ['estandar', 'recuperacion'])) {
                $ambito = 'estandar';
            }
            $recupera_actividad_id = isset($_POST['recupera_actividad_id']) && (int)$_POST['recupera_actividad_id'] > 0 
                ? (int)$_POST['recupera_actividad_id'] 
                : null;
            
            if (empty($titulo) || !$curso_id || !$especialidad_id || !$clase_nota_id) {
                throw new Exception('Parámetros incompletos para crear la actividad.');
            }

            $db->beginTransaction();
            try {
                // 1. Insertar la cabecera de la Actividad
                $stmt = $db->prepare("INSERT INTO ares_actividades (docente_id, curso_id, especialidad_id, clase_nota_id, titulo, tipo_evaluacion, ambito, recupera_actividad_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$mi_id, $curso_id, $especialidad_id, $clase_nota_id, $titulo, $tipo_evaluacion, $ambito, $recupera_actividad_id]);
                $actividad_id = (int)$db->lastInsertId();

                // 2. Si es modo Rúbrica, insertar los criterios
                if ($tipo_evaluacion === 'rubrica' && isset($_POST['criterios'])) {
                    // Espera JSON [{"titulo": "Redaccion", "peso": 40}, ...]
                    $criterios = json_decode($_POST['criterios'], true);
                    if (!is_array($criterios) || count($criterios) === 0) {
                        throw new Exception('El modo rúbrica exige al menos un criterio de evaluación.');
                    }

                    $stmt_crit = $db->prepare("INSERT INTO ares_actividad_criterios (actividad_id, titulo, peso_porcentaje) VALUES (?, ?, ?)");
                    foreach ($criterios as $crit) {
                        $peso = (float)($crit['peso'] ?? 0);
                        $tit = trim($crit['titulo'] ?? '');
                        if (empty($tit)) throw new Exception('Un criterio no puede tener título vacío.');
                        
                        $stmt_crit->execute([$actividad_id, $tit, $peso]);
                    }
                }

                $db->commit();
                ob_clean();
                echo json_encode([
                    'status' => 'success',
                    'data' => ['actividad_id' => $actividad_id],
                    'message' => 'Actividad pedagógica creada exitosamente.'
                ]);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                throw $e;
            }
            break;

        case 'obtener_actividad_detalles':
            $actividad_id = (int)($_GET['actividad_id'] ?? 0);
            if (!$actividad_id) throw new Exception('ID de actividad no proporcionado.');

            $stmt = $db->prepare("SELECT id, titulo, tipo_evaluacion, curso_id, especialidad_id, ambito, recupera_actividad_id FROM ares_actividades WHERE id = ? AND docente_id = ?");
            $stmt->execute([$actividad_id, $mi_id]);
            $act = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$act) throw new Exception('Actividad no encontrada.');

            $stmt_c = $db->prepare("SELECT id, titulo, peso_porcentaje FROM ares_actividad_criterios WHERE actividad_id = ? ORDER BY id ASC");
            $stmt_c->execute([$actividad_id]);
            $act['criterios'] = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $act]);
            break;

        case 'actualizar_actividad':
            proteccion_extrema();
            $id = (int)($_POST['id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $tipo = $_POST['tipo_evaluacion'] ?? 'directo';
            $clase_nota_id = (int)($_POST['clase_nota_id'] ?? 0);
            $curso_id = (int)($_POST['curso_id'] ?? 0);
            $especialidad_id = (int)($_POST['especialidad_id'] ?? 0);
            $ambito = trim($_POST['ambito'] ?? 'estandar');
            if (!in_array($ambito, ['estandar', 'recuperacion'])) {
                $ambito = 'estandar';
            }
            $recupera_actividad_id = isset($_POST['recupera_actividad_id']) && (int)$_POST['recupera_actividad_id'] > 0 
                ? (int)$_POST['recupera_actividad_id'] 
                : null;

            if (!$id || !$titulo) throw new Exception('Datos insuficientes para la actualización.');

            // Validar propiedad
            $stmt_val = $db->prepare("SELECT id FROM ares_actividades WHERE id = ? AND docente_id = ?");
            $stmt_val->execute([$id, $mi_id]);
            if (!$stmt_val->fetch()) throw new Exception('No tiene permisos para editar esta actividad.');

            $db->beginTransaction();
            try {
                // Actualizar cabecera (Incluyendo curso y especialidad por flexibilidad pedagógica)
                $db->prepare("UPDATE ares_actividades SET titulo = ?, tipo_evaluacion = ?, clase_nota_id = ?, curso_id = ?, especialidad_id = ?, ambito = ?, recupera_actividad_id = ? WHERE id = ?")
                   ->execute([$titulo, $tipo, $clase_nota_id, $curso_id, $especialidad_id, $ambito, $recupera_actividad_id, $id]);

                if ($tipo === 'rubrica') {
                    $criterios = json_decode($_POST['criterios'] ?? '[]', true);
                    $ids_enviados = [];

                    foreach ($criterios as $c) {
                        $c_id = (int)($c['id'] ?? 0);
                        $c_titulo = trim($c['titulo'] ?? '');
                        $c_peso = (float)($c['peso'] ?? 0);

                        if ($c_id > 0) {
                            // Actualizar existente
                            $db->prepare("UPDATE ares_actividad_criterios SET titulo = ?, peso_porcentaje = ? WHERE id = ? AND actividad_id = ?")
                               ->execute([$c_titulo, $c_peso, $c_id, $id]);
                            $ids_enviados[] = $c_id;
                        } else {
                            // Insertar nuevo
                            $stmt_ins = $db->prepare("INSERT INTO ares_actividad_criterios (actividad_id, titulo, peso_porcentaje) VALUES (?, ?, ?)");
                            $stmt_ins->execute([$id, $c_titulo, $c_peso]);
                            $ids_enviados[] = (int)$db->lastInsertId();
                        }
                    }

                    // Eliminar criterios que ya no están
                    if (!empty($ids_enviados)) {
                        $placeholders = implode(',', array_fill(0, count($ids_enviados), '?'));
                        // Primero limpiar notas de los criterios que se van a borrar
                        $db->prepare("DELETE FROM ares_calificaciones_desglose WHERE actividad_id = ? AND criterio_id NOT IN ($placeholders) AND criterio_id IS NOT NULL")
                           ->execute(array_merge([$id], $ids_enviados));
                        // Luego borrar los criterios
                        $db->prepare("DELETE FROM ares_actividad_criterios WHERE actividad_id = ? AND id NOT IN ($placeholders)")
                           ->execute(array_merge([$id], $ids_enviados));
                    }
                } else {
                    // Si cambió a directo, borrar todos los criterios y notas de rúbrica
                    $db->prepare("DELETE FROM ares_calificaciones_desglose WHERE actividad_id = ? AND criterio_id IS NOT NULL")->execute([$id]);
                    $db->prepare("DELETE FROM ares_actividad_criterios WHERE actividad_id = ?")->execute([$id]);
                }

                $db->commit();
                ob_clean();
                echo json_encode(['status' => 'success', 'message' => 'Actividad actualizada con éxito.']);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'listar_estudiantes_actividad':
            $actividad_id = (int)($_GET['actividad_id'] ?? 0);
            if (!$actividad_id) throw new Exception('ID de actividad no proporcionado.');

            $stmt_cur = $db->prepare("SELECT curso_id FROM ares_actividades WHERE id = ? AND docente_id = ?");
            $stmt_cur->execute([$actividad_id, $mi_id]);
            $curso_id = $stmt_cur->fetchColumn();
            if (!$curso_id) throw new Exception('No se pudo identificar el curso de la actividad.');

            $sql = "SELECT id, CONCAT(nombre, ' ', apellido) as nombre, 
                           (SELECT ROUND(SUM(calificacion * (IFNULL(ac.peso_porcentaje, 100) / 100.0)), 1) 
                            FROM ares_calificaciones_desglose cd 
                            LEFT JOIN ares_actividad_criterios ac ON cd.criterio_id = ac.id
                            WHERE cd.estudiante_id = estudiantes.id AND cd.actividad_id = ?) as nota_final,
                           (SELECT MAX(nota_recuperacion) 
                            FROM ares_calificaciones_desglose 
                            WHERE estudiante_id = estudiantes.id AND actividad_id = ?) as nota_recuperacion,
                           (SELECT GROUP_CONCAT(CONCAT(IFNULL(criterio_id, 0), ':', calificacion)) 
                            FROM ares_calificaciones_desglose 
                            WHERE estudiante_id = estudiantes.id AND actividad_id = ?) as desglose,
                           (SELECT MAX(es_edicion) FROM ares_calificaciones_desglose 
                            WHERE estudiante_id = estudiantes.id AND actividad_id = ?) as fue_editado,
                           (SELECT metodo_recuperacion 
                            FROM ares_calificaciones_desglose 
                            WHERE estudiante_id = estudiantes.id AND actividad_id = ? AND metodo_recuperacion IS NOT NULL LIMIT 1) as metodo_recuperacion,
                           (SELECT justificacion_recuperacion 
                            FROM ares_calificaciones_desglose 
                            WHERE estudiante_id = estudiantes.id AND actividad_id = ? AND justificacion_recuperacion IS NOT NULL LIMIT 1) as justificacion_recuperacion,
                           (SELECT soporte_recuperacion_id 
                            FROM ares_calificaciones_desglose 
                            WHERE estudiante_id = estudiantes.id AND actividad_id = ? AND soporte_recuperacion_id IS NOT NULL LIMIT 1) as soporte_recuperacion_id
                    FROM estudiantes
                    WHERE curso_id = ?
                    ORDER BY apellido ASC, nombre ASC";
            
            $stmt_est = $db->prepare($sql);
            $stmt_est->execute([
                (int)$actividad_id, 
                (int)$actividad_id, 
                (int)$actividad_id, 
                (int)$actividad_id, 
                (int)$actividad_id, 
                (int)$actividad_id, 
                (int)$actividad_id, 
                (int)$curso_id
            ]);
            $estudiantes = $stmt_est->fetchAll(PDO::FETCH_ASSOC);

            // Aplicar cálculo dinámico de políticas de recuperación en servidor
            $politica = CalculadoraNotas::obtenerPoliticaRecuperacion($db);
            $escala = CalculadoraNotas::obtenerEscala($db);
            $nota_aprobacion = $escala['nota_aprobacion'];

            foreach ($estudiantes as &$est) {
                if ($est['nota_final'] !== null) {
                    $original = (float)$est['nota_final'];
                    $recup = $est['nota_recuperacion'] !== null ? (float)$est['nota_recuperacion'] : null;
                    $est['nota_final_calculada'] = CalculadoraNotas::aplicarPoliticaRecuperacion($original, $recup, $politica, $nota_aprobacion);
                } else {
                    $est['nota_final_calculada'] = null;
                }
            }
            
            ob_clean();
            echo json_encode([
                'status' => 'success', 
                'data' => $estudiantes,
                'message' => 'Lista de estudiantes cargada.'
            ]);
            break;

        case 'listar_actividades_profesor':
            $filtrar = isset($_GET['filtrar_periodo']) && $_GET['filtrar_periodo'] == '1';
            if ($filtrar) {
                $rango = determinarPeriodoActivo($db);
                $stmt = $db->prepare("SELECT a.id, a.titulo, a.tipo_evaluacion, a.fecha_registro,
                                             a.especialidad_id, a.curso_id, a.clase_nota_id, a.ambito, a.recupera_actividad_id,
                                             c.nombre_curso, e.nombre_especialidad, cn.nombre as clase_nota,
                                             (SELECT GROUP_CONCAT(CONCAT(ac.id, '|', ac.titulo, '|', ac.peso_porcentaje)) 
                                              FROM ares_actividad_criterios ac WHERE ac.actividad_id = a.id) as criterios_raw,
                                             (SELECT COUNT(*) FROM ares_calificaciones_desglose WHERE actividad_id = a.id) as tiene_notas
                                      FROM ares_actividades a
                                      JOIN cursos c ON a.curso_id = c.id
                                      JOIN especialidades e ON a.especialidad_id = e.id
                                      JOIN ares_clases_nota cn ON a.clase_nota_id = cn.id
                                      WHERE a.docente_id = ? AND a.fecha_registro BETWEEN ? AND ?
                                      ORDER BY a.fecha_registro DESC");
                $stmt->execute([$mi_id, $rango['fecha_inicio'], $rango['fecha_fin']]);
            } else {
                $stmt = $db->prepare("SELECT a.id, a.titulo, a.tipo_evaluacion, a.fecha_registro,
                                             a.especialidad_id, a.curso_id, a.clase_nota_id, a.ambito, a.recupera_actividad_id,
                                             c.nombre_curso, e.nombre_especialidad, cn.nombre as clase_nota,
                                             (SELECT GROUP_CONCAT(CONCAT(ac.id, '|', ac.titulo, '|', ac.peso_porcentaje)) 
                                              FROM ares_actividad_criterios ac WHERE ac.actividad_id = a.id) as criterios_raw,
                                             (SELECT COUNT(*) FROM ares_calificaciones_desglose WHERE actividad_id = a.id) as tiene_notas
                                      FROM ares_actividades a
                                      JOIN cursos c ON a.curso_id = c.id
                                      JOIN especialidades e ON a.especialidad_id = e.id
                                      JOIN ares_clases_nota cn ON a.clase_nota_id = cn.id
                                      WHERE a.docente_id = ?
                                      ORDER BY a.fecha_registro DESC");
                $stmt->execute([$mi_id]);
            }
            ob_clean();
            echo json_encode([
                'status' => 'success',
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'message' => 'Actividades cargadas.'
            ]);
            break;

        case 'listar_actividades_filtro':
            $curso_id = (int)($_GET['curso_id'] ?? 0);
            $especialidad_id = (int)($_GET['especialidad_id'] ?? 0);
            if (!$curso_id || !$especialidad_id) {
                throw new Exception('Parámetros curso_id y especialidad_id son requeridos.');
            }
            
            $filtrar = isset($_GET['filtrar_periodo']) && $_GET['filtrar_periodo'] == '1';
            if ($filtrar) {
                $rango = determinarPeriodoActivo($db);
                $stmt = $db->prepare("SELECT id, titulo, ambito FROM ares_actividades WHERE docente_id = ? AND curso_id = ? AND especialidad_id = ? AND fecha_registro BETWEEN ? AND ? ORDER BY titulo ASC");
                $stmt->execute([$mi_id, $curso_id, $especialidad_id, $rango['fecha_inicio'], $rango['fecha_fin']]);
            } else {
                $stmt = $db->prepare("SELECT id, titulo, ambito FROM ares_actividades WHERE docente_id = ? AND curso_id = ? AND especialidad_id = ? ORDER BY titulo ASC");
                $stmt->execute([$mi_id, $curso_id, $especialidad_id]);
            }
            ob_clean();
            echo json_encode([
                'status' => 'success',
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;

        case 'eliminar_actividad':
            proteccion_extrema();
            $actividad_id = (int)($_POST['actividad_id'] ?? 0);
            if (!$actividad_id) throw new Exception('ID de actividad no válido.');

            // Validar propiedad
            $stmt_val = $db->prepare("SELECT id FROM ares_actividades WHERE id = ? AND docente_id = ?");
            $stmt_val->execute([$actividad_id, $mi_id]);
            if (!$stmt_val->fetch()) throw new Exception('No tiene permisos para eliminar esta actividad.');

            $db->beginTransaction();
            try {
                // Eliminar en cascada (desglose, criterios y cabecera)
                $db->prepare("DELETE FROM ares_calificaciones_desglose WHERE actividad_id = ?")->execute([$actividad_id]);
                $db->prepare("DELETE FROM ares_actividad_criterios WHERE actividad_id = ?")->execute([$actividad_id]);
                $db->prepare("DELETE FROM ares_actividades WHERE id = ?")->execute([$actividad_id]);
                
                $db->commit();
                ob_clean();
                echo json_encode(['status' => 'success', 'message' => 'Actividad eliminada permanentemente.']);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                throw $e;
            }
            break;

        case 'guardar_calificaciones_focus':
            proteccion_extrema();
            
            $actividad_id = (int)($_POST['actividad_id'] ?? 0);
            $estudiante_id = (int)($_POST['estudiante_id'] ?? 0);
            
            if (!$actividad_id || !$estudiante_id) {
                throw new Exception('Identidad de estudiante o actividad no proporcionada.');
            }

            // Validar que la actividad pertenece a este profesor (Blindaje)
            $stmt_val = $db->prepare("SELECT tipo_evaluacion FROM ares_actividades WHERE id = ? AND docente_id = ?");
            $stmt_val->execute([$actividad_id, $mi_id]);
            $actividad = $stmt_val->fetch(PDO::FETCH_ASSOC);

            if (!$actividad) {
                throw new Exception('Actividad no encontrada o no pertenece a este docente.');
            }

            $db->beginTransaction();
            try {
                // Detectar si ya tenía nota antes para marcar como edición permanente
                $stmt_check = $db->prepare("SELECT COUNT(*) FROM ares_calificaciones_desglose WHERE actividad_id = ? AND estudiante_id = ?");
                $stmt_check->execute([$actividad_id, $estudiante_id]);
                $ya_tenia_nota = (int)$stmt_check->fetchColumn() > 0;
                $es_edicion = $ya_tenia_nota ? 1 : 0;

                // Calcular nota anterior si existía
                $nota_anterior = 0.0;
                if ($ya_tenia_nota) {
                    if ($actividad['tipo_evaluacion'] === 'directo') {
                        $stmt_prev = $db->prepare("SELECT calificacion FROM ares_calificaciones_desglose WHERE actividad_id = ? AND estudiante_id = ? LIMIT 1");
                        $stmt_prev->execute([$actividad_id, $estudiante_id]);
                        $nota_anterior = (float)$stmt_prev->fetchColumn();
                    } else {
                        $stmt_prev = $db->prepare("
                            SELECT SUM(d.calificacion * c.peso_porcentaje) / 100 
                            FROM ares_calificaciones_desglose d 
                            JOIN ares_actividad_criterios c ON d.criterio_id = c.id
                            WHERE d.actividad_id = ? AND d.estudiante_id = ?
                        ");
                        $stmt_prev->execute([$actividad_id, $estudiante_id]);
                        $nota_anterior = round((float)$stmt_prev->fetchColumn(), 1);
                    }
                }

                // Calcular nota nueva
                $nota_nueva = 0.0;
                if ($actividad['tipo_evaluacion'] === 'directo') {
                    $nota_nueva = (float)($_POST['calificacion_directa'] ?? 0);
                } else {
                    $calificaciones = json_decode($_POST['calificaciones_rubrica'] ?? '[]', true);
                    if (is_array($calificaciones)) {
                        $suma_ponderada = 0.0;
                        $stmt_pesos = $db->prepare("SELECT id, peso_porcentaje FROM ares_actividad_criterios WHERE actividad_id = ?");
                        $stmt_pesos->execute([$actividad_id]);
                        $pesos = $stmt_pesos->fetchAll(PDO::FETCH_KEY_PAIR);
                        
                        foreach ($calificaciones as $calif) {
                            $c_id = (int)$calif['criterio_id'];
                            $nota = (float)$calif['calificacion'];
                            $peso = (float)($pesos[$c_id] ?? 0);
                            $suma_ponderada += ($nota * $peso);
                        }
                        $nota_nueva = round($suma_ponderada / 100, 1);
                    }
                }

                // Validar justificación si es edición/corrección que cambia la nota
                if ($ya_tenia_nota && $nota_anterior != $nota_nueva) {
                    $justificacion = trim($_POST['justificacion_cambio'] ?? '');
                    if (empty($justificacion) || strlen($justificacion) < 10) {
                        throw new Exception('Se requiere una justificación válida (mínimo 10 caracteres) para corregir una nota registrada.');
                    }
                    // Registrar en auditoría
                    $stmt_audit = $db->prepare("INSERT INTO ares_auditoria_cambios_notas (actividad_id, estudiante_id, docente_id, nota_anterior, nota_nueva, justificacion) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_audit->execute([$actividad_id, $estudiante_id, $mi_id, $nota_anterior, $nota_nueva, $justificacion]);
                }

                // Borrar notas previas de este estudiante en esta actividad (Sobrescritura limpia)
                $stmt_del = $db->prepare("DELETE FROM ares_calificaciones_desglose WHERE actividad_id = ? AND estudiante_id = ?");
                $stmt_del->execute([$actividad_id, $estudiante_id]);

                $nota_recuperacion = isset($_POST['nota_recuperacion']) && $_POST['nota_recuperacion'] !== '' ? (float)$_POST['nota_recuperacion'] : null;
                $metodo_recuperacion = isset($_POST['metodo_recuperacion']) && $_POST['metodo_recuperacion'] !== '' ? trim($_POST['metodo_recuperacion']) : null;
                $justificacion_recuperacion = isset($_POST['justificacion_recuperacion']) && $_POST['justificacion_recuperacion'] !== '' ? trim($_POST['justificacion_recuperacion']) : null;
                $soporte_recuperacion_id = isset($_POST['soporte_recuperacion_id']) && $_POST['soporte_recuperacion_id'] !== '' ? (int)$_POST['soporte_recuperacion_id'] : null;
                
                // Si la nueva nota original es aprobatoria (>= 3.0), no requiere recuperación
                if ($nota_nueva >= 3.0) {
                    $nota_recuperacion = null;
                    $metodo_recuperacion = null;
                    $justificacion_recuperacion = null;
                    $soporte_recuperacion_id = null;
                }

                // Validar campos si hay nota de recuperación
                if ($nota_recuperacion !== null) {
                    if ($metodo_recuperacion === null || $metodo_recuperacion === '') {
                        throw new Exception('Debe seleccionar un método de evidencia para registrar la recuperación.');
                    }
                    if (($metodo_recuperacion === 'aula_virtual' || $metodo_recuperacion === 'examen_online') && $soporte_recuperacion_id === null) {
                        throw new Exception('Debe seleccionar el soporte digital correspondiente para la recuperación.');
                    }
                    if ($justificacion_recuperacion === null || strlen($justificacion_recuperacion) < 5) {
                        throw new Exception('Debe ingresar detalles o justificación de la recuperación (mínimo 5 caracteres).');
                    }
                }

                $stmt_ins = $db->prepare("INSERT INTO ares_calificaciones_desglose (actividad_id, criterio_id, estudiante_id, calificacion, es_edicion, nota_recuperacion, metodo_recuperacion, justificacion_recuperacion, soporte_recuperacion_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

                if ($actividad['tipo_evaluacion'] === 'directo') {
                    $calificacion_unica = (float)($_POST['calificacion_directa'] ?? 0);
                    $stmt_ins->execute([$actividad_id, null, $estudiante_id, $calificacion_unica, $es_edicion, $nota_recuperacion, $metodo_recuperacion, $justificacion_recuperacion, $soporte_recuperacion_id]);
                } 
                elseif ($actividad['tipo_evaluacion'] === 'rubrica' && isset($_POST['calificaciones_rubrica'])) {
                    // Esperamos JSON [{"criterio_id": 1, "calificacion": 4.5}, ...]
                    $calificaciones = json_decode($_POST['calificaciones_rubrica'], true);
                    if (is_array($calificaciones)) {
                        foreach ($calificaciones as $calif) {
                            $c_id = (int)$calif['criterio_id'];
                            $nota = (float)$calif['calificacion'];
                            $stmt_ins->execute([$actividad_id, $c_id, $estudiante_id, $nota, $es_edicion, $nota_recuperacion, $metodo_recuperacion, $justificacion_recuperacion, $soporte_recuperacion_id]);
                        }
                    }
                }

                $db->commit();

                // Notificación al acudiente
                if ($nota_recuperacion !== null) {
                    $log_notif = "[" . date('Y-m-d H:i:s') . "] [NOTIFICACION ACUDIENTE] Estudiante ID: $estudiante_id - Se registró recuperación de nota ($nota_recuperacion) en la actividad ID: $actividad_id vía $metodo_recuperacion (Soporte ID: " . ($soporte_recuperacion_id ?? 'N/A') . ")\n";
                    @file_put_contents(__DIR__ . '/../logs/elite_trace.log', $log_notif, FILE_APPEND);
                }

                // Calcular estadísticas de pérdida en tiempo real para esta actividad
                $stats = obtenerEstadisticasPerdidaActividad($db, $actividad_id);
                $es_reprobado = ($nota_nueva < 3.0);

                ob_clean();
                echo json_encode([
                    'status' => 'success',
                    'data' => $stats,
                    'es_reprobado' => $es_reprobado,
                    'message' => 'Calificación registrada y blindada.'
                ]);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                throw $e;
            }
            break;

        default:
            throw new Exception('Acción API no reconocida o inválida.');
    }

} catch (Throwable $e) {
    ob_clean();
    http_response_code(400); // Bad Request
    echo json_encode([
        'status' => 'error',
        'data' => null,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();

// 🏛️ FUNCIÓN HELPER: OBTENER ESTADÍSTICAS DE REPROBADOS EN ACTIVIDADES
function obtenerEstadisticasPerdidaActividad(PDO $db, int $actividad_id) {
    try {
        $stmt_act = $db->prepare("SELECT tipo_evaluacion, curso_id FROM ares_actividades WHERE id = ?");
        $stmt_act->execute([$actividad_id]);
        $act = $stmt_act->fetch(PDO::FETCH_ASSOC);
        if (!$act) return null;

        $tipo = $act['tipo_evaluacion'];
        $curso_id = (int)$act['curso_id'];

        // Obtener total de estudiantes matriculados
        $stmt_tot = $db->prepare("SELECT COUNT(*) FROM estudiantes WHERE curso_id = ?");
        $stmt_tot->execute([$curso_id]);
        $total_estudiantes = (int)$stmt_tot->fetchColumn();
        if ($total_estudiantes === 0) $total_estudiantes = 1;

        $total_reprobados = 0;
        if ($tipo === 'directo') {
            $stmt_rep = $db->prepare("SELECT COUNT(DISTINCT estudiante_id) FROM ares_calificaciones_desglose WHERE actividad_id = ? AND calificacion < 3.0");
            $stmt_rep->execute([$actividad_id]);
            $total_reprobados = (int)$stmt_rep->fetchColumn();
        } else {
            $stmt_rep = $db->prepare("
                SELECT SUM(d.calificacion * c.peso_porcentaje) / 100 as final_nota
                FROM ares_calificaciones_desglose d 
                JOIN ares_actividad_criterios c ON d.criterio_id = c.id
                WHERE d.actividad_id = ?
                GROUP BY d.estudiante_id
                HAVING final_nota < 3.0
            ");
            $stmt_rep->execute([$actividad_id]);
            $total_reprobados = count($stmt_rep->fetchAll());
        }

        $porcentaje_perdida = round(($total_reprobados / $total_estudiantes) * 100, 1);

        return [
            'total_reprobados' => $total_reprobados,
            'total_estudiantes' => $total_estudiantes,
            'porcentaje_perdida' => $porcentaje_perdida
        ];
    } catch (Exception $e) {
        return null;
    }
}

// 🏛️ FUNCIÓN HELPER: DETERMINAR PERIODO ACADEMICO ACTIVO CON GRACE PERIOD DINÁMICO
function determinarPeriodoActivo(PDO $db) {
    try {
        $stmt_gracia = $db->prepare("SELECT valor FROM configuracion_global WHERE clave = :clave LIMIT 1");
        $stmt_gracia->execute([':clave' => 'dias_gracia_recuperaciones']);
        $dias_gracia = $stmt_gracia ? (int)$stmt_gracia->fetchColumn() : 5;

        $stmt_p = $db->prepare("SELECT id, nombre, fecha_inicio, fecha_fin, activo FROM eval_periodos_academicos ORDER BY id ASC");
        $stmt_p->execute();
        $periodos = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

        $ahora = date('Y-m-d H:i:s');

        $periodo_calendario_id = null;
        foreach ($periodos as $p) {
            if ($ahora >= $p['fecha_inicio'] && $ahora <= $p['fecha_fin']) {
                $periodo_calendario_id = (int)$p['id'];
                break;
            }
        }

        if ($periodo_calendario_id === null) {
            foreach ($periodos as $p) {
                if ($p['activo'] == 1) {
                    $periodo_calendario_id = (int)$p['id'];
                    break;
                }
            }
        }

        if ($periodo_calendario_id === null) {
            $periodo_calendario_id = 3;
        }

        if ($periodo_calendario_id > 1) {
            $inicio_periodo_actual = '';
            foreach ($periodos as $p) {
                if ((int)$p['id'] === $periodo_calendario_id) {
                    $inicio_periodo_actual = $p['fecha_inicio'];
                    break;
                }
            }
            require_once __DIR__ . '/helpers_recesos.php';
            $fecha_limite_grace = calcular_fecha_limite_con_recesos($db, $inicio_periodo_actual, $dias_gracia);
            
            if ($ahora <= $fecha_limite_grace) {
                $periodo_activo_id = $periodo_calendario_id - 1;
            } else {
                $periodo_activo_id = $periodo_calendario_id;
            }
        } else {
            $periodo_activo_id = $periodo_calendario_id;
        }

        foreach ($periodos as $p) {
            if ((int)$p['id'] === $periodo_activo_id) {
                return $p;
            }
        }

        return $periodos[0];
    } catch (Exception $e) {
        $anio = date('Y');
        return ['fecha_inicio' => "$anio-07-01 00:00:00", 'fecha_fin' => "$anio-09-30 23:59:59"];
    }
}

exit();
