<?php
$path = 'C:/Users/Admin/.gemini/antigravity-ide/brain/00413263-f554-4250-a8df-82b2d7026acb/.system_generated/logs/transcript.jsonl';
if (!file_exists($path)) {
    echo "File does not exist: $path\n";
    exit;
}
$lines = file($path);
foreach ($lines as $line) {
    $data = json_decode($line, true);
    if ($data && isset($data['type']) && $data['type'] === 'USER_INPUT') {
        echo "=== STEP {$data['step_index']} ===\n";
        echo $data['content'] . "\n";
    }
}
