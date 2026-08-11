# 🏛️ MANUAL DE PRUEBAS DE REGULACIÓN ACADÉMICA (ESTÁNDAR VITRINA 06)

Este manual técnico y funcional desglosa detalladamente cada control interactivo, slider e indicador inyectado en el sistema escolar para la gestión dinámica de **Escalas Institucionales**, **Planes de Notas Mínimas** y **Políticas de Recuperación (Retake)**.

---

## 🎨 VISTA 1: PANEL DE CONFIGURACIÓN ACADÉMICA (HEFESTO)
**Ubicación**: *Configuración ➔ Pestaña Académico ➔ Sección "Regulación Académica y Escala de Notas"*

Este panel maestro está protegido bajo un radio de **24px** y sombras con matiz primario. Permite a la rectoría y coordinación parametrizar todo el comportamiento del sistema.

### 📐 Desglose de Controles y Entradas:

| Control Visual | Identificador / Selector | Tipo de Control | Reglas de Validación y Comportamiento |
| :--- | :--- | :--- | :--- |
| **Nota Mínima** | `#escala-nota-minima` | Input Numérico (Step 0.1) | Establece el límite inferior absoluto del software. No puede ser mayor o igual a la Nota Máxima. |
| **Nota Máxima** | `#escala-nota-maxima` | Input Numérico (Step 0.1) | Establece el límite superior absoluto del software. Rige los sliders de calificación de todo el sistema. |
| **Nota de Aprobación** | `#escala-nota-aprobacion` | Input Numérico (Step 0.1) | Rige la frontera entre aprobado y reprobado. Debe estar estrictamente contenida entre la Nota Mínima y la Nota Máxima. |
| **Límite Desempeño Básico** | `#escala-basico-min` | Input Numérico (Step 0.1) | Límite inferior para que una nota califique como "Desempeño Básico". **Debe coincidir exactamente con la Nota de Aprobación institucional.** |
| **Límite Desempeño Alto** | `#escala-alto-min` | Input Numérico (Step 0.1) | Límite inferior para "Desempeño Alto". Debe ser estrictamente superior al límite Básico y menor al Superior. |
| **Límite Desempeño Superior** | `#escala-superior-min` | Input Numérico (Step 0.1) | Límite inferior para "Desempeño Superior". Debe ser estrictamente superior al límite Alto y menor a la Nota Máxima. |
| **Pesos de Dimensiones** | `[name="peso_dim[ID]"]` | Inputs Numéricos (%) | Peso porcentual de cada dimensión (Saber, Hacer, Ser). **La suma de todas las dimensiones activas debe ser exactamente 100%.** |
| **Mínimo Evaluaciones** | `[name="min_eval_dim[ID]"]` | Selectores de Control (44px) | Establece cuántas actividades obligatorias debe subir el docente por dimensión en el periodo. Mínimo 1. |
| **Política de Recuperaciones** | `#global-politica-recuperacion` | Selector de Control (44px) | Define cómo se consolidará la nota final cuando exista un retake. Opciones: *Reemplazo*, *Promedio* o *Tope de Aprobación*. |

---

## ⚡ VISTA 2: CALIFICADOR DE ACTIVIDADES DE AULA (ARES FOCUS MODE)
**Ubicación**: *Constructor de Actividades ➔ Calificar Estudiante ➔ Modal individual "Focus Mode"*

Este modal digital centraliza la evaluación manual de actividades de clase.

### 📐 Desglose de Controles y Entradas:

#### 1. Slider / Input de Calificación Directa (`#focus-input-directo`)
*   **Qué hace**: Permite al docente arrastrar o escribir la calificación original del estudiante.
*   **Comportamiento Dinámico**: Sus atributos `min` y `max` se configuran automáticamente al vuelo desde `window.AresEscala`. Si la escala institucional se cambia a un rango de 1.0 a 10.0, el control se adaptará al instante sin requerir actualizaciones de código.

#### 2. Input de Nota de Recuperación / Retake (`#focus-input-recuperacion`)
*   **Qué hace**: Campo de entrada numérico opcional para registrar la nota de recuperación del estudiante.
*   **Comportamiento Dinámico**: 
    - Se expone visualmente mediante animación de seda en cuanto la calificación directa es menor a la nota de aprobación institucional.
    - Autovalida límites al escribir: si el docente ingresa un valor fuera de la escala configurada, el control se ajusta automáticamente al límite correspondiente.

---

## 🛡️ VISTA 3: CENTRO DE CALIFICACIONES DE EXÁMENES (PERSEUS ENGINE)
**Ubicación**: *Centro de Calificaciones ➔ Calificar Pruebas ➔ Grilla de Entregas ➔ Botón "Revisar"*

Esta vista gestiona la calificación deductiva y manual de exámenes en línea y pruebas físicas digitalizadas.

### 📐 Desglose de Controles y Entradas:

#### 1. Calificación Manual (`#nota-manual`)
*   **Qué hace**: Muestra o permite editar el puntaje asignado manualmente por el docente en las preguntas abiertas.

