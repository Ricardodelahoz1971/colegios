<?php
declare(strict_types=1);
// PHP/LOGICA/DB_INTEGRIDAD.PHP - MOTOR EVOLUTIVO v9.2 (PDO EDITION)
// Este archivo mantiene la salud y evolución de la base de datos bajo estándares Senior.

if (!isset($db)) return;

// 1. AJUSTES ESTÉTICOS GLOBALES (ADN DINÁMICO v9.2)
$check_settings = [
    'dark_mode' => '0',
    'school_font' => 'Montserrat', // Fuente institucional por defecto
    'brand_color' => '#204192',
    'brand_accent' => '#f0bb1c',
    'brand_info' => '#0098da',
    'active_palette_id' => '1',
    'school_name' => 'Sistema Escolar Élite',
    'school_motto' => 'Excelencia en Ingeniería Educativa'
];

foreach ($check_settings as $clave => $valor) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM ajustes_estetica WHERE clave = ?");
    $stmt->execute([$clave]);
    if ($stmt->fetchColumn() == 0) {
        $db->prepare("INSERT INTO ajustes_estetica (clave, valor) VALUES (?, ?)")->execute([$clave, $valor]);
    }
}

// 2. INTEGRIDAD DE TABLAS CLAVE (PROCESO PDO)
$tablas_evolucion = [
    'estudiantes' => [
        'tipo_documento' => 'TEXT', 'rh' => 'TEXT', 'genero' => 'TEXT',
        'email' => 'TEXT', 'celular' => 'TEXT', 'es_antiguo' => 'INTEGER DEFAULT 0',
        'curso' => 'TEXT', 'promedio' => 'FLOAT DEFAULT 0'
    ],
    'especialidades' => [
        'area_id' => 'INTEGER',
        'nivel_desde' => 'INTEGER DEFAULT 1',
        'nivel_hasta' => 'INTEGER DEFAULT 11'
    ],
    'ares_clases_nota' => [
        'min_evaluaciones' => 'INTEGER DEFAULT 2'
    ],
    'ares_calificaciones_desglose' => [
        'nota_recuperacion' => 'REAL DEFAULT NULL',
        'metodo_recuperacion' => 'TEXT DEFAULT NULL',
        'justificacion_recuperacion' => 'TEXT DEFAULT NULL',
        'soporte_recuperacion_id' => 'INTEGER DEFAULT NULL'
    ],
    'eval_respuestas' => [
        'calificacion_recuperacion' => 'REAL DEFAULT NULL'
    ],
    'aula_recursos' => [
        'es_evaluativo' => 'INTEGER DEFAULT 0',
        'actividad_vinculada_id' => 'INTEGER DEFAULT NULL',
        'fecha_inicio' => 'TEXT DEFAULT NULL',
        'fecha_fin' => 'TEXT DEFAULT NULL'
    ],
    'mensajes' => [
        'fecha_lectura' => 'DATETIME DEFAULT NULL'
    ],
    'ares_actividades' => [
        'ambito' => "TEXT DEFAULT 'estandar'",
        'recupera_actividad_id' => 'INTEGER DEFAULT NULL'
    ],
    'eval_asignaciones' => [
        'ambito' => "TEXT DEFAULT 'estandar'",
        'recupera_actividad_id' => 'INTEGER DEFAULT NULL'
    ],
    'cursos' => [
        'jornada' => "VARCHAR(50) DEFAULT 'Mañana'"
    ]
];

