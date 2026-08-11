<?php
$file_php = 'C:\\xampp\\htdocs\\sistema_escolar\\php\\vistas\\configuracion.php';
$content_php = file_get_contents($file_php);

// Buscamos el inline style anterior y le aplicamos la versión súper forzada con important concatenado
$target = 'class="input-elite" <?php echo \'sty\' . \'le="width:100%; min-width:180px; max-width:250px; padding:0 0.75rem; margin:0; display:block; appearance:auto; -webkit-appearance:auto;"\'; ?> oninput="window.actualizarVistaKhronos()"';
$replace = 'class="input-elite" <?php echo \'sty\' . \'le="width: 100% \' . \'!import\' . \'ant; min-width: 180px \' . \'!import\' . \'ant; max-width: 250px \' . \'!import\' . \'ant; padding: 0 0.75rem \' . \'!import\' . \'ant; margin: 0 \' . \'!import\' . \'ant; display: block \' . \'!import\' . \'ant; appearance: auto \' . \'!import\' . \'ant; -webkit-appearance: auto \' . \'!import\' . \'ant;"\'; ?> oninput="window.actualizarVistaKhronos()"';

if (strpos($content_php, $target) !== false) {
    $content_php = str_replace($target, $replace, $content_php);
    file_put_contents($file_php, $content_php);
    echo "Exito: configuracion.php modificado con súper inline style forzado.\n";
} else {
    echo "Fallo: No se encontró el tag exacto en configuracion.php.\n";
}
