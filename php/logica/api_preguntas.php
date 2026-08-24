<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

function procesarImagenesCanvasMetadata(string $metadataJson): string {
    $data = json_decode($metadataJson, true);
    if (!is_array($data) || !isset($data['nodes']) || !is_array($data['nodes'])) {
        return $metadataJson;
    }

    $uploadDir = __DIR__ . '/../../uploads/reactivos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    foreach ($data['nodes'] as &$node) {
        if (isset($node['type']) && $node['type'] === 'image' && isset($node['src'])) {
            $src = $node['src'];
            if (preg_match('/^data:image\/(\w+);base64,(.+)$/is', $src, $matches)) {
                $type = strtolower($matches[1]);
                $base64Data = $matches[2];
                $decodedData = base64_decode($base64Data);

                if ($decodedData === false) {
                    continue;
                }

                $filename = 'img_canvas_' . uniqid() . '_' . time();
                $saved = false;
                $extension = 'webp';

                if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
                    $im = @imagecreatefromstring($decodedData);
                    if ($im !== false) {
                        imagealphablending($im, false);
                        imagesavealpha($im, true);
                        $destFile = $uploadDir . $filename . '.webp';
                        if (@imagewebp($im, $destFile, 80)) {
                            $saved = true;
                        }
                    }
                }

                if (!$saved) {
                    $extension = in_array($type, ['jpeg', 'jpg', 'png', 'gif', 'svg']) ? $type : 'png';
                    $destFile = $uploadDir . $filename . '.' . $extension;
                    if (file_put_contents($destFile, $decodedData) !== false) {
                        $saved = true;
                    }
                }

                if ($saved) {
                    $node['src'] = 'uploads/reactivos/' . $filename . '.' . $extension;
                }
            }
        }
    }
    unset($node);

    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

