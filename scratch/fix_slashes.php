<?php
$file = __DIR__ . '/../php/vistas/aula_virtual_estudiante.php';
$content = file_get_contents($file);

$content = str_replace("\\`", "`", $content);
$content = str_replace("\\$", "$", $content);

file_put_contents($file, $content);
echo "Fixed backslash escapes in JS template literals\n";
