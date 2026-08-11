<?php
declare(strict_types=1);

$lines = file(__DIR__ . '/../php/dashboard.php');
$found = false;
foreach ($lines as $i => $line) {
    if (strpos($line, 'function moduloAutorizadoMovil') !== false) {
        $found = true;
    }
    if ($found) {
        echo ($i + 1) . ": " . $line;
        if (strpos($line, '}') === 0 || (trim($line) === '}' && $i > 200)) {
            // let's print 30 lines
            for ($j = 1; $j <= 20; $j++) {
                echo ($i + 1 + $j) . ": " . $lines[$i + $j];
            }
            break;
        }
    }
}
