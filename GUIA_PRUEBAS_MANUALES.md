# 🗺️ HOJA DE RUTA: PRUEBAS MANUALES (FÍSICO)

Siga este paso a paso detallado directamente en su servidor local para verificar manualmente el comportamiento de los diferentes roles y las políticas de recuperación de notas en el sistema.

---

## 🔑 CREDENCIALES DEL ECOSISTEMA DE PRUEBAS
*   **Administrador (Configura las reglas del colegio)**:
    *   **Usuario**: `admin`
    *   **Contraseña**: `1234`
*   **Docente (Crea actividades y califica alumnos)**:
    *   **Usuario**: `LJP`
    *   **Contraseña**: `1234`
*   **Estudiante (Visualiza sus notas en Aula Virtual)**:
    *   **Usuario**: `EMR1` (Mateo Rodríguez)
    *   **Contraseña**: `1234`

---

## 🚀 PASO A PASO DEL FLUJO ACADÉMICO EN EL NAVEGADOR

### Paso 1: Configurar Regulación Institucional (Rol Administrador)
1. Abra su navegador e ingrese a: 👉 [http://localhost/sistema_escolar/](http://localhost/sistema_escolar/)
2. Inicie sesión con el usuario `admin` y contraseña `1234`.
3. En el menú lateral izquierdo, vaya a **Administración** ➔ **Configuración** ➔ pestaña **Académico**.
4. Configure los parámetros base:
   *   **Nota Mínima**: `1.0`
   *   **Nota Máxima**: `5.0`
   *   **Nota Aprobación**: `3.0`
5. En el selector de **Política Institucional de Recuperaciones (Retakes)**, seleccione **LÍMITE DE APROBACIÓN** (Tope).
6. Presione **GUARDAR REGULACIÓN ACADÉMICA** (se procesará mediante AJAX fluido).

---

### Paso 2: Crear Actividad y Calificar (Rol Docente)
1. Cierre la sesión actual (o abra una ventana de incógnito).
2. Inicie sesión con el usuario docente `LJP` y contraseña `1234`.
3. En el menú lateral, diríjase a **Académico** ➔ **Gestor de Actividades** (Constructor de Actividades).
4. Seleccione el curso y cree una nueva actividad en la dimensión **SABER**.
5. En el listado de alumnos del curso, ubique al alumno **Mateo Rodríguez** (`EMR1`).
6. Haga clic en el botón de calificar para abrir el modal **Focus Mode**.
7. Asigne una calificación original reprobatoria de **`2.0`**.
8. En el campo de **Nota de Recuperación** que se expone automáticamente, ingrese una nota de **`4.5`**.
9. Presione **REGISTRAR NOTA**.

---

### Paso 3: Auditar la Sábana de Notas (Rol Docente / Coordinador)
1. Manteniéndose como docente o ingresando nuevamente como `admin`.
2. En el menú lateral, diríjase a **Académico** ➔ **Sábana de Notas**.
3. Cargue el consolidado del curso y periodo correspondiente.
4. Ubique la celda de la materia correspondiente al alumno **Mateo Rodríguez**:
   *   *Verificación:* La nota final calculada debe ser **`3.0`** (dado que la política activa es **Límite de Aprobación**, la nota de recuperación `4.5` se topa exactamente en la nota de aprobación del colegio, que es `3.0`).
5. Haga clic sobre la celda para desplegar el **Slide-over** lateral.
6. Valide que muestre con absoluta claridad:
   *   La nota original (`2.0`).
   *   La nota de recuperación (`4.5`).
   *   La fórmula aritmética del tope aplicada.

---

### Paso 4: Comprobar la Vista del Alumno (Rol Estudiante)
1. Cierre sesión e ingrese con el usuario de estudiante `EMR1` y contraseña `1234`.
2. Diríjase a su **Aula Virtual**.
3. Verifique que sus definitivos y tarjetas de desglose muestren la nota final de forma transparente.
