# 🗺️ Plan de Ataque: Refactorización y Extracción de JS Inline

Este documento detalla el plan de intervención modular para limpiar el JavaScript inline en las vistas del sistema escolar, garantizando el cumplimiento de los estándares de arquitectura y diseño **Vitrina 06**.

---

## 📊 Matriz de Criticidad y Prioridades (Actualizada)

| Módulo Funcional | Gravedad (%) | Viabilidad (%) | Daños Colaterales (%) | Diagnóstico Clínico |
| :--- | :---: | :---: | :---: | :--- |
| **1. Evaluación y Calificaciones** | **40%** | **95%** | **30%** | **Prioridad 1.** El sembrado de notas está activo y responde correctamente. La viabilidad aumentó y los daños colaterales se redujeron al mínimo. |
| **2. Admisiones y Matrículas** | **75%** | **85%** | **50%** | Alto impacto comercial. JS inline pesado en generación de formatos, pero modularmente aislado del flujo diario. |
| **3. Aula Virtual** | **70%** | **80%** | **40%** | Afecta directamente la experiencia del estudiante. Se concentra principalmente en visualización de datos y exámenes. |
| **4. Core, Seguridad y Vistas Base** | **40%** | **60%** | **90%** | Gravedad técnica baja pero **riesgo colateral extremo**. Modificar `rendered.html` o `security.php` puede quebrar todo el sistema. |
| **5. Planificación y Carga Académica** | **60%** | **90%** | **30%** | Estructuras administrativas aisladas sin cálculos matemáticos complejos en vivo. |
| **6. Calendario y Agenda (Khronos)** | **50%** | **90%** | **20%** | Muy seguro de refactorizar. Modificaciones estrictamente locales de interfaz interactiva. |
| **7. Comunicación y Asistencia** | **45%** | **85%** | **30%** | Lógica de manipulación del DOM simple, sin dependencias complejas. |

---

## 🛡️ Estándar de Implementación Obligatorio (Vitrina 06)
*   **Métrica 44px:** Altura obligatoria para todo elemento interactivo.
*   **Radio de Prestigio:** Bordes de `12px` para controles y `24px` para paneles maestros.
*   **Cero HEX:** Uso exclusivo de variables CSS `var(--el-*)`.
*   **Carga del Script:** Mover el JS a su respectivo archivo externo en `js/` y cargarlo usando `<script src="../js/archivo.js" defer></script>`.
*   **BEM:** Clases nombradas estrictamente bajo metodología BEM-Elite.

---

## 📦 Módulos y Checklist de Intervención

### 1. Módulo de Evaluación y Calificaciones (7 archivos)
*   [x] **sabana_calificaciones.php**  
    *   *Archivo origen:* `php/vistas/sabana_calificaciones.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 273) a archivo externo. (Completado)  
    *   *Destino:* `js/sabana_calificaciones.js`
*   [x] **pruebas_formales.php**  
    *   *Archivo origen:* `php/vistas/pruebas_formales.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 214).  
    *   *Destino:* `js/pruebas_formales.js`
*   [x] **presentar_examen.php**  
    *   *Archivo origen:* `php/vistas/presentar_examen.php`  
    *   *Acción:* Consolidar los dos bloques de script inline (líneas ≈ 129 y ≈ 292).  
    *   *Destino:* `js/presentar_examen.js`
*   [x] **calificar_pruebas.php**  
    *   *Archivo origen:* `php/vistas/calificar_pruebas.php`  
    *   *Acción:* Consolidar los dos bloques de script inline (líneas ≈ 21 y ≈ 269).  
    *   *Destino:* `js/calificar_pruebas.js`
*   [x] **constructor_pruebas.php**  
    *   *Archivo origen:* `php/vistas/constructor_pruebas.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 177).  
    *   *Destino:* `js/constructor_pruebas.js`
*   [x] **constructor_actividades.php**  
    *   *Archivo origen:* `php/vistas/constructor_actividades.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 233).  
    *   *Destino:* `js/constructor_actividades.js`
