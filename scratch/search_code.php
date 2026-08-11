<?php
$file = __DIR__ . '/../js/modules/perseus_engine.js';
$lines = file($file);
foreach ($lines as $i => $line) {
    if (strpos($line, 'Profesor') !== false || strpos($line, 'Docente') !== false) {
        $num = $i + 1;
        echo "$num: $line";
    }
}
?>