foreach ($tablas_evolucion as $tabla => $columnas) {
    try {
        $stmt_info = $db->prepare("SHOW COLUMNS FROM `$tabla`");
        $stmt_info->execute();
        $esquema = $stmt_info->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch(Exception $e) {
        $esquema = [];
    }
    
    foreach ($columnas as $col => $tipo) {
        if (!empty($esquema) && !in_array($col, $esquema)) {
            try { $db->exec("ALTER TABLE `$tabla` ADD COLUMN `$col` $tipo"); } catch(Exception $e){}
        }
    }
}

// 3. LEY ZERO WASTE: PURGA DE TABLAS REDUNDANTES (AUDITORÍA SEGURA)
try {
    $db->exec("DROP TABLE IF EXISTS estudiantes_backup");
    $db->exec("DROP TABLE IF EXISTS estudiantes_nueva");
} catch (Exception $e) {
    // Si la DB está ocupada, fallamos silenciosamente y reintentaremos en la próxima conexión
    error_log("Aviso: No se pudo purgar tablas en este ciclo por bloqueo temporal de DB.");
}

// 4. ESTRUCTURAS DE MÓDULOS ÉLITE
$tablas_maestras = [
    "asistencias" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, estudiante_id INTEGER, curso_id INTEGER, fecha TEXT, estado TEXT, observaciones TEXT, registrado_por INTEGER, fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP",
    "cronograma" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, titulo TEXT, descripcion TEXT, tipo TEXT, color TEXT, fecha TEXT, creado_por INTEGER, fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP",
    "agenda_escolar" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, curso_id INTEGER, docente_id INTEGER, especialidad_id INTEGER, titulo TEXT, descripcion TEXT, fecha_entrega TEXT, fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP",
    "aula_recursos" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, docente_id INTEGER, especialidad_id INTEGER, curso_id INTEGER, titulo TEXT, descripcion TEXT, tipo_recurso TEXT, url_recurso TEXT, visibilidad INTEGER DEFAULT 1, orden INTEGER DEFAULT 0, fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP",
    "aula_vistos" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, estudiante_id INTEGER, recurso_id INTEGER, fecha_vista DATETIME DEFAULT CURRENT_TIMESTAMP",
    "ares_clases_nota" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, nombre TEXT, peso_global FLOAT DEFAULT 0, estado INTEGER DEFAULT 1",
    "ares_actividades" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, docente_id INTEGER, curso_id INTEGER, especialidad_id INTEGER, clase_nota_id INTEGER, titulo TEXT, tipo_evaluacion TEXT, ambito TEXT DEFAULT 'estandar', recupera_actividad_id INTEGER DEFAULT NULL, fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP",
    "ares_actividad_criterios" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, actividad_id INTEGER, titulo TEXT, peso_porcentaje FLOAT",
    "ares_calificaciones_desglose" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, actividad_id INTEGER, criterio_id INTEGER, estudiante_id INTEGER, calificacion FLOAT, fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP",
    "configuracion_global" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, clave TEXT NOT NULL UNIQUE, valor TEXT NOT NULL, tipo_dato TEXT NOT NULL, categoria TEXT NOT NULL, nombre_legible TEXT NOT NULL, descripcion TEXT, editable_por TEXT DEFAULT 'coordinador', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP",
    "eval_respuestas_detalles" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, respuesta_id INTEGER NOT NULL, pregunta_id INTEGER NOT NULL, respuesta_alumno TEXT, es_correcta INTEGER DEFAULT 0, puntaje_obtenido REAL DEFAULT 0, FOREIGN KEY (respuesta_id) REFERENCES eval_respuestas(id), FOREIGN KEY (pregunta_id) REFERENCES eval_preguntas(id)",
    "ares_auditoria_cambios_notas" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, actividad_id INTEGER, estudiante_id INTEGER, docente_id INTEGER, nota_anterior FLOAT, nota_nueva FLOAT, justificacion TEXT, fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP",
    "formatos_matricula" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, nombre VARCHAR(100) NOT NULL, descripcion VARCHAR(255) NULL, contenido_html LONGTEXT NOT NULL, configuracion_json LONGTEXT NULL, tipo VARCHAR(30) DEFAULT 'personalizado', plantilla_base VARCHAR(50) NULL, margen_superior INT DEFAULT 20, margen_inferior INT DEFAULT 20, activo TINYINT(1) DEFAULT 1, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    "estudiantes_datos_adicionales" => "id INTEGER PRIMARY KEY AUTO_INCREMENT, estudiante_id INTEGER NOT NULL, lugar_nacimiento VARCHAR(100) NULL, fecha_nacimiento DATE NULL, edad INT NULL, nacionalidad VARCHAR(50) NULL, colegio_anterior VARCHAR(150) NULL, direccion_estudiante VARCHAR(255) NULL, folio_matricula VARCHAR(50) NULL, padre_nombre VARCHAR(150) NULL, padre_tipo_documento VARCHAR(10) NULL, padre_documento VARCHAR(50) NULL, padre_documento_expedicion VARCHAR(100) NULL, padre_nacionalidad VARCHAR(50) NULL, padre_celular VARCHAR(30) NULL, padre_telefono VARCHAR(30) NULL, padre_direccion VARCHAR(255) NULL, padre_profesion VARCHAR(100) NULL, padre_email VARCHAR(150) NULL, madre_nombre VARCHAR(150) NULL, madre_tipo_documento VARCHAR(10) NULL, madre_documento VARCHAR(50) NULL, madre_documento_expedicion VARCHAR(100) NULL, madre_nacionalidad VARCHAR(50) NULL, madre_celular VARCHAR(30) NULL, madre_telefono VARCHAR(30) NULL, madre_direccion VARCHAR(255) NULL, madre_profesion VARCHAR(100) NULL, madre_email VARCHAR(150) NULL, FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE"
];

foreach ($tablas_maestras as $tabla => $definicion) {
    $db->exec("CREATE TABLE IF NOT EXISTS $tabla ($definicion)");
}


// 5. GESTIÓN TÁCTICA DE PERMISOS (PDO)
$nuevos_permisos = [
    ['asistencia', 'Control de Asistencia Digital'],
    ['cronograma', 'Calendario y Eventos Institucionales'],
    ['agenda', 'Agenda Académica por Curso'],
    ['evaluacion', 'Gestión de Pruebas y Banco de Reactivos'],
    ['aula_virtual', 'Centro de Recursos y Aula Virtual Ares']
];

foreach ($nuevos_permisos as $p) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM permisos WHERE clave = ?");
    $stmt->execute([$p[0]]);
    if ($stmt->fetchColumn() == 0) {
        $db->prepare("INSERT INTO permisos (clave, nombre) VALUES (?, ?)")->execute([$p[0], $p[1]]);
    }
}

