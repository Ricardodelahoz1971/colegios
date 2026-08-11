<?php
declare(strict_types=1);

$transcript_path = 'C:/Users/Admin/.gemini/antigravity/brain/019ddebe-8a71-45b1-914d-28bb8ea5e296/.system_generated/logs/transcript.jsonl';

$handle = fopen($transcript_path, 'r');
if ($handle) {
    $line_num = 0;
    while (($line = fgets($handle)) !== false) {
        $line_num++;
        if ($line_num === 1178 || $line_num === 1182) {
            echo "Line {$line_num}:\n";
            $data = json_decode($line, true);
            if (isset($data['content'])) {
                echo "Content snippet: " . substr($data['content'], 0, 1000) . "\n";
            }
            if (isset($data['tool_calls'])) {
                echo "Tool Calls: " . json_encode($data['tool_calls'], JSON_PRETTY_PRINT) . "\n";
            }
        }
    }
    fclose($handle);
}
