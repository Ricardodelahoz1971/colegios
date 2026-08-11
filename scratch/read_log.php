<?php
$log_file = 'C:\\Users\\Admin\\.gemini\\antigravity-ide\\brain\\110470a2-f334-4790-bffb-27320ba95cef\\.system_generated\\logs\\transcript_full.jsonl';
$lines = file($log_file);
foreach($lines as $line) {
    if (strpos($line, 'endforeach;') !== false && strpos($line, 'zulu.php') !== false) {
        $data = json_decode($line, true);
        if (isset($data['content']) && strpos($data['content'], 'endforeach;') !== false) {
            echo "Step " . $data['step_index'] . ":\n";
            echo $data['content'] . "\n\n";
        }
    }
}
