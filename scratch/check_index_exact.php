<?php
$html = file_get_contents(__DIR__ . '/../php/rendered.html');
$lines = explode("\n", $html);
if (strpos($lines[1], 'Notice:') !== false) {
    unset($lines[0]);
    unset($lines[1]);
}
$clean_html = implode("\n", $lines);

for ($i = 200; $i <= 260; $i++) {
    $char = $clean_html[$i];
    echo "$i: " . ($char == "\n" ? "\\n" : ($char == "\r" ? "\\r" : $char)) . "\n";
}
?>
