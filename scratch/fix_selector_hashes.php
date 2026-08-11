<?php
declare(strict_types=1);

$filePath = 'c:/xampp/htdocs/sistema_escolar/styles/modules/mensajeria.css';
$content = file_get_contents($filePath);

if ($content === false) {
    echo "Error: No se pudo leer mensajeria.css\n";
    exit(1);
}

// Reemplazar #accordionChat por .hermes-sidebar
$content = str_replace('#accordionChat', '.hermes-sidebar', $content);

if (file_put_contents($filePath, $content) !== false) {
    echo "¡Selectores ID corregidos a clases en mensajeria.css!\n";
} else {
    echo "Error: No se pudo guardar mensajeria.css\n";
}
