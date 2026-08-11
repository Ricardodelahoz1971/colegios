# 🧪 GUÍA DE PRUEBAS MANUALES: SISTEMA DE RECUPERACIONES ARES

Esta guía detalla el checklist interactivo para verificar el sistema híbrido de evidencias en recuperaciones y el estado de los errores corregidos.

---

## 🛠️ Errores Críticos Superados (Cerrados y Certificados)

- [x] ~~**Error 1: Nombres de Estudiantes en Cero (`0`)**~~
  * **Problema:** Concatenación de cadenas con tuberías SQLite (`||`) en MariaDB evaluaba a boleano `0`.
  * **Corrección:** Migrado a la función nativa `CONCAT(e.nombre, ' ', COALESCE(e.apellido, ''))` en [api_pruebas.php](file:///c:/xampp/htdocs/sistema_escolar/php/logica/api_pruebas.php).
- [x] ~~**Error 2: Falla de Carga de Evidencias Digitales ("Parámetros obligatorios")**~~
  * **Problema:** El endpoint `obtener_actividad_detalles` en [api_actividades.php](file:///c:/xampp/htdocs/sistema_escolar/php/logica/api_actividades.php) no retornaba la columna `especialidad_id`, dejando el parámetro como `undefined` en el cliente.
  * **Corrección:** Incorporada la columna `especialidad_id` en la consulta del backend y blindada la API.

---

## 🧪 Checklist de Pruebas Manuales (Pendientes de Validación)

### 📌 Fase A: Inicialización y Comportamiento del Calificador
- [ ] **A.1. Comprobación de Nombres Legibles**
  * Entrar al Centro de Calificaciones ([calificar_pruebas.php](file:///c:/xampp/htdocs/sistema_escolar/php/vistas/calificar_pruebas.php)) y comprobar que el listado de alumnos de exámenes ya no muestre ceros (`0`).
- [ ] **A.2. Visibilidad Condicional (Focus Mode)**
  * Abrir el Focus Mode de un alumno con nota original reprobatoria (`< 3.0`) y verificar que se despliegue el panel de recuperación.
  * Abrir el Focus Mode de un alumno con nota aprobatoria (`>= 3.0`) y verificar que el panel de recuperación permanezca **oculto**.

### 📌 Fase B: Simulación de Evidencia Física / Presencial
- [ ] **B.1. Flujo de Taller/Examen Físico**
  * Elegir método `Examen Físico / Sustentación` o `Trabajo / Taller Escrito`.
  * Confirmar que el selector de soporte digital se oculta y el campo de justificación manual permite la escritura libre.
  * Colocar nota, ingresar detalles (mínimo 5 caracteres) y guardar.

### 📌 Fase C: Simulación de Evidencia Digital
- [ ] **C.1. Flujo de Aula Virtual / Examen Online**
  * Seleccionar método `Actividad Aula Virtual` o `Examen en Línea`.
  * Validar la carga asíncrona de las entregas sin errores y que el campo de justificación manual se bloquee a solo lectura (`readonly`).
  * Seleccionar un recurso entregado de la lista y verificar que el campo de justificación se auto-rellene con la información de la entrega seleccionada.
  * Publicar la nota.

### 📌 Fase D: Auditoría Técnica final
- [ ] **D.1. Persistencia y Trazabilidad**
  * Confirmar en la base de datos la correcta escritura de `metodo_recuperacion`, `justificacion_recuperacion` y `soporte_recuperacion_id`.
  * Abrir [elite_trace.log](file:///c:/xampp/htdocs/sistema_escolar/php/logs/elite_trace.log) y comprobar la traza de la notificación dirigida al acudiente.
