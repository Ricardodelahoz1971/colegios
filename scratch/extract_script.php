<?php
$content = file_get_contents('php/vistas/aula_virtual_estudiante.php');
preg_match('/<script>(.*?)<\/script>/s', $content, $matches);
if (isset($matches[1])) {
    file_put_contents('scratch/test_script.js', $matches[1]);
    echo "Extracted script to scratch/test_script.js\n";
} else {
    echo "No script found\n";
}
