# 🏛️ PROYECTO: REDISEÑO DEL BANCO UNIVERSAL DE REACTIVOS ARES

**Ingeniero Responsable:** Ing. Ricardo  
**Estatus:** Planificación Estratégica / Arquitectura de Linaje  
**Versión:** 2.0 (Evolución Soberana)

---

## 🎯 VISIÓN GENERAL
Transformar el actual sistema de preguntas en una **Bóveda Universal de Inteligencia Académica**. El objetivo es atomizar los reactivos para que sean compartidos entre la facultad, permitiendo la adaptación personalizada (Forking) sin perder la esencia ni la trazabilidad de la obra original.

---

## ⚖️ LOS TRES PILARES DEL LINAJE

### 1. SOBERANÍA DE LA "ESENCIA" (Originales vs. Adaptaciones)
Para mantener la integridad pedagógica, el sistema operará bajo una estructura de **Tronco y Ramas**:
*   **Pregunta Maestra (Original):** Creada por un autor único. Solo el autor o la Alta Dirección pueden modificarla globalmente.
*   **Pregunta Adaptada (Rama):** Cuando otro docente toma una pregunta, el sistema genera una copia vinculada (`parent_id`). El docente puede ajustar el estilo, pero el sistema mantiene el vínculo con el ADN original.

### 2. TRAZABILIDAD INTERNA (Capa de Comando)
La información de autoría y modificaciones es de uso **exclusivo administrativo**:
*   **Vista Estudiante:** Pureza total. Solo ve el contenido del reactivo.
*   **Vista Docente:** Colaborativa. Ve quién es el autor para referenciar calidad.
*   **Vista Coordinador:** Auditoría total. Acceso al historial de cambios, fechas y responsables del linaje de cada pregunta.

### 3. REPOSITORIOS POR MATERIA (Atomización)
Las preguntas dejan de estar "secuestradas" en un examen. Se organizan en repositorios globales:
*   **Agrupación:** Física, Química, Matemáticas, etc.
*   **Metadatos Obligatorios:**
    *   `Autor Original` (ID Interno)
    *   `Último Editor` (ID Interno)
    *   `Fecha de Creación/Modificación`
    *   `Nivel de Dificultad` (1-5)
    *   `Grado Académico`
    *   `Categoría/Tema`

---

## 🛠️ ESQUEMA TÉCNICO PRELIMINAR (Bóveda SQLite)

### Tabla: `banco_preguntas_master`
| Campo | Tipo | Función |
| :--- | :--- | :--- |
| `id` | PK | Identificador único del reactivo. |
| `parent_id` | FK | Vincula la adaptación con su "Esencia" original. |
| `materia_id` | FK | Repositorio al que pertenece. |
| `contenido` | TEXT | El cuerpo de la pregunta (Soporta HTML/Aero). |
| `dificultad` | INT | Métrica de complejidad (1 a 5). |
| `grado` | INT | Grado objetivo (1 a 11). |
| `autor_id` | FK | El genio creador (Inamovible). |
| `editor_id` | FK | El último profesor que "moldeó" la versión. |
| `estado` | INT | 1: Activo, 0: Depurado. |

---

## 🎨 ESTÁNDAR VISUAL (VITRINA 06)
*   **Gelería de Reactivos:** Uso de tarjetas con bordes de 12px y efecto de cristal (`--el-glass-bg`).
*   **Filtros Aero Glass:** Barra de búsqueda superior con desenfoque de 12px para navegación rápida entre materias.
*   **Indicadores de Linaje:** Badges discretos en la vista docente: `[Original]` en Oro, `[Adaptado]` en Aero.

---

## 📅 HOJA DE RUTA (MAÑANA)
1.  **Validación del Schema:** Revisión final de la tabla por el Ingeniero Ricardo.
2.  **Prototipo de UI:** Creación de la vista `vistas/banco_universal.php`.
3.  **Lógica de Clonación:** Desarrollo del script `ajax/adaptar_pregunta.php`.

---
**CERTIFICACIÓN:** Este documento representa la voluntad técnica de la dirección y queda guardado en la bóveda de documentación para su ejecución inmediata tras el descanso del Ingeniero.
