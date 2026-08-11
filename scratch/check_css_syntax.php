<?php
$file = 'C:\\xampp\\htdocs\\sistema_escolar\\styles\\ui_kit.css';
$content = file_get_contents($file);

$len = strlen($content);
$open = 0;
$close = 0;
$in_comment = false;

for ($i = 0; $i < $len; $i++) {
    if ($in_comment) {
        if ($content[$i] === '*' && isset($content[$i+1]) && $content[$i+1] === '/') {
            $in_comment = false;
            $i++;
        }
    } else {
        if ($content[$i] === '/' && isset($content[$i+1]) && $content[$i+1] === '*') {
            $in_comment = true;
            $i++;
        } elseif ($content[$i] === '{') {
            $open++;
        } elseif ($content[$i] === '}') {
            $close++;
        }
    }
}

echo "Total open braces: $open\n";
echo "Total close braces: $close\n";

if ($open !== $close) {
    echo "🚨 ALERTA: Desbalance de llaves detectado en ui_kit.css! Open: $open, Close: $close\n";
} else {
    echo "✅ Las llaves están perfectamente balanceadas en ui_kit.css.\n";
}
