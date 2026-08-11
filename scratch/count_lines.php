<?php
declare(strict_types=1);

$transcript_path = 'C:/Users/Admin/.gemini/antigravity/brain/019ddebe-8a71-45b1-914d-28bb8ea5e296/.system_generated/logs/transcript.jsonl';

if (file_exists($transcript_path)) {
    $lines = file($transcript_path);
    echo "Total lines: " . count($lines) . "\n";
    echo "First line: " . substr($lines[0], 0, 1000) . "\n";
} else {
    echo "File not found.\n";
}
