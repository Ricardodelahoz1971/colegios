<?php
declare(strict_types=1);
require_once 'php/db.php';
require_once 'php/auth.php';


$id = (int)($_GET['id'] ?? 0);
$curso_id = (int)($_GET['curso_id'] ?? 0);

// 1. Obtener Datos del Curso y Estudiantes
$stmt = $db->prepare("SELECT e.*, c.nombre_curso 
                      FROM estudiantes e 
                      JOIN cursos c ON e.curso_id = c.id 
                      WHERE e.curso_id = ? 
                      ORDER BY e.apellido, e.nombre");
$stmt->execute([$curso_id]);
$estudiantes = $stmt->fetchAll();

$stmt_p = $db->prepare("SELECT titulo FROM eval_pruebas WHERE id = ?");
$stmt_p->execute([$id]);
$prueba_titulo = $stmt_p->fetchColumn() ?: 'Prueba';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Control QR - <?php echo htmlspecialchars($prueba_titulo); ?></title>
    <link rel="stylesheet" href="assets/libs/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles/ui_kit.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/modules/imprimir_codigos.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="no-print p-3 bg-dark text-white text-center">
    <h5 class="mb-2">HOJA DE CONTROL QR (PARA ESCANEO RÁPIDO)</h5>
    <button onclick="window.print()" class="btn-elite px-5">IMPRIMIR HOJA CARTA</button>
</div>

<div class="sheet">
    <div class="text-center mb-5 border-bottom pb-3">
        <h3 class="fw-bold text-titulo-elite">TABLERO DE CONTROL ARES</h3>
        <p class="mb-0 text-muted"><?php echo htmlspecialchars($prueba_titulo); ?> | CURSO: <?php echo htmlspecialchars($estudiantes[0]['nombre_curso'] ?? 'N/A'); ?></p>
    </div>

    <div class="row row-cols-3 g-4">
        <?php foreach ($estudiantes as $est): 
            $qr_payload = "PRU_{$id}_EST_{$est['id']}";
        ?>
            <div class="col">
                <div class="qr-card shadow-sm-elite">
                    <div class="qr-render qr-placeholder" data-payload="<?php echo $qr_payload; ?>"></div>
                    <div class="student-name"><?php echo htmlspecialchars($est['nombre'] . ' ' . $est['apellido']); ?></div>
                    <div class="course-badge">ID: <?php echo $est['id']; ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-5 pt-5 text-center opacity-50 small">
        SISTEMA DE EVALUACIÓN ELITE | GENERADO EL <?php echo date('d/m/Y H:i'); ?>
    </div>
</div>

<script src="assets/libs/qrcode/qrcode.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(() => {
            const placeholders = document.querySelectorAll('.qr-placeholder');
            placeholders.forEach(el => {
                new QRCode(el, {
                    text: el.getAttribute('data-payload'),
                    width: 120,
                    height: 120,
                    typeNumber: 2,
                    colorDark : "#000000", // Negro absoluto necesario para el contraste de escaneo
                    colorLight : "#ffffff", // Blanco necesario para el contraste de escaneo
                    correctLevel : QRCode.CorrectLevel.M
                });
            });
        }, 200);
    });
</script>

</body>
</html>
