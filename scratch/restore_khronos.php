<?php
$file = 'C:\\xampp\\htdocs\\sistema_escolar\\scratch\\found_transcript.txt';
// El archivo está en UTF-16LE. Lo leemos y lo convertimos a UTF-8.
$content_utf16 = file_get_contents($file);
$content_utf8 = mb_convert_encoding($content_utf16, 'UTF-8', 'UTF-16LE');

// Cada línea es una cadena JSON
$data = json_decode($content_utf8, true);
if (!$data) {
    // Si no es JSON puro, tal vez tenga prefijos de Select-String. Limpiamos:
    if (preg_match('/({.*})/', $content_utf8, $matches)) {
        $data = json_decode($matches[1], true);
    }
}

if ($data && isset($data['content'])) {
    // El contenido es el resultado del tool call view_file de khronos.php
    // Vamos a buscar si contiene las líneas.
    $raw_content = $data['content'];
    
    // Vamos a limpiar el formato numerado de view_file si es necesario o extraerlo
    // Espera, view_file devuelve las líneas con formato "<line_number>: <original_line>" si es view_file.
    // Vamos a ver si el JSON contiene la transcripción de un paso donde se leyó khronos.php.
    file_put_contents('C:\\xampp\\htdocs\\sistema_escolar\\scratch\\extracted.txt', $raw_content);
    echo "Contenido extraído a scratch/extracted.txt\n";
} else {
    echo "No se pudo decodificar el JSON o no contiene 'content'\n";
    print_r($data);
}
