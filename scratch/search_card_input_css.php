<?php
$file = 'C:\\xampp\\htdocs\\sistema_escolar\\styles\\ui_kit.css';
$content = file_get_contents($file);

$pos = 0;
while (false !== ($pos = strpos($content, 'config-input-card-elite', $pos))) {
    echo "Found config-input-card-elite at position $pos\n";
    echo "--- Snippet ---\n";
    echo substr($content, $pos - 100, 600);
    echo "\n---------------\n";
    $pos += strlen('config-input-card-elite');
}
