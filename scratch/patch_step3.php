<?php
$file = 'c:\xampp\htdocs\sistema_escolar\imprimir_matricula.php';
$content = file_get_contents($file);

// Buscar el marcador de inicio donde termina el script
$pos = strpos($content, '</script>' . "\r\n" . '<?php');
if ($pos === false) {
    $pos = strpos($content, '</script>' . "\n" . '<?php');
}

if ($pos !== false) {
    // Cortamos la primera parte (todo hasta </script> inclusive)
    $primera_parte = substr($content, 0, $pos + 9);
    
    // Inyectamos el nuevo bloque PHP
    $nuevo_bloque = '
<?php
require_once __DIR__ . \'/php/logica/formatos_renderer.php\';

$zona_header_limit_mm = 50.0;
$zona_footer_start_mm = 219.4;
$papel_width_mm = 215.9;
$papel_height_mm = 279.4;
$configuracionJson = [];

if (!empty($formato[\'configuracion_json\']) && is_string($formato[\'configuracion_json\'])) {
    $config_parsed = null;
    
    try {
        $config_parsed = json_decode($formato[\'configuracion_json\'], true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        $config_parsed = json_decode($formato[\'configuracion_json\'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $cleaned_json = preg_replace(\'/[\x00-\x1F\x80-\xFF]/\', \'\', $formato[\'configuracion_json\']);
            $config_parsed = json_decode($cleaned_json, true);
            if (!is_array($config_parsed)) {
                $config_parsed = [];
            }
        }
    }
    
    if (is_array($config_parsed)) {
        $configuracionJson = $config_parsed[\'bloques\'] ?? [];
        
        if (isset($config_parsed[\'zonas\']) && is_array($config_parsed[\'zonas\'])) {
            if (isset($config_parsed[\'zonas\'][\'header_limit_mm\'])) {
                $header_value = filter_var($config_parsed[\'zonas\'][\'header_limit_mm\'], FILTER_VALIDATE_FLOAT);
                if ($header_value !== false && $header_value > 0) {
                    $zona_header_limit_mm = $header_value;
                }
            }
            if (isset($config_parsed[\'zonas\'][\'footer_limit_mm\'])) {
                $footer_value = filter_var($config_parsed[\'zonas\'][\'footer_limit_mm\'], FILTER_VALIDATE_FLOAT);
                if ($footer_value !== false && $footer_value > 0) {
                    $zona_footer_start_mm = $footer_value;
                }
            }
        }
    }
}

if ($zona_header_limit_mm <= 0) {
    $zona_header_limit_mm = 50.0;
}
if ($zona_footer_start_mm <= $zona_header_limit_mm) {
    $zona_footer_start_mm = $zona_header_limit_mm + 50.0;
}

if (!empty($formato[\'tamano_lienzo\'])) {
    switch (strtolower($formato[\'tamano_lienzo\'])) {
        case \'media_carta\':
            $papel_width_mm = 215.9;
            $papel_height_mm = 139.7;
            break;
        case \'carne_v\':
            $papel_width_mm = 54.0;
            $papel_height_mm = 86.0;
            break;
        case \'carne_h\':
            $papel_width_mm = 86.0;
            $papel_height_mm = 54.0;
            break;
        case \'carta\':
        default:
            $papel_width_mm = 215.9;
            $papel_height_mm = 279.4;
            break;
    }
}

// -------------------------------------------------------------
// MOTOR DE RENDERIZADO (PASO 3 DE ARQUITECTURA V2)
// -------------------------------------------------------------
$datos_render = [
    \'estudiante\' => $estudiante,
    \'notas\' => $notas,
    \'school_name\' => $school_name,
    \'school_motto\' => $school_motto,
    \'school_logo\' => $school_logo,
    \'rector_nombre\' => $rector_nombre,
    \'secretaria_nombre\' => $secretaria_nombre,
    \'anio_lectivo\' => $anio_lectivo,
    \'formato\' => $formato
];

$html_final = renderizarFormato($configuracionJson, $datos_render, false);

?>
<div class="print-document print-document--<?php echo strtolower($formato[\'tamano_lienzo\'] ?? \'carta\'); ?>"
     data-student-folio="<?php echo htmlspecialchars((string)($estudiante[\'folio_matricula\'] ?? \'\'), ENT_QUOTES, \'UTF-8\'); ?>"
     data-header-limit-mm="<?php echo htmlspecialchars((string)$zona_header_limit_mm, ENT_QUOTES, \'UTF-8\'); ?>"
     data-footer-start-mm="<?php echo htmlspecialchars((string)$zona_footer_start_mm, ENT_QUOTES, \'UTF-8\'); ?>"
     data-paper-width-mm="<?php echo htmlspecialchars((string)$papel_width_mm, ENT_QUOTES, \'UTF-8\'); ?>"
     data-paper-height-mm="<?php echo htmlspecialchars((string)$papel_height_mm, ENT_QUOTES, \'UTF-8\'); ?>">
    <?php echo $html_final; ?>
</div>
</body>
</html>
';

    $nuevo_contenido = $primera_parte . "\n" . $nuevo_bloque;
    file_put_contents($file, $nuevo_contenido);
    echo "¡Archivo reemplazado con éxito!\n";
} else {
    echo "No se encontró el marcador </script> en imprimir_matricula.php\n";
}
