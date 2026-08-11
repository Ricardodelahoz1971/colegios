<?php
$html = file_get_contents(__DIR__ . '/../php/rendered.html');
echo "CHAR AT 236:\n";
echo substr($html, 220, 40) . "\n";
echo "CHAR AT 236 (no whitespace at start):\n";
$trimmed = ltrim($html);
echo substr($trimmed, 220, 40) . "\n";
?>
