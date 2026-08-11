<?php
$dir = __DIR__ . '/../php/logica';
$files = glob($dir . '/*.php');
$added = [];
foreach ($files as $file) {
    if (strpos(basename($file), 'api_') === 0 || strpos(basename($file), 'procesar_') === 0 || strpos(basename($file), 'obtener_') === 0) {
        $content = file_get_contents($file);
        if (strpos($content, 'Cache-Control') === false && strpos($content, 'header') !== false) {
            // Find the first header() call and prepend Cache-Control
            $content = preg_replace('/(header\s*\(\s*[\'"]Content-Type[^;]+;\s*)/i', "header('Cache-Control: no-cache, no-store, must-revalidate');\nheader('Pragma: no-cache');\nheader('Expires: 0');\n$1", $content, 1, $count);
            if ($count > 0) {
                file_put_contents($file, $content);
                $added[] = basename($file);
            }
        }
    }
}
echo "Modificados: " . implode(', ', $added);
