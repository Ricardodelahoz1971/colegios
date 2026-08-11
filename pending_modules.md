# Módulos pendientes de intervención

**Resumen de los archivos que aún requieren extracción de JavaScript inline y/o corrección de funcionalidades.**

| Módulo / Archivo | Tipo de problema detectado | Acción sugerida (prioridad) | Estado |
|------------------|----------------------------|----------------------------|:---:|
| `php/vistas/inicio.php` | **Intervención completada** – JS externo en `js/inicio.js`. | ✅ | ✅ |
| `php/vistas/zulu.php` | **Intervención completada** – JS externo en `js/zulu.js`. | ✅ | ✅ |
| `php/vistas/sabana_calificaciones.php` | **Intervención completada** – JS externo en `js/sabana_calificaciones.js`. | ✅ | ✅ |
| `php/vistas/reporte_listas.php` | **Intervención completada** – JS externo en `js/reporte_listas.js`. | ✅ | ✅ |
| `php/vistas/pruebas_formales.php` | **Intervención completada** – JS externo en `js/pruebas_formales.js`. | ✅ | ✅ |
| `php/vistas/presentar_examen.php` | **Intervención completada** – JS externo en `js/presentar_examen.js`. | ✅ | ✅ |
| `php/vistas/mensajeria.php` | **Intervención completada** – JS externo en `js/mensajeria.js`. | ✅ | ✅ |
| `php/vistas/matriculados.php` | **Intervención completada** – JS externo en `js/matriculados.js`. | ✅ | ✅ |
| `php/vistas/matricula.php` | **Intervención completada** – JS externo en `js/matricula.js`. | ✅ | ✅ |
| `php/vistas/khronos.php` | **Intervención completada** – JS externo en `js/khronos.js`. | ✅ | ✅ |
| `php/vistas/formatos_matricula.php` | **Intervención completada** – JS externo en `js/formatos_matricula.js`. | ✅ | ✅ |
| `php/vistas/estudiante_examenes.php` | **Intervención completada** – JS externo en `js/estudiante_examenes.js`. | ✅ | ✅ |
| `php/vistas/cursos.php` | **Intervención completada** – JS externo en `js/cursos.js`. | ✅ | ✅ |
| `php/vistas/constructor_pruebas.php` | **Intervención completada** – JS externo en `js/constructor_pruebas.js`. | ✅ | ✅ |
| `php/vistas/constructor_actividades.php` | **Intervención completada** – JS externo en `js/constructor_actividades.js`. | ✅ | ✅ |
| `php/vistas/carga.php` | **Intervención completada** – JS externo en `js/carga.js`. | ✅ | ✅ |
| `php/vistas/calificar_pruebas.php` | **Intervención completada** – JS externo en `js/calificar_pruebas.js`. | ✅ | ✅ |
| `php/vistas/calendario.php` | **Intervención completada** – JS externo en `js/calendario.js`. | ✅ | ✅ |
| `php/vistas/aula_virtual_gestion.php` | **Intervención completada** – JS externo en `js/aula_virtual_gestion.js`. | ✅ | ✅ |
| `php/vistas/aula_virtual_estudiante.php` | **Intervención completada** – JS externo en `js/aula_virtual_estudiante.js`. | ✅ | ✅ |
| `php/vistas/asistencia.php` | **Intervención completada** – JS externo en `js/asistencia.js`. | ✅ | ✅ |
| `php/vistas/aplicacion_pruebas.php` | **Intervención completada** – JS externo en `js/aplicacion_pruebas.js`. | ✅ | ✅ |
| `php/vistas/agenda.php` | **Intervención completada** – JS externo en `js/agenda.js`. | ✅ | ✅ |
| `php/security.php` | **Intervención completada** – JS externo en `js/security.js`. | ✅ | ✅ |
| `php/rendered.html` | **Intervención completada** – JS externo en `js/rendered.js`. | ✅ | ✅ |
| `php/dashboard.php` | **Intervención completada** – JS externo en `js/dashboard.js`. | ✅ | ✅ |

### Prioridades recomendadas
1. **Módulos críticos de flujo académico** – `calificar_pruebas.php`, `presentar_examen.php`, `pruebas_formales.php`. (Completado)
2. **Módulos de gestión de usuarios y mensajería** – `mensajeria.php`, `asistencia.php`. (Completado)
3. **Módulos de Admisiones y Matrículas** – `matricula.php`, `matriculados.php`, `formatos_matricula.php`. (Completado)
4. **Módulos de agenda y calendario** – `agenda.php`, `calendario.php`. (Completado)
5. **El resto de vistas con scripts inline** pueden programarse en bloques posteriores siguiendo el mismo patrón. (Completado)

> **Nota**: Todos los cambios deben respetar los manifiestos (`STYLE_MANIFESTO.md`, `FRONTEND_MANIFESTO.md`, `BACKEND_MANIFESTO.md`) y la regla de **44 px** de altura para controles interactivos, BEM‑Elite para clases y uso exclusivo de variables CSS (`var(--el-*)`).