<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/security.php';

header('Content-Type: application/json; charset=utf-8');

$log_file = __DIR__ . '/logs/auditoria_formatos.json';
@mkdir(__DIR__ . '/logs', 0777, true);

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('No autenticado');
    }

    $stmt = $db->prepare("SELECT id, nombre, tipo, margen_superior, margen_inferior FROM formatos_matricula ORDER BY id DESC LIMIT 10");
    $stmt->execute();
    $formatos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $audit_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => phpversion(),
        'formatos_count' => count($formatos),
        'formatos' => []
    ];

    foreach ($formatos as $f) {
        $id = (int)$f['id'];
        $stmt = $db->prepare("SELECT id, nombre, margen_superior, margen_inferior, margen_izquierdo, margen_derecho, configuracion_json, contenido_html FROM formatos_matricula WHERE id = ?");
        $stmt->execute([$id]);
        $formato = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($formato) {
            $json_str = $formato['configuracion_json'] ?? '';
            $html_str = $formato['contenido_html'] ?? '';

            $json_parsed = null;
            $json_error = null;
            $bloques_count = 0;
            $bloques_con_pos = [];

            if (!empty($json_str)) {
                $json_parsed = json_decode($json_str, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json_parsed)) {
                    $bloques_count = count($json_parsed);
                    foreach ($json_parsed as $idx => $bloque) {
                        if (isset($bloque['left']) && isset($bloque['top'])) {
                            $bloques_con_pos[] = [
                                'idx' => $idx,
                                'type' => $bloque['type'] ?? 'unknown',
                                'left' => $bloque['left'],
                                'top' => $bloque['top'],
                                'width' => $bloque['width'] ?? null,
                                'height' => $bloque['height'] ?? null,
                            ];
                        }
                    }
                } else {
                    $json_error = json_last_error_msg();
                }
            }

            $audit_data['formatos'][] = [
                'id' => $formato['id'],
                'nombre' => $formato['nombre'],
                'margenes' => [
                    'superior' => (int)$formato['margen_superior'],
                    'inferior' => (int)$formato['margen_inferior'],
                    'izquierdo' => (int)$formato['margen_izquierdo'],
                    'derecho' => (int)$formato['margen_derecho'],
                ],
                'json' => [
                    'existe' => !empty($json_str),
                    'bytes' => strlen($json_str),
                    'error' => $json_error,
                    'bloques_totales' => $bloques_count,
                    'bloques_con_posicion' => $bloques_con_pos,
                    'muestra' => substr($json_str, 0, 300),
                ],
                'html' => [
                    'existe' => !empty($html_str),
                    'bytes' => strlen($html_str),
                    'tiene_bloque_avanzado' => strpos($html_str, 'bloque-avanzado') !== false,
                    'tiene_position_absolute' => strpos($html_str, 'position:absolute') !== false,
                    'muestra' => substr($html_str, 0, 300),
                ],
            ];
        }
    }

    file_put_contents($log_file, json_encode($audit_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);

    $audit_data['log_file'] = $log_file;
    echo json_encode($audit_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}
