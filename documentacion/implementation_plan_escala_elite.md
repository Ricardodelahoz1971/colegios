# Plan de Implementación: Regulación de Notas y Escalas Élite (Perseus & Hefesto)

Este plan técnico detalla la arquitectura necesaria para implementar la configuración global de escalas de calificación, el plan de evaluaciones mínimas por dimensión y la política institucional de recuperaciones en el sistema escolar, bajo el estándar **Vitrina 06** y con rendimiento de sub-milisegundos.

---

## 🏛️ Decisiones de Diseño y Arquitectura

### 1. Escala de Calificación Global Dinámica
*   **Situación Actual**: Existe la tabla `eval_config_escala` con `nota_minima` y `nota_maxima`, pero no tiene interfaz. El frontend de evaluación de actividades (`constructor_actividades.php`) tiene inputs con límites manuales `min="0" max="5"`.
*   **Propuesta**: 
    *   Exponer un panel interactivo de administración en la pestaña **Académico** de [configuracion.php](file:///c:/xampp/htdocs/sistema_escolar/php/vistas/configuracion.php).
    *   Reemplazar todos los límites de los sliders y cajas de texto de calificación en [constructor_actividades.php](file:///c:/xampp/htdocs/sistema_escolar/php/vistas/constructor_actividades.php) y [calificar_pruebas.php](file:///c:/xampp/htdocs/sistema_escolar/php/vistas/calificar_pruebas.php) para que consuman dinámicamente los límites de la base de datos.

### 2. Plan de Notas Mínimas por Dimensión
*   **Propuesta**:
    *   Evolucionar la tabla `ares_clases_nota` agregando la columna `min_evaluaciones INTEGER DEFAULT 2`.
    *   Exponer en el panel **Académico** un gestor para editar las dimensiones (`Saber`, `Hacer`, `Ser`), sus pesos porcentuales y el número mínimo de calificaciones requeridas antes de cerrar el periodo.
    *   Inyectar alertas estéticas no obstructivas en la **Sábana de Calificaciones** y en la vista del docente si una asignatura no cumple el plan mínimo de notas (ej: *"Saber: 1 de 2 evaluaciones requeridas ⚠️"*).

### 3. Política Institucional de Recuperaciones (Retakes)
*   **Propuesta**:
    *   Inyectar una clave de configuración global `politica_recuperacion` en la tabla `configuracion_global` con tres posibles valores:
        1.  `reemplazo`: La nota de recuperación sustituye a la nota original si es mayor.
        2.  `promedio`: La nota final es el promedio simple entre la nota original y la recuperación.
        3.  `tope_aprobacion`: Reemplaza la nota, pero el resultado final para ese criterio o examen se limita al puntaje mínimo de aprobación (ej: máximo 3.0 o 6.0).
    *   Evolucionar las tablas de desglose y entregas para almacenar la nota de recuperación:
        *   `ares_calificaciones_desglose` => Agregar columna `nota_recuperacion REAL DEFAULT NULL`.
        *   `eval_respuestas` => Agregar columna `calificacion_recuperacion REAL DEFAULT NULL`.
    *   Agregar un campo de entrada para "Recuperación" en los modales de Focus Mode de Actividades y Calificador de Pruebas.
    *   Normalizar los motores de cálculo en backend (`api_actividades.php`, `api_pruebas.php` y `api_sabana.php`) para aplicar automáticamente la política de recuperación vigente al calcular promedios.

---

## 🛠️ Cambios Propuestos

### Componente 1: Base de Datos y Evolución de Esquema

#### [MODIFY] [db_integridad.php](file:///c:/xampp/htdocs/sistema_escolar/php/logica/db_integridad.php)
*   Agregar la columna `min_evaluaciones` a la tabla `ares_clases_nota`:
    ```sql
    ALTER TABLE ares_clases_nota ADD COLUMN min_evaluaciones INTEGER DEFAULT 2;
    ```
*   Agregar la columna `nota_recuperacion` a la tabla `ares_calificaciones_desglose`:
    ```sql
    ALTER TABLE ares_calificaciones_desglose ADD COLUMN nota_recuperacion REAL DEFAULT NULL;
    ```
*   Agregar la columna `calificacion_recuperacion` a la tabla `eval_respuestas`:
    ```sql
    ALTER TABLE eval_respuestas ADD COLUMN calificacion_recuperacion REAL DEFAULT NULL;
    ```
*   Sembrar la clave de configuración global de recuperaciones por defecto (`reemplazo`):
    ```sql
    INSERT INTO configuracion_global (clave, valor, tipo_dato, categoria, nombre_legible, descripcion)
    VALUES ('politica_recuperacion', 'reemplazo', 'string', 'academico', 'Política de Recuperación de Notas', 'Establece el método de cálculo institucional para notas de recuperación: Reemplazo Directo (reemplazo), Promedio Simple (promedio) o Límite de Aprobación (tope_aprobacion).');
    ```

---

### Componente 2: Panel de Configuración Administrativa (Hefesto)

#### [MODIFY] [configuracion.php](file:///c:/xampp/htdocs/sistema_escolar/php/vistas/configuracion.php)
*   En la sección `tab-global` (tab Académico), inyectar dos nuevos paneles visuales premium (Vitrina 06):
    1.  **Escala de Calificación Institucional**: Campos numéricos para `nota_minima`, `nota_maxima`, `nota_aprobacion`, `rango_superior_min`, `rango_alto_min` y `rango_basico_min` con un gráfico dinámico que simule los rangos.
    2.  **Plan de Notas Mínimas**: Listado de las dimensiones del Saber, Hacer y Ser con inputs para alterar sus ponderaciones (%) y definir la cantidad mínima de notas obligatorias.
    3.  **Política de Recuperación**: Dropdown premium con las opciones: *Reemplazo Directo*, *Promedio Simple* y *Límite de Aprobación*.

#### [NEW] [guardar_escala.php](file:///c:/xampp/htdocs/sistema_escolar/php/logica/guardar_escala.php)
*   Endpoint seguro que procese las solicitudes POST para actualizar los registros de `eval_config_escala`, la política de recuperación y los planes mínimos de notas, aplicando validaciones lógicas cruzadas.

#### [MODIFY] [configuracion.js](file:///c:/xampp/htdocs/sistema_escolar/js/modules/configuracion.js)
*   Desarrollar los validadores reactivos del lado del cliente en JS para asegurar que las escalas de notas no se crucen y enviar los datos de forma asíncrona mediante AJAX con latencia imperceptible.

---

### Componente 3: Calificación y Captura Modular (Ares & Rúbricas)

#### [MODIFY] [constructor_actividades.php](file:///c:/xampp/htdocs/sistema_escolar/php/vistas/constructor_actividades.php)
*   Cargar al inicio la escala institucional activa en PHP.
*   En el constructor de criterios (línea 522) y el calificador directo (línea 720), inyectar dinámicamente los valores `min="${escala.nota_minima}" max="${escala.nota_maxima}"`.
*   En el modal de **Focus Mode**, agregar un input opcional titulado `Recuperación` al lado del puntaje final o slider, habilitado si el estudiante ha sido calificado.

#### [MODIFY] [calificar_pruebas.php](file:///c:/xampp/htdocs/sistema_escolar/php/vistas/calificar_pruebas.php)
*   Añadir el campo numérico `Nota de Recuperación` en la sección lateral del cálculo de la prueba en el modal de calificación.

#### [MODIFY] [api_actividades.php](file:///c:/xampp/htdocs/sistema_escolar/php/logica/api_actividades.php)
*   Modificar los endpoints de listado y guardado para procesar y persistir la columna `nota_recuperacion` en `ares_calificaciones_desglose`.
*   Integrar la función de cálculo del promedio final aplicando la política institucional.

#### [MODIFY] [api_pruebas.php](file:///c:/xampp/htdocs/sistema_escolar/php/logica/api_pruebas.php)
*   Ajustar el guardado de calificaciones y recalificación para manejar el campo `calificacion_recuperacion` en `eval_respuestas` y computar la nota final del examen según la política activa.

---

### Componente 4: Visualización y Alertas (Sábana de Calificaciones)

#### [MODIFY] [api_sabana.php](file:///c:/xampp/htdocs/sistema_escolar/php/logica/api_sabana.php)
*   Extraer e inyectar en el JSON de respuesta los datos de configuración del plan de notas mínimas (`min_evaluaciones` por dimensión).
*   Asegurar que el cálculo consolidado final en la sábana aplique de forma estricta las recuperaciones.

#### [MODIFY] [sabana_calificaciones.php](file:///c:/xampp/htdocs/sistema_escolar/php/vistas/sabana_calificaciones.php)
*   Validar dinámicamente si el número de actividades registradas para cada dimensión del docente cumple con `min_evaluaciones`.
*   Si no se cumple, mostrar una alerta discreta y elegante en la cabecera de la columna de la dimensión (ej: `Saber (40%) ⚠️`).

---

## 🛡️ Plan de Verificación

1.  **Base de Datos**: Verificar que las migraciones en `db_integridad.php` se autoejecuten sin conflictos al recargar el dashboard.
2.  **Validación de Escalas**: Probar el cambio de escala de `1.0 a 5.0` a `1.0 a 10.0` y auditar que los sliders de Focus Mode y los validadores de la Sábana se adapten de inmediato.
3.  **Cálculo de Recuperaciones**: Realizar inserciones de prueba y verificar aritméticamente el resultado bajo las tres políticas (`reemplazo`, `promedio`, `tope_aprobacion`).
4.  **Auditoría de Pureza**: Correr el `antigravity_auditor.php` garantizando 0 violaciones del estándar Vitrina 06.

---

## 🚀 Próximo Paso Inmediato: Arquitectura de Boletines Inteligentes (Acordado con el Ingeniero Ricardo)

Una vez completadas y validadas todas las pruebas vigentes del sistema, el siguiente paso del proyecto será la implementación del nuevo **Módulo de Boletines Inteligentes Compactos**, diseñado bajo los siguientes consensos técnicos de alta eficiencia:

### 1. Visualización Jerárquica y Limpieza Cognitiva
*   **Soberanía del Área (Legal)**: El boletín destacará en tipografía grande e institucional la **Nota Numérica Consolidada del Área + su Concepto Cualitativo Oficial** en texto completo (ej: **CIENCIAS SOCIALES: 4.2 — ALTO**).
*   **Semaforización + Número en Asignaturas (Componentes)**: Las materias individuales (*Geografía, Historia, Democracia*) se listarán de forma subordinada mostrando su **nota numérica en formato discreto** acompañada de un **micro-indicador LED de color sutil (🟢/🟡/🔴)**, eliminando las palabras cualitativas repetitivas para evitar el caos visual:
    *   🟢 **Verde**: Rango Superior y Alto (ej. notas superiores a `4.0` en escala 1-5).
    *   🟡 **Amarillo**: Rango Básico/Aprobado (ej. notas mayores o iguales a la aprobación de `3.0` como un `3.2` hasta `3.99`).
    *   🔴 **Rojo**: Rango Bajo/Reprobado (ej. notas estrictamente inferiores a la aprobación `3.0`).

### 2. Flexibilidad del Formato mediante Switch de Configuración
*   **Switch de Control**: Se implementará un interruptor de 44px de estándar **Vitrina 06** en el panel de Configuración Global: *"Incluir Descriptores de Logros Académicos en Boletines Impresos"*.
*   **Comportamiento Adaptativo**:
    *   **Activo**: El boletín incluirá detalladamente los descriptores de logros del periodo para un análisis conceptual profundo.
    *   **Inactivo**: Se generará una tarjeta de calificaciones ejecutiva de una sola página, limpia, compacta y ultra-rápida de procesar y firmar, evitando la saturación de páginas extensas.
