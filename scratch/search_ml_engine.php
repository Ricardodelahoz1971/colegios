<?php
$content = file_get_contents(__DIR__ . '/../assets/libs/ml/ml_engine.js');

echo "Searching ml_engine.js:\n";

// Search for fetch
preg_match_all('/fetch\([^)]*\)/i', $content, $matches);
echo "Fetch matches: " . count($matches[0]) . "\n";
foreach($matches[0] as $m) {
    echo "  - $m\n";
}

// Search for Worker
preg_match_all('/new\s+Worker\([^)]*\)/i', $content, $matches);
echo "Worker matches: " . count($matches[0]) . "\n";
foreach($matches[0] as $m) {
    echo "  - $m\n";
}

// Search for import.meta
preg_match_all('/import\.meta/i', $content, $matches);
echo "import.meta matches: " . count($matches[0]) . "\n";
foreach($matches[0] as $m) {
    echo "  - $m\n";
}

// Search for dynamic script tag insertion
preg_match_all('/createElement\([^)]*script[^)]*\)/i', $content, $matches);
echo "script createElement matches: " . count($matches[0]) . "\n";
foreach($matches[0] as $m) {
    echo "  - $m\n";
}

// Search for .src =
preg_match_all('/\.src\s*=/i', $content, $matches);
echo ".src = matches: " . count($matches[0]) . "\n";

?>
