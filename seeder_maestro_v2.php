<?php
/**
 * SEEDER MAESTRO V2 - ECOSISTEMA SINTÉTICO ÉLITE
 * Arquitectura de reconstrucción total
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

echo "<h1>🚀 INICIANDO RECONSTRUCCIÓN MASIVA (SEEDER V2)...</h1>";

// 1. PROTOCOLO DE BORRADO (Wipe Total)
$tablas = [
    'usuarios', 'roles', 'cursos', 'estudiantes', 'carga_academica', 
    'especialidades', 'ares_actividades', 'ares_calificaciones_desglose', 
    'eval_respuestas', 'ares_clases_nota'
];

foreach ($tablas as $t) {
    $db->exec("DELETE FROM $t");
    $db->exec("DELETE FROM sqlite_sequence WHERE name='$t'");
}
echo "<p>✅ Tablas purgadas y secuencias reiniciadas a cero.</p>";

// 2. INYECCIÓN DE ROLES BASE
$db->exec("INSERT INTO roles (id, nombre_rol) VALUES (1, 'Administrador')");
$db->exec("INSERT INTO roles (id, nombre_rol) VALUES (2, 'Coordinador')");
$db->exec("INSERT INTO roles (id, nombre_rol) VALUES (5, 'Estudiante')");
$db->exec("INSERT INTO roles (id, nombre_rol) VALUES (11, 'Docente')");

// 3. INYECCIÓN DE DIMENSIONES (Clases de nota)
$db->exec("INSERT INTO ares_clases_nota (id, nombre, peso_global, estado) VALUES (1, 'SABER', 40.0, 1)");
$db->exec("INSERT INTO ares_clases_nota (id, nombre, peso_global, estado) VALUES (2, 'HACER', 30.0, 1)");
$db->exec("INSERT INTO ares_clases_nota (id, nombre, peso_global, estado) VALUES (3, 'SER', 30.0, 1)");

// 4. INYECCIÓN DE USUARIOS Y ESPECIALIDADES
$pass_hash = password_hash('1234', PASSWORD_DEFAULT);
$stmt_usr = $db->prepare("INSERT INTO usuarios (usuario, password, email, nombre, rol_id) VALUES (?, ?, ?, ?, ?)");
$stmt_esp = $db->prepare("INSERT INTO especialidades (nombre_especialidad, area_id) VALUES (?, 1)");

// 4.1. Admin y Coordinador
$stmt_usr->execute(['admin', $pass_hash, 'admin@colegio.com', 'Director General', 1]);
$stmt_usr->execute(['CMG', $pass_hash, 'coordinacion@colegio.com', 'María Gómez', 2]);

// 4.2. Docentes (Licenciados) y Materias
$docentes = [
    ['usr' => 'LJP', 'nom' => 'Jorge Pérez', 'mat' => 'Matemáticas'],
    ['usr' => 'LMA', 'nom' => 'Manuel Abello', 'mat' => 'Español'],
    ['usr' => 'LEM', 'nom' => 'Erick Martínez', 'mat' => 'Historia'],
    ['usr' => 'LAA', 'nom' => 'Ana Arias', 'mat' => 'Ciencias'],
    ['usr' => 'LRR', 'nom' => 'Ricardo Rojas', 'mat' => 'Inglés'],
    ['usr' => 'LCR', 'nom' => 'Carlos Ramírez', 'mat' => 'Educación Física']
];

$docentes_ids = [];
$materias_ids = [];

foreach ($docentes as $d) {
    // Insertar usuario docente
    $stmt_usr->execute([$d['usr'], $pass_hash, strtolower($d['usr']).'@colegio.com', $d['nom'], 11]);
    $doc_id = (int)$db->lastInsertId();
    $docentes_ids[] = $doc_id;
    
    // Insertar materia
    $stmt_esp->execute([$d['mat']]);
    $mat_id = (int)$db->lastInsertId();
    $materias_ids[] = $mat_id;
}
echo "<p>✅ Cuentas maestras, docentes y materias creadas. (Contraseña: 1234)</p>";

// 5. INYECCIÓN DE CURSOS, TUTORES Y CARGA ACADÉMICA
$cursos_nuevos = [
    ['nombre' => '1A', 'nivel' => 1], ['nombre' => '1B', 'nivel' => 1],
    ['nombre' => '2A', 'nivel' => 2], ['nombre' => '2B', 'nivel' => 2],
    ['nombre' => '3A', 'nivel' => 3], ['nombre' => '3B', 'nivel' => 3],
];

$stmt_curso = $db->prepare("INSERT INTO cursos (nombre_curso, nivel_id, tutor_id) VALUES (?, ?, ?)");
$stmt_carga = $db->prepare("INSERT INTO carga_academica (curso_id, especialidad_id, docente_id) VALUES (?, ?, ?)");
$cursos_ids = [];

foreach ($cursos_nuevos as $cn) {
    // Escoger un tutor aleatorio (Líder de grupo)
    $tutor = $docentes_ids[array_rand($docentes_ids)];
    $stmt_curso->execute([$cn['nombre'], $cn['nivel'], $tutor]);
    $curso_id = (int)$db->lastInsertId();
    $cursos_ids[] = $curso_id;
    
    // Carga Académica: Asignar cada materia a su respectivo profesor en este curso
    for ($i = 0; $i < count($docentes_ids); $i++) {
        $stmt_carga->execute([$curso_id, $materias_ids[$i], $docentes_ids[$i]]);
    }
}
echo "<p>✅ Topología Académica (Cursos, Tutores y Carga Académica) estructurada.</p>";

// 6. INYECCIÓN DE ESTUDIANTES Y CALIFICACIONES (GAUSS)
$nombres = ["Andrés", "Valentina", "Santiago", "Camila", "Mateo", "Mariana", "Alejandro", "Isabella", "Sebastián", "Sofía", "Diego", "Luciana", "Samuel", "Victoria", "Daniel"];
$apellidos = ["García", "Martínez", "Rodríguez", "López", "Hernández", "González", "Pérez", "Sánchez", "Ramírez", "Torres"];

$stmt_estudiante = $db->prepare("INSERT INTO estudiantes (identificacion, nombre, apellido, curso_id, genero, es_antiguo) VALUES (?, ?, ?, ?, ?, 1)");
// Opcional: Crear cuenta de usuario para el estudiante
$stmt_usr_est = $db->prepare("INSERT INTO usuarios (usuario, password, email, nombre, rol_id, estudiante_id) VALUES (?, ?, ?, ?, 5, ?)");

$stmt_act = $db->prepare("INSERT INTO ares_actividades (docente_id, curso_id, especialidad_id, clase_nota_id, titulo, tipo_evaluacion, fecha_registro) VALUES (?, ?, ?, ?, ?, 'ACTIVIDAD', datetime('now'))");
$stmt_nota = $db->prepare("INSERT INTO ares_calificaciones_desglose (actividad_id, criterio_id, estudiante_id, calificacion, fecha_registro, es_edicion) VALUES (?, ?, ?, ?, datetime('now'), 0)");

function rand_gauss($mean, $std_dev) {
    $x = mt_rand() / mt_getrandmax();
    $y = mt_rand() / mt_getrandmax();
    $u = sqrt(-2 * log($x)) * cos(2 * pi() * $y);
    return $u * $std_dev + $mean;
}

$dimensiones = [1, 2, 3]; // SABER, HACER, SER

foreach ($cursos_ids as $curso_id) {
    $estudiantes_creados = [];
    
    // Crear 15 estudiantes por curso
    for ($i = 1; $i <= 15; $i++) {
        $nom = $nombres[array_rand($nombres)];
        $ape = $apellidos[array_rand($apellidos)];
        $iden = "DOC" . $curso_id . str_pad((string)$i, 4, "0", STR_PAD_LEFT);
        $gen = (in_array($nom, ["Valentina", "Camila", "Mariana", "Isabella", "Sofía", "Luciana", "Victoria"])) ? "F" : "M";
        
        $stmt_estudiante->execute([$iden, $nom, $ape, $curso_id, $gen]);
        $est_id = (int)$db->lastInsertId();
        $estudiantes_creados[] = $est_id;
        
        // Crear cuenta de usuario
        $usr = "E" . strtoupper(substr($nom, 0, 1) . substr($ape, 0, 1)) . $est_id; // ej: EAG12
        $stmt_usr_est->execute([$usr, $pass_hash, strtolower($usr).'@colegio.com', "$nom $ape", $est_id]);
    }
    
    // Sembrar notas
    for ($i = 0; $i < count($docentes_ids); $i++) {
        $doc_id = $docentes_ids[$i];
        $mat_id = $materias_ids[$i];
        
        foreach ($dimensiones as $dim_id) {
            // Crear actividad
            $titulo_act = "Evaluación V2 - Dimensión $dim_id";
            $stmt_act->execute([$doc_id, $curso_id, $mat_id, $dim_id, $titulo_act]);
            $act_id = (int)$db->lastInsertId();
            
            // Poner notas
            foreach ($estudiantes_creados as $est_id) {
                $hash = crc32($est_id . 'gauss');
                $perfil = $hash % 100; 
                
                if ($perfil > 80) $nota = rand_gauss(4.6, 0.3); // Excelencia
                elseif ($perfil < 20) $nota = rand_gauss(2.5, 0.6); // Crítico
                else $nota = rand_gauss(3.6, 0.4); // Promedio
                
                $nota = max(1.0, min(5.0, round($nota, 1)));
                $stmt_nota->execute([$act_id, $dim_id, $est_id, $nota]);
            }
        }
    }
}
echo "<p>✅ Estudiantes reclutados y Campana de Gauss aplicada a las Calificaciones.</p>";
echo "<h2>🎉 ECOSISTEMA SINTÉTICO OPERATIVO AL 100%</h2>";
