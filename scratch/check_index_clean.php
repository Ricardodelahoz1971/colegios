<?php
$html = file_get_contents(__DIR__ . '/../php/rendered.html');
// Remove the first line (the PHP Notice)
$lines = explode("\n", $html);
if (strpos($lines[1], 'Notice:') !== false) {
    unset($lines[0]); // empty line
    unset($lines[1]); // notice line
}
$clean_html = implode("\n", $lines);
echo "CLEAN CHAR AT 236:\n";
echo substr($clean_html, 220, 40) . "\n";
echo "snippet from 200 to 280:\n";
echo substr($clean_html, 200, 80) . "\n";
?>
