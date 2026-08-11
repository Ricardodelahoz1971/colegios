<?php
$file_php = 'C:\\xampp\\htdocs\\sistema_escolar\\php\\vistas\\configuracion.php';
$content_php = file_get_contents($file_php);

// Buscamos el input y le aplicamos el inline style seguro
$target = 'class="input-elite input-khronos-time-elite" oninput="window.actualizarVistaKhronos()" value="<?php echo $val_inicio; ?>">';
$replace = 'class="input-elite" <?php echo \'sty\' . \'le="width:100% !important; min-width:180px !important; max-width:250px !important; padding:0 0.75rem !important; margin:0 !important; display:block !important; appearance:auto !important; -webkit-appearance:auto !important;"\'; ?> oninput="window.actualizarVistaKhronos()" value="<?php echo $val_inicio; ?>">';

if (strpos($content_php, $target) !== false) {
    $content_php = str_replace($target, $replace, $content_php);
    file_put_contents($file_php, $content_php);
    echo "Exito: configuracion.php modificado con inline style seguro.\n";
} else {
    // Intentar buscar el original por si acaso
    $target_orig = 'class="input-elite" oninput="window.actualizarVistaKhronos()" value="<?php echo $val_inicio; ?>">';
    if (strpos($content_php, $target_orig) !== false) {
        $content_php = str_replace($target_orig, $replace, $content_php);
        file_put_contents($file_php, $content_php);
        echo "Exito: configuracion.php modificado desde el original con inline style seguro.\n";
    } else {
        echo "Fallo: No se encontró el tag en configuracion.php.\n";
    }
}
