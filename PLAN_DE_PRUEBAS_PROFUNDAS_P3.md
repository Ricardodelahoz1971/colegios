# 📋 CHECKLIST MAESTRO DE PRUEBAS FUNCIONALES (MÓDULO POR MÓDULO)
**Sistema Escolar Élite** | Estado: *En Ejecución de Pruebas*

---

## 🔐 MÓDULO 1: ACCESO Y SEGURIDAD (`index.php`)
- [x] **1.1 Carga Limpia**: Abre `http://localhost/sistema_escolar/` y renderiza el formulario institucional sin errores de consola. ✅
- [x] **1.2 Rechazo de Clave Errónea**: Al escribir usuario o clave incorrectos, deniega el acceso y muestra la alerta SweetAlert con paleta institucional. ✅
- [ ] **1.3 Login Exitoso Admin**: Entrar con `admin` / `1234` y verificar que redirige al Dashboard institucional.
- [ ] **1.4 Blindaje de Sesión**: Copiar la URL del Dashboard, cerrar sesión, pegar la URL en el navegador y verificar que expulsa al Login.

---

## ⚙️ MÓDULO 2: CONFIGURACIÓN INSTITUCIONAL (`configuracion.php`)
- [ ] **2.1 Identidad del Colegio**: Cambiar el nombre del colegio, lema o colores institucionales y verificar que se reflejen de inmediato.
- [ ] **2.2 Escala de Notas**: Configurar escala (Mín: 1.0, Máx: 5.0, Aprobatoria: 3.0) y guardar con respuesta AJAX exitosa.
- [ ] **2.3 Pesos de Periodos**: Configurar porcentajes de los 4 periodos (25%, 25%, 25%, 25% = 100%) y verificar que valide que no sume más ni menos de 100%.
- [ ] **2.4 Jornadas Escolares**: Configurar horas de inicio y duración de bloques para Mañana y Tarde.

---

## 👥 MÓDULO 3: GESTIÓN DE PERSONAL Y ROLES (`personal.php` / `roles.php`)
- [ ] **3.1 Crear Docente**: Registrar un nuevo docente con nombre, documento, correo y jornada laboral asignada.
- [ ] **3.2 Edición de Datos**: Modificar teléfono o datos de un docente existente y verificar que persista en la base de datos.
- [ ] **3.3 Cambio de Estado**: Inactivar un usuario y comprobar que ya no pueda iniciar sesión.
- [ ] **3.4 Permisos por Rol**: Verificar que un usuario con rol Docente no vea los menús de administración ni configuración.

---

## 📚 MÓDULO 4: MALLA CURRICULAR (`areas.php`, `especialidades.php`, `cursos.php`)
- [ ] **4.1 Crear Área**: Crear un área fundamental (ej. Matemáticas, Humanidades) y verificar que aparezca en la lista.
- [ ] **4.2 Crear Asignatura/Materia**: Crear una materia vinculada al área con su intensidad horaria semanal.
- [ ] **4.3 Crear Curso / Grado**: Crear un curso (ej. 901) con su jornada asignada (Mañana/Tarde) y director de grupo (Tutor).

---

## 📝 MÓDULO 5: MATRÍCULAS Y ADMISIONES (`matricula.php` / `matriculados.php`)
- [ ] **5.1 Cascada Geográfica**: Seleccionar País ➔ Departamento ➔ Ciudad y comprobar que los desplegables se filtren en tiempo real.
- [ ] **5.2 Registro de Alumno**: Matricular un alumno con datos personales, documento, acudiente y SISBÉN.
- [ ] **5.3 Búsqueda de Matriculados**: En `matriculados.php`, buscar al alumno por nombre o documento y verificar que cargue su ficha.
- [ ] **5.4 Generar Ficha / Formato**: Exportar o imprimir la ficha de matrícula y verificar que los datos salgan ordenados.

---

## 🧩 MÓDULO 6: CARGA ACADÉMICA - ZULU (`zulu.php`)
- [ ] **6.1 Plan de Estudios**: Asignar las materias correspondientes a cada curso según la malla curricular.
- [ ] **6.2 Asignación Docente**: Asignar un docente a una materia de un curso (Drag & Drop o selector manual).
- [ ] **6.3 Alerta de Incompatibilidad de Jornada**: Intentar asignar un docente de jornada Mañana a un curso de jornada Tarde y verificar que el sistema lance la alerta de confirmación.
- [ ] **6.4 Balance de Horas**: Verificar que el contador de horas semanales del docente se actualice automáticamente.

---

## ⏰ MÓDULO 7: HORARIOS INTELIGENTES - KHRONOS (`khronos.php`)
- [ ] **7.1 Parámetros de Horario**: Configurar bloques por día, recesos (recreos) y hora de inicio.
- [ ] **7.2 Generación Automática**: Presionar el botón de auto-generación de horarios para un curso.
- [ ] **7.3 Regla Anti-Fatiga**: Verificar que ninguna materia tenga más de 2 horas continuas en el mismo día.
- [ ] **7.4 Respeto de Recesos**: Verificar que no se programen clases durante los bloques de descanso.
- [ ] **7.5 Cero Choques de Docente**: Verificar que un mismo docente nunca quede asignado en dos cursos a la misma hora.

---

## 📊 MÓDULO 8: CALIFICACIONES Y ARES (`constructor_actividades.php` / `sabana_calificaciones.php`)
- [ ] **8.1 Crear Actividad Evaluativa**: Crear una tarea/taller bajo la dimensión Saber, Hacer o Ser con su porcentaje.
- [ ] **8.2 Calificar en Focus Mode**: Abrir el calificador rápido, ingresar notas a los alumnos y guardar con AJAX fluido.
- [ ] **8.3 Límite de Notas**: Intentar ingresar una nota superior al máximo (ej. 6.0) y verificar que el sistema lo impida.
- [ ] **8.4 Recuperaciones (Retakes)**: Asignar nota de recuperación a un alumno reprobado y verificar que aplique la regla configurada (Tope 3.0 o Reemplazo).
- [ ] **8.5 Sábana Consolidada**: Abrir la sábana de notas del curso y corroborar que el promedio final coincida con el cálculo matemático exacto.

---

## 🧪 MÓDULO 9: BANCO DE REACTIVOS Y EXÁMENES ONLINE
- [ ] **9.1 Crear Pregunta / Reactivo (`editor_preguntas.php`)**: Crear una pregunta con opciones múltiples y formato enriquecido/imagen.
- [ ] **9.2 Armar Examen (`constructor_pruebas.php`)**: Seleccionar preguntas del banco, definir tiempo límite y publicar el examen.
- [ ] **9.3 Presentar Examen (Estudiante)**: Entrar con usuario de estudiante, responder el examen con cronómetro activo y enviar.
- [ ] **9.4 Calificación Automática**: Comprobar que el sistema calcule el puntaje al instante y lo envíe a la sábana de notas.

---

## 📅 MÓDULO 10: ASISTENCIA Y AGENDA ESCOLAR
- [ ] **10.1 Toma de Asistencia Diaria (`asistencia.php`)**: Marcar presentes, ausentes y excusas para un curso en la fecha actual.
- [ ] **10.2 Persistencia**: Recargar la página y verificar que las marcas de asistencia queden guardadas.
- [ ] **10.3 Mensajería / Circulares (`mensajeria.php`)**: Enviar un comunicado interno y verificar que llegue a la bandeja del destinatario.
