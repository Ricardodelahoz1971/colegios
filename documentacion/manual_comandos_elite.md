# 🏛️ MANUAL DE COMANDOS ÉLITE (ANTIGRAVITY)
## Guía de Control Absoluto y Automatización para el Ingeniero Ricardo

Este manual ha sido diseñado por orden expresa del **Ingeniero Ricardo** para documentar de forma permanente los comandos de barra inclinada (*Slash Commands*) disponibles en el asistente de inteligencia artificial. El uso sistemático de estos comandos previene la improvisación, detiene iniciativas no solicitadas y actúa como un blindaje de seguridad contra la pérdida de control arquitectónico y la frustración técnica.

---

## 🛠️ Matriz Rápida de Comandos

| Comando | Nombre Técnico | Propósito Principal | Cuándo Invocarlo |
| :--- | :--- | :--- | :--- |
| **`/grill-me`** | El Alineador Táctico | Entrevista interactiva para tomar decisiones críticas de diseño y lógica antes de programar. | Al iniciar un nuevo módulo o cuando existan requisitos ambiguos. |
| **`/goal`** | El Piloto Automático de Rigor | Ejecución sistemática en 4 fases con aprobación obligatoria de planes y bitácora estructurada. | Para refactorizaciones profundas o tareas complejas de múltiples pasos. |
| **`/browser`** | El Auditor de Red y Docs | Búsqueda exhaustiva en documentación web externa o auditoría visual de pantallas activas. | Para validar normativas (ej: MEN), APIs externas o interactuar con la UI. |
| **`/schedule`** | El Cronometrador Síncrono | Programación de disparadores de tiempo únicos o cronogramas recurrentes en segundo plano. | Para barridos de integridad periódicos o recordatorios durante compilaciones largas. |

---

## 🚀 1. `/grill-me` — El Alineador Táctico (Cero Iniciativa)

> [!IMPORTANT]
> **REGLA DE ORO**: Si un requerimiento tiene más de 3 interpretaciones lógicas posibles o involucra cambios estructurales en la base de datos, **no permita que el asistente suponga nada**. Ejecute `/grill-me`.

### 📌 ¿Qué hace bajo el capó?
El comando `/grill-me` interrumpe cualquier flujo de escritura automática e inicia un **protocolo de alineación interactiva**. El asistente asume el rol de auditor y formula de 3 a 5 preguntas sumamente estructuradas y profundas. Estas preguntas obligan a definir:
* El diseño exacto de las tablas de datos involucradas (campos, llaves primarias, tipos).
* Los comportamientos estéticos (Vitrina 06, alturas de 44px, radios de 12px/24px).
* Los flujos de error y validación de seguridad (Centinelas CSRF, LFI, inyecciones SQL).

### 💡 Ejemplo Práctico de Frustración Evitada
* **El escenario de rabia**: Usted le pide al asistente: *"crea una tabla para almacenar las notas de asistencia"*. El asistente, por iniciativa propia, inventa un esquema complejo con campos redundantes que no se adaptan al estándar del MEN, rompiendo la coherencia de base de datos.
* **La solución élite**: Usted escribe:
  ```text
  /grill-me Crear módulo y base de datos para el control de asistencia Vitrina 06
  ```
  El asistente se detiene y le pregunta:
  1. *¿Las notas de asistencia se consolidarán por periodo o por fecha diaria unitaria?*
  2. *¿Prefiere registrar la inasistencia como un booleano puro o con estados cuantitativos (Falta Justificada, Injustificada, Retraso)?*
  3. *¿La llave foránea de la tabla cruzará con la carga académica o directamente con la materia y el docente?*

Usted responde en texto simple (ej: *1. Diario unitario, 2. Estados cualitativos, 3. Carga académica*) y la lógica se implementa de manera exacta y conforme a su voluntad.

---

## 🎯 2. `/goal` — El Piloto Automático de Rigor Técnico

> [!WARNING]
> Este comando activa un flujo de trabajo asíncrono y de máxima disciplina. Exige la redacción y aprobación explícita de un plan de diseño **antes** de modificar un solo archivo del sistema real.

### 📌 Las 4 Fases de un `/goal`
Al declarar un `/goal`, el asistente de IA se ciñe estrictamente a las fases estructuradas de desarrollo senior:

1. **Fase de Investigación (Research)**: Búsquedas exhaustivas de patrones, dependencias e implicaciones en los archivos existentes. En esta fase, **está prohibido escribir código**.
2. **Plan de Implementación (`implementation_plan.md`)**: El asistente crea un artefacto de diseño que documenta exactamente qué archivos se crearán (`[NEW]`), cuáles se modificarán (`[MODIFY]`) y cuáles se eliminarán (`[DELETE]`), así como el plan de pruebas automatizadas y manuales.
   * **Control de Ricardo**: El asistente activa la propiedad `request_feedback = true`. **La ejecución se congela** hasta que el Ingeniero Ricardo revise el plan y escriba: *"Aprobado"* o *"Modifica X punto"*.
