<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/API_AULA.PHP - MOTOR DE RECURSOS SOBERANOS v1.0
require_once '../db.php';
require_once '../auth.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión expirada']);
    exit();
}

$action = $_GET['action'] ?? '';
$mi_id = (int)$_SESSION['usuario_id'];
$ver_todo = tiene_permiso('matricula') || tiene_permiso('personal');

// 🏛️ DEFENSOR DE SESIÓN ESTUDIANTIL (HEALER EN CALIENTE)
$rol_id_session = (int)($_SESSION['rol_id'] ?? 0);
if ($rol_id_session === 5) {
    if (!isset($_SESSION['estudiante_id']) || empty($_SESSION['estudiante_id'])) {
        $stmt_seg = $db->prepare("SELECT estudiante_id FROM usuarios WHERE id = ?");
        $stmt_seg->execute([$mi_id]);
        $est_id_healed = (int)$stmt_seg->fetchColumn();
        if ($est_id_healed > 0) {
            $_SESSION['estudiante_id'] = $est_id_healed;
        }
    }
    
    if (isset($_SESSION['estudiante_id']) && $_SESSION['estudiante_id'] > 0 && (!isset($_SESSION['curso_id']) || empty($_SESSION['curso_id']))) {
        $stmt_c = $db->prepare("SELECT curso_id FROM estudiantes WHERE id = ?");
        $stmt_c->execute([$_SESSION['estudiante_id']]);
        $cur_id_healed = (int)$stmt_c->fetchColumn();
        if ($cur_id_healed > 0) {
            $_SESSION['curso_id'] = $cur_id_healed;
        }
    }
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

switch ($action) {
    case 'listar':
        $materia_id = (int)($_GET['materia_id'] ?? 0);
        $curso_id = (int)($_GET['curso_id'] ?? 0);
        
        $sql = "SELECT r.*, e.nombre_especialidad, c.nombre_curso, a.ambito as aula_ambito, a.recupera_actividad_id as recupera_actividad_id
                FROM aula_recursos r
                LEFT JOIN especialidades e ON r.especialidad_id = e.id
                LEFT JOIN cursos c ON r.curso_id = c.id
                LEFT JOIN ares_actividades a ON r.actividad_vinculada_id = a.id ";
        $params = [];
        $where_clauses = [];
        
        if (!$ver_todo) {
            $where_clauses[] = "r.docente_id = ?";
            $params[] = $mi_id;
        }
        
        if ($materia_id > 0) {
            $where_clauses[] = "r.especialidad_id = ?";
            $params[] = $materia_id;
        }
        if ($curso_id > 0) {
            $where_clauses[] = "r.curso_id = ?";
            $params[] = $curso_id;
        }
        
        if (count($where_clauses) > 0) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        
        $sql .= " ORDER BY r.fecha_registro DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        break;

    case 'guardar':
        try {
            proteccion_extrema(); // 🛡️ CAPA CSRF: Evitar secuestros de formulario
            $id = (int)($_POST['id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $tipo = $_POST['tipo_recurso'] ?? 'LINK';
            $url = trim($_POST['url_recurso'] ?? '');
            $materia = (int)($_POST['especialidad_id'] ?? 0);
            $curso = (int)($_POST['curso_id'] ?? 0);
            $desc = trim($_POST['descripcion'] ?? '');
            $es_evaluativo = (int)($_POST['es_evaluativo'] ?? 0);
            $aula_ambito = trim($_POST['aula_ambito'] ?? 'estandar');
            $recupera_actividad_id = (int)($_POST['recupera_actividad_id'] ?? 0);
            $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
            $fecha_fin = trim($_POST['fecha_fin'] ?? '');

            if ($mi_id === 0) {
                throw new Exception("Sesión de docente no detectada.");
            }

            // Restricción estricta de tipo
            if (!in_array(strtoupper($tipo), ['PDF', 'DOC', 'DOCX'])) {
                $es_evaluativo = 0;
            }

            // Mapear fechas vacías a NULL, y convertir T de datetime-local a espacio
            $f_inicio_val = $fecha_inicio !== '' ? str_replace('T', ' ', $fecha_inicio) : null;
            $f_fin_val = $fecha_fin !== '' ? str_replace('T', ' ', $fecha_fin) : null;

            // RECUPERACIÓN DE DATOS PREVIOS (Garantía de Edición y Seguridad)
            $docente_propietario = $mi_id;
            $es_evaluativo_actual = 0;
            if ($id > 0) {
                $prev = $db->prepare("SELECT url_recurso, es_evaluativo, docente_id FROM aula_recursos WHERE id = ?");
                $prev->execute([$id]);
                $row_prev = $prev->fetch(PDO::FETCH_ASSOC);
                if (!$row_prev) {
                    throw new Exception("Recurso no encontrado.");
                }
                if (!$ver_todo && (int)$row_prev['docente_id'] !== $mi_id) {
                    throw new Exception("No tiene permisos para modificar este recurso.");
                }
                $url_actual = $row_prev['url_recurso'];
                $es_evaluativo_actual = (int)$row_prev['es_evaluativo'];
                $docente_propietario = (int)$row_prev['docente_id'];

                if ($url === '' && $url_actual) {
                    $url = $url_actual;
                }

                // Restricción: solo el docente propietario puede alternar si es calificable
                if ($es_evaluativo !== $es_evaluativo_actual && $docente_propietario !== $mi_id) {
                    throw new Exception("Solo el docente propietario del recurso puede cambiar el estado calificable.");
                }
            }

            // PROCESAMIENTO DE ARCHIVOS (Soberanía Local y Blindaje RCE)
            if (isset($_FILES['archivo_recurso']) && $_FILES['archivo_recurso']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['archivo_recurso']['tmp_name'];
                $original_name = $_FILES['archivo_recurso']['name'];
                $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                // 🛡️ LISTA BLANCA ESTRICTA DE SEGURIDAD (Evitar RCE de Web Shells)
                $allowed_extensions = ['pdf', 'doc', 'docx'];
                if (!in_array($ext, $allowed_extensions, true)) {
                    throw new Exception("Tipo de archivo no permitido. Solo se aceptan extensiones: " . implode(', ', $allowed_extensions));
                }

                // 🛡️ VALIDACIÓN DE TIPO MIME REAL (Orientada a Objetos compatible con PHP 8.5+)
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmp_name);

                $allowed_mimes = [
                    'application/pdf', 
                    'application/msword', 
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
                if (!in_array($mime, $allowed_mimes, true)) {
                    throw new Exception("Contenido de archivo inválido para la extensión provista.");
                }

                $new_name = "recurso_" . time() . "_" . uniqid() . "." . $ext;
                $dest = "../../uploads/aula/" . $new_name;
                
                if (move_uploaded_file($tmp_name, $dest)) {
                    $url = "uploads/aula/" . $new_name;
                }
            }

            $db->beginTransaction();

            if ($id === 0) {
                $stmt = $db->prepare("INSERT INTO aula_recursos (docente_id, especialidad_id, curso_id, titulo, descripcion, tipo_recurso, url_recurso, es_evaluativo, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$mi_id, $materia, $curso, $titulo, $desc, $tipo, $url, $es_evaluativo, $f_inicio_val, $f_fin_val]);
                $id = (int)$db->lastInsertId();
            } else {
                if ($ver_todo) {
                    $stmt = $db->prepare("UPDATE aula_recursos SET especialidad_id=?, curso_id=?, titulo=?, descripcion=?, tipo_recurso=?, url_recurso=?, es_evaluativo=?, fecha_inicio=?, fecha_fin=? WHERE id=?");
                    $stmt->execute([$materia, $curso, $titulo, $desc, $tipo, $url, $es_evaluativo, $f_inicio_val, $f_fin_val, $id]);
                } else {
                    $stmt = $db->prepare("UPDATE aula_recursos SET especialidad_id=?, curso_id=?, titulo=?, descripcion=?, tipo_recurso=?, url_recurso=?, es_evaluativo=?, fecha_inicio=?, fecha_fin=? WHERE id=? AND docente_id=?");
                    $stmt->execute([$materia, $curso, $titulo, $desc, $tipo, $url, $es_evaluativo, $f_inicio_val, $f_fin_val, $id, $mi_id]);
                }
            }

            // Sincronización en Caliente con Gradebook (Ares Actividades)
            if ($es_evaluativo === 1) {
                $act_stmt = $db->prepare("SELECT actividad_vinculada_id FROM aula_recursos WHERE id = ?");
                $act_stmt->execute([$id]);
                $act_id = $act_stmt->fetchColumn();

                $db_ambito = $aula_ambito === 'recuperacion' ? 'recuperacion' : 'estandar';
                $db_recupera_id = $db_ambito === 'recuperacion' && $recupera_actividad_id > 0 ? $recupera_actividad_id : null;

                if ($act_id) {
                    // Actualizar actividad existente
                    $up_act = $db->prepare("UPDATE ares_actividades SET titulo = ?, curso_id = ?, especialidad_id = ?, ambito = ?, recupera_actividad_id = ? WHERE id = ?");
                    $up_act->execute([$titulo, $curso, $materia, $db_ambito, $db_recupera_id, $act_id]);
                } else {
                    // Crear nueva actividad en el calificador
                    $cn_stmt = $db->prepare("SELECT id FROM ares_clases_nota WHERE estado = :estado ORDER BY id LIMIT 1");
                    $cn_stmt->execute([':estado' => 1]);
                    $clase_nota_id = (int)($cn_stmt->fetchColumn() ?: 1);

                    $ins_act = $db->prepare("INSERT INTO ares_actividades (docente_id, curso_id, especialidad_id, clase_nota_id, titulo, tipo_evaluacion, ambito, recupera_actividad_id) VALUES (?, ?, ?, ?, ?, 'directo', ?, ?)");
                    $ins_act->execute([$mi_id, $curso, $materia, $clase_nota_id, $titulo, $db_ambito, $db_recupera_id]);
                    $act_id = (int)$db->lastInsertId();

                    $up_res = $db->prepare("UPDATE aula_recursos SET actividad_vinculada_id = ? WHERE id = ?");
                    $up_res->execute([$act_id, $id]);
                }
            } else {
                // Si ya no es evaluativo, purgar la actividad asociada en cascada limpia
                $act_stmt = $db->prepare("SELECT actividad_vinculada_id FROM aula_recursos WHERE id = ?");
                $act_stmt->execute([$id]);
                $act_id = $act_stmt->fetchColumn();

                if ($act_id) {
                    $del_calif = $db->prepare("DELETE FROM ares_calificaciones_desglose WHERE actividad_id = ?");
                    $del_calif->execute([$act_id]);

                    $del_crit = $db->prepare("DELETE FROM ares_actividad_criterios WHERE actividad_id = ?");
                    $del_crit->execute([$act_id]);

                    $del_act = $db->prepare("DELETE FROM ares_actividades WHERE id = ?");
                    $del_act->execute([$act_id]);

                    $up_res = $db->prepare("UPDATE aula_recursos SET actividad_vinculada_id = NULL WHERE id = ?");
                    $up_res->execute([$id]);
                }
            }

            $db->commit();
            echo json_encode(['status' => 'success', 'message' => 'Sincronización exitosa con la Bóveda']);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => 'Falla de Bóveda: ' . $e->getMessage()]);
        }
        break;

    case 'eliminar':
        try {
            proteccion_extrema(); // 🛡️ CAPA CSRF: Evitar secuestros de formulario
            $id = (int)($_POST['id'] ?? 0);
            
            $db->beginTransaction();
            
            if ($ver_todo) {
                $act_stmt = $db->prepare("SELECT actividad_vinculada_id FROM aula_recursos WHERE id = ?");
                $act_stmt->execute([$id]);
            } else {
                $act_stmt = $db->prepare("SELECT actividad_vinculada_id FROM aula_recursos WHERE id = ? AND docente_id = ?");
                $act_stmt->execute([$id, $mi_id]);
            }
            $act_id = $act_stmt->fetchColumn();

            // Verificar existencia/permiso
            if ($ver_todo) {
                $check_stmt = $db->prepare("SELECT id FROM aula_recursos WHERE id = ?");
                $check_stmt->execute([$id]);
            } else {
                $check_stmt = $db->prepare("SELECT id FROM aula_recursos WHERE id = ? AND docente_id = ?");
                $check_stmt->execute([$id, $mi_id]);
            }
            if (!$check_stmt->fetchColumn()) {
                throw new Exception("Recurso no encontrado o no tiene permisos.");
            }

            if ($act_id) {
                // Borrado en cascada limpia
                $del_calif = $db->prepare("DELETE FROM ares_calificaciones_desglose WHERE actividad_id = ?");
                $del_calif->execute([$act_id]);

                $del_crit = $db->prepare("DELETE FROM ares_actividad_criterios WHERE actividad_id = ?");
                $del_crit->execute([$act_id]);

                $del_act = $db->prepare("DELETE FROM ares_actividades WHERE id = ?");
                $del_act->execute([$act_id]);
            }

            if ($ver_todo) {
                $stmt = $db->prepare("DELETE FROM aula_recursos WHERE id = ?");
                $stmt->execute([$id]);
            } else {
                $stmt = $db->prepare("DELETE FROM aula_recursos WHERE id = ? AND docente_id = ?");
                $stmt->execute([$id, $mi_id]);
            }

            $db->commit();
            echo json_encode(['status' => 'success', 'message' => 'Recurso y actividad vinculada eliminados']);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => 'Falla de Bóveda: ' . $e->getMessage()]);
        }
        break;

    case 'toggle_evaluativo':
        try {
            proteccion_extrema(); // 🛡️ CAPA CSRF: Evitar secuestros de formulario
            $id = (int)($_POST['id'] ?? 0);
            
            $db->beginTransaction();
            
            $res_stmt = $db->prepare("SELECT es_evaluativo, actividad_vinculada_id, titulo, tipo_recurso, curso_id, especialidad_id, docente_id FROM aula_recursos WHERE id = ?");
            $res_stmt->execute([$id]);
            $res = $res_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$res) {
                throw new Exception("Recurso no encontrado.");
            }
            
            if ((int)$res['docente_id'] !== $mi_id) {
                throw new Exception("Solo el docente propietario del recurso puede cambiar el estado calificable.");
            }
            
            $nuevo_estado = $res['es_evaluativo'] == 1 ? 0 : 1;
            
            if ($nuevo_estado == 1 && !in_array(strtoupper($res['tipo_recurso']), ['PDF', 'DOC', 'DOCX'])) {
                throw new Exception("Solo los archivos PDF o documentos pueden ser marcados como calificables.");
            }
            
            $up_stmt = $db->prepare("UPDATE aula_recursos SET es_evaluativo = ? WHERE id = ? AND docente_id = ?");
            $up_stmt->execute([$nuevo_estado, $id, $mi_id]);
            
            if ($nuevo_estado === 1) {
                $act_id = $res['actividad_vinculada_id'];
                if ($act_id) {
                    $up_act = $db->prepare("UPDATE ares_actividades SET titulo = ?, curso_id = ?, especialidad_id = ? WHERE id = ?");
                    $up_act->execute([$res['titulo'], $res['curso_id'], $res['especialidad_id'], $act_id]);
                } else {
                    $cn_stmt = $db->prepare("SELECT id FROM ares_clases_nota WHERE estado = :estado ORDER BY id LIMIT 1");
                    $cn_stmt->execute([':estado' => 1]);
                    $clase_nota_id = (int)($cn_stmt->fetchColumn() ?: 1);

                    $ins_act = $db->prepare("INSERT INTO ares_actividades (docente_id, curso_id, especialidad_id, clase_nota_id, titulo, tipo_evaluacion) VALUES (?, ?, ?, ?, ?, 'directo')");
                    $ins_act->execute([$mi_id, $res['curso_id'], $res['especialidad_id'], $clase_nota_id, $res['titulo']]);
                    $act_id = (int)$db->lastInsertId();

                    $up_res = $db->prepare("UPDATE aula_recursos SET actividad_vinculada_id = ? WHERE id = ?");
                    $up_res->execute([$act_id, $id]);
                }
            } else {
                $act_id = $res['actividad_vinculada_id'];
                if ($act_id) {
                    $del_calif = $db->prepare("DELETE FROM ares_calificaciones_desglose WHERE actividad_id = ?");
                    $del_calif->execute([$act_id]);

                    $del_crit = $db->prepare("DELETE FROM ares_actividad_criterios WHERE actividad_id = ?");
                    $del_crit->execute([$act_id]);

                    $del_act = $db->prepare("DELETE FROM ares_actividades WHERE id = ?");
                    $del_act->execute([$act_id]);

                    $up_res = $db->prepare("UPDATE aula_recursos SET actividad_vinculada_id = NULL WHERE id = ?");
                    $up_res->execute([$id]);
                }
            }
            
            $db->commit();
            echo json_encode([
                'status' => 'success', 
                'message' => $nuevo_estado === 1 ? 'Recurso vinculado al Calificador como Actividad' : 'Recurso desvinculado del Calificador',
                'es_evaluativo' => $nuevo_estado
            ]);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'toggle_visibilidad':
        $id = (int)($_POST['id'] ?? 0);
        if ($ver_todo) {
            $stmt = $db->prepare("UPDATE aula_recursos SET visibilidad = 1 - visibilidad WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $db->prepare("UPDATE aula_recursos SET visibilidad = 1 - visibilidad WHERE id = ? AND docente_id = ?");
            $stmt->execute([$id, $mi_id]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Visibilidad actualizada']);
        break;
    case 'listar_estudiante':
        try {
            $est_id = (int)($_SESSION['estudiante_id'] ?? 0);
            $cur_id = (int)($_SESSION['curso_id'] ?? 0);
            
            if ($cur_id === 0 && $est_id > 0) {
                // Intento de recuperación de curso_id desde DB si no está en sesión
                $stmt_c = $db->prepare("SELECT curso_id FROM estudiantes WHERE id = ?");
                $stmt_c->execute([$est_id]);
                $cur_id = (int)$stmt_c->fetchColumn();
            }

            if ($est_id === 0 || $cur_id === 0) {
                echo json_encode([]);
                exit();
            }
            
            $sql = "SELECT r.*, e.nombre_especialidad, 
                           IFNULL(a.titulo, r.titulo) as titulo_sincronizado,
                           (SELECT COUNT(*) FROM aula_vistos v WHERE v.recurso_id = r.id AND v.estudiante_id = ?) as visto
                    FROM aula_recursos r
                    LEFT JOIN especialidades e ON r.especialidad_id = e.id
                    LEFT JOIN ares_actividades a ON r.actividad_vinculada_id = a.id
                    WHERE r.curso_id = ? AND r.visibilidad = 1
                      AND (r.fecha_inicio IS NULL OR r.fecha_inicio = '' OR NOW() >= r.fecha_inicio)
                      AND (r.fecha_fin IS NULL OR r.fecha_fin = '' OR NOW() <= r.fecha_fin)
                      AND (
                          a.ambito IS NULL 
                          OR a.ambito != 'recuperacion'
                          OR (
                              a.ambito = 'recuperacion' 
                              AND ? IN (
                                  SELECT cd.estudiante_id 
                                  FROM ares_calificaciones_desglose cd
                                  WHERE cd.actividad_id = a.recupera_actividad_id 
                                    AND cd.calificacion < 3.0
                              )
                          )
                      )
                    ORDER BY r.fecha_registro DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$est_id, $cur_id, $est_id]);
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'resumen_pendientes':
        try {
            $est_id = (int)($_SESSION['estudiante_id'] ?? 0);
            $cur_id = (int)($_SESSION['curso_id'] ?? 0);
            
            if ($cur_id === 0 && $est_id > 0) {
                $stmt_c = $db->prepare("SELECT curso_id FROM estudiantes WHERE id = ?");
                $stmt_c->execute([$est_id]);
                $cur_id = (int)$stmt_c->fetchColumn();
            }
            if ($est_id === 0) exit();
            $sql = "SELECT e.nombre_especialidad as materia, COUNT(*) as total
                    FROM aula_recursos r
                    JOIN especialidades e ON r.especialidad_id = e.id
                    WHERE r.curso_id = ? AND r.visibilidad = 1
                      AND (r.fecha_inicio IS NULL OR r.fecha_inicio = '' OR NOW() >= r.fecha_inicio)
                      AND (r.fecha_fin IS NULL OR r.fecha_fin = '' OR NOW() <= r.fecha_fin)
                      AND r.id NOT IN (SELECT recurso_id FROM aula_vistos WHERE estudiante_id = ?)
                    GROUP BY e.id";
            $stmt = $db->prepare($sql);
            $stmt->execute([$cur_id, $est_id]);
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'marcar_visto':
        try {
            $est_id = (int)($_SESSION['estudiante_id'] ?? 0);
            $recurso_id = (int)($_POST['recurso_id'] ?? 0);
            
            if ($est_id > 0 && $recurso_id > 0) {
                $check = $db->prepare("SELECT COUNT(*) FROM aula_vistos WHERE estudiante_id = ? AND recurso_id = ?");
                $check->execute([$est_id, $recurso_id]);
                if ($check->fetchColumn() == 0) {
                    $stmt = $db->prepare("INSERT INTO aula_vistos (estudiante_id, recurso_id) VALUES (?, ?)");
                    $stmt->execute([$est_id, $recurso_id]);
                }
            }
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}
