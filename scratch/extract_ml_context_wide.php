<?php
$content = file_get_contents(__DIR__ . '/../assets/libs/ml/ml_engine.js');
$offset = 515583;
$start = max(0, $offset - 1000);
$end = min(strlen($content), $offset + 1000);
echo "---WIDE CONTEXT START---\n";
echo substr($content, $start, $end - $start) . "\n";
echo "---WIDE CONTEXT END---\n";
?>
