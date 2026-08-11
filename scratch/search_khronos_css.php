<?php
$file = 'C:\\xampp\\htdocs\\sistema_escolar\\styles\\modules\\khronos.css';
$content = file_get_contents($file);

$lines = explode("\n", $content);
foreach ($lines as $idx => $line) {
    if (stripos($line, 'input') !== false || stripos($line, 'width') !== false) {
        $num = $idx + 1;
        echo "Línea $num: " . trim($line) . "\n";
    }
}
