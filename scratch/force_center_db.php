<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    $stmt = $db->prepare("SELECT contenido_html, configuracion_json FROM formatos_matricula WHERE id = 30");
    $stmt->execute();
    $formato = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($formato) {
        // Modificar HTML
        $html = $formato['contenido_html'];
        // Reemplazar data-align="left" por data-align="center" para titulo_colegio
        $html = str_replace('data-tipo="titulo_colegio" data-left_mm="30" data-top_mm="10.27" data-width_mm="118" data-height_mm="10.32" data-size="22" data-align="left"', 'data-tipo="titulo_colegio" data-left_mm="30" data-top_mm="10.27" data-width_mm="118" data-height_mm="10.32" data-size="22" data-align="center"', $html);
        
        // Modificar JSON
        $json = json_decode($formato['configuracion_json'], true);
        if (is_array($json) && isset($json['bloques'])) {
            foreach ($json['bloques'] as &$bloque) {
                if ($bloque['type'] === 'titulo_colegio') {
                    $bloque['align'] = 'center';
                }
            }
        }
        $json_str = json_encode($json, JSON_UNESCAPED_UNICODE);
        
        $update = $db->prepare("UPDATE formatos_matricula SET contenido_html = ?, configuracion_json = ? WHERE id = 30");
        $update->execute([$html, $json_str]);
        echo "Formato 30 actualizado con éxito a Centrado en BD.\n";
    } else {
        echo "No se encontró el formato 30.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