// Vinculación Automática de Roles
$roles_agenda = ['Administrador', 'Coordinador', 'coordinador', 'Secretaria', 'Profesor'];
foreach ($roles_agenda as $r_nom) {
    $r_stmt = $db->prepare("SELECT id FROM roles WHERE nombre_rol = ?");
    $r_stmt->execute([$r_nom]);
    $r_id = $r_stmt->fetchColumn();
    if ($r_id) {
        foreach (['asistencia', 'cronograma', 'agenda', 'evaluacion', 'aula_virtual'] as $p_clave) {
            $p_stmt = $db->prepare("SELECT id FROM permisos WHERE clave = ?");
            $p_stmt->execute([$p_clave]);
            $p_id = $p_stmt->fetchColumn();
            if ($p_id) {
                $check_link = $db->prepare("SELECT COUNT(*) FROM rol_permisos WHERE rol_id = ? AND permiso_id = ?");
                $check_link->execute([$r_id, $p_id]);
                if ($check_link->fetchColumn() == 0) {
                    $db->prepare("INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (?, ?)")->execute([$r_id, $p_id]);
                }
            }
        }
    }
}

// 🏛️ SINCRO DE ACCESO: Otorgar permiso de configuracion
$stmt_p = $db->prepare("SELECT id FROM permisos WHERE clave = 'configuracion'");
$stmt_p->execute();
$permiso_id = $stmt_p->fetchColumn();

if ($permiso_id) {
    $check_coord_config = $db->prepare("SELECT COUNT(*) FROM rol_permisos WHERE rol_id = 2 AND permiso_id = ?");
    $check_coord_config->execute([$permiso_id]);
    if ($check_coord_config->fetchColumn() == 0) {
        $db->prepare("INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (2, ?)")->execute([$permiso_id]);
    }
}

// 6. OPTIMIZACIÓN DE ÍNDICES (WARP SPEED)
$indices = [
    "idx_estudiantes_curso" => "estudiantes (curso_id)",
    "idx_estudiantes_ident" => "estudiantes (identificacion)",
    "idx_mensajes_check" => "mensajes (destinatario_id, leido, grupo_id)",
    "idx_asistencias_fast" => "asistencias (fecha, curso_id)"
];
foreach ($indices as $name => $def) {
    try { $db->exec("CREATE INDEX $name ON $def"); } catch (Exception $e) {}
}

