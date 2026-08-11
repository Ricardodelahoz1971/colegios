<?php
$file = 'C:\\xampp\\htdocs\\sistema_escolar\\php\\vistas\\khronos.php';
$content = file_get_contents($file);
$lines = explode("\n", $content);
// Encontrar donde empieza <?php
$start = 0;
foreach ($lines as $idx => $line) {
    if (trim($line) === '<?php') {
        $start = $idx;
        break;
    }
}
$clean = array_slice($lines, $start);
file_put_contents($file, implode("\n", $clean));
echo "Corte completado. Archivo limpiado.\n";