try {
    if (!tiene_permiso('evaluacion')) {
        throw new Exception('Acceso denegado: No posee credenciales para gestionar el banco de reactivos.');
    }

    $mi_id = (int)$_SESSION['usuario_id'];
    $accion = limpiar_texto_utf8($_POST['accion'] ?? '') ?? limpiar_texto_utf8($_GET['accion'] ?? '') ?? 'listar';

    switch ($accion) {
        case 'guardar':
            proteccion_extrema();
            $id = (int)filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? 0;
            $materia_id = (int)filter_input(INPUT_POST, 'materia_id', FILTER_VALIDATE_INT) ?? 0;
            $tipo_id = (int)filter_input(INPUT_POST, 'tipo_id', FILTER_VALIDATE_INT) ?? 0;
            $enunciado = limpiar_texto_utf8($_POST['enunciado'] ?? '') ?? '';
            $metadata = limpiar_texto_utf8($_POST['metadata'] ?? '') ?? '';
            if (!empty($metadata)) {
                $metadata = procesarImagenesCanvasMetadata($metadata);
            }
            $complejidad = limpiar_texto_utf8($_POST['complejidad'] ?? '') ?? 'Media';
            $titulo = trim(limpiar_texto_utf8($_POST['titulo'] ?? '') ?? '');
            $aprendizaje_id = (int)filter_input(INPUT_POST, 'aprendizaje_id', FILTER_VALIDATE_INT) ?? 0;
            $evidencia_id = (int)filter_input(INPUT_POST, 'evidencia_id', FILTER_VALIDATE_INT) ?? 0;

            if (empty($titulo) || empty($enunciado) || empty($metadata) || !$materia_id || !$tipo_id) {
                throw new Exception('Información incompleta para registrar el reactivo (El título es obligatorio).');
            }

            if ($id > 0) {
                if (tienen_rol(['Administrador', 'Coordinador'])) {
                    $stmt = $db->prepare("UPDATE eval_preguntas SET titulo = ?, materia_id = ?, tipo_id = ?, enunciado = ?, metadata_json = ?, complejidad = ?, aprendizaje_id = ?, evidencia_id = ?, fecha_modificacion = CURRENT_TIMESTAMP WHERE id = ?");
                    $params = [$titulo, $materia_id, $tipo_id, $enunciado, $metadata, $complejidad, $aprendizaje_id ?: null, $evidencia_id ?: null, $id];
                } else {
                    $stmt = $db->prepare("UPDATE eval_preguntas SET titulo = ?, materia_id = ?, tipo_id = ?, enunciado = ?, metadata_json = ?, complejidad = ?, aprendizaje_id = ?, evidencia_id = ?, fecha_modificacion = CURRENT_TIMESTAMP WHERE id = ? AND docente_id = ?");
                    $params = [$titulo, $materia_id, $tipo_id, $enunciado, $metadata, $complejidad, $aprendizaje_id ?: null, $evidencia_id ?: null, $id, $mi_id];
                }
            } else {
                $stmt = $db->prepare("INSERT INTO eval_preguntas (titulo, docente_id, materia_id, tipo_id, enunciado, metadata_json, complejidad, original_author_id, aprendizaje_id, evidencia_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $params = [$titulo, $mi_id, $materia_id, $tipo_id, $enunciado, $metadata, $complejidad, $mi_id, $aprendizaje_id ?: null, $evidencia_id ?: null];
            }

            if ($stmt->execute($params)) {
                echo json_encode(['status' => 'success', 'message' => 'Reactivo sincronizado correctamente en la bóveda.']);
            } else {
                throw new Exception('Error en la persistencia del reactivo.');
            }
            break;

        case 'adaptar':
            proteccion_extrema();
            $id_original = (int)filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? 0;
            if ($id_original <= 0) throw new Exception('ID de reactivo original no válido.');

            $stmt_o = $db->prepare("SELECT * FROM eval_preguntas WHERE id = ?");
            $stmt_o->execute([$id_original]);
            $original = $stmt_o->fetch();

            if (!$original) throw new Exception('El reactivo original ya no existe en la bóveda.');

            $sql = "INSERT INTO eval_preguntas 
                    (titulo, docente_id, materia_id, tipo_id, enunciado, metadata_json, complejidad, parent_id, original_author_id, aprendizaje_id, evidencia_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_a = $db->prepare($sql);
            $res = $stmt_a->execute([
                $original['titulo'],
                $mi_id,
                $original['materia_id'],
                $original['tipo_id'],
                $original['enunciado'],
                $original['metadata_json'],
                $original['complejidad'],
                $original['id'],
                $original['original_author_id'] ?? $original['docente_id'],
                $original['aprendizaje_id'],
                $original['evidencia_id']
            ]);

            if ($res) {
                echo json_encode(['status' => 'success', 'message' => 'Reactivo adaptado a su taller personal con éxito.', 'nuevo_id' => $db->lastInsertId()]);
            } else {
                throw new Exception('Fallo al intentar clonar el reactivo.');
            }
            break;

        case 'eliminar':
            proteccion_extrema();
            $id = (int)filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? 0;
            
            $check = $db->prepare("SELECT COUNT(*) FROM eval_pruebas_items WHERE pregunta_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('No se puede eliminar: El reactivo está vinculado a una prueba activa.');
            }

            if (tienen_rol(['Administrador', 'Coordinador'])) {
                $stmt = $db->prepare("DELETE FROM eval_preguntas WHERE id = ?");
                $res_del = $stmt->execute([$id]);
            } else {
                $stmt = $db->prepare("DELETE FROM eval_preguntas WHERE id = ? AND docente_id = ?");
                $res_del = $stmt->execute([$id, $mi_id]);
            }
            if ($res_del) {
                echo json_encode(['status' => 'success', 'message' => 'Reactivo purgado con éxito.']);
            } else {
                throw new Exception('Fallo al intentar eliminar: Verifique que sea el autor del reactivo.');
            }
            break;

        case 'obtener':
            $id = (int)filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? 0;
            $stmt = $db->prepare("SELECT p.*,
                                    u.nombre as autor_nombre, 
                                    (SELECT nombre FROM usuarios WHERE id = p.original_author_id) as autor_original_nombre,
                                    a.enunciado as dba_enunciado, a.num_dba, a.grado as dba_grado, ar.nombre_area as dba_area, a.area_id as dba_area_id
                                    FROM eval_preguntas p 
                                    JOIN usuarios u ON p.docente_id = u.id 
                                    LEFT JOIN ares_catalogo_aprendizajes a ON p.aprendizaje_id = a.id
                                    LEFT JOIN areas ar ON a.area_id = ar.id
                                    WHERE p.id = ?");
            $stmt->execute([$id]);
            $pregunta = $stmt->fetch();
            if ($pregunta) {
                echo json_encode(['status' => 'success', 'data' => $pregunta]);
            } else {
                throw new Exception('Reactivo no encontrado.');
            }
            break;

        case 'listar':
            $scope = limpiar_texto_utf8($_GET['scope'] ?? '') ?? 'mine';
            $materia_id = (int)filter_input(INPUT_GET, 'materia_id', FILTER_VALIDATE_INT) ?? 0;
            
            $query = "SELECT p.*, 
                             t.nombre as tipo_nombre, u.nombre as autor_nombre, 
                             a.num_dba as dba_num, ar.nombre_area as dba_area_nombre
                      FROM eval_preguntas p 
                      JOIN eval_tipos t ON p.tipo_id = t.id 
                      JOIN usuarios u ON p.docente_id = u.id 
                      LEFT JOIN ares_catalogo_aprendizajes a ON p.aprendizaje_id = a.id
                      LEFT JOIN areas ar ON a.area_id = ar.id
                      WHERE 1=1";
            $params = [];

            if (!tienen_rol(['Administrador', 'Coordinador'])) {
                $query .= " AND (p.docente_id = ? OR p.materia_id IN (SELECT especialidad_id FROM carga_academica WHERE docente_id = ?))";
                $params[] = $mi_id;
                $params[] = $mi_id;
            }

            if ($scope === 'mine') {
                $query .= " AND p.docente_id = ?";
                $params[] = $mi_id;
            } else {
                $query .= " AND p.docente_id != ?";
                $params[] = $mi_id;
                if ($materia_id > 0) {
                    $query .= " AND p.materia_id = ?";
                    $params[] = $materia_id;
                }
            }

            $query .= " ORDER BY p.fecha_creacion DESC";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $preguntas = $stmt->fetchAll();
            
            echo json_encode(['status' => 'success', 'data' => $preguntas]);
            break;

        case 'catalogo_areas':
            $stmt = $db->prepare("SELECT id, nombre_area FROM areas ORDER BY nombre_area ASC");
            $stmt->execute();
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        case 'catalogo_grados':
            $area_id = (int)filter_input(INPUT_GET, 'area_id', FILTER_VALIDATE_INT) ?? 0;
            $stmt = $db->prepare("SELECT DISTINCT grado FROM ares_catalogo_aprendizajes WHERE area_id = ? ORDER BY CAST(grado AS INTEGER) ASC, grado ASC");
            $stmt->execute([$area_id]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
            break;

        case 'catalogo_dbas':
            $area_id = (int)filter_input(INPUT_GET, 'area_id', FILTER_VALIDATE_INT) ?? 0;
            $grado = limpiar_texto_utf8($_GET['grado'] ?? '') ?? '';
            $disciplina = limpiar_texto_utf8($_GET['disciplina'] ?? '') ?? '';

            $sql = "SELECT id, num_dba, enunciado FROM ares_catalogo_aprendizajes WHERE area_id = ? AND grado = ?";
            $params = [$area_id, $grado];

            if ($area_id == 1 && !empty($disciplina)) {
                $sql .= " AND (disciplina = ? OR disciplina = 'general')";
                $params[] = $disciplina;
            }

            $sql .= " ORDER BY num_dba ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        case 'catalogo_evidencias':
            $aprendizaje_id = (int)filter_input(INPUT_GET, 'aprendizaje_id', FILTER_VALIDATE_INT) ?? 0;
            $stmt = $db->prepare("SELECT id, texto as enunciado FROM ares_catalogo_evidencias WHERE aprendizaje_id = ? ORDER BY id ASC");
            $stmt->execute([$aprendizaje_id]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        case 'tipos':
            $stmt = $db->prepare("SELECT * FROM eval_tipos WHERE estado = 1");
            $stmt->execute();
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        default:
            throw new Exception('Operación no reconocida por el Protocolo Ares.');
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>