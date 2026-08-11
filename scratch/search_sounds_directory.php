<?php
$content = file_get_contents(__DIR__ . '/../assets/libs/ml/ml_engine.js');
preg_match_all('/_soundsDirectory\s*=\s*[^;]+/i', $content, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[0] as $match) {
    echo "Match: {$match[0]} at offset {$match[1]}\n";
    $start = max(0, $match[1] - 100);
    $end = min(strlen($content), $match[1] + strlen($match[0]) + 100);
    echo substr($content, $start, $end - $start) . "\n\n";
}
?>
