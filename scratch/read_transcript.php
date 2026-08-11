<?php
$lines = file("C:/Users/Admin/.gemini/antigravity/brain/748723d3-3a76-41a0-ae95-0473597da5ed/.system_generated/logs/transcript.jsonl");
foreach ($lines as $line) {
    $data = json_decode($line, true);
    if ($data && $data['step_index'] >= 600 && $data['step_index'] <= 695) {
        echo "=== STEP {$data['step_index']} ({$data['source']} / {$data['type']}) ===\n";
        if (isset($data['content'])) {
            echo substr($data['content'], 0, 1000) . "\n";
        }
        if (isset($data['tool_calls'])) {
            echo "Tools: " . json_encode($data['tool_calls'], JSON_PRETTY_PRINT) . "\n";
        }
        echo "\n";
    }
}
