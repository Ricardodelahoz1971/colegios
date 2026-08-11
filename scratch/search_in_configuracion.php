<?php
$file = 'C:\\xampp\\htdocs\\sistema_escolar\\php\\vistas\\configuracion.php';
$content = file_get_contents($file);

// Contar apariciones de "khronos_inicio"
$count = substr_count($content, 'khronos_inicio');
echo "Apariciones de khronos_inicio: $count\n";

// Buscar dónde aparece y mostrar las líneas
$lines = explode("\n", $content);
foreach ($lines as $idx => $line) {
    if (strpos($line, 'khronos_inicio') !== false) {
        $num = $idx + 1;
        echo "Línea $num: " . trim($line) . "\n";
    }
}
