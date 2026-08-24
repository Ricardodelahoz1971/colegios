<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();

ob_start();
require_once '../db.php';
require_once '../auth.php';

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

$mi_user_id = (int)($_SESSION['usuario_id'] ?? 0);
$resultado = null;

try {
    if (!$mi_user_id || ($_SESSION['rol_id'] != 3 && $_SESSION['rol_nombre'] != 'Estudiante' && !tiene_permiso('evaluacion'))) {
        throw new Exception("Acceso denegado a la bóveda estudiantil.");
    }

    $sql_est = "SELECT id, curso_id FROM estudiantes WHERE id = (SELECT estudiante_id FROM usuarios WHERE id = ?) LIMIT 1";
    $stmt_est = $db->prepare($sql_est);
    $stmt_est->execute([$mi_user_id]);
    $mi_info = $stmt_est->fetch();
    
    if (!$mi_info) throw new Exception("No se encontró perfil de estudiante vinculado.");
    
    $mi_id = (int)$mi_info['id'];
    $mi_curso_id = (int)$mi_info['curso_id'];

    $accion = limpiar_texto_utf8($_GET['accion'] ?? '') ?? limpiar_texto_utf8($_POST['accion'] ?? '') ?? '';

    switch ($accion) {
        case 'listar_mis_examenes':
            $sql = "SELECT a.id as asignacion_id, a.fecha_inicio, a.fecha_fin, a.clave_acceso, 
                    p.id as prueba_id, p.titulo, p.tiempo_limite, e.nombre_especialidad as materia,
                    (SELECT COUNT(*) FROM eval_pruebas_items WHERE prueba_id = p.id) as total_preguntas,
                    (SELECT SUM(peso) FROM eval_pruebas_items WHERE prueba_id = p.id) as puntaje_maximo
                    FROM eval_asignaciones a
                    JOIN eval_pruebas p ON a.prueba_id = p.id
                    JOIN especialidades e ON p.materia_id = e.id
                    WHERE a.curso_id = ?
                    ORDER BY a.fecha_inicio DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$mi_curso_id]);
            $asignaciones = $stmt->fetchAll();

            $ahora = new DateTime();
            $data = [];

            foreach ($asignaciones as $a) {
                $inicio = new DateTime($a['fecha_inicio']);
                $fin = new DateTime($a['fecha_fin']);
                
                $estado = 'PROGRAMADO';
                if ($ahora >= $inicio && $ahora <= $fin) {
                    $estado = 'EN_CURSO';
                } elseif ($ahora > $fin) {
                    $estado = 'FINALIZADO';
                }

                $stmt_ent = $db->prepare("SELECT estado, calificacion_automatica, calificacion_manual FROM eval_respuestas WHERE asignacion_id = ? AND estudiante_id = ?");
                $stmt_ent->execute([$a['asignacion_id'], $mi_id]);
                $ent_row = $stmt_ent->fetch();
                $nota_total = null;

                if ($ent_row) {
                    if ($ent_row['estado'] == 'enviado' || $ent_row['estado'] == 1) {
                        $estado = 'ENTREGADO';
                    } elseif ($ent_row['estado'] == 2) {
                        $estado = 'CALIFICADO';
                        $nota_total = $ent_row['calificacion_automatica'] + $ent_row['calificacion_manual'];
                    }
                }

                $data[] = [
                    'asignacion_id' => $a['asignacion_id'],
                    'titulo' => $a['titulo'],
                    'materia' => $a['materia'],
                    'duracion_minutos' => $a['tiempo_limite'],
                    'total_preguntas' => $a['total_preguntas'],
                    'puntaje_maximo' => (float)($a['puntaje_maximo'] ?? 0),
                    'estado_eval' => $estado,
                    'nota_final' => $nota_total,
                    'requiere_clave' => !empty($a['clave_acceso'])
                ];
            }
            $resultado = ['status' => 'success', 'data' => $data];
            break;

        case 'entregar_examen':
            $csrf_token = limpiar_texto_utf8($_POST['csrf_token'] ?? '');
            if (!$csrf_token || $csrf_token !== $_SESSION['csrf_token']) {
                throw new Exception("Error de seguridad (CSRF).");
            }
            $asig_id = (int)filter_input(INPUT_POST, 'asignacion_id', FILTER_VALIDATE_INT);
            $respuestas_json = limpiar_texto_utf8($_POST['respuestas'] ?? '') ?? '{}';
            $respuestas = json_decode($respuestas_json, true) ?: [];

            if (!isset($_SESSION['ticket_examen']) || (int)($_SESSION['ticket_asignacion_id'] ?? 0) !== $asig_id) {
                throw new Exception("Acceso no autorizado: No se ha iniciado una sesión de examen legítima.");
            }

            $nota_automatica = 0.0;
            $tiene_abiertas = false;

            $stmt = $db->prepare("SELECT prueba_id, fecha_fin FROM eval_asignaciones WHERE id = ?");
            $stmt->execute([$asig_id]);
            $asig = $stmt->fetch();
            if (!$asig) throw new Exception("Asignación no válida.");

            $ahora = new DateTime();
            $fin = new DateTime($asig['fecha_fin']);
            if ($ahora > $fin) {
                throw new Exception("El tiempo del examen ha expirado y no se reciben más respuestas.");
            }

            $prueba_id = $asig['prueba_id'];

            $sql_preguntas = "SELECT p.id, p.tipo_id, p.enunciado, p.metadata_json, i.peso FROM eval_pruebas_items i JOIN eval_preguntas p ON i.pregunta_id = p.id WHERE i.prueba_id = ?";
            $stmt_p = $db->prepare($sql_preguntas);
            $stmt_p->execute([$prueba_id]);
            $preguntas_db = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

            $detalles_respuestas = [];

            foreach ($preguntas_db as $preg) {
                $qid = (int)$preg['id']; $peso = (float)$preg['peso']; $tipo = (int)$preg['tipo_id'];
                if ($tipo == 2) $tiene_abiertas = true;

                $meta = json_decode($preg['metadata_json'], true) ?: [];
                $respuesta_alumno = $respuestas[$qid] ?? null;
                $es_correcta = false;
                $puntaje_item = 0.0;

                if ($respuesta_alumno !== null) {
                    if ($tipo == 1) {
                        if (isset($meta['opciones'])) {
                            foreach ($meta['opciones'] as $i => $opc) {
                                $texto_opc = trim(mb_strtolower((string)($opc['t'] ?? $opc['texto'] ?? '')));
                                $texto_alu = trim(mb_strtolower((string)$respuesta_alumno));
                                
                                if ($texto_alu === "idx_$i" || $texto_opc === $texto_alu) {
                                    if ((isset($opc['c']) && $opc['c'] == true) || (isset($opc['es_correcta']) && $opc['es_correcta'] == true)) {
                                        $es_correcta = true;
                                        $puntaje_item = $peso;
                                    }
                                    break;
                                }
                            }
                        }
                    } 
                    elseif ($tipo == 3) {
                        $correctas_b = array_map(function($p) { return trim(mb_strtolower((string)$p['b'])); }, $meta['pares'] ?? []);
                        $respondidas = is_array($respuesta_alumno) ? $respuesta_alumno : (json_decode($respuesta_alumno, true) ?: []);
                        $aciertos = 0;
                        foreach ($respondidas as $idx => $val) {
                            $val_alu = trim(mb_strtolower((string)$val));
                            if (isset($correctas_b[$idx]) && $val_alu === $correctas_b[$idx]) $aciertos++;
                        }
                        $total_pares = count($correctas_b);
                        if ($total_pares > 0) {
                            $puntaje_item = ($peso * ($aciertos / $total_pares));
                            if ($aciertos === $total_pares) $es_correcta = true;
                        }
                    }
                    elseif ($tipo == 4) {
                        $correctas = $meta['respuestas'] ?? [];
                        if (is_string($correctas)) $correctas = array_map('trim', explode(',', $correctas));
                        $correctas = array_map(function($r) { return trim(mb_strtolower((string)$r)); }, (array)$correctas);
                        $respondidas = is_array($respuesta_alumno) ? $respuesta_alumno : json_decode($respuesta_alumno, true);
                        if ($respondidas === null && is_string($respuesta_alumno)) $respondidas = [$respuesta_alumno];
                        if (!is_array($respondidas)) $respondidas = [];
                        
                        $aciertos = 0;
                        foreach ($respondidas as $idx => $val_alu) {
                            if (isset($correctas[$idx]) && trim(mb_strtolower((string)$val_alu)) === $correctas[$idx]) $aciertos++;
                        }
                        
                        $total_huecos = substr_count($preg['enunciado'], '[?]');
                        if ($total_huecos <= 0) $total_huecos = count($correctas);
                        if ($total_huecos > 0) {
                            $puntaje_item = ($peso * (min($aciertos, $total_huecos) / $total_huecos));
                            if ($aciertos >= $total_huecos) $es_correcta = true;
                        }
                    }
                    elseif ($tipo == 2) {
                        $puntaje_item = 0;
                    }
                }

                $nota_automatica += $puntaje_item;
                $detalles_respuestas[] = [
                    'qid' => $qid,
                    'resp' => is_array($respuesta_alumno) ? json_encode($respuesta_alumno) : (string)$respuesta_alumno,
                    'correcta' => $es_correcta ? 1 : 0,
                    'puntos' => $puntaje_item
                ];
            }

            $nuevo_estado = $tiene_abiertas ? 1 : 2;

            $stmt_check = $db->prepare("SELECT id FROM eval_respuestas WHERE asignacion_id = ? AND estudiante_id = ?");
            $stmt_check->execute([$asig_id, $mi_id]);
            $existe = $stmt_check->fetch();

            if ($existe) {
                $respuesta_id = $existe['id'];
                $stmt_upd = $db->prepare("UPDATE eval_respuestas SET respuestas_json = ?, calificacion_automatica = ?, estado = ?, fecha_entrega = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt_upd->execute([$respuestas_json, $nota_automatica, $nuevo_estado, $respuesta_id]);
                
                $db->prepare("DELETE FROM eval_respuestas_detalles WHERE respuesta_id = ?")->execute([$respuesta_id]);
            } else {
                $stmt_ins = $db->prepare("INSERT INTO eval_respuestas (asignacion_id, estudiante_id, prueba_id, respuestas_json, calificacion_automatica, estado) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_ins->execute([$asig_id, $mi_id, $prueba_id, $respuestas_json, $nota_automatica, $nuevo_estado]);
                $respuesta_id = (int)$db->lastInsertId();
            }

            $stmt_det = $db->prepare("INSERT INTO eval_respuestas_detalles (respuesta_id, pregunta_id, respuesta_alumno, es_correcta, puntaje_obtenido) VALUES (?, ?, ?, ?, ?)");
            foreach ($detalles_respuestas as $det) {
                $stmt_det->execute([
                    $respuesta_id,
                    $det['qid'],
                    $det['resp'],
                    $det['correcta'],
                    $det['puntos']
                ]);
            }

            unset($_SESSION['ticket_examen'], $_SESSION['ticket_asignacion_id']);
            $resultado = ['status' => 'success', 'message' => 'Examen entregado y sellado.'];
            break;

        case 'verificar_acceso':
            $csrf_token = limpiar_texto_utf8($_POST['csrf_token'] ?? '');
            if (!$csrf_token || $csrf_token !== $_SESSION['csrf_token']) {
                throw new Exception("Error de validación (CSRF).");
            }
            $asig_id = (int)filter_input(INPUT_POST, 'asignacion_id', FILTER_VALIDATE_INT);
            $clave = limpiar_texto_utf8($_POST['clave'] ?? '') ?? '';
            $stmt = $db->prepare("SELECT id, clave_acceso, fecha_inicio, fecha_fin FROM eval_asignaciones WHERE id = ?");
            $stmt->execute([$asig_id]);
            $asig = $stmt->fetch();
            if (!$asig) throw new Exception("Asignación no encontrada (ID: $asig_id).");

            $ahora = new DateTime();
            $inicio = new DateTime($asig['fecha_inicio']);
            $fin = new DateTime($asig['fecha_fin']);

            if ($ahora < $inicio) throw new Exception('El examen aún no ha comenzado.');
            if ($ahora > $fin) throw new Exception('El tiempo del examen ha expirado.');
            if (!empty($asig['clave_acceso']) && $asig['clave_acceso'] !== $clave) {
                throw new Exception('Clave de acceso incorrecta.');
            }

            $ticket = bin2hex(random_bytes(16));
            $_SESSION['ticket_examen'] = $ticket;
            $_SESSION['ticket_asignacion_id'] = $asig_id;
            $resultado = ['status' => 'success', 'token' => $ticket];
            break;

        case 'registrar_incidente':
            $csrf_token = limpiar_texto_utf8($_POST['csrf_token'] ?? '');
            if (!$csrf_token || $csrf_token !== $_SESSION['csrf_token']) {
                throw new Exception("CSRF Inválido.");
            }
            $asig_id = (int)filter_input(INPUT_POST, 'asignacion_id', FILTER_VALIDATE_INT);
            $tipo = limpiar_texto_utf8($_POST['tipo'] ?? '') ?? 'desconocido';
            $detalles = limpiar_texto_utf8($_POST['detalles'] ?? '') ?? '';
            $stmt_p = $db->prepare("SELECT prueba_id FROM eval_asignaciones WHERE id = ?");
            $stmt_p->execute([$asig_id]);
            $prueba_id = $stmt_p->fetchColumn() ?: 0;
            $stmt = $db->prepare("INSERT INTO eval_incidentes (asignacion_id, estudiante_id, tipo_evento, detalles, prueba_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$asig_id, $mi_id, $tipo, $detalles, $prueba_id]);
            $resultado = ['status' => 'success'];
            break;

        default:
            throw new Exception('Acción no reconocida.');
    }

} catch (Throwable $e) {
    $resultado = ['status' => 'error', 'message' => $e->getMessage()];
}

ob_clean();
echo json_encode($resultado);
ob_end_flush();