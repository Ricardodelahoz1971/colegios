# 🏛️ ROADMAP: MÓDULOS FALTANTES CRÍTICOS EN PERSEUS (GRADO INDUSTRIAL)

Este documento consolida el análisis de ingeniería de producto sobre los módulos ausentes pero obligatorios en el software educativo **Perseus** para lograr la dominancia comercial en el mercado de Colombia y Latinoamérica (Educación K-12).

---

## ⚖️ 1. MÓDULO DE OBSERVADOR DEL ALUMNO DIGITAL
*Garantía de debido proceso disciplinario y convivencia escolar.*

El "Observador del Alumno" es un requerimiento legal ineludible en Colombia, regido por la **Ley 1620 de 2013** (Sistema Nacional de Convivencia Escolar). Las instituciones de educación formal están obligadas a registrar de manera inmutable el comportamiento de los estudiantes.

### 📋 Requerimientos y Flujo Lógico
*   **Clasificación de Faltas (Normativa MEN):**
    *   **Situaciones Tipo I:** Conflictos manejados por conciliación inmediata.
    *   **Situaciones Tipo II:** Acoso escolar (bullying), ciberacoso que no constituya delito.
    *   **Situaciones Tipo III:** Hechos que constituyan delitos constitucionales (requieren activación del protocolo externo de la Fiscalía o Policía de Infancia y Adolescencia).
*   **Debido Proceso Digital:**
    *   Registro del evento (Fecha, Hora, Actor, Detalles).
    *   Descargos escritos del estudiante.
    *   Estrategias pedagógicas / Compromisos de mejoramiento conductual acordados.
*   **Trazabilidad Inviolable (Toque Élite):**
    *   El observador no debe ser un texto estático editable. Cada anotación conductual se convierte en un registro histórico inalterable en base de datos.
    *   **Firma Digitalizada:** Integrar un lienzo táctil Canvas en la interfaz móvil/tablet que permita a los acudientes y alumnos estampar su firma física de forma digital, registrando el timestamp y la dirección IP.

### 🧱 Integración en la Base de Datos
*   Nueva tabla `observador_anotaciones` vinculando: `estudiante_id`, `actor_docente_id`, `tipo_falta` (I, II, III), `descripcion`, `descargos`, `compromisos_acudiente` y `firma_blob`.

---

## 📄 2. MOTOR DE BOLETINES OFICIALES Y EXPORTACIÓN MASIVA (PDF ENGINE)
*Consolidación del periodo y entrega de cuentas oficiales.*

Al finalizar cada periodo académico regulado por el **Decreto 1290 de 2009** (SIEE), las instituciones deben emitir informes oficiales de evaluación (Boletines) a los padres de familia.

### 📋 Requerimientos y Flujo Lógico
*   **Consolidación de Datos Directa:**
    *   Cálculo automático de la nota definitiva del periodo por asignatura, ponderada por los macro-pesos configurados en el sistema institucional.
    *   Conversión automática de la nota cuantitativa a la Escala de Valoración Nacional en Colombia (*Superior, Alto, Básico, Bajo*).
    *   Inyección dinámica del consolidado de inasistencias y del puesto del estudiante dentro de su respectivo curso/grado.
    *   Incorporación de las observaciones cualitativas del Director de Grupo.
*   **Arquitectura de Alto Rendimiento (High Fidelity PDF):**
    *   La generación debe ser asíncrona y por lotes (batch processing). Permitir al Rector generar el PDF de boletines de 400 alumnos en un solo bloque en segundos, optimizando el renderizado para evitar desbordamientos de memoria del servidor PHP (XAMPP).
    *   **Estética Vitrina 06:** Diseños extremadamente limpios, limpios de ornamentos visuales redundantes, aptos para firmas digitales digitalizadas de la Rectoría y la Coordinación Académica.

---

## 👨‍👩‍👧‍👦 3. PORTAL SEGURO DE PADRES DE FAMILIA Y ACUDIENTES
*Transparencia en el hogar y retención de estudiantes.*

En los niveles de preescolar, primaria y bachillerato, el cliente y auditor final del software no es el docente, sino el padre de familia/acudiente. El portal de padres democratiza la información escolar en tiempo real.

### 📋 Requerimientos y Flujo Lógico
*   **Seguridad de Control de Accesos:**
    *   Creación de identidades específicas vinculando uno o más estudiantes a una sola cuenta de acudiente.
*   **Vigilancia del Desempeño en Tiempo Real:**
    *   **Alertas Tempranas de Rendimiento:** Acceso de solo lectura al consolidado parcial del periodo (Notas de *Ares* destacando promedios en riesgo o perdidos en rojo).
    *   **Seguimiento de Tareas (Khronos Integration):** Agenda visible de exámenes y entregas extraordinarias.
    *   **Control de Ausentismo Directo:** Alerta instantánea si el estudiante acumula fallas, previendo la pérdida de la asignatura por inasistencias (límite del 20% según norma nacional).

---

## 📝 4. EMBUDO DE ADMISIONES EN LÍNEA Y PREMATRÍCULA
*Optimización comercial y digitalización de captación.*

En los colegios privados, el proceso anual de admisiones y pre-registro colapsa administrativamente las secretarías. Este módulo digitaliza el flujo completo de captación.

### 📋 Requerimientos y Flujo Lógico
*   **Formulario de Pre-registro Público:**
    *   Formulario abierto a internet para que el acudiente ingrese los datos del aspirante y cargue los documentos obligatorios (Registro Civil, Boletines del colegio anterior, Certificado de paz y salvo).
*   **Embudo de Aprobación (Pipeline Kanban):**
    *   Interfaz interactiva estilo Kanban (Vitrina 06) donde la coordinación gestiona las fases de los aspirantes:
        `Inscrito ➔ Validación de Papeles ➔ Examen de Admisión ➔ Entrevista ➔ Aprobado ➔ Matriculado`
*   **Sincronización Automática de Identidades:**
    *   Al mover un aspirante a la fase final "Matriculado", Perseus debe generar de forma automática:
        1.  El registro en la tabla `estudiantes`.
        2.  El perfil de login en la tabla `usuarios` (encriptado).
        3.  La vinculación del curso correspondiente, ahorrando 20 minutos de transcripción manual por estudiante.

---

## 💡 NOTAS DE DISEÑO ARQUITECTÓNICO

Todos estos módulos deberán ser construidos estrictamente bajo los cimientos establecidos en los manifiestos de **Perseus**:
1.  **Backend:** PHP Tipado Estricto (`declare(strict_types=1);`), transacciones controladas con PDO y Prepared Statements (Blindaje OWASP).
2.  **Frontend:** Estilo **Vitrina 06**, métrica estricta de **44px** en interactivos, radios de **12px** en controles y **24px** en paneles maestros, y cero uso de colores HEX en CSS (`var(--el-*)`).
