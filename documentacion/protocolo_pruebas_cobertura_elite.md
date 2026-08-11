# 🏛️ PROTOCOLO DE PRUEBAS CRUZADAS CONCURRENTES (V3)
**Auditoría de Cobertura Curricular ARES & PERSEUS**  
*Estándar de Control de Calidad Élite - Vitrina 06*

---

## 👥 1. ACTORES Y CREDENCIALES DE PRUEBA
Para ejecutar esta prueba concurrente de forma segura y evitar conflictos de sesión en el navegador, se deben utilizar ventanas normales y ventanas de incógnito separadas.

| Rol | Usuario | Contraseña | Objetivo de Prueba |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin` | `1234` | Supervisar métricas globales, latencias y consistencia de datos de múltiples docentes. |
| **Coordinador** | `CMG` | `1234` | Auditar la cobertura de carga académica y avalar la alineación del MEN. |
| **Docente (Matemáticas)** | `LJP` | `1234` | Crear y alinear reactivos con DBAs reales en el Centro de Inteligencia Ares. |

---

## 📝 2. ESCENARIO DE PRUEBA 1: ALINEACIÓN DINÁMICA ARES (PORTABILIDAD)
Este escenario comprueba que la **Falencia 1 (JS Hardcoding)** está solucionada y que el editor es 100% dinámico y portable en cualquier base de datos.

### Pasos de Ejecución (Docente o Coordinador):
1.  Iniciar sesión con el usuario `LJP` o `CMG`.
2.  Navegar a **Cursos** ➔ **Centro de Inteligencia Ares** (Editor de Preguntas).
3.  Hacer clic en el botón azul de la esquina superior izquierda **`[+]`** (Crear Reactivo).
4.  En el selector de **Materia / Especialidad**, elegir **Matemáticas** (ID de base de datos 1, demostrando dinamismo).
5.  **Verificación Visual:** El badge inferior debe cargarse de forma instantánea mostrando:  
    `[i] Área MEN: Matemáticas & Ciencias Exactas` (Cargado dinámicamente desde el DOM).
6.  En el selector de **Grado**, elegir **Grado 1º**.
7.  En el selector de **Aprendizaje / DBA**, elegir cualquier DBA oficial del listado.
8.  En el selector de **Evidencia**, elegir la primera evidencia observable.
9.  **Resultado Exitoso:** Todos los selectores interactivos deben medir **44px** de alto con radios de **12px** y transiciones sin retardo.
10. Llenar los campos de *Título Técnico*, elegir *Tipo de Pregunta: Abierta*, agregar un enunciado corto en Quill y hacer clic en **GUARDAR EN BÓVEDA**. Confirmar modal de SweetAlert2.

---

## 📝 3. ESCENARIO DE PRUEBA 2: AUDITORÍA CONCURRENTE PERSEUS (VELOCIDAD & NORMALIZACIÓN)
Este escenario comprueba que la **Falencia 2 (Regex de Grados)** y la **Falencia 3 (Rendimiento SQL)** están solucionadas y operan a velocidad de sub-milisegundo de forma concurrente.

### Pasos de Ejecución (Simultáneo - Admin y Coordinador):
1.  Iniciar sesión con `admin` en un navegador e iniciar sesión con `CMG` en otro.
2.  Ambos actores deben ir a la pantalla de **Inicio** (Dashboard).
3.  Desplazarse hasta el widget **SISTEMA PERSEUS** (el semáforo visual de cobertura).
4.  En el selector de carga académica de PERSEUS, elegir el curso correspondiente al test de Matemáticas (ej: **Matemáticas - 1A**).
5.  **Verificación de Resultados Simultáneos:**
    *   **Velocidad de Carga:** El widget debe responder al instante (**< 1ms**) debido a los nuevos índices físicos creados en SQLite.
    *   **Normalización:** El helper `el_normalize_grade` debe traducir el curso "1A" a "Grado 1º" y cruzar exitosamente los DBAs sin arrojar errores de base de datos ni quedar en bucle de carga.
    *   **Anillo de Cobertura:** El semáforo debe renderizar el porcentaje de cobertura real calculando los DBA distintos de esa especialidad en ese curso.
    *   **Consistencia de Datos:** Tanto el Administrador como el Coordinador deben ver exactamente las mismas métricas de cobertura y estados visuales (Verde, Amarillo, Rojo).

---

## 🛡️ 4. CERTIFICACIÓN DE CALIDAD
*   **Estatus del Auditor:** Aprobado al 100% con **0 Violaciones** en `ELITE_AUDIT_REPORT.md`.
*   **Seguridad:** Blindaje contra vulnerabilidades de escalación de privilegios (IDOR) implementado en `api_perseus.php`.
