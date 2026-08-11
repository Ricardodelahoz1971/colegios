<?php
$html = file_get_contents(__DIR__ . '/full_rendered.html');
$lines = explode("\n", $html);
for ($i = 780; $i <= 810; $i++) {
    if (isset($lines[$i])) {
        echo ($i + 1) . ": " . htmlspecialchars($lines[$i]) . "\n";
    }
}