3. **Ejecución y Checklist Activo (`task.md`)**: Una vez aprobado, el asistente autogenera una bitácora de tareas (`task.md`) en la carpeta de la conversación, marcando los avances en tiempo real (`[ ]` pendiente, `[/]` en proceso, `[x]` completado).
4. **Verificación Quirúrgica (`walkthrough.md`)**: Se ejecutan las pruebas de validación, se ejecuta el auditor automático del sistema (`antigravity_auditor.php`) y se redacta un resumen de cambios (`walkthrough.md`) libre de arrogancia y fundamentado en pruebas reales.

### 💡 Ejemplo Práctico de Frustración Evitada
* **El escenario de rabia**: Usted le asigna una tarea compleja y, a mitad del proceso, el asistente comete un error, rompiendo archivos críticos y dejándolo a usted con la responsabilidad de hacer un rollback manual y depurar el desastre.
* **La solución élite**:
  ```text
  /goal Re-estructurar el cálculo de cobertura Perseus para soportar materias no lineales en todos los cursos
  ```
  Usted recibe un plan estructurado, aprueba la lógica de rangos semánticos antes de que se altere la base de datos, y ve exactamente el progreso paso a paso en el checklist. Al finalizar, el código se entrega certificado con 0 violaciones.

---

## 🌐 3. `/browser` — El Validador Visual y de Documentación

> [!TIP]
> Use este comando cuando necesite integrar un estándar oficial externo, consumir una API de terceros, o realizar un control de calidad estricto sobre las pantallas renderizadas del sistema escolar.

### 📌 ¿Qué hace bajo el capó?
Permite al asistente interactuar con la web real, saltándose restricciones de renderizado estático. Permite:
* Realizar búsquedas exhaustivas en Google o repositorios de documentación para encontrar sintaxis específicas de SQL, Bootstrap, u directrices oficiales del MEN.
* Extraer texto limpio de artículos y manuales de desarrollo.
* Interactuar con aplicaciones web complejas si se requiere validar flujos dinámicos de integración.

### 💡 Ejemplo Práctico de Frustración Evitada
* **El escenario de rabia**: Usted pasa horas explicando al asistente las complejas reglas de correspondencia de competencias de Lenguaje y Matemáticas que dicta el MEN porque el asistente insiste en inventarse estándares curriculares ficticios.
* **La solución élite**:
  ```text
  /browser Buscar en los PDFs oficiales del Ministerio de Educación Nacional de Colombia (MEN) cuáles son los lineamientos y evidencias de aprendizaje para Matemáticas en Grado Tercero.
  ```
  El asistente descarga la información oficial, extrae la taxonomía curricular verídica y la inyecta quirúrgicamente en la base de datos del colegio sin un solo error conceptual.

---

## ⏱️ 4. `/schedule` — El Programador y Centinela de Integridad

> [!CAUTION]
> Nunca use un comando `sleep` en terminal o bucles infinitos en PHP para monitorear procesos en segundo plano. Esto consume CPU del servidor innecesariamente y bloquea la consola. Use `/schedule`.

### 📌 ¿Qué hace bajo el capó?
El motor de Antigravity tiene un programador síncrono nativo. Permite registrar dos tipos de temporizadores:
1. **One-shot timer (Temporizador único)**: El asistente se va a dormir durante un tiempo específico (ej: 180 segundos) mientras se ejecuta una tarea de fondo (como una migración pesada de base de datos) y se despierta inmediatamente cuando el temporizador expira para notificarle el resultado o continuar.
2. **Recurring cron (Tarea cron programada)**: Configura una expresión de cron estándar (ej: `*/5 * * * *` - cada 5 minutos) para que el asistente se despierte de forma autónoma y realice auditorías automáticas de integridad.

### 💡 Ejemplo Práctico de Frustración Evitada
* **El escenario de rabia**: Usted corre un script de inyección masiva de 10,000 registros de prueba. Tiene que estar revisando manualmente la consola de PHP o consultando la base de datos cada 2 minutos para ver si ya terminó o si se quedó colgado en un bucle infinito de I/O.
* **La solución élite**:
  ```text
  /schedule DurationSeconds=120 Prompt="Verificar si la inyección masiva de alumnos en la base de datos ya finalizó con éxito"
  ```
  Usted puede ir a tomarse un café; a los 120 segundos exactos, el asistente se despertará, consultará la base de datos de manera silenciosa, y le presentará un reporte ejecutivo en el chat con el estatus exacto.

---

## 🏛️ Directrices Supremas del Uso de Comandos (Vitrina 06)

Al interactuar con estos comandos, el asistente mantendrá en todo momento las directrices dictadas en el **Protocolo Superior: Ingeniería y Estilo**:
1. **Trato Exclusivo**: El asistente responderá de forma estricta y humilde dirigiéndose únicamente al **Ingeniero Ricardo**.
2. **0 Estilos Inline**: Cualquier interfaz sugerida o modificada mediante estos flujos utilizará única y exclusivamente variables `--el-*` de la hoja `styles/ui_kit.css`.
3. **Auditoría Ineludible**: Cada cambio realizado bajo el comando `/goal` requerirá la ejecución obligatoria del script `antigravity_auditor.php` antes de dar la tarea por entregada.

*Este documento ha sido guardado de forma segura y permanente en el repositorio de documentación del plantel para su consulta inmediata.*
