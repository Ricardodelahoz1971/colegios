CREATE TABLE usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    email TEXT,
    nombre TEXT
, rol_id INTEGER DEFAULT 2, especialidad_id INTEGER DEFAULT NULL, permisos_custom INTEGER DEFAULT 0, ultima_actividad DATETIME, estudiante_id INTEGER DEFAULT NULL);

CREATE TABLE sqlite_sequence(name,seq);

CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    nombre_rol TEXT UNIQUE NOT NULL
);

CREATE TABLE permisos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    clave TEXT UNIQUE NOT NULL, -- Ej: 'personal', 'roles'
    nombre TEXT NOT NULL        -- Ej: 'Gestión de Personal'
);

CREATE TABLE rol_permisos (
    rol_id INTEGER,
    permiso_id INTEGER,
    PRIMARY KEY (rol_id, permiso_id),
    FOREIGN KEY (rol_id) REFERENCES roles(id),
    FOREIGN KEY (permiso_id) REFERENCES permisos(id)
);

CREATE TABLE usuario_permisos (
    usuario_id INTEGER, 
    permiso_id INTEGER, 
    PRIMARY KEY(usuario_id, permiso_id),
    FOREIGN KEY(usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY(permiso_id) REFERENCES permisos(id)
);

CREATE TABLE ajustes_estetica (
    clave TEXT PRIMARY KEY,
    valor TEXT NOT NULL
);

CREATE TABLE especialidades (
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    nombre_especialidad TEXT UNIQUE NOT NULL
, area_id INTEGER, disciplina_men TEXT DEFAULT 'general');

CREATE TABLE areas (
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    nombre_area TEXT UNIQUE NOT NULL
);

CREATE TABLE cursos (
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    nombre_curso TEXT NOT NULL, 
    tutor_id INTEGER DEFAULT NULL, nivel_id INTEGER DEFAULT 0,
    FOREIGN KEY(tutor_id) REFERENCES usuarios(id)
);

CREATE TABLE carga_academica (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    curso_id INTEGER,
    especialidad_id INTEGER,
    docente_id INTEGER,
    FOREIGN KEY(curso_id) REFERENCES cursos(id),
    FOREIGN KEY(especialidad_id) REFERENCES especialidades(id),
    FOREIGN KEY(docente_id) REFERENCES usuarios(id)
);

CREATE TABLE logs_auditoria (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    actor_usuario_id INTEGER,
    accion TEXT,
    objetivo_tipo TEXT,
    objetivo_id INTEGER,
    detalles TEXT
);

CREATE TABLE estudiantes_backup(
  id INT,
  identificacion TEXT,
  nombre TEXT,
  apellido TEXT,
  curso TEXT,
  promedio REAL,
  curso_id INT,
  tipo_documento TEXT,
  rh TEXT,
  genero TEXT,
  email TEXT,
  celular TEXT,
  es_antiguo INT
);

CREATE TABLE estudiantes_nueva (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        identificacion TEXT,
        nombre TEXT,
        apellido TEXT,
        promedio REAL,
        curso_id INTEGER,
        tipo_documento TEXT,
        rh TEXT,
        genero TEXT,
        email TEXT,
        celular TEXT,
        es_antiguo INTEGER
    );

CREATE TABLE "estudiantes" (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        identificacion TEXT,
        nombre TEXT,
        apellido TEXT,
        promedio REAL,
        curso_id INTEGER,
        tipo_documento TEXT,
        rh TEXT,
        genero TEXT,
        email TEXT,
        celular TEXT,
        es_antiguo INTEGER
    , curso TEXT);

CREATE TABLE mensajes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    remitente_id INTEGER NOT NULL,
    destinatario_id INTEGER NOT NULL,
    asunto TEXT,
    contenido TEXT NOT NULL,
    prioridad INTEGER DEFAULT 1,
    entregado INTEGER DEFAULT 0,
    leido INTEGER DEFAULT 0,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP
, grupo_id INTEGER DEFAULT 0);