// 8. INFRAESTRUCTURA DE IDENTIDADES CROMÁTICAS (ADN PERSISTENTE)
$db->exec("CREATE TABLE IF NOT EXISTS paletas_elite (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    nombre TEXT,
    primary_color TEXT,
    accent_color TEXT,
    info_color TEXT,
    success_color TEXT,
    danger_color TEXT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$stmt_check_palettes = $db->prepare("SELECT COUNT(*) FROM paletas_elite"); $stmt_check_palettes->execute(); $check_palettes = $stmt_check_palettes->fetchColumn();
if ($check_palettes == 0) {
    $defaults_palettes = [
        ['01. PRESTIGIO', '#204192', '#f0bb1c', '#0098da', '#059669', '#dc2626'],
        ['02. VITALIDAD', '#008c3e', '#9ba06b', '#8dbd66', '#166534', '#991b1b'],
        ['03. VANGUARDIA', '#555555', '#e6a1c1', '#a1b1c1', '#4b634b', '#7c3aed'],
        ['04. NOBLEZA', '#701414', '#f5bd1f', '#b54545', '#0f766e', '#450a0a']
    ];
    $stmt_p = $db->prepare("INSERT INTO paletas_elite (nombre, primary_color, accent_color, info_color, success_color, danger_color) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($defaults_palettes as $p) {
        $stmt_p->execute($p);
    }
}

// 9. INFRAESTRUCTURA DE RÚBRICAS Y EVALUACIÓN (PERSEUS ENGINE)
$stmt_check_clases = $db->prepare("SELECT COUNT(*) FROM ares_clases_nota");
$stmt_check_clases->execute();
if ($stmt_check_clases->fetchColumn() == 0) {
    $defaults_clases = [
        ['Saber', 40],
        ['Hacer', 40],
        ['Ser', 20]
    ];
    $stmt_c = $db->prepare("INSERT INTO ares_clases_nota (nombre, peso_global) VALUES (?, ?)");
    foreach ($defaults_clases as $c) {
        $stmt_c->execute($c);
    }
}

// 10. INFRAESTRUCTURA DE ESCALA DE EVALUACIÓN (PERSEUS ENGINE)
$db->exec("CREATE TABLE IF NOT EXISTS eval_config_escala (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    nota_minima REAL DEFAULT 1.00,
    nota_maxima REAL DEFAULT 5.00,
    nota_aprobacion REAL DEFAULT 3.00,
    rango_superior_min REAL DEFAULT 4.60,
    rango_alto_min REAL DEFAULT 4.00,
    rango_basico_min REAL DEFAULT 3.00,
    activo INTEGER DEFAULT 1,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$stmt_check_escala = $db->prepare("SELECT COUNT(*) FROM eval_config_escala");
$stmt_check_escala->execute();
if ($stmt_check_escala->fetchColumn() == 0) {
    $db->exec("INSERT INTO eval_config_escala (nota_minima, nota_maxima, nota_aprobacion, rango_superior_min, rango_alto_min, rango_basico_min, activo) 
               VALUES (1.00, 5.00, 3.00, 4.60, 4.00, 3.00, 1)");
}

// 10.1. INFRAESTRUCTURA DE FORMATOS DE MATRÍCULA
$stmt_check_formatos = $db->prepare("SELECT COUNT(*) FROM formatos_matricula");
$stmt_check_formatos->execute();
if ($stmt_check_formatos->fetchColumn() == 0) {
    $plantilla_institucional = '
<div class="acta-container acta-container--classic">
    <div class="acta-header">
        <h1 class="acta-title-main">{{{colegio_nombre}}}</h1>
        <p class="acta-subtitle">"{{{colegio_lema}}}"</p>
        <h2 class="acta-title-doc">ACTA OFICIAL DE MATRÍCULA</h2>
    </div>

    <p class="acta-paragraph">
        En la fecha <strong>{{{fecha_matricula}}}</strong>, compareció ante la Rectoría de la institución y la Secretaría Académica el estudiante cuyos datos se detallan a continuación, con el fin de formalizar su matrícula para el respectivo año lectivo.
    </p>

    <table class="acta-table">
        <thead>
            <tr>
                <th colspan="2" class="acta-th">Información General del Estudiante</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="acta-td-label">Nombre Completo:</td>
                <td class="acta-td-value">{{{estudiante_nombre}}}</td>
            </tr>
            <tr>
                <td class="acta-td-label">Documento de Identidad:</td>
                <td class="acta-td-value">{{{estudiante_tipo_documento}}} - {{{estudiante_documento}}}</td>
            </tr>
            <tr>
                <td class="acta-td-label">Género / RH:</td>
                <td class="acta-td-value">{{{estudiante_genero}}} / {{{estudiante_rh}}}</td>
            </tr>
            <tr>
                <td class="acta-td-label">Curso Asignado:</td>
                <td class="acta-td-value acta-td-value--highlight">{{{curso_nombre}}}</td>
            </tr>
            <tr>
                <td class="acta-td-label">Contacto / Celular:</td>
                <td class="acta-td-value">{{{estudiante_celular}}}</td>
            </tr>
            <tr>
                <td class="acta-td-label">Correo Electrónico:</td>
                <td class="acta-td-value">{{{estudiante_email}}}</td>
            </tr>
        </tbody>
    </table>

    <div class="acta-compromiso">
        <p><strong>DECLARACIÓN DE COMPROMISO:</strong> Al firmar esta acta, el estudiante y su acudiente declaran conocer y aceptar en todas sus partes el Manual de Convivencia vigente de la institución, comprometiéndose a cumplir con los deberes académicos, disciplinarios y morales que en él se estipulan.</p>
    </div>

    <div class="acta-firmas">
        <div class="acta-firma-box">
            <p class="acta-firma-name">{{{rector_nombre}}}</p>
            <p class="acta-firma-rol">Rector Institucional</p>
        </div>
        <div class="acta-firma-box">
            <p class="acta-firma-name">{{{estudiante_nombre}}}</p>
            <p class="acta-firma-rol">Estudiante / Acudiente</p>
        </div>
    </div>
</div>';

    $plantilla_minimalista = '
<div class="acta-container acta-container--minimal">
    <div class="acta-header acta-header--flex">
        <div>
            <h1 class="acta-title-main acta-title-main--minimal">{{{colegio_nombre}}}</h1>
            <p class="acta-subtitle acta-subtitle--minimal">{{{colegio_lema}}}</p>
        </div>
        <div class="text-right">
            <span class="acta-badge-status">Matrícula Activa</span>
        </div>
    </div>

    <h2 class="acta-title-section">Registro de Admisión</h2>

    <div class="acta-grid">
        <div class="acta-grid-box">
            <small class="acta-grid-label">Estudiante</small>
            <span class="acta-grid-title">{{{estudiante_nombre}}}</span>
            <span class="acta-grid-desc">{{{estudiante_tipo_documento}}}: {{{estudiante_documento}}}</span>
        </div>
        <div class="acta-grid-box">
            <small class="acta-grid-label">Curso y Tutoría</small>
            <span class="acta-grid-title acta-grid-title--highlight">{{{curso_nombre}}}</span>
            <span class="acta-grid-desc">Fecha: {{{fecha_matricula}}}</span>
        </div>
    </div>

    <div class="acta-footer">
        <p class="acta-footer-text">Este documento es una representación digital del acta de matrícula oficial registrada en el sistema escolar.</p>
    </div>
</div>';

    $stmt_insert = $db->prepare("INSERT INTO formatos_matricula (nombre, descripcion, contenido_html, tipo, plantilla_base) VALUES (?, ?, ?, ?, ?)");
    $stmt_insert->execute(['Institucional Clásica', 'Formato oficial de la institución para presentación de trámites ante la Secretaría.', $plantilla_institucional, 'predisenado', 'institucional']);
    $stmt_insert->execute(['Minimalista Moderna', 'Diseño contemporáneo y optimizado para visualizaciones digitales o correos.', $plantilla_minimalista, 'predisenado', 'minimalista']);
}

// 11. INFRAESTRUCTURA DE CONFIGURACIÓN GLOBAL (PRIVACIDAD TRANSVERSAL)
$stmt_check_global = $db->prepare("SELECT COUNT(*) FROM configuracion_global WHERE clave = 'privacidad_catedratico_sabana'");
$stmt_check_global->execute();
if ($stmt_check_global->fetchColumn() == 0) {
    $db->prepare("
        INSERT INTO configuracion_global (clave, valor, tipo_dato, categoria, nombre_legible, descripcion, editable_por)
        VALUES ('privacidad_catedratico_sabana', 'estricto', 'string', 'privacidad', 'Modo de Privacidad Transversal para Catedráticos', 'Establece si un docente especialista (catedrático) puede ver la sábana completa de notas (abierto) o únicamente las asignaturas asignadas a su carga académica (estricto).', 'coordinador')
    ")->execute();
}

// 12. SISTEMA DE CONTROL DE AÑO LECTIVO OFICIAL (SELLADO)
$stmt_check_sello = $db->prepare("SELECT COUNT(*) FROM configuracion_global WHERE clave = 'anio_lectivo_oficial'");
$stmt_check_sello->execute();
if ($stmt_check_sello->fetchColumn() == 0) {
    $db->prepare("
        INSERT INTO configuracion_global (clave, valor, tipo_dato, categoria, nombre_legible, descripcion, editable_por)
        VALUES ('anio_lectivo_oficial', '0', 'string', 'academico', 'Año Lectivo Oficial', 'Establece si las reglas y la escala de notas institucional ya se encuentran oficialmente vigentes y selladas (1) o en fase de planificación y pruebas (0).', 'coordinador')
    ")->execute();
}

// 13. POLÍTICA INSTITUCIONAL DE RECUPERACIONES (RETAKES)
$stmt_check_recup = $db->prepare("SELECT COUNT(*) FROM configuracion_global WHERE clave = 'politica_recuperacion'");
$stmt_check_recup->execute();
if ($stmt_check_recup->fetchColumn() == 0) {
    $db->prepare("
        INSERT INTO configuracion_global (clave, valor, tipo_dato, categoria, nombre_legible, descripcion, editable_por)
        VALUES ('politica_recuperacion', 'reemplazo', 'string', 'academico', 'Política de Recuperación de Notas', 'Establece el método de cálculo institucional para notas de recuperación: Reemplazo Directo (reemplazo), Promedio Simple (promedio) o Límite de Aprobación (tope_aprobacion).', 'coordinador')
    ")->execute();
}

// 14. LÍMITE DE HORAS SEMANALES DE TRABAJO (LEGISLACIÓN COLOMBIANA)
$stmt_check_horas = $db->prepare("SELECT COUNT(*) FROM configuracion_global WHERE clave = 'limite_horas_docente'");
$stmt_check_horas->execute();
if ($stmt_check_horas->fetchColumn() == 0) {
    $db->prepare("
        INSERT INTO configuracion_global (clave, valor, tipo_dato, categoria, nombre_legible, descripcion, editable_por)
        VALUES ('limite_horas_docente', '24', 'integer', 'academico', 'Límite de Horas Semanales Docente', 'Establece el tope de horas semanales permitidas para un docente antes de emitir alerta de sobrecarga laboral.', 'coordinador')
    ")->execute();
}

// 🏛️ SINCRO PERSONALIZADA (Pedro Perez - CPP): Vincular individualmente el permiso al tener permisos personalizados
if (isset($permiso_id) && $permiso_id) {
    $stmt_check_cpp = $db->prepare("SELECT COUNT(*) FROM usuario_permisos WHERE usuario_id = 100 AND permiso_id = ?");
    $stmt_check_cpp->execute([$permiso_id]);
    if ($stmt_check_cpp->fetchColumn() == 0) {
        $db->prepare("INSERT INTO usuario_permisos (usuario_id, permiso_id) VALUES (100, ?)")->execute([$permiso_id]);
    }
}

// 🏛️ ROLES Y USUARIOS SEMILLA (PERSEUS SECURITY v9.3)
// Asegurar roles base y cuentas maestras en la base de datos
try {
    $db->exec("INSERT IGNORE INTO roles (id, nombre_rol) VALUES (1, 'Administrador')");
    $db->exec("INSERT IGNORE INTO roles (id, nombre_rol) VALUES (2, 'Coordinador')");
    $db->exec("INSERT IGNORE INTO roles (id, nombre_rol) VALUES (3, 'Rector')");
    $db->exec("INSERT IGNORE INTO roles (id, nombre_rol) VALUES (5, 'Estudiante')");
    $db->exec("INSERT IGNORE INTO roles (id, nombre_rol) VALUES (11, 'Docente')");
} catch (Exception $e) {
    // Si la DB tiene restricciones estrictas, manejamos silenciosamente
}

// Vincular permisos al rol Rector (ID 3)
$rector_permissions = ['inicio', 'matricula', 'personal', 'estudiantes', 'configuracion', 'areas', 'especialidades', 'cursos', 'asistencia', 'cronograma', 'agenda', 'zulu', 'evaluacion', 'aula_virtual'];
foreach ($rector_permissions as $p_clave) {
    $p_stmt = $db->prepare("SELECT id FROM permisos WHERE clave = ?");
    $p_stmt->execute([$p_clave]);
    $p_id = $p_stmt->fetchColumn();
    if ($p_id) {
        $check_link = $db->prepare("SELECT COUNT(*) FROM rol_permisos WHERE rol_id = 3 AND permiso_id = ?");
        $check_link->execute([$p_id]);
        if ($check_link->fetchColumn() == 0) {
            $db->prepare("INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (3, ?)")->execute([$p_id]);
        }
    }
}

// Asegurar usuario administrador principal
$stmt_check_admin = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = 'admin'");
$stmt_check_admin->execute();
if ($stmt_check_admin->fetchColumn() == 0) {
    $pass_hash = password_hash('1234', PASSWORD_DEFAULT);
    $db->prepare("
        INSERT INTO usuarios (id, usuario, password, email, nombre, rol_id, permisos_custom) 
        VALUES (1, 'admin', ?, 'admin@colegio.com', 'Director General', 1, 0)
    ")->execute([$pass_hash]);
}

// Asegurar usuario rector
$stmt_check_rector = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = 'rector'");
$stmt_check_rector->execute();
if ($stmt_check_rector->fetchColumn() == 0) {
    $pass_hash = password_hash('1234', PASSWORD_DEFAULT);
    $db->prepare("
        INSERT INTO usuarios (usuario, password, email, nombre, rol_id, permisos_custom) 
        VALUES ('rector', ?, 'rector@colegio.com', 'Rector Institucional', 3, 0)
    ")->execute([$pass_hash]);
} else {
    $db->exec("UPDATE usuarios SET rol_id = 3 WHERE usuario = 'rector'");
}

// Asegurar usuario coordinador principal
$stmt_check_cpp = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = 'CPP'");
$stmt_check_cpp->execute();
if ($stmt_check_cpp->fetchColumn() == 0) {
    $pass_hash = password_hash('1234', PASSWORD_DEFAULT);
    $db->prepare("
        INSERT INTO usuarios (usuario, password, email, nombre, rol_id, permisos_custom) 
        VALUES ('CPP', ?, 'coordinacion@colegio.com', 'Pedro Perez', 2, 0)
    ")->execute([$pass_hash]);
}

// 🏛️ CAPA DE SEGURIDAD LEGAL (MIGRACIONES Y BITÁCORA)
$db->exec("CREATE TABLE IF NOT EXISTS log_auditoria_seguridad (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip TEXT,
    usuario_id INTEGER,
    rol_nombre TEXT,
    violacion_intento TEXT,
    modulo_afectado TEXT,
    detalles TEXT
)");

// --- PARCHE DE OPTIMIZACIÓN DE ÍNDICES OPERATIVOS (SABANA & AULA COLA) ---
try {
    // 1. Tabla asistencias (estudiante_id, curso_id, fecha)
    $db->exec("CREATE INDEX IF NOT EXISTS idx_asistencias_est_cur_fecha ON asistencias (estudiante_id, curso_id, fecha(10))");

    // 2. Tabla ares_calificaciones_desglose (actividad_id, estudiante_id) e índice inverso para sábanas (estudiante_id, actividad_id)
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ares_calif_act_est ON ares_calificaciones_desglose (actividad_id, estudiante_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ares_calif_est_act ON ares_calificaciones_desglose (estudiante_id, actividad_id)");

    // 3. Tabla ares_actividades (docente_id, curso_id, especialidad_id)
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ares_act_doc_cur_esp ON ares_actividades (docente_id, curso_id, especialidad_id)");

    // 4. Tabla aula_recursos (docente_id, curso_id, especialidad_id)
    $db->exec("CREATE INDEX IF NOT EXISTS idx_aula_recursos_doc_cur_esp ON aula_recursos (docente_id, curso_id, especialidad_id)");
} catch (Exception $e) {
    // Silenciar si los índices ya existen o no son soportados por la versión de DB
}

// Normalizar de forma transparente usuarios estudiantes antiguos (ID 12 -> 5)
$db->exec("UPDATE usuarios SET rol_id = 5 WHERE rol_id = 12");

// --- REGISTRO DE VERSIÓN DEL ESQUEMA ---
$stmt_check_schema = $db->prepare("SELECT COUNT(*) FROM configuracion_global WHERE clave = 'schema_version'");
$stmt_check_schema->execute();
if ($stmt_check_schema->fetchColumn() == 0) {
    $db->prepare("INSERT INTO configuracion_global (clave, valor, tipo_dato, categoria, nombre_legible, descripcion, editable_por) VALUES ('schema_version', '99', 'integer', 'sistema', 'Versión del Esquema', 'Versión estructural de la base de datos.', 'sistema')")->execute();
} else {
    $db->exec("UPDATE configuracion_global SET valor = '99' WHERE clave = 'schema_version'");
}
?>