#### 2. Input de Recuperación (`#nota-recuperacion-prueba`)
*   **Qué hace**: Campo de entrada numérica opcional exclusivo para registrar la nota de recuperación de la prueba.
*   **Comportamiento Dinámico**:
    - Dispara en tiempo real la función `actualizarNotaFinal()`.
    - Realiza el cálculo matemático en caliente de la **Nota Final** mostrada en la base del modal, aplicando al vuelo la política vigente (`window.AresPoliticaRecuperacion`) para que el docente visualice el impacto del beneficio antes de guardarlo.

#### 3. Nota Final Resultante (`#nota-final-calc`)
*   **Qué hace**: Indicador de visualización premium destacado que proyecta el promedio consolidado exacto que se persistirá.

---

## 📊 VISTA 4: SÁBANA DE CALIFICACIONES CONSOLIDADA
**Ubicación**: *Sábana de Calificaciones ➔ Selector de Curso y Periodo*

Matriz general de desempeño académico institucional en tiempo real.

### 📐 Desglose de Controles e Indicadores:

#### 1. Indicador de Alerta de Plan Incompleto (`⚠️`)
*   **Qué hace**: Aparece automáticamente en el encabezado de la columna de la materia si el docente no cumple con el plan mínimo de notas del periodo.
*   **Comportamiento Dinámico**: 
    - Al pasar el cursor por encima (`:hover`), despliega un tooltip nativo detallando de forma exacta qué dimensiones no cumplen con el plan institucional y cuántas actividades han sido subidas en realidad (ej: *Hacer: requiere mín. 2 (subidas: 1)*).

#### 2. Celdas de Calificación (`.col-calif`)
*   **Qué hace**: Exhibe la nota definitiva del estudiante en la materia.
*   **Comportamiento Dinámico**:
    - Muestra la nota final consolidando las actividades y exámenes tras aplicar la política de recuperación en el servidor.
    - Se colorea dinámicamente con matices HSL de alta fidelidad según el rango de desempeño:
        - **Verde Esmeralda** (`.nota-superior`): Desempeño Superior.
        - **Azul Zafiro** (`.nota-alto`): Desempeño Alto.
        - **Naranja Ámbar** (`.nota-basico`): Desempeño Básico.
        - **Rojo Carmesí** (`.nota-reprobado`): Desempeño Bajo (Reprobado).
    - Al hacer clic, abre el panel Slide-over deslizable de lectura pura.

#### 3. Panel Deslizable de Desglose (Slide-over Details)
*   **Qué hace**: Despliega desde el costado derecho una planilla premium de todas las calificaciones del estudiante.
*   **Comportamiento Dinámico**:
    - Si el estudiante cuenta con nota de recuperación en una actividad o examen, la tarjeta de desglose exhibirá de forma transparente el indicador:
      `Orig: [Nota Original] | Recup: [Nota Recuperación]`
    - El badge numérico de la tarjeta mostrará la calificación final ya normalizada, brindando total claridad de auditoría al docente y coordinador.

---

## 🚀 RUTA DE PRUEBAS RECOMENDADA (GUIADA)

### Paso A: Simular el Ajuste de Escalas
1. Vaya a *Configuración ➔ Académico*.
2. Defina una escala comercial: `Mínima: 1.0`, `Máxima: 10.0`, y `Aprobación: 6.0`.
3. Configure `Básico inferior: 6.0` (debe coincidir), `Alto inferior: 8.0`, y `Superior inferior: 9.5`.
4. Defina la **Política de Recuperaciones** en **Tope de Aprobación**.
5. Presione **Guardar Escala**.

### Paso B: Evaluar Actividad en Aula (Ares)
1. Ingrese a *Constructor de Actividades* como docente.
2. Cree una actividad en cualquier curso.
3. Abra el modal de calificación (**Focus Mode**).
4. Verifique que el slider le permita desplazarse en el rango dinámico de **1.0 a 10.0**.
5. Asigne una nota original de `4.0` (Reprobado).
6. Escriba una nota de recuperación de `8.5` en el campo que se expone.
7. Presione **REGISTRAR NOTA**.

### Paso C: Evaluar Examen en Centro de Calificaciones (Perseus)
1. Ingrese a *Centro de Calificaciones ➔ Calificar Pruebas*.
2. Abra el calificador (**Revisar**) de un estudiante.
3. Asigne una calificación manual original de `3.0` (Reprobado).
4. Digite una nota de recuperación de `9.0`.
5. Observe en tiempo real el indicador de la base del modal:
   - Dado que la política activa es **Tope de Aprobación**, la nota final del estudiante debe autolimitarse exactamente a `6.0` (la nota de aprobación), a pesar de haber sacado un `9.0` en la recuperación.
6. Presione **PUBLICAR NOTA FINAL**.

### Paso D: Validar Consolidación en la Sábana
1. Vaya a *Sábana de Calificaciones* y cargue el consolidado del grupo y periodo.
2. Localice la celda del estudiante evaluado:
   - Su promedio reflejará el impacto exacto de las notas recuperadas (`8.5` en la actividad del Paso B [ya que 8.5 es mayor a 4.0] y `6.0` en el examen del Paso C [bajo el tope de aprobación]).
3. Haga clic sobre la celda para abrir el **Slide-over**:
   - Constate que las tarjetas de las evaluaciones exhiban de forma transparente sus notas originales y de recuperación correspondientes.
