<?php
$file_php = 'C:\\xampp\\htdocs\\sistema_escolar\\php\\vistas\\configuracion.php';
$content_php = file_get_contents($file_php);

// Buscamos el input de tipo time y le agregamos la clase especial
$target = 'class="input-elite" oninput="window.actualizarVistaKhronos()" value="<?php echo $val_inicio; ?>">';
$replace = 'class="input-elite input-khronos-time-elite" oninput="window.actualizarVistaKhronos()" value="<?php echo $val_inicio; ?>">';

if (strpos($content_php, $target) !== false) {
    $content_php = str_replace($target, $replace, $content_php);
    file_put_contents($file_php, $content_php);
    echo "Exito: configuracion.php modificado con la nueva clase.\n";
} else {
    echo "Fallo: No se encontró el tag del input de time en configuracion.php.\n";
}
