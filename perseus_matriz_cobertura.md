# 🛡️ SISTEMA PERSEUS: MATRIZ DE COBERTURA CURRICULAR (SEMÁFORO)

Este documento detalla la arquitectura de implementación del **Módulo de Cobertura (Paso 3)**. El objetivo de PERSEUS es actuar como un "auditor académico en tiempo real", informando al docente y a la coordinación qué porcentaje de las directivas del MEN (DBA y Evidencias) se han evaluado realmente durante el periodo.

---

## 1. FUNDAMENTO LÓGICO Y ESTRUCTURAL
Antes de que PERSEUS pueda emitir un diagnóstico, el sistema ARES (el pantalón) debe estar firmemente atado a las directivas (DBA y Evidencias) en cada pregunta. Una vez garantizado esto, PERSEUS actúa como una capa de inteligencia sobre la tabla `eval_pruebas` y `eval_pruebas_items`.

### ¿Cómo funciona el Semáforo?
PERSEUS cruza tres dimensiones de datos:
1.  **Catálogo MEN:** El total de DBA exigidos para un grado y materia específicos (Ej: 10 DBA en Matemáticas 5°).
2.  **Producción ARES:** Los reactivos que el docente ha incluido en sus pruebas *Publicadas* o *Cerradas*.
3.  **Matriz de Cálculo:** Compara los DBA evaluados vs. los DBA totales.

---

## 2. PLAN DE IMPLEMENTACIÓN PASO A PASO

### PASO 1: Inyección del Motor SQL (La Lupa de Perseus)
Para no saturar PHP con miles de arrays, el cálculo pesado debe hacerlo el motor de base de datos.
*   **Crearemos una vista o consulta maestra (`view_perseus_cobertura`)** que agrupe y cuente los DBA distintos (`COUNT(DISTINCT p.dba_id)`) que un docente ha evaluado en una materia y grado determinados.
*   Se contrastará contra el total de DBA de esa materia en la tabla `men_dba` para calcular el porcentaje de cobertura.

### PASO 2: Construcción del Endpoint REST (`api_perseus.php`)
Desarrollaremos un microservicio que alimentará el dashboard del docente.
*   **Entrada:** `docente_id`, `materia_id`, `grado_id`, `periodo_id`.
*   **Salida (JSON):** 
    ```json
    {
      "materia": "Matemáticas",
      "grado": "5°",
      "dba_totales": 10,
      "dba_evaluados": 4,
      "cobertura_porcentaje": 40,
      "estado_semaforo": "rojo",
      "dba_faltantes": [5, 6, 7, 8, 9, 10]
    }
    ```

### PASO 3: UI Dashboard Institucional (El Semáforo Vitrina 06)
Construiremos una tarjeta analítica (Widget) en el panel de ARES o en el Dashboard principal.
*   **Diseño:** Un componente visual de alto impacto.
*   **Rojo (0% - 49%):** Cobertura deficiente. Muestra alerta y sugiere al docente buscar reactivos en el Banco Universal para los DBA faltantes.
*   **Amarillo (50% - 79%):** Cobertura aceptable, pero incompleta.
*   **Verde (80% - 100%):** Excelencia académica. Desbloquea el **"Bonus Institucional"** (reconocimiento visual en el perfil del docente, insignias, o métricas positivas para la coordinación).

### PASO 4: Integración del Acelerador (Sugerencias Inteligentes)
Cuando PERSEUS detecte un DBA no evaluado, no solo advertirá, sino que ofrecerá la solución:
*   Inyectaremos un botón en el semáforo que diga: *"Buscar reactivos para el DBA 5"*.
*   Al hacer clic, ARES abrirá el **Banco Universal**, filtrará automáticamente por el DBA faltante, y permitirá al docente hacer un *Fork* (Adaptación) en 2 clics para cubrir la métrica.

---

## 3. BENEFICIO INSTITUCIONAL (EL BONUS)
Al implementar PERSEUS, la institución se blinda ante cualquier auditoría externa (Secretaría de Educación/MEN). La coordinación académica podrá exportar con un clic el **"Reporte de Saberes"**, demostrando matemáticamente que el currículo se está ejecutando y evaluando con precisión milimétrica.
