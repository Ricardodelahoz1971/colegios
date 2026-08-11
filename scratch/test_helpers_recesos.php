<?php
include 'php/db.php';
include 'php/logica/helpers_recesos.php';

// Limpiar recesos previos de prueba
$db->exec("DELETE FROM recesos_escolares");

// Agregar un receso de prueba de 5 días de Semana Santa (Lunes a Viernes)
$stmt = $db->prepare("INSERT INTO recesos_escolares (nombre, fecha_inicio, fecha_fin) VALUES ('Semana Santa Test', '2026-04-06', '2026-04-10')");
$stmt->execute();

// Fecha origen: Viernes 2026-04-03
// Queremos sumar 3 días hábiles
// Paso 1: Lunes 2026-04-06 (receso, saltar)
// Paso 2: Martes 2026-04-07 (receso, saltar)
// Paso 3: Miércoles 2026-04-08 (receso, saltar)
// Paso 4: Jueves 2026-04-09 (receso, saltar)
// Paso 5: Viernes 2026-04-10 (receso, saltar)
// Paso 6: Sábado 2026-04-11 (fin de semana, saltar)
// Paso 7: Domingo 2026-04-12 (fin de semana, saltar)
// Paso 8: Lunes 2026-04-13 (1er día hábil)
// Paso 9: Martes 2026-04-14 (2do día hábil)
// Paso 10: Miércoles 2026-04-15 (3er día hábil)
$fecha_origen = '2026-04-03 12:00:00';
$dias_habiles = 3;
$limite = calcular_fecha_limite_con_recesos($db, $fecha_origen, $dias_habiles);

echo "Fecha origen: $fecha_origen\n";
echo "Días hábiles a sumar: $dias_habiles\n";
echo "Fecha límite calculada: $limite (Esperado: 2026-04-15 12:00:00)\n";

if (str_starts_with($limite, '2026-04-15')) {
    echo "¡PRUEBA EXITOSA!\n";
} else {
    echo "¡PRUEBA FALLIDA!\n";
}
