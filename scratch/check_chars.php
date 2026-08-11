<?php
$lines = file('php/vistas/zulu.php');
for ($j = 300; $j <= 304; $j++) {
    $line = $lines[$j];
    echo "Line " . ($j+1) . " raw: " . bin2hex($line) . "\n";
    echo "Line " . ($j+1) . " chars:\n";
    for($i=0; $i<strlen($line); $i++) {
        echo ord($line[$i]) . " (" . $line[$i] . ")\n";
    }
    echo "-----------------\n";
}
