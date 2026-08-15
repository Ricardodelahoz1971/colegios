<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

try {
    guardia_sesion();
    session_write_close();

    $estudiante_id = (int)($_GET['estudiante_id'] ?? 0);
    $formato_id = (int)($_GET['formato_id'] ?? 0);

    if ($estudiante_id <= 0 || $formato_id <= 0) {
        throw new Exception("Parámetros inválidos.");
    }

    // 1. Obtener la plantilla y configuración JSON de la base de datos
    $stmt_f = $db->prepare("SELECT id, configuracion_json, margen_superior, margen_inferior, margen_izquierdo, margen_derecho, tamano_lienzo FROM formatos_matricula WHERE id = ?");
    $stmt_f->execute([$formato_id]);
    $formato = $stmt_f->fetch(PDO::FETCH_ASSOC);

    if (!$formato) {
        throw new Exception("Formato no encontrado.");
    }

    // 2. Ejecutar la lógica de inicialización y renderizado de imprimir_matricula.php en búfer
    ob_start();
    // Inyectar parámetros que espera imprimir_matricula.php
    $_GET['estudiante_id'] = $estudiante_id;
    $_GET['formato_id'] = $formato_id;
    
    require dirname(__DIR__, 2) . '/imprimir_matricula.php';
    ob_end_clean();

    // 3. Decodificar la configuración de bloques del JSON
    $config_json = json_decode($formato['configuracion_json'] ?? '[]', true);
    if (!is_array($config_json)) {
        throw new Exception("Configuración de formato no válida o vacía.");
    }

    // 4. Instanciar TCPDF
    $tcpdf_path = dirname(__DIR__, 2) . '/assets/libs/tcpdf/';
    if (!file_exists($tcpdf_path . 'tcpdf.php')) {
        throw new Exception("TCPDF no encontrado.");
    }

    require_once $tcpdf_path . 'tcpdf_autoconfig.php';
    require_once $tcpdf_path . 'tcpdf.php';

    // Determinar tamaño de papel (Carta por defecto, Carta = LETTER)
    $tamano_lienzo = strtolower($formato['tamano_lienzo'] ?? 'carta') === 'carta' ? 'LETTER' : 'A4';
    $papel_width = $tamano_lienzo === 'LETTER' ? 215.9 : 210.0;

    $pdf = new TCPDF('P', 'mm', $tamano_lienzo, true, 'UTF-8', false);
    
    // Configuración técnica estricta del documento para evadir saltos e inline absolutos
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Perseus Cloud');
    $pdf->SetTitle('Ficha de Matrícula');
    
    // Desactivar cabeceras y pies de página nativos de TCPDF para liberar los márgenes de 10mm
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    $pdf->AddPage();
    
    // Forzar márgenes en 0 y remover paddings internos
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(0);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->setCellPaddings(0, 0, 0, 0);
    $pdf->setCellMargins(0, 0, 0, 0);
    
    $pdf->SetFont('helvetica', '', 10);

    $raiz_proyecto = dirname(__DIR__, 2);

    // 5. Iterar e inyectar bloques con posicionamiento nativo
    foreach ($config_json as $block) {
        $tipo = $block['type'] ?? '';
        if ($tipo === 'salto_pagina') {
            $pdf->AddPage();
            continue;
        }

        $x = isset($block['left_mm']) ? (float)$block['left_mm'] : 0.0;
        $y = isset($block['top_mm']) ? (float)$block['top_mm'] : 0.0;
        
        $m_izq = (float)($formato['margen_izquierdo'] ?? 20);
        $m_der = (float)($formato['margen_derecho'] ?? 20);
        
        $w = !empty($block['width_mm']) ? (float)$block['width_mm'] : ($papel_width - $m_izq - $m_der);
        $h = !empty($block['height_mm']) ? (float)$block['height_mm'] : 0.0;

        $es_bloque_ancho_completo = in_array($tipo, ['ficha', 'calificaciones', 'texto_certificacion', 'linea']);
        if ($es_bloque_ancho_completo) {
            $x = $m_izq;
            $w = $papel_width - $m_izq - $m_der;
        }

        $html_block = '';

        if ($tipo === 'texto') {
            $contenido = $block['content'] ?? '';
            // Reemplazar dinámicamente las variables badge por sus valores reales del estudiante
            $html_block = preg_replace_callback(
                '/<span[^>]*class="[^"]*ares-variable-badge[^"]*"[^>]*data-var="([^"]+)"[^>]*>.*?<\/span>/i',
                function($m_badge) use ($var_map, $alias_extra) {
                    $var_code = $m_badge[1];
                    if (isset($var_map[$var_code])) {
                        return htmlspecialchars((string)$var_map[$var_code], ENT_QUOTES, 'UTF-8');
                    }
                    if (isset($alias_extra[$var_code])) {
                        return htmlspecialchars((string)$alias_extra[$var_code], ENT_QUOTES, 'UTF-8');
                    }
                    return '';
                },
                $contenido
            );
        } else {
            // Invocar el renderizador heredado de imprimir_matricula.php para bloques avanzados
            $cols = isset($block['columnas']) ? (int)$block['columnas'] : null;
            $html_block = $renderizador($tipo, $cols);
        }

        if (trim($html_block) === '') {
            continue;
        }

        // Aplicar tamaño y alineación dinámicos usando bloque style concatenado (Decálogo de Oro)
        if ($tipo === 'titulo_colegio' || $tipo === 'lema_colegio' || $tipo === 'metadatos') {
            $defaultSize = ($tipo === 'titulo_colegio') ? '20' : (($tipo === 'lema_colegio') ? '12' : '16');
            $size = $block['size'] ?? $defaultSize;
            $align = $block['align'] ?? 'center';
            $open_tag_css = '<' . 'style' . '>';
            $close_tag_css = '</' . 'style' . '>';
            $selector = ($tipo === 'metadatos') ? '.metadatos-titulo-linea' : 
                        (($tipo === 'lema_colegio') ? '.ares-lema-cabecera' : '.ares-titulo-cabecera');
            $html_block = $open_tag_css . $selector . ' { font-size: ' . $size . 'pt; text-align: ' . $align . '; }' . $close_tag_css . $html_block;
        }

        // Escalar la imagen del logo al ancho real en píxeles (1mm = 3.78px) para evitar desbordamiento asimétrico en TCPDF
        if ($tipo === 'logo') {
            $w_px = (int)($w * 3.78);
            $html_block = str_replace('<img class="ares-logo-cabecera"', '<img class="ares-logo-cabecera" width="' . $w_px . '"', $html_block);
        }

        // Convertir todas las rutas de imágenes locales a rutas absolutas del sistema para TCPDF
        $html_block = str_replace('src="perseus.png"', 'src="' . $raiz_proyecto . '/perseus.png"', $html_block);
        $html_block = str_replace("src='perseus.png'", "src='" . $raiz_proyecto . "/perseus.png'", $html_block);
        $html_block = str_replace('src="uploads/fotos/', 'src="' . $raiz_proyecto . '/uploads/fotos/', $html_block);
        $html_block = str_replace("src='uploads/fotos/", "src='" . $raiz_proyecto . "/uploads/fotos/", $html_block);
        $html_block = str_replace('src="/uploads/', 'src="' . $raiz_proyecto . '/uploads/', $html_block);
        $html_block = str_replace("src='/uploads/", "src='" . $raiz_proyecto . "/uploads/", $html_block);

        // Dibujar el bloque en el PDF usando la API de posicionamiento nativo
        $pdf->writeHTMLCell($w, $h, $x, $y, $html_block, 0, 0, false, true, '', true);
    }

    $filename = 'matricula_' . $estudiante_id . '_' . time() . '.pdf';
    $pdf->Output($filename, 'D');

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit();
}
