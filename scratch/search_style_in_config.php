<?php
$file = 'C:\\xampp\\htdocs\\sistema_escolar\\php\\vistas\\configuracion.php';
$content = file_get_contents($file);

$lines = explode("\n", $content);
foreach ($lines as $idx => $line) {
    if (stripos($line, '<style') !== false || (stripos($line, 'style') !== false && stripos($line, 'style=') !== false)) {
        $num = $idx + 1;
        echo "Línea $num: " . trim($line) . "\n";
    }
}
