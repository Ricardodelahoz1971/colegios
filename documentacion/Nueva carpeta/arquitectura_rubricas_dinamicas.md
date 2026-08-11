# 🏗️ Arquitectura de Calificación y Rúbricas Dinámicas

Este documento define la estructura técnica, pedagógica y de experiencia de usuario (UX/UI) para la implementación del nuevo modelo de evaluación dentro del motor Perseus, cumpliendo estrictamente con la doctrina estética y funcional Vitrina 06.

## 1. 🎯 El Concepto: Naturaleza de la Nota y Evaluación Multicriterio

Antes de calificar, el sistema debe entender pedagógicamente **qué se está calificando**. Por ello, el modelo se estructura en dos grandes capas conceptuales:

**A. La Clase de Nota (Dimensión del Aprendizaje):**
Toda actividad creada por el docente debe pertenecer a una "Clase de Nota" o dimensión institucional (Ej: *Saber, Hacer, Ser*, o *Formativa vs. Sumativa*). Esta categorización es vital, ya que permite al sistema generar reportes y promedios analíticos a final de periodo basados en competencias reales, y no en una simple bolsa de notas revueltas.

**B. La Calificación Multicriterio (Rúbricas):**
Una vez definida la dimensión (Ej: Un ensayo clasificado en el *Hacer*), la calificación no tiene que limitarse a un único valor numérico. El sistema permitirá al docente desglosar la nota de dicha actividad en múltiples criterios ponderables (Ej: Redacción 30%, Presentación 20%, Contenido 50%). La sumatoria o promedio matemático de estos criterios generará la nota final y blindada de la actividad, otorgando transparencia total al estudiante y defensa argumentativa al docente.

## 2. 🗄️ Desafío de Base de Datos (Estructura Relacional Profunda)

Para soportar la libertad creativa del docente y la categorización pedagógica, queda estrictamente prohibido el uso de columnas estáticas (`nota1`, `nota2`) en la base de datos. Se implementará una arquitectura relacional (1 a N) con tipado robusto:

*   **Entidad de Dimensión (`clases_nota`):** Tabla catálogo gestionada por la institución que define las categorías (Saber, Hacer, Ser, etc.) y su peso global en el periodo si aplica.
*   **Entidad Principal (`actividades`):** Define el trabajo o examen maestro. **Obligatoriamente** tendrá una llave foránea (`clase_nota_id`) para heredar su naturaleza pedagógica. Además, definirá el modo de evaluación (Deductivo, Rúbrica o Directo).
*   **Entidad de Desglose (`actividad_criterios`):** Almacena los criterios específicos creados por el profesor para esa actividad (Redacción, Presentación, etc.) y su peso porcentual.
*   **Entidad Transaccional (`calificaciones_desglose`):** Almacena la calificación individual que obtuvo el estudiante en cada criterio, con trazabilidad de fecha y auditoría.

## 3. 🎨 Desafío UX/UI: El "Focus Mode" (Vitrina 06)
Se prohíbe el uso de interfaces tipo "Hoja de Cálculo" o tablas masivas (grillas de Excel) para la inserción de estas notas. Mostrar 160 inputs en pantalla para 40 alumnos genera fatiga visual, alta tasa de error y contraviene el estándar Elite de Ares.

**La Solución: Flujo de Calificación "Uno por Uno" (Focus Mode)**
1.  **Aislamiento Visual:** El profesor visualizará la interfaz de calificación centrada en un único estudiante a la vez.
2.  **Interactividad Premium:** Los criterios se presentarán mediante controles interactivos de gran tamaño (altura 44px, radios de 12px), como *sliders* o botones de acción rápida, eliminando la necesidad de digitar manualmente números decimales a menos que sea estrictamente necesario.
3.  **Matemática y Reactividad en Vivo:** Al manipular los controles de los criterios, la "Nota Final de la Actividad" se calculará y actualizará en tiempo real en la pantalla usando JavaScript, proporcionando feedback instantáneo (micro-animaciones).
4.  **Transición de Seda:** Al presionar "Siguiente" o "Enter", el sistema guardará asíncronamente la calificación (Fetch API) y deslizará suavemente hacia el siguiente estudiante en la lista.

## 4. 📷 Integración con Motor OCR (Escaneo Físico)
Este flujo Focus Mode está diseñado para acoplarse armónicamente con la futura lectura por cámara:
El docente expone el examen físico ante la cámara $\rightarrow$ El sistema detecta la identidad (QR) $\rightarrow$ Despliega automáticamente el "Focus Mode" del estudiante $\rightarrow$ El docente ajusta rápidamente la rúbrica $\rightarrow$ Siguiente hoja.

## 5. ⚖️ Arquitectura Dual: Rúbrica vs. Nota Directa
El sistema debe ser flexible y no imponer la fricción de crear criterios si el docente no lo desea o la actividad no lo requiere. Se implementará un **Modo Dual** en la creación/calificación de la actividad:

*   **Modo Rúbrica (Multicriterio):** El profesor desglosa la nota en múltiples partes (Redacción, Presentación, etc.), según lo descrito en los puntos anteriores.
*   **Modo Directo (Nota Única):** Si el profesor determina que es una evaluación sencilla (Ej. Quiz rápido o revisión de cuaderno), el sistema desactiva la obligación de crear criterios. En la base de datos, la nota se guarda directamente asociada a la actividad sin desglose. En el "Focus Mode", la interfaz mostrará un único control (input gigante o slider maestro) para ingresar el valor de forma inmediata.

---
*Documento vivo para moldeado de idea. Integración con Perseus Engine.*
*Aprobado por: Ingeniero Ricardo*
