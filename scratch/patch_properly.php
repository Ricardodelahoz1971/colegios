<?php
$currentFile = 'c:\xampp\htdocs\sistema_escolar\js\modules\formatos_matricula_builder.js';
$missingFile = 'c:\xampp\htdocs\sistema_escolar\scratch\missing_full_fixed.txt';

$currentContent = file_get_contents($currentFile);
$missingContent = file_get_contents($missingFile);

$startMarker = 'function actualizarFiltroCatalogoContextual(tipoDocumento) {';
$endMarker = 'window.sincronizarValoresRealesBadges = function() {';

$startPos = strpos($currentContent, $startMarker);
$endPos = strpos($currentContent, $endMarker);

if ($startPos !== false && $endPos !== false) {
    // Reemplazamos todo lo que qued a medias con el bloque completo correcto extrado del backup
    $newContent = substr($currentContent, 0, $startPos) . $missingContent . "\n\n" . substr($currentContent, $endPos);
    file_put_contents($currentFile, $newContent);
    echo "REEMPLAZO EXITOSO";
} else {
    echo "ERROR: NO SE ENCONTRARON LOS MARCADORES";
}
