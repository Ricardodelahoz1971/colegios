<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/OBTENER_EVENTOS.PHP
include '../db.php';
include '../auth.php';

if (!isset($_SESSION['usuario_id']) || !tiene_permiso('cronograma')) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Importar el motor legal de festivos
require_once __DIR__ . '/helpers_festivos.php';

// Obtener solo eventos personalizados de la escuela (omitir los festivos nacionales estáticos precargados en BD)
$stmt_stmt = $db->prepare("SELECT * FROM cronograma WHERE tipo != 'NACIONAL' ORDER BY fecha ASC"); $stmt_stmt->execute(); $stmt = $stmt_stmt;
$eventos = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $eventos[] = $row;
}

// Inyectar dinámicamente los festivos nacionales de la Ley Emiliani para el año actual (2026 en este caso) y adyacentes
$anio_actual = (int)date('Y');
$festivos_calculados = array_merge(
    calcular_festivos_colombia($anio_actual - 1),
    calcular_festivos_colombia($anio_actual),
    calcular_festivos_colombia($anio_actual + 1)
);

foreach ($festivos_calculados as $f) {
    $eventos[] = [
        'id' => null, // Omitir ID para evitar que se confundan con la tabla física
        'titulo' => $f['titulo'],
        'descripcion' => $f['descripcion'],
        'tipo' => 'NACIONAL',
        'color' => $f['color'],
        'fecha' => $f['fecha']
    ];
}

// 🏛️ INTEGRACIÓN DEL CENTINELA: INCORPORAR RECESOS ESCOLARES AL CRONOGRAMA VISUAL
try {
    $stmt_rec = $db->prepare("SELECT nombre, fecha_inicio, fecha_fin FROM recesos_escolares ORDER BY fecha_inicio ASC");
    $stmt_rec->execute();
    $recesos = $stmt_rec->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($recesos as $r) {
        $curr = strtotime($r['fecha_inicio']);
        $end = strtotime($r['fecha_fin']);
        
        while ($curr <= $end) {
            $fecha_str = date('Y-m-d', $curr);
            $eventos[] = [
                'id' => 99000 + $curr, // ID virtual único
                'titulo' => $r['nombre'],
                'descripcion' => 'Periodo de receso escolar oficial.',
                'tipo' => 'FESTIVO', // Activa comportamiento de receso/festivo
                'color' => '#f59e0b', // Color institucional para advertencias (naranja)
                'fecha' => $fecha_str
            ];
            $curr = strtotime('+1 day', $curr);
        }
    }
} catch (Exception $e) {
    // Falla silenciosa si no existe la tabla
}

header('Content-Type: application/json');
echo json_encode($eventos);
exit;
?>