CREATE TABLE grupos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    tipo TEXT NOT NULL, -- 'CURSO', 'ROL', 'PERSONALIZADO'
    referencia_id INTEGER, -- ID del curso o rol
    creado_por INTEGER,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE miembros_grupo (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    grupo_id INTEGER NOT NULL,
    usuario_id INTEGER NOT NULL,
    es_admin INTEGER DEFAULT 0,
    fecha_union DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(grupo_id) REFERENCES grupos(id),
    FOREIGN KEY(usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE vistas_canales (
    usuario_id INTEGER, 
    grupo_id INTEGER, 
    ultimo_mensaje_id INTEGER DEFAULT 0, 
    UNIQUE(usuario_id, grupo_id)
);

CREATE TABLE cronograma (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo TEXT,
    descripcion TEXT,
    tipo TEXT, -- EVENTO, FESTIVO, EXAMEN
    color TEXT,
    fecha TEXT,
    creado_por INTEGER,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE asistencias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    estudiante_id INTEGER,
    curso_id INTEGER,
    fecha TEXT,
    estado TEXT, -- P, F, R, E
    observaciones TEXT,
    registrado_por INTEGER,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE agenda_escolar (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    curso_id INTEGER,
    docente_id INTEGER,
    especialidad_id INTEGER,
    titulo TEXT,
    descripcion TEXT,
    fecha_entrega TEXT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE zulu_plan_maestro (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nivel_nombre TEXT,
    especialidad_id INTEGER,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
, intensidad_horaria INTEGER DEFAULT 1);

CREATE TABLE khronos_horarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    curso_id INTEGER,
    docente_id INTEGER,
    especialidad_id INTEGER,
    dia_semana TEXT, -- Lun, Mar, Mie, Jue, Vie, Sab
    hora_numero INTEGER, -- 1, 2, 3...
    hora_inicio TEXT,
    hora_fin TEXT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
, evento_nombre TEXT);

CREATE TABLE khronos_disponibilidad (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    docente_id INTEGER,
    dia_semana TEXT,
    hora_numero INTEGER,
    disponible INTEGER DEFAULT 1, -- 1: Sí, 0: No
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE paletas_elite (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT,
    primary_color TEXT,
    accent_color TEXT,
    info_color TEXT,
    success_color TEXT,
    danger_color TEXT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE eval_tipos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    estado INTEGER DEFAULT 1
);

CREATE TABLE eval_preguntas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    docente_id INTEGER NOT NULL,
    materia_id INTEGER NOT NULL,
    tipo_id INTEGER NOT NULL,
    enunciado TEXT NOT NULL,
    metadata_json TEXT NOT NULL, -- Almacena opciones, distractores y respuesta correcta
    complejidad TEXT CHECK(complejidad IN ('Baja', 'Media', 'Alta')) DEFAULT 'Media',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP, parent_id INTEGER DEFAULT NULL, original_author_id INTEGER DEFAULT NULL, fecha_modificacion DATETIME, titulo VARCHAR(255), aprendizaje_id INTEGER DEFAULT NULL, evidencia_id INTEGER DEFAULT NULL,
    FOREIGN KEY (tipo_id) REFERENCES eval_tipos(id)
);

CREATE TABLE eval_pruebas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    docente_id INTEGER NOT NULL,
    materia_id INTEGER NOT NULL,
    titulo TEXT NOT NULL,
    instrucciones TEXT,
    tiempo_limite INTEGER DEFAULT 60, -- En minutos
    estado INTEGER DEFAULT 1, -- 1: Borrador, 2: Publicada, 3: Cerrada
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
, modalidad INTEGER DEFAULT 1);

CREATE TABLE eval_pruebas_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    prueba_id INTEGER NOT NULL,
    pregunta_id INTEGER NOT NULL,
    orden INTEGER DEFAULT 0,
    peso REAL DEFAULT 1.0, -- Ponderación de la pregunta dentro del examen
    FOREIGN KEY (prueba_id) REFERENCES eval_pruebas(id),
    FOREIGN KEY (pregunta_id) REFERENCES eval_preguntas(id)
);

CREATE TABLE eval_incidentes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    estudiante_id INTEGER NOT NULL,
    prueba_id INTEGER NOT NULL,
    tipo_evento TEXT NOT NULL, -- 'focus_loss', 'paste_attempt', 'context_menu'
    detalles TEXT,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP, asignacion_id INTEGER DEFAULT 0,
    FOREIGN KEY (prueba_id) REFERENCES eval_pruebas(id)
);

CREATE TABLE eval_respuestas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    estudiante_id INTEGER NOT NULL,
    prueba_id INTEGER NOT NULL,
    pregunta_id INTEGER NOT NULL,
    respuesta_alumno TEXT,
    puntaje_obtenido REAL DEFAULT 0,
    comentario_docente TEXT,
    estado INTEGER DEFAULT 0, -- 0: Pendiente de calificar, 1: Calificada
    fecha_entrega DATETIME DEFAULT CURRENT_TIMESTAMP, asignacion_id INTEGER DEFAULT 0, calificacion_automatica REAL DEFAULT 0, calificacion_manual REAL DEFAULT 0, respuestas_json TEXT, calificacion_recuperacion REAL DEFAULT NULL,
    FOREIGN KEY (prueba_id) REFERENCES eval_pruebas(id),
    FOREIGN KEY (pregunta_id) REFERENCES eval_preguntas(id)
);

