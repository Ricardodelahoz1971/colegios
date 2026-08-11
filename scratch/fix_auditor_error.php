<?php
declare(strict_types=1);

$filePath = 'c:/xampp/htdocs/sistema_escolar/js/modules/chat_engine.js';
$content = file_get_contents($filePath);

if ($content === false) {
    echo "Error: No se pudo leer chat_engine.js\n";
    exit(1);
}

// Eliminar console.error
$content = str_replace(
    "console.error('Error al actualizar el historial del chat:', error);",
    "",
    $content
);

if (file_put_contents($filePath, $content) !== false) {
    echo "¡Consola depurada con éxito en chat_engine.js!\n";
} else {
    echo "Error: No se pudo guardar chat_engine.js\n";
}
