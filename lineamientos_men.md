# 🇨🇴 ESTRUCTURA CURRICULAR: DIRECTIVAS MEN (COLOMBIA)

Este documento centraliza y describe la arquitectura lógica de los Lineamientos Curriculares, Estándares Básicos de Competencias (EBC) y Derechos Básicos de Aprendizaje (DBA) emitidos por el Ministerio de Educación Nacional de Colombia. Servirá como plano fundacional para modelar el catálogo de ARES v2.0.

---

## 1. ESTÁNDARES BÁSICOS DE COMPETENCIAS (EBC)
Los EBC establecen el horizonte de aprendizaje. Definen de manera pública y clara "lo que un estudiante debe saber y saber hacer" en cada área. Su nivel de granularidad es amplio.

### Arquitectura de un EBC:
A diferencia de los DBA, los EBC **NO** se asignan grado por grado de manera individual, sino por **Grupos de Grados** (Bloques).

*   **Eje de Área:** Lenguaje, Matemáticas, Ciencias Sociales, Ciencias Naturales, Competencias Ciudadanas.
*   **Bloques de Grados:**
    *   1° a 3° (Básica Primaria Inferior)
    *   4° a 5° (Básica Primaria Superior)
    *   6° a 7° (Básica Secundaria Inferior)
    *   8° a 9° (Básica Secundaria Superior)
    *   10° a 11° (Educación Media)
*   **Componentes / Factores (Ej. en Matemáticas):**
    *   Pensamiento numérico y sistemas numéricos
    *   Pensamiento espacial y sistemas geométricos
    *   Pensamiento métrico y sistemas de medidas
    *   Pensamiento aleatorio y sistemas de datos
    *   Pensamiento variacional y sistemas algebraicos y analíticos
*   **El Enunciado (El Estándar):** Generalmente redactado en primera persona. *(Ej: "Resuelvo y formulo problemas cuya estrategia de solución requiera de las relaciones y propiedades de los números naturales y sus operaciones")*.

---

## 2. DERECHOS BÁSICOS DE APRENDIZAJE (DBA)
Los DBA son aterrizajes más específicos y medibles. Determinan los aprendizajes estructurantes que un estudiante *tiene el derecho* de alcanzar grado a grado.

### Arquitectura de un DBA:
A diferencia de los Estándares, los DBA **SÍ** se definen grado por grado.

*   **Áreas Aplicables:** Lenguaje, Matemáticas, Ciencias (Naturales y Sociales), Inglés, y Transición.
*   **Grado Específico:** Transición, 1°, 2°, 3°... hasta 11°.
*   **Número Identificador:** Cada DBA tiene un índice numérico dentro de su grado y área (Ej: "DBA 3 de Matemáticas 5°").
*   **Estructura Tridimensional del DBA:**
    1.  **El Enunciado:** Describe el aprendizaje fundamental. *(Ej: "Comprende que algunos escritos y manifestaciones artísticas pueden estar compuestos por texto, sonido e imágenes").*
    2.  **Evidencias de Aprendizaje:** Son las viñetas observables que indican que el estudiante está logrando el DBA. Estas evidencias son clave para el **Banco de Reactivos de ARES**, ya que una pregunta suele medir una o varias evidencias.
    3.  **Ejemplo:** Situación didáctica de referencia (No modelable en DB, es solo referencial).

---

## 3. PROPUESTA DE MODELADO RELACIONAL PARA ARES (SQLITE)

Para que ARES trabaje con estas directivas limpiamente sin ensuciar la tabla `eval_preguntas`, el modelo óptimo es:

### Tablas de Catálogo (Sola Lectura):
1.  **`men_areas`**: id, nombre (Lenguaje, Matemáticas...)
2.  **`men_ebc_bloques`**: id, nombre_bloque (1-3, 4-5...)
3.  **`men_ebc_estandares`**: id, area_id, bloque_id, componente, enunciado
4.  **`men_dba`**: id, area_id, grado, numero, enunciado
5.  **`men_dba_evidencias`**: id, dba_id, enunciado_evidencia

### Modificación en ARES (`eval_preguntas`):
En lugar de incrustar texto plano, la tabla de reactivos debería apuntar a los catálogos mediante claves foráneas (o IDs simples si se quiere flexibilidad):

*   `ebc_id` (INTEGER) -> Apunta al estándar general que persigue la pregunta.
*   `dba_id` (INTEGER) -> Apunta al Derecho Básico específico del grado.
*   *(Opcional)* `evidencia_id` (INTEGER) -> Apunta a la evidencia exacta que la pregunta evalúa, ideal para reportes quirúrgicos del PDF.

---
**NOTA ESTRATÉGICA:** Esta separación permite que, si el MEN actualiza los documentos el próximo año, solo se actualice el catálogo sin alterar el historial de ARES.
