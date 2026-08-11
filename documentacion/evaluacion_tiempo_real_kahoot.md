# 🏛️ BLUEPRINT: MOTOR DE EVALUACIÓN EN TIEMPO REAL (ESTILO KAHOOT!)

Este documento rige la arquitectura de software, la lógica de negocio y las directrices de interfaz para la implementación futura del módulo **ARES Live!**, un sistema gamificado de evaluación en tiempo real diseñado bajo las especificaciones de la **Vitrina 06** para el Ingeniero Ricardo.

---

## 1. Concepto Pedagógico y Dinámica de Juego

**ARES Live!** transforma las evaluaciones estáticas en un juego de competición grupal en vivo:
1. **Acceso Simplificado**: El docente inicia una sesión de examen en la pantalla del salón y genera un PIN numérico temporal. Los estudiantes ingresan desde su aula virtual digitando dicho PIN.
2. **Control Centralizado**: El docente controla el paso de una pregunta a otra. La pregunta se proyecta en la pantalla principal (video beam) o se sincroniza en los dispositivos móviles.
3. **Puntuación de Agilidad**: Premia tanto la precisión (acertar la respuesta) como la velocidad (tiempo de respuesta en milisegundos).
4. **Ciclo de Dopamina**: Al finalizar cada reactivo se muestra un podio temporal (Top 5) con animaciones fluidas y micro-interacciones.

---

## 2. Arquitectura Técnica (MariaDB & Hermes)

### A. Capa de Datos (MariaDB)
Aprovechando que la base de datos se encuentra migrada a MariaDB, se utilizará un esquema relacional con índices optimizados para lecturas y escrituras simultáneas rápidas.

#### Nueva Tabla: `eval_live_sesiones`
Almacena el estado global de cada sesión de juego en vivo iniciada por un docente.
* `id` (INT, PK, AUTO_INCREMENT)
* `docente_id` (INT, FK -> `usuarios.id`)
* `prueba_id` (INT, FK -> `eval_pruebas.id`)
* `pin` (VARCHAR(6), UNIQUE) - Código de acceso de 6 dígitos.
* `estado` (ENUM: `'lobby'`, `'pregunta_activa'`, `'mostrando_podio'`, `'finalizada'`)
* `pregunta_actual_id` (INT, NULL, FK -> `eval_preguntas.id`)
* `created_at` (TIMESTAMP)

#### Nueva Tabla: `eval_live_participantes`
Registra los estudiantes conectados a una sesión activa.
* `id` (INT, PK, AUTO_INCREMENT)
* `sesion_id` (INT, FK -> `eval_live_sesiones.id`)
* `estudiante_id` (INT, FK -> `usuarios.id`)
* `puntaje_acumulado` (INT, DEFAULT 0)
* `activo` (BOOLEAN, DEFAULT TRUE)

#### Nueva Tabla: `eval_live_respuestas`
Almacena cada envío individual en tiempo real.
* `id` (INT, PK, AUTO_INCREMENT)
* `sesion_id` (INT, FK -> `eval_live_sesiones.id`)
* `pregunta_id` (INT, FK -> `eval_preguntas.id`)
* `estudiante_id` (INT, FK -> `usuarios.id`)
* `opcion_seleccionada` (VARCHAR(1)) - Ej: `'A'`, `'B'`, `'C'`, `'D'`
* `milisegundos_respuesta` (INT) - Tiempo desde que se abrió la pregunta.
* `puntaje_obtenido` (INT)
* `created_at` (TIMESTAMP)

### B. Capa en Tiempo Real (Motor Hermes)
El flujo requiere comunicación bidireccional continua de baja latencia:
* **Protocolo**: WebSockets o Server-Sent Events (SSE) a través de Hermes para notificar a los estudiantes cuando el docente cambia el estado de la partida (ej: transicionar de lobby a pregunta activa).
* **Envío de Respuestas**: AJAX / Fetch API estándar del cliente al endpoint `api_live_responder.php`. Al usar MariaDB con transacciones rápidas, el servidor responderá en menos de 50ms sin bloqueos de concurrencia.

