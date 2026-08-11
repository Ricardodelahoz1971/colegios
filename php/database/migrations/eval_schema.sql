-- PROTOCOLO ARES: MIGRACIÓN DE ESTRUCTURA ACADÉMICA (SQLite)
-- v1.0 - Ingeniero Ricardo & Antigravity

-- 1. Catálogo de Tipos de Pregunta
CREATE TABLE IF NOT EXISTS eval_tipos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    estado INTEGER DEFAULT 1
);

INSERT OR IGNORE INTO eval_tipos (nombre, slug) VALUES 
('Selección Múltiple', 'seleccion_multiple'),
('Respuesta Abierta', 'abierta'),
('Emparejamiento', 'emparejamiento'),
('Completar Huecos', 'completar');

-- 2. Banco de Reactivos (Preguntas)
CREATE TABLE IF NOT EXISTS eval_preguntas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    docente_id INTEGER NOT NULL,
    materia_id INTEGER NOT NULL,
    tipo_id INTEGER NOT NULL,
    enunciado TEXT NOT NULL,
    metadata_json TEXT NOT NULL, -- Almacena opciones, distractores y respuesta correcta
    complejidad TEXT CHECK(complejidad IN ('Baja', 'Media', 'Alta')) DEFAULT 'Media',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tipo_id) REFERENCES eval_tipos(id)
);

-- 3. Cabecera de Pruebas (Exámenes)
CREATE TABLE IF NOT EXISTS eval_pruebas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    docente_id INTEGER NOT NULL,
    materia_id INTEGER NOT NULL,
    titulo TEXT NOT NULL,
    instrucciones TEXT,
    tiempo_limite INTEGER DEFAULT 60, -- En minutos
    estado INTEGER DEFAULT 1, -- 1: Borrador, 2: Publicada, 3: Cerrada
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 4. Ensamblaje de Preguntas en la Prueba
CREATE TABLE IF NOT EXISTS eval_pruebas_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    prueba_id INTEGER NOT NULL,
    pregunta_id INTEGER NOT NULL,
    orden INTEGER DEFAULT 0,
    peso REAL DEFAULT 1.0, -- Ponderación de la pregunta dentro del examen
    FOREIGN KEY (prueba_id) REFERENCES eval_pruebas(id),
    FOREIGN KEY (pregunta_id) REFERENCES eval_preguntas(id)
);

-- 5. Bitácora de Incidentes (Motor Centinela)
CREATE TABLE IF NOT EXISTS eval_incidentes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    estudiante_id INTEGER NOT NULL,
    prueba_id INTEGER NOT NULL,
    tipo_evento TEXT NOT NULL, -- 'focus_loss', 'paste_attempt', 'context_menu'
    detalles TEXT,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prueba_id) REFERENCES eval_pruebas(id)
);

-- 6. Bóveda de Respuestas y Calificaciones
CREATE TABLE IF NOT EXISTS eval_respuestas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    estudiante_id INTEGER NOT NULL,
    prueba_id INTEGER NOT NULL,
    pregunta_id INTEGER NOT NULL,
    respuesta_alumno TEXT,
    puntaje_obtenido REAL DEFAULT 0,
    comentario_docente TEXT,
    estado INTEGER DEFAULT 0, -- 0: Pendiente de calificar, 1: Calificada
    fecha_entrega DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prueba_id) REFERENCES eval_pruebas(id),
    FOREIGN KEY (pregunta_id) REFERENCES eval_preguntas(id)
);

-- Indexación para optimizar el pulso del sistema
CREATE INDEX IF NOT EXISTS idx_eval_preguntas_docente ON eval_preguntas(docente_id);
CREATE INDEX IF NOT EXISTS idx_eval_incidentes_estudiante ON eval_incidentes(estudiante_id, prueba_id);
CREATE INDEX IF NOT EXISTS idx_eval_respuestas_entrega ON eval_respuestas(estudiante_id, prueba_id);
