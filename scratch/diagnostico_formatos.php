<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Formatos de Matrícula</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 3px solid #0066cc; padding-bottom: 10px; }
        h2 { color: #0066cc; margin-top: 30px; }
        .info-box { background: #e8f4f8; border-left: 4px solid #0066cc; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .error-box { background: #ffe8e8; border-left: 4px solid #cc0000; padding: 15px; margin: 10px 0; border-radius: 4px; color: #cc0000; }
        .success-box { background: #e8ffe8; border-left: 4px solid #00cc00; padding: 15px; margin: 10px 0; border-radius: 4px; color: #00cc00; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0066cc; color: white; font-weight: bold; }
        tr:hover { background: #f9f9f9; }
        .json-box { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; font-family: monospace; font-size: 12px; }
        .truncated { color: #999; font-style: italic; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico: Formatos de Matrícula</h1>

        <div class="info-box">
            <strong>Propósito:</strong> Este script verifica qué datos están realmente guardados en la base de datos para los formatos de matrícula.
        </div>

        <h2>1. Estado de la Tabla</h2>
        <?php
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM formatos_matricula");
            $stmt->execute();
            $row = $stmt->fetch();
            $total = $row['total'] ?? 0;

            echo '<div class="success-box">';
            echo "✅ La tabla <code>formatos_matricula</code> existe.<br>";
            echo "<strong>Total de registros:</strong> <strong>$total</strong>";
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="error-box">';
            echo "❌ Error al acceder a la tabla: " . htmlspecialchars($e->getMessage());
            echo '</div>';
            exit;
        }
        ?>

        <h2>2. Estructura de Columnas</h2>
        <?php
        try {
            $stmt = $db->prepare("DESCRIBE formatos_matricula");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo '<table>';
            echo '<thead><tr><th>Columna</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr></thead>';
            echo '<tbody>';
            foreach ($columns as $col) {
                echo '<tr>';
                echo '<td><strong>' . htmlspecialchars($col['Field']) . '</strong></td>';
                echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                echo '<td>' . ($col['Null'] === 'YES' ? 'Sí' : 'No') . '</td>';
                echo '<td>' . ($col['Key'] ? htmlspecialchars($col['Key']) : '-') . '</td>';
                echo '<td>' . (isset($col['Default']) ? htmlspecialchars($col['Default']) : 'NULL') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } catch (Exception $e) {
            echo '<div class="error-box">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>

        <h2>3. Listado de Formatos Guardados</h2>
        <?php
        try {
            $stmt = $db->prepare("SELECT id, nombre, descripcion, tipo, activo, tipo_documento, tamano_lienzo,
                                 LENGTH(configuracion_json) as config_json_size,
                                 LENGTH(contenido_html) as html_size,
                                 creado_en
                          FROM formatos_matricula
                          ORDER BY id DESC LIMIT 20");
            $stmt->execute();
            $formatos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($formatos)) {
                echo '<div class="info-box">⚠️ No hay formatos guardados aún.</div>';
            } else {
                echo '<table>';
                echo '<thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Activo</th><th>Config JSON (bytes)</th><th>HTML (bytes)</th><th>Documento</th><th>Tamaño</th><th>Creado</th></tr></thead>';
                echo '<tbody>';
                foreach ($formatos as $fmt) {
                    $config_size = intval($fmt['config_json_size']);
                    $html_size = intval($fmt['html_size']);
                    $activo = $fmt['activo'] ? '✅ Sí' : '❌ No';

                    echo '<tr>';
                    echo '<td><strong>#' . (int)$fmt['id'] . '</strong></td>';
                    echo '<td>' . htmlspecialchars((string)$fmt['nombre']) . '</td>';
                    echo '<td>' . htmlspecialchars((string)$fmt['tipo']) . '</td>';
                    echo '<td>' . $activo . '</td>';
                    echo '<td><strong>' . number_format($config_size) . '</strong></td>';
                    echo '<td><strong>' . number_format($html_size) . '</strong></td>';
                    echo '<td>' . htmlspecialchars((string)($fmt['tipo_documento'] ?? 'N/A')) . '</td>';
                    echo '<td>' . htmlspecialchars((string)($fmt['tamano_lienzo'] ?? 'N/A')) . '</td>';
                    echo '<td>' . htmlspecialchars((string)($fmt['creado_en'] ?? 'N/A')) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
        } catch (Exception $e) {
            echo '<div class="error-box">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>

        <h2>4. Inspección Detallada del Último Formato</h2>
        <?php
        try {
            $stmt = $db->prepare("SELECT * FROM formatos_matricula ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ultimo) {
                echo '<div class="info-box">⚠️ No hay formatos para inspeccionar.</div>';
            } else {
                echo '<h3>Información Básica</h3>';
                echo '<table>';
                echo '<tr><td><strong>ID</strong></td><td>' . (int)$ultimo['id'] . '</td></tr>';
                echo '<tr><td><strong>Nombre</strong></td><td>' . htmlspecialchars((string)$ultimo['nombre']) . '</td></tr>';
                echo '<tr><td><strong>Descripción</strong></td><td>' . htmlspecialchars((string)($ultimo['descripcion'] ?? 'N/A')) . '</td></tr>';
                echo '<tr><td><strong>Tipo</strong></td><td>' . htmlspecialchars((string)($ultimo['tipo'] ?? 'N/A')) . '</td></tr>';
                echo '<tr><td><strong>Tipo Documento</strong></td><td>' . htmlspecialchars((string)($ultimo['tipo_documento'] ?? 'N/A')) . '</td></tr>';
                echo '<tr><td><strong>Tamaño Lienzo</strong></td><td>' . htmlspecialchars((string)($ultimo['tamano_lienzo'] ?? 'N/A')) . '</td></tr>';
                echo '<tr><td><strong>Márgenes</strong></td><td>Superior: ' . htmlspecialchars((string)($ultimo['margen_superior'] ?? '?')) . ', Inferior: ' . htmlspecialchars((string)($ultimo['margen_inferior'] ?? '?')) . ', Izquierdo: ' . htmlspecialchars((string)($ultimo['margen_izquierdo'] ?? '?')) . ', Derecho: ' . htmlspecialchars((string)($ultimo['margen_derecho'] ?? '?')) . '</td></tr>';
                echo '</table>';

                echo '<h3>Configuración JSON</h3>';
                $json_str = $ultimo['configuracion_json'] ?? '';
                if (!empty($json_str)) {
                    $json_decoded = json_decode($json_str, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        echo '<div class="success-box">✅ JSON válido (' . number_format(strlen($json_str)) . ' bytes)</div>';
                        echo '<div class="json-box">';
                        echo '<pre>' . htmlspecialchars(json_encode($json_decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                        echo '</div>';
                    } else {
                        echo '<div class="error-box">❌ JSON inválido: ' . htmlspecialchars(json_last_error_msg()) . '</div>';
                        echo '<div class="json-box"><pre>' . htmlspecialchars($json_str) . '</pre></div>';
                    }
                } else {
                    echo '<div class="info-box">⚠️ Campo vacío</div>';
                }

                echo '<h3>Contenido HTML (primeros 500 caracteres)</h3>';
                $html_str = $ultimo['contenido_html'] ?? '';
                if (!empty($html_str)) {
                    echo '<div class="success-box">✅ HTML guardado (' . number_format(strlen($html_str)) . ' bytes)</div>';
                    echo '<div class="json-box">';
                    $html_preview = substr($html_str, 0, 500);
                    echo '<pre>' . htmlspecialchars($html_preview) . '</pre>';
                    if (strlen($html_str) > 500) {
                        echo '<p class="truncated">... (' . number_format(strlen($html_str) - 500) . ' bytes más)</p>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="info-box">⚠️ Campo vacío</div>';
                }
            }
        } catch (Exception $e) {
            echo '<div class="error-box">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>

        <hr style="margin: 30px 0; border: none; border-top: 2px solid #ddd;">

        <h2>📋 Cómo Usar Este Diagnóstico</h2>
        <div class="info-box">
            <ol>
                <li>Acceda a este script en: <code>/sistema_escolar/scratch/diagnostico_formatos.php</code></li>
                <li>Guarde un formato desde la interfaz</li>
                <li>Actualice esta página para ver si aparece el nuevo registro</li>
                <li>Verifique que el JSON y HTML no estén vacíos</li>
                <li>Compruebe en la consola del navegador (F12 → Console) los logs que agregué</li>
            </ol>
        </div>
    </div>
</body>
</html>
