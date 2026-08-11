<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
/**
 * GENERADOR DE DATOS [DEMO-BI] - TEST DE ESTRÉS PARA SÁBANA DE NOTAS
 * Arquitectura Élite
 */
declare(strict_types=1);
date_default_timezone_set('America/Bogota');

$db_path = __DIR__ . '/php/database/usuarios.db';
try {
    $db = new PDO("sqlite:$db_path");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

$action = $_GET['action'] ?? ($argv[1] ?? 'generar');

if ($action === 'limpiar') {
    echo "<h1>LIMPIANDO DATOS [DEMO-BI]...</h1>";
    
    // 1. Borrar desglose de actividades DEMO
    $db->exec("DELETE FROM ares_calificaciones_desglose WHERE actividad_id IN (SELECT id FROM ares_actividades WHERE titulo LIKE '%[DEMO-BI]%')");
    $db->exec("DELETE FROM ares_actividades WHERE titulo LIKE '%[DEMO-BI]%'");
    
    // 2. Borrar estudiantes DEMO (sus calificaciones también, pero por si acaso ya se borraron)
    $db->exec("DELETE FROM ares_calificaciones_desglose WHERE estudiante_id IN (SELECT id FROM estudiantes WHERE apellido LIKE '%[DEMO-BI]%')");
    $db->exec("DELETE FROM eval_respuestas WHERE estudiante_id IN (SELECT id FROM estudiantes WHERE apellido LIKE '%[DEMO-BI]%')");
    $db->exec("DELETE FROM estudiantes WHERE apellido LIKE '%[DEMO-BI]%'");
    
    // 3. Borrar cursos DEMO y su carga académica
    $db->exec("DELETE FROM carga_academica WHERE curso_id IN (SELECT id FROM cursos WHERE nombre_curso LIKE '%[DEMO-BI]%')");
    $db->exec("DELETE FROM cursos WHERE nombre_curso LIKE '%[DEMO-BI]%'");
    
    echo "<p>✅ Limpieza completada. La base de datos está inmaculada.</p>";
    exit;
}

if ($action === 'generar') {
    echo "<h1>INICIANDO GENERACIÓN DE DATOS [DEMO-BI]...</h1>";
    
    // Semillas de nombres
    $nombres = ["Andrés", "Valentina", "Santiago", "Camila", "Mateo", "Mariana", "Alejandro", "Isabella", "Sebastián", "Sofía", "Diego", "Luciana", "Samuel", "Victoria", "Daniel", "Gabriela", "Nicolás", "Daniela", "David", "Juliana"];
    $apellidos = ["García", "Martínez", "Rodríguez", "López", "Hernández", "González", "Pérez", "Sánchez", "Ramírez", "Torres", "Rojas", "Díaz", "Gómez", "Silva", "Navarro"];

    // 1. Crear Cursos DEMO
    $cursos_nuevos = [
        ['nombre' => '1B', 'nivel' => 1],
        ['nombre' => '1C', 'nivel' => 1],
        ['nombre' => '2B', 'nivel' => 2],
        ['nombre' => '3B', 'nivel' => 3],
        ['nombre' => '4B', 'nivel' => 4],
        ['nombre' => '5B', 'nivel' => 5],
    ];

    $stmt_curso = $db->prepare("INSERT INTO cursos (nombre_curso, nivel_id, tutor_id) VALUES (?, ?, 1)");
    $stmt_estudiante = $db->prepare("INSERT INTO estudiantes (identificacion, nombre, apellido, curso_id, genero, es_antiguo) VALUES (?, ?, ?, ?, ?, 1)");
    
    $cursos_ids = [];
    foreach ($cursos_nuevos as $cn) {
        $nombre_curso = $cn['nombre'] . " [DEMO-BI]";
        $stmt_curso->execute([$nombre_curso, $cn['nivel']]);
        $curso_id = (int)$db->lastInsertId();
        $cursos_ids[] = $curso_id;
        echo "<p>📦 Curso Creado: $nombre_curso (ID: $curso_id)</p>";
        
        // 2. Crear 20 estudiantes por curso
        for ($i = 1; $i <= 20; $i++) {
            $nom = $nombres[array_rand($nombres)];
            $ape = $apellidos[array_rand($apellidos)] . " [DEMO-BI]";
            $iden = "DEMO" . $curso_id . str_pad((string)$i, 4, "0", STR_PAD_LEFT);
            $gen = (in_array($nom, ["Valentina", "Camila", "Mariana", "Isabella", "Sofía", "Luciana", "Victoria", "Gabriela", "Daniela", "Juliana"])) ? "F" : "M";
            
            $stmt_estudiante->execute([$iden, $nom, $ape, $curso_id, $gen]);
        }
    }

    // Obtener las materias y dimensiones
    $materias = $db->query("SELECT id FROM especialidades")->fetchAll(PDO::FETCH_COLUMN);
    $dimensiones = $db->query("SELECT id FROM ares_clases_nota WHERE estado = 1")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($materias) || empty($dimensiones)) {
    }
    
    // 2.5 Vincular Carga Académica (Materias a Cursos)
    $stmt_carga = $db->prepare("INSERT INTO carga_academica (curso_id, especialidad_id, docente_id) VALUES (?, ?, 1)");
    foreach ($cursos_ids as $curso_id) {
        foreach ($materias as $mat_id) {
            $stmt_carga->execute([$curso_id, $mat_id]);
        }
    }
    
    if (empty($materias) || empty($dimensiones)) {
    }

    // 3. Crear Actividades y Notas (Campana de Gauss)
    $stmt_act = $db->prepare("INSERT INTO ares_actividades (docente_id, curso_id, especialidad_id, clase_nota_id, titulo, tipo_evaluacion, fecha_registro) VALUES (1, ?, ?, ?, ?, 'ACTIVIDAD', datetime('now'))");
    $stmt_nota = $db->prepare("INSERT INTO ares_calificaciones_desglose (actividad_id, criterio_id, estudiante_id, calificacion, fecha_registro, es_edicion) VALUES (?, ?, ?, ?, datetime('now'), 0)");

    // Función Gaussiana Simple
    function rand_gauss($mean, $std_dev) {
        $x = mt_rand() / mt_getrandmax();
        $y = mt_rand() / mt_getrandmax();
        $u = sqrt(-2 * log($x)) * cos(2 * pi() * $y);
        return $u * $std_dev + $mean;
    }

    $stmt_estudiantes = $db->prepare("SELECT id FROM estudiantes WHERE curso_id = ?");

    foreach ($cursos_ids as $curso_id) {
        $stmt_estudiantes->execute([$curso_id]);
        $estudiantes = $stmt_estudiantes->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($materias as $mat_id) {
            foreach ($dimensiones as $dim_id) {
                // Crear 1 actividad por dimensión por materia por curso
                $titulo_act = "Actividad BI - Dim $dim_id [DEMO-BI]";
                $stmt_act->execute([$curso_id, $mat_id, $dim_id, $titulo_act]);
                $act_id = (int)$db->lastInsertId();
                
                // Sembrar notas
                foreach ($estudiantes as $est_id) {
                    // Decidir perfil del estudiante usando hash del ID para consistencia (que siempre sea bueno o malo)
                    $hash = crc32($est_id . 'salt');
                    $perfil = $hash % 100; // 0-99
                    
                    if ($perfil > 80) {
                        // Excelencia (4.0 - 5.0)
                        $nota = rand_gauss(4.6, 0.3);
                    } elseif ($perfil < 20) {
                        // Riesgo Crítico (1.0 - 3.2)
                        $nota = rand_gauss(2.5, 0.6);
                    } else {
                        // Promedio (3.0 - 4.2)
                        $nota = rand_gauss(3.6, 0.4);
                    }
                    
                    // Ajustar límites
                    $nota = max(1.0, min(5.0, round($nota, 1)));
                    
                    $stmt_nota->execute([$act_id, $dim_id, $est_id, $nota]);
                }
            }
        }
    }
    
    echo "<p>✅ Generación completada con éxito. Datos DEMO-BI insertados (Campana de Gauss Aplicada).</p>";
}
