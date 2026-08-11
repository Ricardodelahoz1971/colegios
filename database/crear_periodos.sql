CREATE TABLE IF NOT EXISTS eval_periodos_academicos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    activo TINYINT(1) DEFAULT 0
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Insertar los 4 periodos estándar para el año actual
INSERT IGNORE INTO eval_periodos_academicos (id, nombre, fecha_inicio, fecha_fin, activo) VALUES
(1, 'Primer Periodo', '2026-01-01 00:00:00', '2026-03-31 23:59:59', 0),
(2, 'Segundo Periodo', '2026-04-01 00:00:00', '2026-06-30 23:59:59', 0),
(3, 'Tercer Periodo', '2026-07-01 00:00:00', '2026-09-30 23:59:59', 1),
(4, 'Cuarto Periodo', '2026-10-01 00:00:00', '2026-12-31 23:59:59', 0);

-- Insertar parámetro de configuración de gracia
INSERT IGNORE INTO configuracion_global (clave, valor, tipo_dato, categoria, nombre_legible, descripcion, editable_por) VALUES
('dias_gracia_recuperaciones', '5', 'integer', 'academico', 'Días de Holgura para Cierre de Periodo', 'Número de días de holgura permitidos tras el cierre de un periodo académico para crear y calificar actividades de recuperación asociadas.', 'coordinador');
