<?php
$content = file_get_contents('js/modules/ares_canvas_engine.js');
$word = 'board.addEventListener';
$pos = 0;
while (($pos = strpos($content, $word, $pos)) !== false) {
    echo "Found '$word' at position $pos. Context:\n";
    echo substr($content, max(0, $pos - 100), 250) . "\n";
    echo "---------------------------------\n";
    $pos += strlen($word);
}
