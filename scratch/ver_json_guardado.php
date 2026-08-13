<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver JSON Guardado</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; }
        pre { background: #f0f0f0; padding: 15px; border-radius: 4px; overflow-x: auto; border: 1px solid #ddd; }
        h2 { color: #333; border-bottom: 2px solid #0066cc; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 JSON Guardado en Base de Datos</h1>

        <?php
        $stmt = $db->prepare("SELECT id, nombre, configuracion_json FROM formatos_matricula WHERE nombre = 'Prueba' LIMIT 1");
        $stmt->execute();
        $formato = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($formato) {
            echo '<h2>Formato: ' . htmlspecialchars($formato['nombre']) . ' (ID: ' . $formato['id'] . ')</h2>';
            echo '<h3>JSON RAW (como está en BD):</h3>';
            echo '<pre>' . htmlspecialchars($formato['configuracion_json']) . '</pre>';

            echo '<h3>JSON FORMATEADO (más legible):</h3>';
            $json_decoded = json_decode($formato['configuracion_json'], true);
            echo '<pre>' . json_encode($json_decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';

            echo '<h3>Posiciones de cada bloque:</h3>';
            echo '<ul>';
            foreach ($json_decoded as $idx => $bloque) {
                echo '<li><strong>' . htmlspecialchars($bloque['type']) . '</strong> - left: ' . $bloque['left'] . ', top: ' . $bloque['top'] . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p style="color: red;">No hay formato "Prueba" en la BD.</p>';
        }
        ?>
    </div>
</body>
</html>
