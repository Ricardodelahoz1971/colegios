# 🏛️ PLAN ARQUITECTÓNICO: INYECCIÓN DIRECTIVAS MEN EN ARES v2.0

Este documento rige el protocolo de integración de la taxonomía del Ministerio de Educación Nacional (MEN) en el motor de evaluación institucional ARES. 
Se ejecutará paso a paso según las directrices del Ingeniero Ricardo.

---

## FASE 1: CAPA DE DATOS (Expansión SQLite)
**Estado:** `[ ] PENDIENTE`
**Objetivo:** Adaptar la bóveda principal de reactivos sin alterar ni corromper los exámenes existentes.

*   **Acción 1.1:** Desarrollar y ejecutar un script de parcheo (ej. `patch_men_ares.php`) que realice `ALTER TABLE` sobre la tabla `eval_preguntas`.
*   **Acción 1.2:** Inyectar la columna `competencia_men` (TEXT) - Para almacenar el estándar de competencia.
*   **Acción 1.3:** Inyectar la columna `dba_men` (TEXT) - Para almacenar los Derechos Básicos de Aprendizaje (DBA).

---

## FASE 2: CAPA LÓGICA (API REST - `api_preguntas.php`)
**Estado:** `[ ] PENDIENTE`
**Objetivo:** Permitir la comunicación bidireccional entre el servidor y el cliente respecto a las nuevas directivas.

*   **Acción 2.1:** Modificar la instrucción `INSERT` en la creación de reactivos para capturar y blindar `competencia_men` y `dba_men` mediante PDO.
*   **Acción 2.2:** Modificar la instrucción `UPDATE` para la edición de reactivos propios.
*   **Acción 2.3:** Auditar y actualizar la lógica de **Forking (Adaptación)**. Asegurar que al adaptar un reactivo del "Banco Universal", este herede automáticamente la competencia MEN original.

---

## FASE 3: CAPA VISUAL Y ESTRUCTURAL (Interfaz Vitrina 06)
**Estado:** `[ ] PENDIENTE`
**Objetivo:** Modificar el frontend de ARES para exponer la configuración al docente sin abrumarlo, manteniendo el estándar estético de altura 44px y bordes curvos.

*   **Acción 3.1:** Actualizar el modal de **Creación/Edición de Reactivos** (`ares_reactivos.js` o vista PHP equivalente) para agregar un `<fieldset>` titulado "Alineación Curricular MEN".
*   **Acción 3.2:** Añadir los controles de entrada o selección (`<select>` o `<input>`) con clases `.form-control.border-secondary` para capturar la Competencia y el DBA.
*   **Acción 3.3:** Modificar la renderización de las "Tarjetas" en el **Banco Universal** para mostrar un *badge* sutil que indique la competencia MEN alineada a esa pregunta.
*   **Acción 3.4 (Opcional):** Inyectar un filtro en la UI del Banco Universal que permita buscar preguntas específicas por Competencia MEN.

---
*Documento preparado a la espera de la orden de ejecución.*
