# Cimentación Académica v1.0: Del Esqueleto a la Realidad de Datos

Tras la acertada observación del Capitán sobre la falta de datos crudos para analizar, reorientamos la misión. Antes de las gráficas (BI), necesitamos los **Generadores de Datos**. El primer objetivo es el **Motor Académico (Calificaciones)**.

## Diagnóstico Táctico
El sistema posee actualmente la armadura (tablas de estudiantes, cursos, carga académica) pero carece de la "sangre" de datos (notas, logros, incidentes). Un panel tipo Power BI en este estado mostraría cuadros vacíos. Debemos construir los capturadores primero.

## Propuesta: El Motor Académico (Calificaciones)

### 1. Infraestructura de Base de Datos
- **Tabla `calificaciones`**:
    - `id`: Identificador único.
    - `estudiante_id`: Referencia al alumno.
    - `curso_id`: Contexto de grado.
    - `especialidad_id`: Materia específica.
    - `periodo`: Segmento de tiempo (Periodo 1, 2, 3, 4).
    - `nota`: Valor cuantitativo.
    - `observacion`: Feedback del docente.
    - `autor_id`: Trazabilidad del docente.

### 2. Interfaz de Captura (Modo Élite)
- **Notas Rápidas**: Matriz dinámica donde el docente puede ingresar notas masivamente por curso y materia.
- **Validación Automática**: Feedbak visual (Semáforo de colores) según el rendimiento ingresado.
- **Persistencia Silenciosa**: Autoguardado vía AJAX para evitar pérdida de datos durante la digitación.

## Preguntas de Navegación Abiertas
1. **Calendario**: ¿Cuántos periodos académicos maneja la institución?
2. **Modelo**: ¿Nota única o desglose por logros (Saber, Hacer, Ser)?

---
*Archivo generado para referencia estratégica de Analistas de Datos Zulu.*
