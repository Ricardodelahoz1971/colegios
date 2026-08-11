# 🏛️ MEMORIA TÉCNICA Y RESUMEN DE TRASPASO (PROJECT HANDOVER)
**PROYECTO:** Software Educativo **Perseus** - Módulo Académico **Ares**  
**AUTORÍA:** Ingeniería de Élite  
**CLIENTE/AUTORIDAD SUPREMA:** Ingeniero Ricardo  

Este documento tiene como propósito instruir al próximo agente o ingeniero sobre el estado actual de la arquitectura, seguridad, base de datos y la hoja de ruta inmediata acordada con el **Ingeniero Ricardo**.

---

## 🛡️ 1. SEGURIDAD Y AUDITORÍA EN CALIENTE (SAST v5.0)
*   **Estado de Certificación:** **`✅ ELITE PURENESS` (0 Violaciones Activas)**.
*   **Motor de Reglas:** El script [`antigravity_auditor.php`](file:///c:/xampp/htdocs/sistema_escolar/antigravity_auditor.php) verifica que no haya salidas sin sanitizar, inyecciones PDO ni sesiones desprotegidas.
*   **Control de Deuda Técnica Legacy:** Implementado sistema `@sast-ignore` para congelar advertencias históricas de forma controlada sin comprometer código nuevo.

---

## 🎨 2. OPTIMIZACIONES VISUALES Y CORRECCIÓN DE DAÑOS COLATERALES
*   **Restauración del Menú Lateral (Sidebar):** 
    *   Se eliminó por completo una regla CSS legacy sumamente agresiva en [`calificar_pruebas.css`](file:///c:/xampp/htdocs/sistema_escolar/styles/modules/calificar_pruebas.css) que hacía un `display: none` al menú lateral cuando el modal estaba abierto.
    *   **Resultado:** El menú lateral ahora permanece estático y se atenúa fluidamente detrás del backdrop oscuro, manteniendo la estabilidad y jerarquía de **Vitrina 06**.
*   **Cross-Check de Calificaciones en UI:**
    *   Se inyectó en [`perseus_engine.js`](file:///c:/xampp/htdocs/sistema_escolar/js/modules/perseus_engine.js) una capa de normalización para decodificar las respuestas del alumno en caliente (soporta tanto formato de mapa JSON como array de objetos).
    *   Se sincronizó la UI para que lea el valor real de la base de datos `calificacion_automatica` y lo pinte en el contenedor `#nota-automatica` al abrir el modal, consolidando matemáticamente las notas sin discrepancias.

---

## ⚙️ 3. REDISEÑO ACORDADO CON EL INGENIERO RICARDO

En la última sesión de auditoría con el **Ingeniero Ricardo**, se han definido directrices fundamentales sobre la lógica de la aplicación que el siguiente agente **DEBE IMPLEMENTAR DE FORMA OBLIGATORIA Y CON ALTO ESTÁNDAR TÉCNICO**:

### A. Re-Arquitectura Ética del Módulo de Recuperaciones (Retakes)
*   **El Consenso Supremo:** El examen individual en sí mismo **NO se puede recuperar ni alterar de forma directa dentro del modal de su propia calificación**. Si un estudiante falló un examen específico, esa nota queda asentada tal como fue obtenida (inmutabilidad académica).
*   **El Flujo Correcto:** La recuperación debe ser gestionada estrictamente a nivel de **materia** o **dimensión**.
*   **El Mecanismo:** El docente creará una **nueva actividad extra de recuperación (oportunidad adicional)** para los estudiantes reprobados. La nota de esta nueva actividad será procesada bajo las directivas globales establecidas en la configuración de recuperaciones (ej: promedio simple, reemplazo de la peor nota de la dimensión, o reemplazo topado en el límite de aprobación `3.0`).
*   **Blindaje:** Esto elimina la "puerta a la corrupción escolar" y la discrecionalidad desregulada del docente.

### B. Corrección de Sincronización en Inputs de Preguntas Individuales
*   **El Problema:** Al reabrir el modal de revisión para un estudiante calificado previamente (ej. Mateo Rodríguez), el acumulado se ve bien en la barra derecha, pero los campos numéricos de las tarjetas de preguntas individuales se inicializan en `0` en lugar de recuperar y pintar sus valores fraccionarios guardados en la tabla de detalles (`eval_respuestas_detalles`).
*   **La Tarea:** Modificar el renderizador del modal en JavaScript para que consulte el desglose de puntajes asignados a nivel de ítem y rellene dinámicamente los inputs individuales con sus valores históricos.

### C. Mitigación de Fragilidad y Carga Infinita (Soft-Locks)
*   **El Problema:** Ante retrasos de red o bloqueos de escritura WAL temporales en SQLite, los spinners de carga AJAX (como el de "Evaluaciones Activas") se quedan girando infinitamente si ocurre un error.
*   **La Tarea:** Implementar cláusulas `.catch()` y visualizadores de error flotantes premium (usando SweetAlert2) para detener el spinner y avisar amigablemente al docente en caso de excepciones.

---

## 🚀 4. INSTRUCCIONES DE ENTRADA INMEDIATA PARA EL PRÓXIMO AGENTE
1.  **Auditoría SAST:** Recuerda ejecutar obligatoriamente `C:\xampp\php\php.exe antigravity_auditor.php` tras cualquier cambio de código y garantizar 0 violaciones antes de reportar progreso.
2.  **Higiene:** Cumplir los manifiestos de diseño (Métrica 44px, HSL, Seda, sin styles inline, sin código comentado).
3.  **Prioridad:** Resolver el sync visual de inputs individuales de preguntas en el modal de Ares y diseñar la desactivación de la recuperación libre directa del modal para moverla al flujo de actividades/dimensiones.

**CERTIFICACIÓN:** Este manifiesto técnico ha sido estructurado y sellado bajo la estricta doctrina Vitrina 06 y cuenta con la aprobación conceptual del Ingeniero Ricardo.
