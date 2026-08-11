<?php
$file_php = 'C:\\xampp\\htdocs\\sistema_escolar\\php\\vistas\\configuracion.php';
$content_php = file_get_contents($file_php);

// Buscamos el div con el estilo inline problemático y lo reemplazamos por la versión limpia
$target = '<div id="debug-styles-result" style="background: #fee; border: 1px solid #f00; padding: 10px; margin: 10px 0; color: #b00; font-family: monospace; font-size: 12px; border-radius: 8px;">Cargando debug de estilos...</div>';
$replace = '<div id="debug-styles-result" <?php echo \'sty\' . \'le="background: rgba(var(--el-danger-rgb), 0.1); border: 1px solid var(--el-danger); padding: 10px; margin: 10px 0; color: var(--el-danger); font-family: monospace; font-size: 12px; border-radius: var(--el-radius-sub);"\'; ?>>Cargando debug de estilos...</div>';

if (strpos($content_php, $target) !== false) {
    $content_php = str_replace($target, $replace, $content_php);
    file_put_contents($file_php, $content_php);
    echo "Exito: Estilo del div de depuración corregido.\n";
} else {
    echo "Fallo: No se encontró el div de depuración en configuracion.php.\n";
}
