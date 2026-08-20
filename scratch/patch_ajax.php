<?php
$file = 'c:\xampp\htdocs\sistema_escolar\php\logica\formatos_ajax.php';
$content = file_get_contents($file);

// Eliminar la validacion de contenido_html vacio
$content = str_replace(
    'if (empty($contenido_html)) {
            throw new Exception("El contenido de la plantilla no puede estar vacío.");
        }',
    'if (empty($configuracion_json)) {
            throw new Exception("El esquema JSON no puede estar vacío.");
        }',
    $content
);

file_put_contents($file, $content);
echo "Backend ajax parchado correctamente.\n";
