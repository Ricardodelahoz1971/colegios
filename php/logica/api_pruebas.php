<?php
declare(strict_types=1);
// PHP/LOGICA/API_PRUEBAS.PHP - ENSAMBLADOR DE PRUEBAS v1.0
ob_start(); // BLINDAJE: Capturar cualquier advertencia antes de enviar JSON
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';
require_once '../security.php';
guardia_sesion();

try {
    if (!tiene_permiso('evaluacion')) {
        throw new Exception('Acceso denegado: No posee credenciales para gestionar exámenes.');
    }

    $mi_id = (int)$_SESSION['usuario_id'];
    session_write_close();
    $accion = filter_input(INPUT_POST, 'accion') ?? $_GET['accion'] ?? 'listar';

    switch ($accion) {
        case 'guardar':
            proteccion_extrema();
            $id = (int)(filter_input(INPUT_POST, 'id') ?? 0);
            $materia_id = (int)(filter_input(INPUT_POST, 'materia_id') ?? 0);
            $titulo = filter_input(INPUT_POST, 'titulo') ?? '';
            $instrucciones = filter_input(INPUT_POST, 'instrucciones') ?? '';
            $tiempo = (int)(filter_input(INPUT_POST, 'tiempo_limite') ?? 60);

            $modalidad = (int)(filter_input(INPUT_POST, 'modalidad') ?? 1);

            if (empty($titulo) || !$materia_id) {
                throw new Exception('El título y la materia son obligatorios.');
            }

            // Verificar si la prueba ya tiene respuestas (Inmutabilidad)
            if ($id > 0) {
                $check = $db->prepare("SELECT COUNT(*) FROM eval_respuestas WHERE prueba_id = ?");
                $check->execute([$id]);
                if ($check->fetchColumn() > 0) {
                    throw new Exception('Inmutabilidad Activa: No se puede editar una prueba que ya ha sido presentada por estudiantes.');
                }

                $stmt = $db->prepare("UPDATE eval_pruebas SET materia_id = ?, titulo = ?, instrucciones = ?, tiempo_limite = ?, modalidad = ? WHERE id = ? AND docente_id = ?");
                $params = [$materia_id, $titulo, $instrucciones, $tiempo, $modalidad, $id, $mi_id];
            } else {
                $stmt = $db->prepare("INSERT INTO eval_pruebas (docente_id, materia_id, titulo, instrucciones, tiempo_limite, modalidad) VALUES (?, ?, ?, ?, ?, ?)");
                $params = [$mi_id, $materia_id, $titulo, $instrucciones, $tiempo, $modalidad];
            }

            if ($stmt->execute($params)) {
                $new_id = $id > 0 ? $id : $db->lastInsertId();
                echo json_encode(['status' => 'success', 'message' => 'Prueba sincronizada.', 'id' => $new_id]);
            } else {
                throw new Exception('Error en la persistencia de la prueba.');
            }
            break;

        case 'vincular_pregunta':
            proteccion_extrema();
            $prueba_id = (int)filter_input(INPUT_POST, 'prueba_id');
            $pregunta_id = (int)filter_input(INPUT_POST, 'pregunta_id');
            $peso = (float)(filter_input(INPUT_POST, 'peso') ?? 1.0);
            $vincular = (filter_input(INPUT_POST, 'vincular') === 'true');

            if ($vincular) {
                // Agregar
                $stmt = $db->prepare("INSERT INTO eval_pruebas_items (prueba_id, pregunta_id, peso) VALUES (?, ?, ?)");
                $stmt->execute([$prueba_id, $pregunta_id, $peso]);
            } else {
                // Quitar
                $stmt = $db->prepare("DELETE FROM eval_pruebas_items WHERE prueba_id = ? AND pregunta_id = ?");
                $stmt->execute([$prueba_id, $pregunta_id]);
            }
            echo json_encode(['status' => 'success']);
            break;

        case 'actualizar_items':
            proteccion_extrema();
            // Actualización masiva de pesos y orden
            $prueba_id = (int)filter_input(INPUT_POST, 'prueba_id');
            $items = json_decode(filter_input(INPUT_POST, 'items'), true); // [{pregunta_id, peso, orden}, ...]

            $db->beginTransaction();
            try {
                foreach ($items as $item) {
                    $stmt = $db->prepare("UPDATE eval_pruebas_items SET peso = ?, orden = ? WHERE prueba_id = ? AND pregunta_id = ?");
                    $stmt->execute([$item['peso'], $item['orden'], $prueba_id, $item['pregunta_id']]);
                }
                $db->commit();
                echo json_encode(['status' => 'success', 'message' => 'Estructura de la prueba actualizada.']);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'listar':
            $sql_listar = "SELECT p.*, e.nombre_especialidad as materia_nombre,
                                (SELECT COUNT(*) FROM eval_pruebas_items WHERE prueba_id = p.id) as total_preguntas,
                                (SELECT COUNT(*) FROM eval_asignaciones WHERE prueba_id = p.id) as total_asignaciones
                                FROM eval_pruebas p
                                JOIN especialidades e ON p.materia_id = e.id
                                WHERE p.docente_id = ?
                                ORDER BY p.fecha_creacion DESC";
            $stmt = $db->prepare($sql_listar);
            $stmt->execute([$mi_id]);
            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        case 'obtener':
            $id = (int)$_GET['id'];
            // Seguridad: Solo el autor puede obtener el detalle completo
            $stmt = $db->prepare("SELECT * FROM eval_pruebas WHERE id = ? AND docente_id = ?");
            $stmt->execute([$id, $mi_id]);
            $prueba = $stmt->fetch();
            
            if ($prueba) {
                // Obtener preguntas vinculadas
                $sql_items = "SELECT p.id, p.enunciado, t.nombre as tipo_nombre, i.peso, i.orden,
                                        a.num_dba as dba_num, ar.nombre_area as dba_area_nombre
                                       FROM eval_preguntas p
                                       JOIN eval_tipos t ON p.tipo_id = t.id
                                       JOIN eval_pruebas_items i ON p.id = i.pregunta_id
                                       LEFT JOIN ares_catalogo_aprendizajes a ON p.aprendizaje_id = a.id
                                       LEFT JOIN areas ar ON a.area_id = ar.id
                                       WHERE i.prueba_id = ?
                                       ORDER BY i.orden ASC";
                $stmt_i = $db->prepare($sql_items);
                $stmt_i->execute([$id]);
                $prueba['items'] = $stmt_i->fetchAll();
                
                echo json_encode(['status' => 'success', 'data' => $prueba]);
            } else {
                throw new Exception('Prueba no encontrada.');
            }
            break;

        case 'eliminar':
        case 'eliminar_prueba':
            proteccion_extrema();
            $id = (int)filter_input(INPUT_POST, 'id');

            // Validar existencia y permisos
            $es_admin = tienen_rol(['Administrador', 'Coordinador']);
            if (!$es_admin) {
                $stmt_check = $db->prepare("SELECT 1 FROM eval_pruebas WHERE id = ? AND docente_id = ?");
                $stmt_check->execute([$id, $mi_id]);
                if (!$stmt_check->fetch()) {
                    throw new Exception("Acceso denegado: No es el autor de esta prueba o la prueba no existe.");
                }
            } else {
                $stmt_check = $db->prepare("SELECT 1 FROM eval_pruebas WHERE id = ?");
                $stmt_check->execute([$id]);
                if (!$stmt_check->fetch()) {
                    throw new Exception("La prueba no existe.");
                }
            }

            // Verificar inmutabilidad
            $check = $db->prepare("SELECT COUNT(*) FROM eval_respuestas WHERE prueba_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('No se puede eliminar una prueba con respuestas o calificaciones registradas.');
            }

            $db->beginTransaction();
            try {
                $db->prepare("DELETE FROM eval_asignaciones WHERE prueba_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM eval_pruebas_items WHERE prueba_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM eval_pruebas WHERE id = ?")->execute([$id]);
                $db->commit();
                echo json_encode(['status' => 'success', 'message' => 'Prueba eliminada del sistema.']);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'guardar_asignacion':
            proteccion_extrema();
            $id = (int)(filter_input(INPUT_POST, 'id') ?? 0);
            $prueba_id = (int)filter_input(INPUT_POST, 'prueba_id');
            $curso_id = (int)filter_input(INPUT_POST, 'curso_id');
            $inicio = filter_input(INPUT_POST, 'fecha_inicio');
            $fin = filter_input(INPUT_POST, 'fecha_fin');
            $clave = filter_input(INPUT_POST, 'clave') ?? '';
            $intentos = (int)(filter_input(INPUT_POST, 'intentos') ?? 1);
            $resultados = (int)(filter_input(INPUT_POST, 'resultados') ?? 0);
            $tipo_navegacion = filter_input(INPUT_POST, 'tipo_navegacion') ?? 'libre';
            $ambito = trim(filter_input(INPUT_POST, 'ambito') ?? 'estandar');
            $recupera_actividad_id = (int)(filter_input(INPUT_POST, 'recupera_actividad_id') ?? 0);
            
            $db_ambito = $ambito === 'recuperacion' ? 'recuperacion' : 'estandar';
            $db_recupera_id = $db_ambito === 'recuperacion' && $recupera_actividad_id > 0 ? $recupera_actividad_id : null;

            if ($id > 0) {
                $stmt = $db->prepare("UPDATE eval_asignaciones SET prueba_id = ?, curso_id = ?, fecha_inicio = ?, fecha_fin = ?, clave_acceso = ?, intentos_permitidos = ?, mostrar_resultados = ?, tipo_navegacion = ?, ambito = ?, recupera_actividad_id = ? WHERE id = ? AND docente_id = ?");
                $params = [$prueba_id, $curso_id, $inicio, $fin, $clave, $intentos, $resultados, $tipo_navegacion, $db_ambito, $db_recupera_id, $id, $mi_id];
            } else {
                $stmt = $db->prepare("INSERT INTO eval_asignaciones (prueba_id, curso_id, docente_id, fecha_inicio, fecha_fin, clave_acceso, intentos_permitidos, mostrar_resultados, tipo_navegacion, ambito, recupera_actividad_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $params = [$prueba_id, $curso_id, $mi_id, $inicio, $fin, $clave, $intentos, $resultados, $tipo_navegacion, $db_ambito, $db_recupera_id];
            }

            if ($stmt->execute($params)) {
                echo json_encode(['status' => 'success', 'message' => 'Examen programado correctamente.']);
            } else {
                throw new Exception('Fallo al programar la aplicación del examen.');
            }
            break;

        case 'listar_asignaciones':
            $sql_asignaciones = "SELECT a.*, p.titulo as prueba_titulo, e.nombre_especialidad as materia_nombre, 
                                g.nombre_curso as curso_nombre
                                FROM eval_asignaciones a
                                JOIN eval_pruebas p ON a.prueba_id = p.id
                                JOIN especialidades e ON p.materia_id = e.id
                                JOIN cursos g ON a.curso_id = g.id
                                WHERE a.docente_id = ?
                                ORDER BY a.fecha_inicio DESC";
            $stmt = $db->prepare($sql_asignaciones);
            $stmt->execute([$mi_id]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        case 'obtener_asignacion':
            $id = (int)$_GET['id'];
            $stmt = $db->prepare("SELECT * FROM eval_asignaciones WHERE id = ? AND docente_id = ?");
            $stmt->execute([$id, $mi_id]);
            $asig = $stmt->fetch();
            if ($asig) {
                echo json_encode(['status' => 'success', 'data' => $asig]);
            } else {
                throw new Exception('Asignación no encontrada.');
            }
            break;

        case 'eliminar_asignacion':
            proteccion_extrema();
            $id = (int)filter_input(INPUT_POST, 'id');
            $db->prepare("DELETE FROM eval_asignaciones WHERE id = ? AND docente_id = ?")->execute([$id, $mi_id]);
            echo json_encode(['status' => 'success', 'message' => 'Programación cancelada.']);
            break;

        case 'duplicar_prueba':
            proteccion_extrema();
            $id = (int)filter_input(INPUT_POST, 'id');
            
            $db->beginTransaction();
            try {
                // 1. Obtener la prueba original
                $stmt_orig = $db->prepare("SELECT * FROM eval_pruebas WHERE id = ?");
                $stmt_orig->execute([$id]);
                $orig = $stmt_orig->fetch();
                
                if (!$orig) throw new Exception("La prueba original no existe.");

                // 2. Crear la nueva cabecera (título con sufijo [COPIA])
                $nuevo_titulo = $orig['titulo'] . " [COPIA]";
                $stmt_ins = $db->prepare("INSERT INTO eval_pruebas (docente_id, materia_id, titulo, instrucciones, tiempo_limite, modalidad) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_ins->execute([$mi_id, $orig['materia_id'], $nuevo_titulo, $orig['instrucciones'], $orig['tiempo_limite'], $orig['modalidad']]);
                $new_id = (int)$db->lastInsertId();

                // 3. Clonar los reactivos (ítems) en el mismo orden y con el mismo peso
                $stmt_items = $db->prepare("INSERT INTO eval_pruebas_items (prueba_id, pregunta_id, peso, orden) 
                                           SELECT ?, pregunta_id, peso, orden FROM eval_pruebas_items WHERE prueba_id = ?");
                $stmt_items->execute([$new_id, $id]);

                $db->commit();
                echo json_encode(['status' => 'success', 'message' => 'Prueba duplicada con éxito.', 'new_id' => $new_id]);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                throw $e;
            }
            break;

        case 'eliminar_prueba':
            proteccion_extrema();
            $id = (int)filter_input(INPUT_POST, 'id');
            
            $db->beginTransaction();
            try {
                // 1. Obtener asignaciones
                $stmt_asig = $db->prepare("SELECT id FROM eval_asignaciones WHERE prueba_id = ?");
                $stmt_asig->execute([$id]);
                $asigs = $stmt_asig->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($asigs)) {
                    $in_query = implode(',', array_fill(0, count($asigs), '?'));
                    $sql_resp = "DELETE FROM eval_respuestas WHERE asignacion_id IN ($in_query)";
                    $sql_inci = "DELETE FROM eval_incidentes WHERE asignacion_id IN ($in_query)";
                    
                    $db->prepare($sql_resp)->execute($asigs);
                    $db->prepare($sql_inci)->execute($asigs);
                }
                
                $db->prepare("DELETE FROM eval_asignaciones WHERE prueba_id = ? AND docente_id = ?")->execute([$id, $mi_id]);
                $db->prepare("DELETE FROM eval_pruebas_items WHERE prueba_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM eval_pruebas WHERE id = ? AND docente_id = ?")->execute([$id, $mi_id]);
                
                $db->commit();
                echo json_encode(['status' => 'success', 'message' => 'Prueba y dependencias purgadas con éxito.']);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                throw $e;
            }
            break;
        case 'listar_asignaciones_profesor':
            $sql = "SELECT a.id, a.prueba_id, a.fecha_inicio, a.fecha_fin, p.titulo, p.modalidad, e.nombre_especialidad as materia, c.nombre_curso as curso_nombre,
                    (SELECT COUNT(*) FROM eval_respuestas WHERE asignacion_id = a.id AND estado IN (2, 3)) as total_entregas,
                    (SELECT COUNT(*) FROM estudiantes WHERE curso_id = a.curso_id) as total_estudiantes
                    FROM eval_asignaciones a 
                    JOIN eval_pruebas p ON a.prueba_id = p.id 
                    JOIN especialidades e ON p.materia_id = e.id 
                    JOIN cursos c ON a.curso_id = c.id
                    WHERE a.docente_id = ?
                    ORDER BY a.id DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$mi_id]);
            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        case 'listar_entregas_asignacion':
            $asig_id = (int)filter_input(INPUT_POST, 'asignacion_id');
            $prueba_id = (int)filter_input(INPUT_POST, 'prueba_id');
            
            // Traer estructura
            $stmt_p = $db->prepare("SELECT p.id, p.enunciado, p.tipo_id, p.metadata_json, i.peso 
                                    FROM eval_pruebas_items i 
                                    JOIN eval_preguntas p ON i.pregunta_id = p.id 
                                    WHERE i.prueba_id = ? ORDER BY i.orden ASC");
            $stmt_p->execute([$prueba_id]);
            $estructura = $stmt_p->fetchAll();

            // Traer entregas
            $stmt = $db->prepare("SELECT 
                                     r.id as id,
                                     e.id as estudiante_id,
                                     COALESCE(r.estado, 0) as estado,
                                     COALESCE(r.calificacion_automatica, 0.0) as calificacion_automatica,
                                     COALESCE(r.calificacion_manual, 0.0) as calificacion_manual,
                                     r.calificacion_recuperacion as calificacion_recuperacion,
                                     CONCAT(e.nombre, ' ', COALESCE(e.apellido, '')) as estudiante_nombre,
                                     COALESCE((SELECT COUNT(*) FROM eval_incidentes WHERE asignacion_id = a.id AND estudiante_id = e.id), 0) as incidentes
                                 FROM estudiantes e
                                 JOIN eval_asignaciones a ON a.id = ? AND a.curso_id = e.curso_id
                                 LEFT JOIN eval_respuestas r ON r.asignacion_id = a.id AND r.estudiante_id = e.id
                                 ORDER BY e.nombre ASC, e.apellido ASC");
            $stmt->execute([$asig_id]);
            $entregas = $stmt->fetchAll();

            // Aplicar cálculo dinámico de políticas de recuperación en servidor
            $politica = CalculadoraNotas::obtenerPoliticaRecuperacion($db);
            $escala = CalculadoraNotas::obtenerEscala($db);
            $nota_aprobacion = $escala['nota_aprobacion'];

            foreach ($entregas as &$ent) {
                if ($ent['id']) {
                    $original = (float)$ent['calificacion_automatica'] + (float)$ent['calificacion_manual'];
                    $recup = $ent['calificacion_recuperacion'] !== null ? (float)$ent['calificacion_recuperacion'] : null;
                    $ent['calificacion_final_calculada'] = CalculadoraNotas::aplicarPoliticaRecuperacion($original, $recup, $politica, $nota_aprobacion);
                } else {
                    $ent['calificacion_final_calculada'] = null;
                }
            }
            
            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $entregas, 'estructura_prueba' => $estructura]);
            break;

        case 'obtener_detalle_entrega':
            $entrega_id = (int)filter_input(INPUT_POST, 'entrega_id');
            $asig_id = (int)filter_input(INPUT_POST, 'asignacion_id');
            $est_id = (int)filter_input(INPUT_POST, 'estudiante_id');

            // 🛡️ CONTROL DE ACCESO ANTICORRUPCIÓN / ANTI-LEAK: Verificar que el docente sea propietario de la asignación
            $stmt_prop = $db->prepare("SELECT docente_id FROM eval_asignaciones WHERE id = ?");
            $stmt_prop->execute([$asig_id]);
            $owner_id = (int)$stmt_prop->fetchColumn();

            if ($owner_id !== $mi_id && (int)($_SESSION['rol_id'] ?? 0) !== 1) {
                throw new Exception("Acceso denegado: No posee permisos sobre esta entrega de examen.");
            }

            $stmt = $db->prepare("SELECT * FROM eval_respuestas WHERE id = ?");
            $stmt->execute([$entrega_id]);
            $entrega = $stmt->fetch();

            // Sincronización manual: consultar detalles de cada ítem respondido para re-rellenar inputs
            $stmt_det = $db->prepare("SELECT pregunta_id, respuesta_alumno, es_correcta, puntaje_obtenido FROM eval_respuestas_detalles WHERE respuesta_id = ?");
            $stmt_det->execute([$entrega_id]);
            $detalles = $stmt_det->fetchAll();

            $stmt_inc = $db->prepare("SELECT tipo_evento, detalles, fecha_hora FROM eval_incidentes WHERE asignacion_id = ? AND estudiante_id = ? ORDER BY fecha_hora DESC");
            $stmt_inc->execute([$asig_id, $est_id]);
            $incidentes = $stmt_inc->fetchAll();

            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $entrega, 'detalles' => $detalles, 'incidentes' => $incidentes]);
            break;

        case 'guardar_calificacion_final':
            proteccion_extrema();
            $entrega_id = (int)filter_input(INPUT_POST, 'entrega_id');
            $nota_manual = (float)filter_input(INPUT_POST, 'calificacion_manual');
            $nota_auto = (null !== filter_input(INPUT_POST, 'calificacion_auto')) ? (float)filter_input(INPUT_POST, 'calificacion_auto') : null;
            $respuestas_json = filter_input(INPUT_POST, 'respuestas_json') ?? null;
            
            $calificacion_recuperacion = (null !== filter_input(INPUT_POST, 'calificacion_recuperacion')) && filter_input(INPUT_POST, 'calificacion_recuperacion') !== '' ? (float)filter_input(INPUT_POST, 'calificacion_recuperacion') : null;

            // Autodetectar si la asignación es de recuperación para guardar la nota obtenida como recuperación
            $stmt_asig_info = $db->prepare("SELECT r.asignacion_id, r.calificacion_automatica FROM eval_respuestas r WHERE r.id = ?");
            $stmt_asig_info->execute([$entrega_id]);
            $resp_info = $stmt_asig_info->fetch();
            if ($resp_info) {
                $asig_id = (int)$resp_info['asignacion_id'];
                
                // 🛡️ CONTROL DE ACCESO ANTICORRUPCIÓN / ANTI-IDOR: Validar que el docente autenticado sea propietario
                $stmt_prop = $db->prepare("SELECT docente_id, ambito FROM eval_asignaciones WHERE id = ?");
                $stmt_prop->execute([$asig_id]);
                $asig_data = $stmt_prop->fetch();
                $owner_id = (int)($asig_data['docente_id'] ?? 0);
                $ambito_asig = $asig_data['ambito'] ?? 'estandar';

                if ($owner_id !== $mi_id && (int)($_SESSION['rol_id'] ?? 0) !== 1) {
                    throw new Exception("Acceso denegado: No está autorizado para calificar esta entrega.");
                }
                
                if ($ambito_asig === 'recuperacion' && $calificacion_recuperacion === null) {
                    $auto_val = $nota_auto !== null ? $nota_auto : (float)$resp_info['calificacion_automatica'];
                    $calificacion_recuperacion = $nota_manual + $auto_val;
                }
            } else {
                throw new Exception("Entrega no encontrada.");
            }

            // Obtener el estado actual para saber si es recalificación (estado 2 o 3)
            $stmt_estado = $db->prepare("SELECT estado FROM eval_respuestas WHERE id = ?");
            $stmt_estado->execute([$entrega_id]);
            $estado_actual = (int)($stmt_estado->fetchColumn() ?? 0);

            $nuevo_estado = ($estado_actual === 2 || $estado_actual === 3) ? 3 : 2;

            // 1. Guardar calificación y marcar entrega como PUBLICADA o RE-CALIFICADA
            if ($nota_auto !== null) {
                if ($respuestas_json !== null) {
                    $stmt = $db->prepare("UPDATE eval_respuestas SET calificacion_manual = ?, calificacion_automatica = ?, respuestas_json = ?, estado = ?, calificacion_recuperacion = ? WHERE id = ?");
                    $ejecutado = $stmt->execute([$nota_manual, $nota_auto, $respuestas_json, $nuevo_estado, $calificacion_recuperacion, $entrega_id]);
                } else {
                    $stmt = $db->prepare("UPDATE eval_respuestas SET calificacion_manual = ?, calificacion_automatica = ?, estado = ?, calificacion_recuperacion = ? WHERE id = ?");
                    $ejecutado = $stmt->execute([$nota_manual, $nota_auto, $nuevo_estado, $calificacion_recuperacion, $entrega_id]);
                }
            } else {
                if ($respuestas_json !== null) {
                    $stmt = $db->prepare("UPDATE eval_respuestas SET calificacion_manual = ?, respuestas_json = ?, estado = ?, calificacion_recuperacion = ? WHERE id = ?");
                    $ejecutado = $stmt->execute([$nota_manual, $respuestas_json, $nuevo_estado, $calificacion_recuperacion, $entrega_id]);
                } else {
                    $stmt = $db->prepare("UPDATE eval_respuestas SET calificacion_manual = ?, estado = ?, calificacion_recuperacion = ? WHERE id = ?");
                    $ejecutado = $stmt->execute([$nota_manual, $nuevo_estado, $calificacion_recuperacion, $entrega_id]);
                }
            }

            if ($ejecutado) {
                // 1.1 Persistir los detalles de calificaciones manuales por reactivo si vienen provistos
                $detalles_manuales_raw = filter_input(INPUT_POST, 'detalles_manuales') ?? null;
                if ($detalles_manuales_raw !== null) {
                    $detalles_manuales = json_decode($detalles_manuales_raw, true) ?: [];
                    foreach ($detalles_manuales as $det) {
                        $qid = (int)$det['pregunta_id'];
                        $pts = (float)$det['puntaje_obtenido'];
                        
                        // Verificar si ya existe para no destruir la respuesta en texto del alumno
                        $check_stmt = $db->prepare("SELECT id FROM eval_respuestas_detalles WHERE respuesta_id = ? AND pregunta_id = ?");
                        $check_stmt->execute([$entrega_id, $qid]);
                        $existe_det = $check_stmt->fetchColumn();
                        
                        if ($existe_det) {
                            $stmt_upd_det = $db->prepare("UPDATE eval_respuestas_detalles SET puntaje_obtenido = ? WHERE id = ?");
                            $stmt_upd_det->execute([$pts, $existe_det]);
                        } else {
                            $stmt_ins_det = $db->prepare("INSERT INTO eval_respuestas_detalles (respuesta_id, pregunta_id, respuesta_alumno, es_correcta, puntaje_obtenido) VALUES (?, ?, '', 0, ?)");
                            $stmt_ins_det->execute([$entrega_id, $qid, $pts]);
                        }
                    }
                }

                // 2. LOGICA DE CIERRE AUTOMÁTICO DE MISIÓN
                // Obtener asignacion_id de esta entrega
                $stmt_asig = $db->prepare("SELECT asignacion_id FROM eval_respuestas WHERE id = ?");
                $stmt_asig->execute([$entrega_id]);
                $asig_info = $stmt_asig->fetch();
                
                if ($asig_info) {
                    $asig_id = $asig_info['asignacion_id'];
                    
                    // Contar cuántos estudiantes del curso faltan por calificar
                    $sql_check = "SELECT 
                        (SELECT COUNT(*) FROM estudiantes WHERE curso_id = a.curso_id) as total_esperados,
                        (SELECT COUNT(*) FROM eval_respuestas WHERE asignacion_id = a.id AND estado IN (2, 3)) as total_calificados
                        FROM eval_asignaciones a WHERE a.id = ?";
                    $stmt_check = $db->prepare($sql_check);
                    $stmt_check->execute([$asig_id]);
                    $progress = $stmt_check->fetch();
                    
                    if ($progress && $progress['total_calificados'] >= $progress['total_esperados']) {
                        // Cierre de Misión: Marcar asignación como Finalizada
                        $db->prepare("UPDATE eval_asignaciones SET estado = 2 WHERE id = ?")->execute([$asig_id]);
                    }
                }

                // Obtener estadísticas de pérdida en tiempo real para esta asignación
                $stats = obtenerEstadisticasPerdidaAsignacion($db, (int)$asig_id);

                // Determinar si la nota final de esta entrega es reprobatoria
                $stmt_final_check = $db->prepare("SELECT calificacion_automatica, calificacion_manual FROM eval_respuestas WHERE id = ?");
                $stmt_final_check->execute([$entrega_id]);
                $final_res = $stmt_final_check->fetch();
                $es_reprobado = false;
                if ($final_res) {
                    $final_nota = (float)$final_res['calificacion_automatica'] + (float)$final_res['calificacion_manual'];
                    if ($final_nota < 3.0) {
                        $es_reprobado = true;
                    }
                }

                ob_clean();
                echo json_encode([
                    'status' => 'success',
                    'data' => $stats,
                    'es_reprobado' => $es_reprobado,
                    'message' => 'Nota publicada exitosamente.'
                ]);
            } else {
                throw new Exception('Error al publicar la nota.');
            }
            break;

        case 'recalibrar_nota':
            $entrega_id = (int)filter_input(INPUT_POST, 'entrega_id');
            $nota_auto = (float)filter_input(INPUT_POST, 'nota_auto');
            $stmt = $db->prepare("UPDATE eval_respuestas SET calificacion_automatica = ? WHERE id = ?");
            if ($stmt->execute([$nota_auto, $entrega_id])) {
                ob_clean();
                echo json_encode(['status' => 'success', 'message' => 'Nota sincronizada.']);
            }
            break;

        case 'identificar_estudiante_qr':
            $est_id = (int)filter_input(INPUT_POST, 'estudiante_id');
            $asig_id = (int)filter_input(INPUT_POST, 'asignacion_id');
            
            // LOG DE DEBUG TEMPORAL
            // error_log("ARES_DEBUG: Escaneando Est: $est_id en Asig: $asig_id");

            // 1. Obtener datos del estudiante validando curso_id en la tabla estudiantes
            $stmt = $db->prepare("SELECT e.id as estudiante_id, e.nombre, e.apellido, a.prueba_id, a.id as asignacion_id
                                 FROM estudiantes e
                                 JOIN eval_asignaciones a ON a.id = ?
                                 WHERE e.id = ? AND e.curso_id = a.curso_id");
            $stmt->execute([$asig_id, $est_id]);
            $est = $stmt->fetch();

            if (!$est) {
                throw new Exception('Estudiante no encontrado o no pertenece a este curso.');
            }
            
            // Concatenar nombre y apellido si es necesario
            $est['nombre'] = $est['nombre'] . ' ' . ($est['apellido'] ?? '');

            // 2. Verificar si ya existe una entrega para esta asignación
            $stmt_r = $db->prepare("SELECT id, estado, calificacion_automatica, calificacion_manual, respuestas_json FROM eval_respuestas WHERE asignacion_id = ? AND estudiante_id = ?");
            $stmt_r->execute([$asig_id, $est_id]);
            $entrega = $stmt_r->fetch();

            if (!$entrega) {
                // Crear entrega inicial si no existe (satisfaciendo pregunta_id NOT NULL con un 0 técnico)
                $stmt_ins = $db->prepare("INSERT INTO eval_respuestas (asignacion_id, estudiante_id, prueba_id, pregunta_id, estado, calificacion_automatica, calificacion_manual, respuestas_json) VALUES (?, ?, ?, 0, 1, 0, 0, '{}')");
                $stmt_ins->execute([$asig_id, $est_id, $est['prueba_id']]);
                $id_entrega = (int)$db->lastInsertId();
                $est['estado'] = 1;
                $est['calificacion_automatica'] = 0.0;
                $est['calificacion_manual'] = 0.0;
                $est['respuestas_json'] = '{}';
            } else {
                $id_entrega = (int)$entrega['id'];
                $est['estado'] = (int)$entrega['estado'];
                $est['calificacion_automatica'] = (float)$entrega['calificacion_automatica'];
                $est['calificacion_manual'] = (float)$entrega['calificacion_manual'];
                $est['respuestas_json'] = $entrega['respuestas_json'];
            }

            $est['id_entrega'] = $id_entrega;

            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $est]);
            break;

        default:
            throw new Exception('Acción Ares no reconocida.');
    }

} catch (Throwable $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e. ' | ' . $e->getMessage()]);
}
ob_end_flush();

