# Sistema de Asistencia (Mobile-First)

¡Recibido, Director! La estrategia es clara: el profesor desplegará su teléfono o tablet en el salón, elegirá el curso y marcará el estado rápidamente.

Al estar en un entorno escolar dinámico, el docente no tiene tiempo para botones minúsculos ni letras borrosas. La interfaz debe ser **100% táctil ("Touch-First")**. 

A continuación presento el plan de implementación:

## User Review Required

> [!IMPORTANT]
> **Estados Tácticos Posibles**: Los sistemas de la industria suelen manejar 3 estados base: `✅ Presente`, `❌ Ausente`, y `⏱️ Retraso`. El selector por defecto estará siempre en `Presente` para todos los alumnos, para que el docente solo deba tocar a los pocos alumnos que no están o llegaron tarde. ¿Aprueba esta estructura de tres estados, o su institución maneja 'Justificaciones' directamente en este módulo?

## Proposed Changes

### Arquitectura de Datos (Base de Datos SQLite)
#### [NEW] `php/logica/crear_tabla_asistencia.php` (o en `db.php`)
*   Se creará una nueva tabla relacional llamada `asistencias`:
    *   `id` (PK)
    *   `estudiante_id` (o Identificación)
    *   `fecha` (YYYY-MM-DD)
    *   `estado` (Presente, Ausente, Retraso)

### Front-End del Docente (Interfaz Táctil)
#### [NEW] `php/vistas/asistencia.php`
*   **Selector Superior**: Un filtro grande para seleccionar el `Curso` (Ej: 1ro Medio) y la `Fecha` (predeterminará el día de hoy).
*   **Tarjetas de Estudiantes (Mobile Grid)**: En lugar de una tabla corporativa fría de escritorio, los alumnos se mostrarán en "Tarjetas" adaptables. 
*   **Controles Grandes**: Dentro de cada tarjeta, habrá 3 grandes botones de selección o un *Toggle Switch* diseñado para pulgares, facilitando el llenado rápido.

### Back-End (Cerebro Procesador)
#### [NEW] `php/logica/procesar_asistencia.php`
*   Recibirá los datos masivos del salón (mediante AJAX o envío estructurado POST).
*   Realizará un ciclo (loop) procesando y guardando a cada estudiante.
*   **Prevención de Choques**: Si un docente recarga accidentalmente la página e intenta pasar la lista de nuevo el mismo día para ese curso, el sistema actualizará (UPDATE) los datos en vez de duplicarlos (INSERT).

### Integración en el Panel de Comando
#### [MODIFY] `php/dashboard.php`
*   Añadir la sección **"Pasar Lista (Asistencia)"** al submenú de Estudiantes.

---

## Open Questions

1. **Gestión de Materias**: Actualmente los estudiantes están clasificados solo por "Curso" (Ej. 1ro Medio). ¿La asistencia se pasa de forma general "Por Día" (una vez en la mañana) o desea que se pase asistencia "Por Materia/Hora" (varias veces al día)? Para esta fase sugiero hacerla *Por Día* para no sobrecomplicar la estructura sin tener aún el módulo de Calendarios.

## Verification Plan
*   **Simulación Táctil**: Abriré las herramientas para forzar un sistema de emulación iPad/iPhone y comprobaré que los botones sean fácilmente tocables bajo presión de tiempo con un pulgar humano.
*   **Prueba de Estrés Backend**: Agarraré un curso, registraré asistencia, cerraré la página e intentaré pasarla de nuevo para ver cómo la base de datos reacciona y actualiza correctamente las ausencias.