*   [x] **aplicacion_pruebas.php**  
    *   *Archivo origen:* `php/vistas/aplicacion_pruebas.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 183).  
    *   *Destino:* `js/aplicacion_pruebas.js`

### 2. Módulo de Admisiones y Matrículas (3 archivos)
*   [x] **matricula.php**  
    *   *Archivo origen:* `php/vistas/matricula.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 336).  
    *   *Destino:* `js/matricula.js`
*   [x] **matriculados.php**  
    *   *Archivo origen:* `php/vistas/matriculados.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 216).  
    *   *Destino:* `js/matriculados.js`
*   [x] **formatos_matricula.php**  
    *   *Archivo origen:* `php/vistas/formatos_matricula.php`  
    *   *Acción:* Consolidar los tres bloques de script inline (líneas ≈ 706, 714, 997).  
    *   *Destino:* `js/formatos_matricula.js`

### 3. Módulo de Aula Virtual (3 archivos)
*   [x] **aula_virtual_gestion.php**  
    *   *Archivo origen:* `php/vistas/aula_virtual_gestion.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 207).  
    *   *Destino:* `js/aula_virtual_gestion.js`
*   [x] **aula_virtual_estudiante.php**  
    *   *Archivo origen:* `php/vistas/aula_virtual_estudiante.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 79).  
    *   *Destino:* `js/aula_virtual_estudiante.js`
*   [x] **estudiante_examenes.php**  
    *   *Archivo origen:* `php/vistas/estudiante_examenes.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 62).  
    *   *Destino:* `js/estudiante_examenes.js`

### 4. Módulo de Planificación y Carga Académica (3 archivos)
*   [x] **cursos.php**  
    *   *Archivo origen:* `php/vistas/cursos.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 177).  
    *   *Destino:* `js/cursos.js`
*   [x] **carga.php**  
    *   *Archivo origen:* `php/vistas/carga.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 232).  
    *   *Destino:* `js/carga.js`
*   [x] **reporte_listas.php**  
    *   *Archivo origen:* `php/vistas/reporte_listas.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 245).  
    *   *Destino:* `js/reporte_listas.js`

### 5. Módulo de Calendario y Agenda (3 archivos)
*   [x] **calendario.php**  
    *   *Archivo origen:* `php/vistas/calendario.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 77).  
    *   *Destino:* `js/calendario.js`
*   [x] **agenda.php**  
    *   *Archivo origen:* `php/vistas/agenda.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 81).  
    *   *Destino:* `js/agenda.js`
*   [x] **khronos.php**  
    *   *Archivo origen:* `php/vistas/khronos.php`  
    *   *Acción:* Consolidar los dos bloques de script inline (líneas ≈ 311 y ≈ 320).  
    *   *Destino:* `js/khronos.js`

### 6. Módulo de Comunicación y Asistencia (2 archivos)
*   [x] **mensajeria.php**  
    *   *Archivo origen:* `php/vistas/mensajeria.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 622).  
    *   *Destino:* `js/mensajeria.js`
*   [x] **asistencia.php**  
    *   *Archivo origen:* `php/vistas/asistencia.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 152).  
    *   *Destino:* `js/asistencia.js`

### 7. Core, Seguridad y Vistas Base (4 archivos)
*   [x] **inicio.php**  
    *   *Archivo origen:* `php/vistas/inicio.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 294).  
    *   *Destino:* `js/inicio.js`
*   [x] **zulu.php**  
    *   *Archivo origen:* `php/vistas/zulu.php`  
    *   *Acción:* Extraer JS inline (línea ≈ 317).  
    *   *Destino:* `js/zulu.js`
*   [x] **security.php**  
    *   *Archivo origen:* `php/security.php`  
    *   *Acción:* Mover los dos bloques de script (líneas ≈ 86 y ≈ 118).  
    *   *Destino:* `js/security.js`
*   [x] **rendered.html**  
    *   *Archivo origen:* `php/rendered.html`  
    *   *Acción:* Crear script general y referenciarlo desde las vistas que lo incluyen.  
    *   *Destino:* `js/rendered.js`