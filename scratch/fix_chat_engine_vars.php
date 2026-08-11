<?php
declare(strict_types=1);

$filePath = 'c:/xampp/htdocs/sistema_escolar/js/modules/chat_engine.js';
$content = file_get_contents($filePath);

if ($content === false) {
    echo "Error: No se pudo leer chat_engine.js\n";
    exit(1);
}

// 1. Corregir res.status por data.status
$content = str_replace(
    "if (res.status === 'success') {",
    "if (data.status === 'success') {",
    $content
);

// 2. Corregir chatHistory por chatHistoryContainer en el scrollTop
$content = str_replace(
    "chatHistory.scrollTop = chatHistoryContainer.scrollHeight;",
    "chatHistoryContainer.scrollTop = chatHistoryContainer.scrollHeight;",
    $content
);

if (file_put_contents($filePath, $content) !== false) {
    echo "¡Variables corregidas con éxito en chat_engine.js!\n";
} else {
    echo "Error: No se pudo guardar chat_engine.js\n";
}
