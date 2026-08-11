<?php
declare(strict_types=1);

/**
 * 🏛️ SANDBOX SEEDER & PURGER DE NOTAS DEMO (PERSEUS ENGINE)
 * Herramienta de pruebas de alta fidelidad para el Reporte BI sin contaminación de datos.
 * 
 * @author Ingeniería Élite v9.5
 * @version 1.0 (Sandbox Edition)
 */

require_once __DIR__ . '/../php/db.php';

$mensaje = '';
$tipo_mensaje = 'info';

$accion = $_POST['accion'] ?? $_GET['accion'] ?? $argv[1] ?? '';

// 1. OBTENER ESTADÍSTICAS ACTUALES DE DATOS DEMO
function obtenerStatsDemo($db): array {
    try {
        $stmt_act = $db->query("SELECT COUNT(*) FROM ares_actividades WHERE titulo LIKE '%[DEMO-BI]%'");
        $cnt_act = $stmt_act->fetchColumn();

        $stmt_cal = $db->query("
            SELECT COUNT(*) 
            FROM ares_calificaciones_desglose 
            WHERE actividad_id IN (SELECT id FROM ares_actividades WHERE titulo LIKE '%[DEMO-BI]%')
        ");
        $cnt_cal = $stmt_cal->fetchColumn();

        $stmt_pr = $db->query("SELECT COUNT(*) FROM eval_pruebas WHERE titulo LIKE '%[DEMO-BI]%'");
        $cnt_pr = $stmt_pr->fetchColumn();

        $stmt_as = $db->query("
            SELECT COUNT(*) 
            FROM eval_asignaciones 
            WHERE prueba_id IN (SELECT id FROM eval_pruebas WHERE titulo LIKE '%[DEMO-BI]%')
        ");
        $cnt_as = $stmt_as->fetchColumn();

        $stmt_re = $db->query("
            SELECT COUNT(*) 
            FROM eval_respuestas 
            WHERE asignacion_id IN (
                SELECT id FROM eval_asignaciones WHERE prueba_id IN (SELECT id FROM eval_pruebas WHERE titulo LIKE '%[DEMO-BI]%')
            )
        ");
        $cnt_re = $stmt_re->fetchColumn();

        return [
            'actividades' => (int)$cnt_act,
            'calificaciones' => (int)$cnt_cal,
            'pruebas' => (int)$cnt_pr,
            'asignaciones' => (int)$cnt_as,
            'respuestas' => (int)$cnt_re
        ];
    } catch (Exception $e) {
        return ['actividades' => 0, 'calificaciones' => 0, 'pruebas' => 0, 'asignaciones' => 0, 'respuestas' => 0];
    }
}

// 2. LOGICA DE PURGADO QUIRÚRGICO (RIESGO CERO)
function purgarDatosDemo($db): string {
    $db->beginTransaction();
    try {
        // Eliminar respuestas asociadas a asignaciones demo
        $db->exec("
            DELETE FROM eval_respuestas 
            WHERE asignacion_id IN (
                SELECT id FROM eval_asignaciones WHERE prueba_id IN (SELECT id FROM eval_pruebas WHERE titulo LIKE '%[DEMO-BI]%')
            )
        ");

        // Eliminar asignaciones demo
        $db->exec("
            DELETE FROM eval_asignaciones 
            WHERE prueba_id IN (SELECT id FROM eval_pruebas WHERE titulo LIKE '%[DEMO-BI]%')
        ");

        // Eliminar pruebas demo
        $db->exec("DELETE FROM eval_pruebas WHERE titulo LIKE '%[DEMO-BI]%'");

        // Eliminar calificaciones desglose de actividades demo
        $db->exec("
            DELETE FROM ares_calificaciones_desglose 
            WHERE actividad_id IN (SELECT id FROM ares_actividades WHERE titulo LIKE '%[DEMO-BI]%')
        ");

        // Eliminar actividades demo
        $db->exec("DELETE FROM ares_actividades WHERE titulo LIKE '%[DEMO-BI]%'");

        $db->commit();
        return "🧹 Purga quirúrgica completada con éxito. Absolutamente ningún residuo demo queda en el sistema escolar.";
    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception("Error al purgar datos: " . $e->getMessage());
    }
}

// 3. LOGICA DE POBLADO DE ALTA FIDELIDAD (SEED)
function inyectarDatosDemo($db): string {
    // Primero, limpiar cualquier demo anterior para evitar colisiones
    purgarDatosDemo($db);

    $db->beginTransaction();
    try {
        // Obtener todos los cursos
        $stmt_c = $db->query("SELECT id, nombre_curso FROM cursos");
        $cursos = $stmt_c->fetchAll();

        // Obtener especialidades / materias con carga académica
        $stmt_ca = $db->query("SELECT id, curso_id, especialidad_id, docente_id FROM carga_academica");
        $cargas = $stmt_ca->fetchAll();

        if (empty($cargas)) {
            $db->rollBack();
            return "⚠️ No hay carga académica en el sistema. Debe asignar materias a los cursos primero para poder generar notas.";
        }

        // Año escolar actual y fechas del periodo 2 (Abril 1 a Junio 30)
        $anio = date('Y');
        $fechas_acts = [
            1 => "$anio-04-12 10:00:00", // Saber
            2 => "$anio-05-08 14:30:00", // Hacer
            3 => "$anio-05-18 09:00:00"  // Ser
        ];
        $fecha_examen = "$anio-05-20 08:00:00";
        $fecha_fin_examen = "$anio-05-20 23:59:59";

        $total_actividades = 0;
        $total_calificaciones = 0;
        $total_pruebas = 0;
        $total_asignaciones = 0;
        $total_respuestas = 0;

        // Por cada carga académica
        foreach ($cargas as $ca) {
            $ca_id = (int)$ca['id'];
            $curso_id = (int)$ca['curso_id'];
            $esp_id = (int)$ca['especialidad_id'];
            $doc_id = (int)$ca['docente_id'];

            // Obtener estudiantes matriculados en este curso
            $stmt_est = $db->prepare("SELECT id FROM estudiantes WHERE curso_id = ?");
            $stmt_est->execute([$curso_id]);
            $estudiantes = $stmt_est->fetchAll(PDO::FETCH_COLUMN);

            if (empty($estudiantes)) {
                continue; // Saltar si el curso no tiene estudiantes
            }

            // A. CREAR LAS 3 ACTIVIDADES DIARIAS (Saber, Hacer, Ser)
            $acts_generadas = [];
            foreach ($fechas_acts as $clase_nota_id => $fecha) {
                $dim_nombre = $clase_nota_id === 1 ? 'Cognitiva (Saber)' : ($clase_nota_id === 2 ? 'Procedimental (Hacer)' : 'Actitudinal (Ser)');
                $titulo = "[DEMO-BI] Actividad Diaria: " . $dim_nombre;
                
                $stmt_ins_act = $db->prepare("
                    INSERT INTO ares_actividades (docente_id, curso_id, especialidad_id, clase_nota_id, titulo, tipo_evaluacion, fecha_registro)
                    VALUES (?, ?, ?, ?, ?, 'Clase', ?)
                ");
                $stmt_ins_act->execute([$doc_id, $curso_id, $esp_id, $clase_nota_id, $titulo, $fecha]);
                $act_id = (int)$db->lastInsertId();
                $acts_generadas[] = $act_id;
                $total_actividades++;
            }

            // B. CREAR EL EXAMEN EN LÍNEA (Saber)
            $titulo_examen = "[DEMO-BI] Evaluación Parcial de Materia";
            $stmt_ins_pr = $db->prepare("
                INSERT INTO eval_pruebas (docente_id, materia_id, titulo, instrucciones, tiempo_limite, estado, fecha_creacion, modalidad)
                VALUES (?, ?, ?, 'Responda con honestidad.', 60, 1, ?, 1)
            ");
            $stmt_ins_pr->execute([$doc_id, $esp_id, $titulo_examen, $fecha_examen]);
            $prueba_id = (int)$db->lastInsertId();
            $total_pruebas++;

            // Asignar el examen
            $stmt_ins_as = $db->prepare("
                INSERT INTO eval_asignaciones (prueba_id, curso_id, docente_id, fecha_inicio, fecha_fin, clave_acceso, estado, intentos_permitidos, mostrar_resultados, fecha_creacion)
                VALUES (?, ?, ?, ?, ?, '1234', 1, 1, 1, ?)
            ");
            $stmt_ins_as->execute([$prueba_id, $curso_id, $doc_id, $fecha_examen, $fecha_fin_examen, $fecha_examen]);
            $asignacion_id = (int)$db->lastInsertId();
            $total_asignaciones++;

            // C. POBLAR CALIFICACIONES DE ALTA FIDELIDAD PARA CADA ESTUDIANTE
            foreach ($estudiantes as $est_id) {
                $est_id = (int)$est_id;

                // Calificación de actividades
                foreach ($acts_generadas as $idx => $act_id) {
                    // Distribución realista de calificaciones entre 1.0 y 5.0 (focalizado en el rango medio-alto, con casos de reprobación)
                    $rand = mt_rand(1, 100);
                    if ($rand <= 8) { // 8% de reprobación crítica (1.0 a 2.9)
                        $calif = mt_rand(10, 29) / 10.0;
                    } elseif ($rand <= 22) { // 14% de aprobación básica (3.0 a 3.7)
                        $calif = mt_rand(30, 37) / 10.0;
                    } elseif ($rand <= 75) { // 53% de aprobación alta (3.8 a 4.5)
                        $calif = mt_rand(38, 45) / 10.0;
                    } else { // 25% de excelencia superior (4.6 a 5.0)
                        $calif = mt_rand(46, 50) / 10.0;
                    }

                    $stmt_ins_cal = $db->prepare("
                        INSERT INTO ares_calificaciones_desglose (actividad_id, criterio_id, estudiante_id, calificacion, fecha_registro)
                        VALUES (?, 0, ?, ?, ?)
                    ");
                    $stmt_ins_cal->execute([$act_id, $est_id, $calif, $fechas_acts[$idx + 1]]);
                    $total_calificaciones++;
                }

                // Calificación del Examen
                $rand_ex = mt_rand(1, 100);
                if ($rand_ex <= 12) { // 12% reprueba el examen
                    $calif_ex = mt_rand(15, 29) / 10.0;
                } elseif ($rand_ex <= 30) {
                    $calif_ex = mt_rand(30, 37) / 10.0;
                } elseif ($rand_ex <= 80) {
                    $calif_ex = mt_rand(38, 45) / 10.0;
                } else {
                    $calif_ex = mt_rand(46, 50) / 10.0;
                }

                $stmt_ins_res = $db->prepare("
                    INSERT INTO eval_respuestas (estudiante_id, prueba_id, pregunta_id, respuesta_alumno, puntaje_obtenido, comentario_docente, estado, fecha_entrega, asignacion_id, calificacion_automatica, calificacion_manual, respuestas_json)
                    VALUES (?, ?, 0, '', ?, NULL, 2, ?, ?, ?, NULL, '')
                ");
                $stmt_ins_res->execute([$est_id, $prueba_id, $calif_ex, $fecha_examen, $asignacion_id, $calif_ex]);
                $total_respuestas++;
            }
        }

        $db->commit();
        return "🌱 Inyección de alta fidelidad completada con éxito.<br>" .
               "Se crearon <b>$total_actividades</b> actividades diarias, <b>$total_calificaciones</b> calificaciones, <b>$total_pruebas</b> exámenes de periodo y <b>$total_respuestas</b> respuestas para los <b>69</b> estudiantes en forma segura.";
    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception("Error al inyectar datos: " . $e->getMessage());
    }
}

// 4. MANEJAR ACCIONES HTTP
if ($accion === 'seed') {
    try {
        $mensaje = inyectarDatosDemo($db);
        $tipo_mensaje = 'success';
    } catch (Exception $e) {
        $mensaje = "❌ " . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
} elseif ($accion === 'purgar') {
    try {
        $mensaje = purgarDatosDemo($db);
        $tipo_mensaje = 'success';
    } catch (Exception $e) {
        $mensaje = "❌ " . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

$stats = obtenerStatsDemo($db);
$total_demo = array_sum($stats);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soberanía de Pruebas: Sandbox BI - Ares</title>
    <!-- Google Fonts Institutional Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --el-primary: #204192;
            --el-primary-rgb: 32, 65, 146;
            --el-bg: #f8fafc;
            --el-text: #1e293b;
            --el-white: #ffffff;
            --el-radius-main: 24px;
            --el-radius-sub: 12px;
            --el-font: 'Outfit', sans-serif;
            --el-transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: var(--el-bg);
            color: var(--el-text);
            font-family: var(--el-font);
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        /* CARD AERO GLASS (Vitrina 06 Prestigious Aesthetics) */
        .sandbox-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(var(--el-primary-rgb), 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.5); /* Edge Glow */
            border-radius: var(--el-radius-main);
            width: 100%;
            max-width: 32rem;
            padding: 2.5rem;
            box-shadow: 0 1rem 3rem rgba(var(--el-primary-rgb), 0.08); /* Sombra con matiz primario */
            text-align: center;
            box-sizing: border-box;
        }

        .header-icon {
            width: 4rem;
            height: 4rem;
            border-radius: var(--el-radius-sub);
            background-color: rgba(var(--el-primary-rgb), 0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--el-primary);
            margin-bottom: 1.5rem;
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--el-primary);
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.02em;
        }

        p.subtitle {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0 0 2rem 0;
        }

        /* WIDGET STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            background-color: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(var(--el-primary-rgb), 0.04);
            border-radius: var(--el-radius-sub);
            padding: 1rem;
        }

        .stat-val {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--el-primary);
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-lbl {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }

        .stat-item--total {
            grid-column: span 2;
            background-color: rgba(var(--el-primary-rgb), 0.04);
            border-color: rgba(var(--el-primary-rgb), 0.08);
        }

        /* BOTONES INTERACTIVOS 44PX */
        .btn-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn-sandbox {
            height: 2.75rem; /* Exactamente 44px */
            border-radius: var(--el-radius-sub); /* 12px Prestige */
            font-family: var(--el-font);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: var(--el-transition);
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-sandbox--primary {
            background-color: var(--el-primary);
            color: var(--el-white);
            box-shadow: 0 4px 12px rgba(var(--el-primary-rgb), 0.15);
        }

        .btn-sandbox--primary:hover {
            background-color: rgba(var(--el-primary-rgb), 0.85);
            transform: translateY(-2px);
        }

        .btn-sandbox--danger {
            background-color: hsla(0, 85%, 45%, 0.1);
            color: hsla(0, 85%, 45%, 1);
            border: 1px solid hsla(0, 85%, 45%, 0.2);
        }

        .btn-sandbox--danger:hover {
            background-color: hsla(0, 85%, 45%, 1);
            color: var(--el-white);
            transform: translateY(-2px);
        }

        .btn-sandbox--secondary {
            background-color: transparent;
            color: #64748b;
            border: 1px solid #cbd5e1;
            text-decoration: none;
        }

        .btn-sandbox--secondary:hover {
            background-color: #f1f5f9;
        }

        /* ALERTAS PREMIUM */
        .alert {
            border-radius: var(--el-radius-sub);
            padding: 1rem;
            font-size: 0.8125rem;
            font-weight: 600;
            text-align: left;
            margin-bottom: 2rem;
            border: 1px solid transparent;
            line-height: 1.5;
        }

        .alert-success {
            background-color: hsla(145, 80%, 35%, 0.08);
            border-color: hsla(145, 80%, 35%, 0.2);
            color: hsla(145, 80%, 35%, 1);
        }

        .alert-danger {
            background-color: hsla(0, 85%, 45%, 0.08);
            border-color: hsla(0, 85%, 45%, 0.2);
            color: hsla(0, 85%, 45%, 1);
        }

        .alert-info {
            background-color: rgba(var(--el-primary-rgb), 0.04);
            border-color: rgba(var(--el-primary-rgb), 0.1);
            color: var(--el-primary);
        }
    </style>
</head>
<body>

    <div class="sandbox-card">
        <div class="header-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="9" y1="3" x2="9" y2="21"></line>
                <line x1="15" y1="3" x2="15" y2="21"></line>
                <line x1="3" y1="9" x2="21" y2="9"></line>
                <line x1="3" y1="15" x2="21" y2="15"></line>
            </svg>
        </div>

        <h1>Sandbox de Pruebas BI</h1>
        <p class="subtitle">Gestión e inyección controlada de calificaciones demo de alta fidelidad</p>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-item stat-item--total">
                <div class="stat-val"><?php echo $total_demo; ?></div>
                <div class="stat-lbl">Registros Demo en el Sistema</div>
            </div>
            <div class="stat-item">
                <div class="stat-val"><?php echo $stats['actividades']; ?></div>
                <div class="stat-lbl">Actividades</div>
            </div>
            <div class="stat-item">
                <div class="stat-val"><?php echo $stats['calificaciones']; ?></div>
                <div class="stat-lbl">Calificaciones</div>
            </div>
            <div class="stat-item">
                <div class="stat-val"><?php echo $stats['pruebas']; ?></div>
                <div class="stat-lbl">Exámenes</div>
            </div>
            <div class="stat-item">
                <div class="stat-val"><?php echo $stats['respuestas']; ?></div>
                <div class="stat-lbl">Respuestas</div>
            </div>
        </div>

        <div class="btn-container">
            <form action="seed_sabana_demo.php" method="POST" style="width: 100%;">
                <input type="hidden" name="accion" value="seed">
                <button type="submit" class="btn-sandbox btn-sandbox--primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 0.25rem;">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    Poblar Base de Datos (Inyectar Demo)
                </button>
            </form>

            <form action="seed_sabana_demo.php" method="POST" style="width: 100%;">
                <input type="hidden" name="accion" value="purgar">
                <button type="submit" class="btn-sandbox btn-sandbox--danger">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 0.25rem;">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Limpiar Base de Datos (Purgar Quirúrgico)
                </button>
            </form>

            <a href="../index.php?view=sabana_calificaciones" class="btn-sandbox btn-sandbox--secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 0.25rem;">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Volver a la Sábana de Notas
            </a>
        </div>
    </div>

</body>
</html>
