# Módulo de Calificaciones Élite v2.0

Este plan detalla la construcción del componente académico más importante del sistema: el **Libro de Calificaciones**. Implementaremos un motor robusto y seguro que permita a los docentes registrar notas por periodos y a la institución emitir reportes de rendimiento.

## User Review Required

> [!IMPORTANT]
> **Definición de Escala**: El sistema se configurará inicialmente con una escala de 1.0 a 5.0 (Estándar en Colombia). Si requiere una escala diferente (0-10 o letras), debe informarlo antes de la ejecución.
> **Periodos por Defecto**: Se inicializarán 4 Bimestres.

## Proposed Changes

### 1. Infraestructura de Datos (Bóveda Académica)

Aumentaremos el esquema actual para soportar la persistencia de la evaluación.

#### [MODIFY] [db.php](file:///c:/xampp/htdocs/sistema_escolar/php/db.php)
- Añadir autoparche para crear las tablas `periodos` y `calificaciones`.
- Inyectar el permiso global `calificaciones`.

#### [NEW] `periodos` (Tabla)
- `id`, `nombre` (Bimestre 1, etc.), `estado` (Abierto/Cerrado).

#### [NEW] `calificaciones` (Tabla)
- `id`, `estudiante_id`, `carga_id` (vínculo docente-curso-materia), `periodo_id`, `nota`, `observaciones`, `fecha_registro`.

---

### 2. Motor Lógico (Backend)

Implementaremos los servicios asíncronos para la gestión de notas.

#### [NEW] [obtener_planilla_ajax.php](file:///c:/xampp/htdocs/sistema_escolar/php/logica/obtener_planilla_ajax.php)
- Motor que devuelve la lista de estudiantes para un docente en una materia específica, junto con sus notas actuales.

#### [NEW] [guardar_notas_ajax.php](file:///c:/xampp/htdocs/sistema_escolar/php/logica/guardar_notas_ajax.php)
- Procesa el guardado masivo de notas mediante Sentencias Preparadas (Blindaje Total).

---

### 3. Interfaz de Usuario (Front-End SPA)

Diseñaremos una experiencia de usuario vibrante y eficiente.

#### [NEW] [calificaciones.php](file:///c:/xampp/htdocs/sistema_escolar/php/vistas/calificaciones.php)
- Vista principal con selectores dinámicos: Periodo -> Materia.
- **Grilla de Notas**: Tabla editable en tiempo real con validación visual (Verde para aprobados, Rojo Pasión para reprobados).
- Integración con el motor SPA (`navegarModulo`).

#### [MODIFY] [dashboard.php](file:///c:/xampp/htdocs/sistema_escolar/php/dashboard.php)
- Añadir el acceso al módulo en el menú lateral bajo la categoría "Académico".

## Open Questions

- **¿Validación de Notas?**: ¿Desea que el sistema bloquee notas fuera del rango 1.0 - 5.0?
- **¿Observaciones?**: ¿Es necesario que cada nota tenga un campo de comentario para el docente?

## Verification Plan

### Automated Tests
- Ejecutar script de prueba de inserción de 100 notas para verificar latencia.
- Verificar blindaje SQL mediante intentos de inyección en `guardar_notas_ajax.php`.

### Manual Verification
- Acceder como Docente y verificar que solo aparezcan sus materias.
- Acceder como Admin y verificar que pueda ver (y editar) todas las materias.
