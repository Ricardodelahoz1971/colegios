<?php
declare(strict_types=1);

$transcript_path = 'C:/Users/Admin/.gemini/antigravity/brain/019ddebe-8a71-45b1-914d-28bb8ea5e296/.system_generated/logs/transcript.jsonl';

$handle = fopen($transcript_path, 'r');
if ($handle) {
    $line_num = 0;
    while (($line = fgets($handle)) !== false) {
        $line_num++;
        if (strpos($line, 'LFV') !== false && preg_match('/\$2y\$10\$[A-Za-z0-9.\/]{53}/', $line)) {
            echo "Line {$line_num}: ";
            preg_match_all('/\$2y\$10\$[A-Za-z0-9.\/]{53}/', $line, $matches);
            echo implode(', ', $matches[0]) . "\n";
            // Print a snippet around the match
            $idx = strpos($line, 'LFV');
            echo "   Snippet: " . substr($line, max(0, $idx - 50), 200) . "\n";
        }
    }
    fclose($handle);
}