// 🏛️ FUNCIÓN HELPER: OBTENER ESTADÍSTICAS DE REPROBADOS EN EXÁMENES
function obtenerEstadisticasPerdidaAsignacion(PDO $db, int $asignacion_id) {
    try {
        $stmt_asig = $db->prepare("SELECT curso_id FROM eval_asignaciones WHERE id = ?");
        $stmt_asig->execute([$asignacion_id]);
        $curso_id = (int)$stmt_asig->fetchColumn();
        if (!$curso_id) return null;

        // Obtener total de estudiantes matriculados
        $stmt_tot = $db->prepare("SELECT COUNT(*) FROM estudiantes WHERE curso_id = ?");
        $stmt_tot->execute([$curso_id]);
        $total_estudiantes = (int)$stmt_tot->fetchColumn();
        if ($total_estudiantes === 0) $total_estudiantes = 1;

        // Contar reprobados
        $stmt_rep = $db->prepare("
            SELECT COUNT(*) FROM eval_respuestas 
            WHERE asignacion_id = ? AND estado IN (2, 3) AND (calificacion_automatica + calificacion_manual) < 3.0
        ");
        $stmt_rep->execute([$asignacion_id]);
        $total_reprobados = (int)$stmt_rep->fetchColumn();

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

exit();
?>

