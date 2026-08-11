<?php
$file = 'C:\\xampp\\htdocs\\sistema_escolar\\styles\\ui_kit.css';
$content = file_get_contents($file);

$lines = explode("\n", $content);
foreach ($lines as $idx => $line) {
    if (stripos($line, 'width') !== false && preg_match('/\b(6|7|8|9|10|11|12)0px\b|\b[3-7](\.[0-9]+)?rem\b/', $line)) {
        $num = $idx + 1;
        echo "Línea $num: " . trim($line) . "\n";
    }
}
