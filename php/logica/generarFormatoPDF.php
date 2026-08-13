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

    // Obtener información del formato para validar que existe
    $stmt_f = $db->prepare("SELECT id FROM formatos_matricula WHERE id = ?");
    $stmt_f->execute([$formato_id]);
    $formato = $stmt_f->fetch(PDO::FETCH_ASSOC);

    if (!$formato) {
        throw new Exception("Formato no encontrado.");
    }

    // Obtener datos del estudiante para validar que existe
    $stmt_e = $db->prepare("SELECT id FROM estudiantes WHERE id = ?");
    $stmt_e->execute([$estudiante_id]);
    $estudiante = $stmt_e->fetch(PDO::FETCH_ASSOC);

    if (!$estudiante) {
        throw new Exception("Estudiante no encontrado.");
    }

    // Iniciar buffer de salida
    ob_start();
    
    // Variables necesarias para el renderizado (simulando el GET de imprimir_matricula.php)
    $_GET['estudiante_id'] = $estudiante_id;
    $_GET['formato_id'] = $formato_id;
    
    // Incluir el archivo de renderizado
    require dirname(__DIR__, 2) . '/imprimir_matricula.php';
    
    // Capturar el HTML generado
    $html_completo = ob_get_clean();

    // Extraer el div con clase "print-document" de forma segura
    $patron = '/<div class="print-document"[^>]*>(.*?)<\/div>\s*<\/body>/s';
    if (preg_match($patron, $html_completo, $matches)) {
        $contenido_html = '<div class="print-document">' . $matches[1] . '</div>';
    } else {
        throw new Exception("No se pudo extraer el contenido del documento.");
    }

    // Definir la raíz del proyecto
    $raiz_proyecto = dirname(__DIR__, 2);
    
    // Reemplazar rutas relativas por rutas absolutas
    $contenido_html = str_replace('src="perseus.png"', 'src="' . $raiz_proyecto . '/perseus.png"', $contenido_html);
    $contenido_html = str_replace("src='perseus.png'", "src='" . $raiz_proyecto . "/perseus.png'", $contenido_html);
    
    $contenido_html = str_replace('src="uploads/fotos/', 'src="' . $raiz_proyecto . '/uploads/fotos/', $contenido_html);
    $contenido_html = str_replace("src='uploads/fotos/", "src='" . $raiz_proyecto . "/uploads/fotos/", $contenido_html);
    
    // También reemplazar cualquier otra ruta relativa común
    $contenido_html = str_replace('src="/uploads/', 'src="' . $raiz_proyecto . '/uploads/', $contenido_html);
    $contenido_html = str_replace("src='/uploads/", "src='" . $raiz_proyecto . "/uploads/", $contenido_html);

    // Construir el documento HTML completo para TCPDF
    $html_document = <<< 'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
        }
        .print-document {
            width: 215.9mm;
            height: 279.4mm;
            position: relative;
            overflow: hidden;
        }
        .canvas-block-wrapper {
            position: absolute;
        }
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
HTML;

    $html_document .= $contenido_html;
    $html_document .= "\n</body>\n</html>";

    // Configurar y generar el PDF
    $tcpdf_path = dirname(__DIR__, 2) . '/assets/libs/tcpdf/';
    if (!file_exists($tcpdf_path . 'tcpdf.php')) {
        throw new Exception("TCPDF no encontrado en: " . $tcpdf_path);
    }

    require_once $tcpdf_path . 'tcpdf_autoconfig.php';
    require_once $tcpdf_path . 'tcpdf.php';

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->AddPage('P', 'A4');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->writeHTML($html_document, true, false, true, false, '');

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
?>
