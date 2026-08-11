<?php
$content = file_get_contents(__DIR__ . '/../assets/libs/ml/ml_engine.js');

function showContext($pattern, $content) {
    echo "Matches for pattern $pattern:\n";
    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $offset = $match[1];
            $length = strlen($match[0]);
            $start = max(0, $offset - 150);
            $end = min(strlen($content), $offset + $length + 150);
            echo "Offset $offset:\n";
            echo "---CONTEXT START---\n";
            echo substr($content, $start, $end - $start) . "\n";
            echo "---CONTEXT END---\n\n";
        }
    } else {
        echo "No matches.\n\n";
    }
}

showContext('/fetch\([^)]*\)/i', $content);
showContext('/\.src\s*=/i', $content);
?>
