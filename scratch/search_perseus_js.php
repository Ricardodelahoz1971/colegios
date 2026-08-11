<?php
declare(strict_types=1);

$filepath = __DIR__ . '/../js/modules/perseus_engine.js';
$content = file_get_contents($filepath);
echo "File length: " . strlen($content) . "\n";
if (stripos($content, 'ciencias') !== false) echo "Has 'ciencias'\n";
if (stripos($content, 'educación') !== false) echo "Has 'educación'\n";
if (stripos($content, 'profesor') !== false) echo "Has 'profesor'\n";
if (stripos($content, 'curso') !== false) echo "Has 'curso'\n";

// Let's print occurrences
$lines = file($filepath);
foreach ($lines as $i => $line) {
    if (stripos($line, 'ciencias') !== false || stripos($line, 'educa') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