---

## 3. Algoritmo de Puntuación de Agilidad

Para mantener la equidad pedagógica y premiar la velocidad sin desincentivar la precisión, se implementará una fórmula de puntaje dinámico decreciente:

$$Puntaje = \text{Puntos Base} \times \left( 1 - \left( \frac{\text{Tiempo de Respuesta}}{\text{Tiempo Límite}} \times \text{Factor de Penalización} \right) \right)$$

### Parámetros Estándar:
* **Puntos Base**: Max `1000` puntos por respuesta correcta.
* **Tiempo Límite ($T_{\text{max}}$)**: Definido por el reactivo (ej: `30` segundos).
* **Tiempo de Respuesta ($t$)**: Medido en el cliente para neutralizar la latencia del servidor.
* **Factor de Penalización**: `0.5` (garantiza que responder al último segundo otorgue al menos el 50% de los puntos base si es correcta). Si la respuesta es incorrecta, el puntaje es `0`.

---

## 4. Lineamientos de Diseño e Interfaz (Vitrina 06)

La interfaz se ceñirá estrictamente al ADN Élite del sistema escolar:

1. **La Métrica 44px**: Todos los botones de opciones (A, B, C, D) y selectores de lobby tendrán una altura mínima de 44px para facilitar la pfación en dispositivos móviles.
2. **Radio de Prestigio**: Las tarjetas de las preguntas usarán bordes suavizados de `24px` (`border-radius: var(--el-rounded-lg)`), y los botones de respuesta tendrán `12px` de curvatura.
3. **Paleta de Colores Curada**: Prohibido el uso de HEX. Las opciones de respuesta se renderizarán usando variables de CSS HSL:
   * **Opción A (Triángulo)**: Fondo rojo tenue con borde `var(--el-danger)`.
   * **Opción B (Rombo)**: Fondo azul tenue con borde `var(--el-primary)`.
   * **Opción C (Círculo)**: Fondo amarillo tenue con borde `var(--el-warning)`.
   * **Opción D (Cuadrado)**: Fondo verde tenue con borde `var(--el-success)`.
4. **Física de Sombras**: Las tarjetas del podio tendrán sombras flotantes difuminadas con matiz primario: `box-shadow: 0 10px 30px hsla(var(--el-primary-hsl), 0.15)`.
5. **Transiciones Seda**: Todas las transiciones de estados (revelar respuestas correctas, cambios en el podio, avance del reloj) usarán `transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1)`.

---

## 5. Análisis Comparativo de Mercado (Kahoot! vs. Quizizz)

ARES Live! se posiciona como una herramienta híbrida superior al tomar las ventajas competitivas de los dos líderes del mercado global:

| Característica | Kahoot! | Quizizz | **ARES Live! (Propuesta Híbrida)** |
| :--- | :--- | :--- | :--- |
| **Ritmo de Ejecución** | Sincrónico estricto (controlado por docente). | En vivo o al ritmo del estudiante (asincrónico). | **Lobby Sincrónico Controlado** para mantener el debate pedagógico activo. |
| **Dispositivo del Alumno** | Solo figuras/colores de selección (requiere proyector). | Pregunta + Opciones en pantalla propia. | **Pantalla Activa Completa**: El alumno lee la pregunta y opciones directamente en su móvil, optimizando la legibilidad de fórmulas matemáticas y diagramas. |
| **Integración con Notas** | Externa (requiere transcripción manual del docente). | Externa (requiere transcripción manual del docente). | **Sincronización Directa**: Al terminar la sesión, la nota resultante se inyecta de forma directa a la Sábana de Notas oficial (`ares_calificaciones_desglose`). |

---
*Documento arquitectónico guardado y certificado para su desarrollo posterior.*
