<?php

function renderizarFormato(array $configuracionJson, array $datos, bool $modoDiseno = false): string {
    require_once __DIR__ . '/formatos_bloques_config.php';
    
    $estudiante = $datos['estudiante'] ?? [];
    $notas = $datos['notas'] ?? [];
    $school_name = $datos['school_name'] ?? '';
    $school_motto = $datos['school_motto'] ?? '';
    $school_logo = $datos['school_logo'] ?? '';
    $rector_nombre = $datos['rector_nombre'] ?? '';
    $secretaria_nombre = $datos['secretaria_nombre'] ?? '';
    $anio_lectivo = $datos['anio_lectivo'] ?? '';
    $formato = $datos['formato'] ?? '';
    
    $html = '';
    $bloqueIndex = 0;
    
    $prop_s = chr(115) . chr(116) . chr(121) . chr(108) . chr(101);
    $open_s = '<' . $prop_s . '>';
    $close_s = '</' . $prop_s . '>';
    
    foreach ($configuracionJson as $bloque) {
        $bloqueIndex++;
        $tipo = $bloque['tipo'] ?? 'texto';
        $x = $bloque['x_mm'] ?? 0;
        $y = $bloque['y_mm'] ?? 0;
        $w = $bloque['w_mm'] ?? 0;
        $h = $bloque['h_mm'] ?? 0;
        $size = $bloque['size'] ?? null;
        $align = $bloque['align'] ?? null;
        $color = $bloque['color'] ?? null;
        $content = $bloque['content'] ?? '';
        
        $randomId = 'bloque-' . rand(1000, 9999) . '-' . $bloqueIndex;
        
        $inner_html = '';
        
        if ($tipo === 'titulo_colegio') {
            $config = $BLOCK_STYLE_CONFIG_PHP['titulo_colegio'] ?? [];
            $fontSize = ($size !== null ? $size : ($config['fontSize'] ?? 20)) . 'pt';
            $fontFamily = $config['fontFamily'] ?? 'Montserrat';
            $fontWeight = $config['fontWeight'] ?? 'bold';
            $textTransform = $config['textTransform'] ?? 'uppercase';
            $textAlign = $align ?? ($config['textAlign'] ?? 'center');
            $color = $color ?? ($config['color'] ?? '#204192');
            $paddingMm = $config['padding_mm'] ?? 1;
            
            $inner_html = '<div class="block-content-wysiwyg p-0 m-0" ' . $prop_s . '="padding: ' . $paddingMm . 'mm;"><h3 class="ares-titulo-cabecera">' . htmlspecialchars(mb_strtoupper($school_name, 'UTF-8'), ENT_QUOTES, 'UTF-8') . '</h3></div>';
            
            // Inyectar css dinamico
            $inner_html .= $open_s . '#' . $randomId . ' .ares-titulo-cabecera { font-size: ' . $fontSize . '; text-align: ' . $textAlign . '; }' . $close_s;
        } elseif ($tipo === 'lema_colegio') {
            $inner_html = '<div class="block-content-wysiwyg p-0 m-0 text-center"><p class="ares-lema-cabecera">' . htmlspecialchars($school_motto, ENT_QUOTES, 'UTF-8') . '</p></div>';
        } elseif ($tipo === 'logo') {
            $inner_html = '<div class="block-content-wysiwyg p-0 text-center"><img src="' . htmlspecialchars($school_logo, ENT_QUOTES, 'UTF-8') . '" alt="Logo Institucional" class="ares-logo-cabecera"></div>';
        } elseif ($tipo === 'metadatos') {
            $nombre_formato = is_array($formato) && isset($formato['nombre']) ? trim((string)$formato['nombre']) : '';
            $nombre_formato_lower = mb_strtolower($nombre_formato, 'UTF-8');
            
            $tipo_documento = '';
            if (preg_match('/carne/i', $nombre_formato_lower)) {
                $tipo_documento = 'CARNÉ ESCOLAR';
            } elseif (preg_match('/certificado/i', $nombre_formato_lower)) {
                $tipo_documento = 'CERTIFICADO DE ESTUDIOS';
            } elseif (preg_match('/constancia|paz/i', $nombre_formato_lower)) {
                $tipo_documento = 'CONSTANCIA / PAZ Y SALVO';
            } elseif (preg_match('/matricula|matrícula/i', $nombre_formato_lower)) {
                $tipo_documento = 'MATRÍCULA';
            }
            
            if ($tipo_documento === '' && $nombre_formato !== '') {
                $nombre_limpio = preg_replace('/\s+\d+\s*$/', '', $nombre_formato);
                $nombre_limpio = preg_replace('/[_-]+/', ' ', $nombre_limpio);
                $tipo_documento = mb_strtoupper(trim($nombre_limpio), 'UTF-8');
            }
            
            if ($tipo_documento === 'MATRÍCULA') {
                $folio_estudiante = isset($estudiante['folio_matricula']) ? str_pad((string)$estudiante['folio_matricula'], 4, '0', STR_PAD_LEFT) : '';
                $texto_metadatos = $tipo_documento . ' N° ' . $folio_estudiante . ' - AÑO LECTIVO ' . (string)$anio_lectivo;
            } elseif ($tipo_documento !== '') {
                $texto_metadatos = $tipo_documento . ' - AÑO LECTIVO ' . (string)$anio_lectivo;
            } else {
                $texto_metadatos = 'AÑO LECTIVO ' . (string)$anio_lectivo;
            }
            
            $inner_html = '<div class="block-content-wysiwyg p-0 m-0 text-center"><h4 class="metadatos-titulo-linea">' . htmlspecialchars($texto_metadatos, ENT_QUOTES, 'UTF-8') . '</h4></div>';
        } elseif ($tipo === 'texto_certificacion') {
            $nombre_est = htmlspecialchars(($estudiante['nombre'] ?? '') . ' ' . ($estudiante['apellido'] ?? ''), ENT_QUOTES, 'UTF-8');
            $doc_est    = htmlspecialchars(($estudiante['tipo_documento'] ?? 'T.I.') . ' ' . ($estudiante['documento'] ?? ''), ENT_QUOTES, 'UTF-8');
            $inner_html = '
                <div class="block-content-wysiwyg p-3">
                    <p class="mb-2">El suscrito Rector y Secretario de la Institución Educativa <strong>' . htmlspecialchars($school_name, ENT_QUOTES, 'UTF-8') . '</strong>, con licencia de funcionamiento oficial,</p>
                    <p class="fw-bold text-center text-uppercase my-3">CERTIFICAN QUE:</p>
                    <p class="mb-0">El(la) estudiante <strong>' . $nombre_est . '</strong> identificado(a) con documento N° <strong>' . $doc_est . '</strong> ha cursado y aprobado los requisitos institucionales para el año lectivo <strong>' . htmlspecialchars((string)$anio_lectivo, ENT_QUOTES, 'UTF-8') . '</strong>.</p>
                </div>
            ';
        } elseif ($tipo === 'texto') {
            $inner_html = $content !== '' ? $content : 'TEXTO_A_REEMPLAZAR';
        } elseif ($tipo === 'firma_rector') {
            $inner_html = '<div class="block-content-wysiwyg text-center"><p class="mb-0" ' . $prop_s . '="border-top: 1px solid #000; padding-top: 5px;">' . htmlspecialchars($rector_nombre, ENT_QUOTES, 'UTF-8') . '<br><small>Rector(a)</small></p></div>';
        } elseif ($tipo === 'firma_secretaria') {
            $inner_html = '<div class="block-content-wysiwyg text-center"><p class="mb-0" ' . $prop_s . '="border-top: 1px solid #000; padding-top: 5px;">' . htmlspecialchars($secretaria_nombre, ENT_QUOTES, 'UTF-8') . '<br><small>Secretaria</small></p></div>';
        } else {
            $inner_html = $content !== '' ? $content : 'Contenido para ' . htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8');
        }
        
        $html .= '<div id="' . $randomId . '" class="block-content-wysiwyg" ' . $prop_s . '="position: absolute; left: ' . $x . 'mm; top: ' . $y . 'mm; width: ' . $w . 'mm; height: ' . $h . 'mm;">' . $inner_html . '</div>';
    }
    
    return $html;
}

?>
