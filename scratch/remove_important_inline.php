<?php
$file_php = 'C:\\xampp\\htdocs\\sistema_escolar\\php\\vistas\\configuracion.php';
$content_php = file_get_contents($file_php);

// Buscamos el inline style con important y lo reemplazamos por la versión sin important
$target = 'class="input-elite" <?php echo \'sty\' . \'le="width:100% !important; min-width:180px !important; max-width:250px !important; padding:0 0.75rem !important; margin:0 !important; display:block !important; appearance:auto !important; -webkit-appearance:auto !important;"\'; ?> oninput="window.actualizarVistaKhronos()"';
$replace = 'class="input-elite" <?php echo \'sty\' . \'le="width:100%; min-width:180px; max-width:250px; padding:0 0.75rem; margin:0; display:block; appearance:auto; -webkit-appearance:auto;"\'; ?> oninput="window.actualizarVistaKhronos()"';

if (strpos($content_php, $target) !== false) {
    $content_php = str_replace($target, $replace, $content_php);
    file_put_contents($file_php, $content_php);
    echo "Exito: configuracion.php modificado (removido important de inline style).\n";
} else {
    echo "Fallo: No se encontró el tag exacto en configuracion.php.\n";
}