CREATE TABLE eval_asignaciones (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        prueba_id INTEGER,
        curso_id INTEGER,
        docente_id INTEGER,
        fecha_inicio DATETIME,
        fecha_fin DATETIME,
        clave_acceso TEXT,
        estado INTEGER DEFAULT 0,
        intentos_permitidos INTEGER DEFAULT 1,
        mostrar_resultados INTEGER DEFAULT 0,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
    , tipo_navegacion TEXT DEFAULT 'libre');

CREATE TABLE aula_recursos (id INTEGER PRIMARY KEY AUTOINCREMENT, docente_id INTEGER, especialidad_id INTEGER, curso_id INTEGER, titulo TEXT, descripcion TEXT, tipo_recurso TEXT, url_recurso TEXT, visibilidad INTEGER DEFAULT 1, orden INTEGER DEFAULT 0, fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP, es_evaluativo INTEGER DEFAULT 0, actividad_vinculada_id INTEGER DEFAULT NULL, fecha_inicio TEXT DEFAULT NULL, fecha_fin TEXT DEFAULT NULL);

CREATE TABLE aula_vistos (id INTEGER PRIMARY KEY AUTOINCREMENT, estudiante_id INTEGER, recurso_id INTEGER, fecha_vista DATETIME DEFAULT CURRENT_TIMESTAMP);

CREATE TABLE ares_catalogo_competencias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        area_id INTEGER,
        nombre TEXT NOT NULL,
        descripcion TEXT,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
    );

CREATE TABLE ares_catalogo_aprendizajes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        competencia_id INTEGER,
        area_id INTEGER,
        grado TEXT NOT NULL,
        num_dba INTEGER,
        enunciado TEXT NOT NULL,
        version TEXT DEFAULT 'V.1',
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
    , disciplina TEXT DEFAULT NULL);

CREATE TABLE ares_catalogo_evidencias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        aprendizaje_id INTEGER,
        texto TEXT NOT NULL,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
    );

CREATE TABLE ares_clases_nota (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT, peso_global FLOAT DEFAULT 0, estado INTEGER DEFAULT 1, min_evaluaciones INTEGER DEFAULT 2);

CREATE TABLE ares_actividades (id INTEGER PRIMARY KEY AUTOINCREMENT, docente_id INTEGER, curso_id INTEGER, especialidad_id INTEGER, clase_nota_id INTEGER, titulo TEXT, tipo_evaluacion TEXT, fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP);

CREATE TABLE ares_actividad_criterios (id INTEGER PRIMARY KEY AUTOINCREMENT, actividad_id INTEGER, titulo TEXT, peso_porcentaje FLOAT);

CREATE TABLE ares_calificaciones_desglose (id INTEGER PRIMARY KEY AUTOINCREMENT, actividad_id INTEGER, criterio_id INTEGER, estudiante_id INTEGER, calificacion FLOAT, fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP, es_edicion INTEGER DEFAULT 0, nota_recuperacion REAL DEFAULT NULL);

CREATE TABLE eval_config_escala (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nota_minima REAL DEFAULT 1.00,
    nota_maxima REAL DEFAULT 5.00,
    nota_aprobacion REAL DEFAULT 3.00,
    rango_superior_min REAL DEFAULT 4.60,
    rango_alto_min REAL DEFAULT 4.00,
    rango_basico_min REAL DEFAULT 3.00,
    activo INTEGER DEFAULT 1,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE configuracion_global (id INTEGER PRIMARY KEY AUTOINCREMENT, clave TEXT NOT NULL UNIQUE, valor TEXT NOT NULL, tipo_dato TEXT NOT NULL, categoria TEXT NOT NULL, nombre_legible TEXT NOT NULL, descripcion TEXT, editable_por TEXT DEFAULT 'coordinador', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP);

CREATE TABLE eval_respuestas_detalles (id INTEGER PRIMARY KEY AUTOINCREMENT, respuesta_id INTEGER NOT NULL, pregunta_id INTEGER NOT NULL, respuesta_alumno TEXT, es_correcta INTEGER DEFAULT 0, puntaje_obtenido REAL DEFAULT 0, FOREIGN KEY (respuesta_id) REFERENCES eval_respuestas(id), FOREIGN KEY (pregunta_id) REFERENCES eval_preguntas(id));

