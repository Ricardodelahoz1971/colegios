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

    $stmt_f = $db->prepare("SELECT * FROM formatos_matricula WHERE id = ?");
    $stmt_f->execute([$formato_id]);
    $formato = $stmt_f->fetch(PDO::FETCH_ASSOC);

    if (!$formato) {
        throw new Exception("Formato no encontrado.");
    }

    $stmt_e = $db->prepare("
        SELECT e.*, c.nombre_curso, da.*
        FROM estudiantes e
        LEFT JOIN cursos c ON e.curso_id = c.id
        LEFT JOIN estudiantes_datos_adicionales da ON e.id = da.estudiante_id
        WHERE e.id = ?
    ");
    $stmt_e->execute([$estudiante_id]);
    $estudiante = $stmt_e->fetch(PDO::FETCH_ASSOC);

    if (!$estudiante) {
        throw new Exception("Estudiante no encontrado.");
    }

    $tcpdf_path = dirname(__DIR__, 2) . '/assets/libs/tcpdf/';
    if (!file_exists($tcpdf_path . 'tcpdf.php')) {
        throw new Exception("TCPDF no encontrado en: " . $tcpdf_path);
    }

    require_once $tcpdf_path . 'tcpdf_autoconfig.php';
    require_once $tcpdf_path . 'tcpdf.php';

    $contenido_html = $formato['contenido_html'] ?? '';
    if (empty($contenido_html)) {
        throw new Exception("Contenido de formato vacío.");
    }

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
    </style>
</head>
<body>
HTML;

    $html_document .= $contenido_html;
    $html_document .= "\n</body>\n</html>";

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
