<?php
declare(strict_types=1);

$transcript_path = 'C:/Users/Admin/.gemini/antigravity/brain/019ddebe-8a71-45b1-914d-28bb8ea5e296/.system_generated/logs/transcript.jsonl';

$handle = fopen($transcript_path, 'r');
if ($handle) {
    $line_num = 0;
    while (($line = fgets($handle)) !== false) {
        $line_num++;
        if (stripos($line, 'UPDATE usuarios') !== false) {
            echo "Line {$line_num}: ";
            echo substr($line, 0, 500) . "\n\n";
        }
    }
    fclose($handle);
}
