# 🧪 Ruta de Pruebas Modularizada y Depuración en Caliente (Ares)

Este checklist rige las pruebas físicas ordenadas por módulos independientes. Cada módulo debe completarse (Creación y Verificación) antes de avanzar al siguiente para detectar y corregir fallos de forma aislada.

## 📌 Módulo 0: Inicialización y Línea Cero
- [x] **Paso 0.1:** Ejecutar purga radical (`purga_radical_ares.php`) en servidor local.
- [ ] **Paso 0.2:** Verificar acceso de administrador en navegador (`admin` / `1234`).

---

## 📌 Módulo I: Estructura Curricular (Áreas y Especialidades)
- [x] **Paso I.1 [Creación]:** Entrar a *Áreas* y registrar un área (ej. *Ciencias Naturales*).
- [x] **Paso I.2 [Creación]:** Entrar a *Especialidades* y registrar una materia (ej. *Biología*) asociada al área anterior.
- [x] **Paso I.3 [Verificación]:** Validar que los anuncios premium de "Sin datos" desaparezcan y las materias expongan su badge de área responsable sin fallos de PHP.

---

## 📌 Módulo II: Gestión de Docentes (Talento Humano)
- [x] **Paso II.1 [Creación]:** Ir a *Personal* y crear una cuenta de docente (ej. `docente_biologia` / clave `1234`) asociándole la especialidad de *Biología*.
- [x] **Paso II.2 [Verificación]:** Validar su registro en la tabla general, cerrar sesión e iniciar sesión como el docente creado para comprobar que el login responda correctamente.

---

## 📌 Módulo III: Estructura de Cursos y Tutoría
- [x] **Paso III.1 [Creación]:** Iniciar sesión de nuevo como `admin` e ir a *Cursos* para registrar un nuevo curso (ej. *Noveno A*), asignando al docente de biología como tutor.
- [x] **Paso III.2 [Verificación]:** Verificar en la lista general de cursos que el tutor aparezca correctamente y el docente tenga asignada la insignia de `DIRECTOR_DE_GRUPO`.

---

## 📌 Módulo IV: Distribución de Carga Académica
- [x] **Paso IV.1 [Creación]:** Ir a *Carga Académica*, seleccionar el curso *Noveno A* y asignar la materia *Biología* al docente creado.
- [x] **Paso IV.2 [Verificación]:** Confirmar la correcta vinculación en la consola de carga y validar que la acción de eliminación (borrar asignación) esté plenamente disponible.

---

## 📌 Módulo V: Admisión y Matrícula de Alumnos
- [x] **Paso V.1 [Creación]:** Ir a *Matriculados ➔ Nueva Matrícula*, registrar un alumno de prueba y matricularlo en el curso *Noveno A*.
- [x] **Paso V.2 [Verificación]:** Comprobar que el contador de la tarjeta métrica de la comunidad estudiantil se incremente a **1 Alumno** y que el expediente exponga su promedio en cero (`0.0`) y estado `NUEVO` sin errores.

---

## 📌 Módulo VI: Calificaciones y Calificador Híbrido (Docente)
- [ ] **Paso VI.1 [Creación]:** Entrar como el docente, crear una actividad en la dimensión **SABER** y calificar al estudiante con nota reprobatoria (ej. **2.0**).
- [ ] **Paso VI.2 [Creación]:** En el modal del **Focus Mode**, registrar la nota de recuperación (ej. **4.5**), seleccionar el método (Físico o Digital) e ingresar la justificación obligatoria.
- [ ] **Paso VI.3 [Verificación]:** Guardar la calificación y auditar la base de datos o consola de desarrollador del navegador para asegurar que no se produzcan errores AJAX y que persistan las columnas de recuperación.

---

## 📌 Módulo VII: Sábana de Notas y Políticas Institucionales
- [ ] **Paso VII.1 [Configuración]:** Como `admin`, ir a *Configuración ➔ Académico* y establecer la política de retakes en **LÍMITE DE APROBACIÓN** (Tope) con nota de aprobación en **3.0**.
- [ ] **Paso VII.2 [Verificación]:** Ir a la *Sábana de Notas* del curso *Noveno A*. La nota definitiva calculada del alumno debe toparse exactamente en **3.0** (a pesar de la recuperación de **4.5**).
- [ ] **Paso VII.3 [Verificación]:** Entrar como Estudiante y verificar que sus definitivos en el Aula Virtual coincidan con el tope establecido.
