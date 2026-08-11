# 🏛️ ANÁLISIS DE VIABILIDAD: MOTOR DE REPORTES PERSONALIZADOS
**Estatus:** Propuesta de Diseño de Arquitectura (Pre-Aprobación)  
**Autor:** Ingeniero de Software & Arquitectura  
**Aprobado por:** Ing. Ricardo  

---

## 1. 📋 Descripción del Concepto
El **Editor de Reportes** funcionará mediante un lienzo interactivo (Canvas) y paquetes de bloques componentizados pre-construidos que se pueden arrastrar y soltar:

### A. Paquetes de Bloques Predefinidos (Componentes)
* **Cabecera Institucional**: Bloque oficial con el logo, nombre del plantel y metadatos del periodo (ya estructurado estéticamente).
* **Boletín de Calificaciones**: Tabla dinámica de asignaturas, notas definitivas, desempeños cualitativos y observaciones por periodo.
* **Materias Perdidas**: Tablas y listas dinámicas de asignaciones reprobadas por estudiante.
* **Inasistencias**: Resumen cuantitativo e historial de ausencias justificadas e injustificadas.
* **Métricas Perseus**: Estado de la cobertura curricular de DBA asociada al docente o materia.

### B. Ciclo de Vida del Reporte
1. **Maquetación Visual**: El Coordinador arrastra los componentes deseados en el lienzo para estructurar el reporte. Esto permite diseñar **Boletines Académicos 100% a medida** del plantel.
2. **Persistencia de Plantillas**: El diseño se guarda en una base de datos centralizada como una plantilla JSON, disponible en una lista de reportes creados para ser reusados en cualquier momento.
3. **Evaluación y Ejecución**: Al ejecutar una plantilla de la lista, el sistema solicita los parámetros dinámicos de consulta:
   * **Curso** (para reportes consolidados de grado o generación masiva de boletines de grupo)
   * **Estudiante** (para reportes individuales/hojas de vida o boletín específico)
   * **Profesor / Asignatura** (para reportes de desempeño de carga académica)

---

## 2. ⚡ Estrategia de Rendimiento para Servidor Web Compartido
En entornos de hosting compartido, los recursos (CPU, memoria RAM e I/O de disco) están estrictamente limitados. Para evitar suspensiones de cuenta o lentitud extrema, implementaremos las siguientes mejores prácticas:

### A. Generación Híbrida de Consultas (Pre-Compiladas)
* **El Problema**: Las consultas SQL generadas de forma 100% dinámica ("ad-hoc") son difíciles de indexar y optimizar.
* **La Solución**: Los bloques del reporte consultarán a través de **procedimientos definidos** o *Data Providers* parametrizados. El Coordinador solo define *qué* bloque usar y sus filtros (ej. Periodo, Grado), pero la estructura de la consulta SQL es estática, limpia y utiliza los índices correctos de la base de datos MariaDB.

### B. Renderizado Asíncrono y Chunking (Segmentación)
* Para reportes masivos (ej. consolidados de toda la institución), no se procesará todo en una sola petición HTTP para evitar el timeout del servidor (usualmente establecido a 30s en compartidos).
* La recopilación de datos se fragmentará por lotes mediante peticiones AJAX secuenciales controladas por el frontend.

### C. Estrategia de Caché de Datos Temporales
* Implementación de una tabla intermedia de caché (`reportes_cache`) que persista el resultado de consultas complejas durante el día. Si el Coordinador vuelve a exportar el mismo reporte en un intervalo corto, el sistema leerá la caché en lugar de volver a calcular promedios históricos.

---

## 3. 🛡️ Blindaje de Seguridad a Nivel de Código
Hemos logrado resolver lógicas extremadamente complejas previamente, por lo que el control de acceso en este módulo se programará con las siguientes fronteras herméticas:

### A. Validador Jerárquico Absoluto (Frontera en el Backend)
* Cada llamada al endpoint que provee los datos del reporte pasará por el validador de roles institucional de forma estricta:
  ```php
  // Validar rol de Coordinación o Directivo de forma estricta en el backend
  if (!in_array((int)$_SESSION['rol_id'], [1, 2, 3])) {
      throw new SecurityException("Acceso no autorizado a la data de reportes.");
  }
  ```

### B. Sanitización de Inputs y Control contra Inyección de Consultas (SQLi)
* Ningún parámetro de filtrado enviado por el frontend (ej. nombres de tablas o nombres de columnas) se inyectará de forma cruda en el Query.
* Se usará un mapeador estricto (*White-list*) que valide que las tablas a consultar y columnas permitidas correspondan únicamente al diccionario de datos autorizado para el Coordinador.

### C. Protección de Datos Sensibles (PII)
* El módulo filtrará campos sensibles (como contraseñas, hashes, registros de auditoría de seguridad o datos financieros) a nivel de la capa del Data Provider, asegurando que la API del reporte no exponga información ajena al ámbito académico-coordinador.

---

## 4. 📊 Matriz de Viabilidad (Escala 1 a 10)

| Dimensión | Calificación | Justificación / Plan de Mitigación |
| :--- | :---: | :--- |
| **Dificultad de Implementación** | **7.5 / 10** | **75% de Dificultad**. Interfaz drag-and-drop en JS unificada con el parseador estructurado en PHP. |
| **Impacto en Rendimiento** | **Bajo** *(Mitigado)* | Con caché intermedia y consultas indexadas, el impacto en el hosting compartido es mínimo. |
| **Riesgo de Seguridad** | **Bajo** *(Controlado)* | Mitigado al 100% mediante el uso estricto de Prepared Statements y listas blancas de parámetros. |
| **Impacto Positivo Institucional** | **9.5 / 10** | Ahorro masivo de tiempo en la generación manual de reportes por parte de Coordinación. |

---

## 5. ⚖️ Pros y Contras del Modelo de Paquetes en Lienzo

### Pros (+):
* **Autonomía Total (Cero dependencia)**: El coordinador puede inventar y modificar plantillas de informes en minutos sin necesidad de que un programador altere el código del sistema.
* **Seguridad por Diseño**: Al encapsular la lógica en bloques predefinidos, es imposible que el usuario ejecute consultas SQL arbitrarias destructivas.
* **Ahorro de Carga**: El servidor no tiene que recalcular páginas enteras; solo procesa e inyecta la información del componente seleccionado.
* **Reutilización**: Las plantillas creadas pueden clonarse y aplicarse a diferentes grados o periodos académicos.

### Contras (-):
* **Esfuerzo de Maquetación Inicial**: Se requiere definir perfectamente en CSS/Bootstrap la visualización de impresión de cada bloque para que encaje de manera estéticamente premium en formatos tamaño carta/oficio.
* **Curva de Aprendizaje de Diseño**: Aunque la interfaz sea Drag & Drop, coordinadores no familiarizados con el diseño visual pueden crear reportes desorganizados visualmente (se controlará mediante plantillas pre-armadas por defecto).

---

## 6. 🇨🇴 Contexto y Comparativa con Plataformas en Colombia

En el ecosistema de software educativo en Colombia, la generación de informes personalizados es uno de los mayores dolores de cabeza de los planteles:

* **Sistemas Rígidos (Ej. Gnosoft, Ciudad Educativa, Master2000)**:
  * Ofrecen boletines y reportes estandarizados. Si la institución requiere un formato nuevo o una variación específica para coordinadores, debe abrir un ticket de soporte y esperar semanas de desarrollo a medida, pagando costos adicionales.
  * Tienen interfaces administrativas complejas y poco intuitivas que requieren capacitaciones técnicas de varias horas.
* **Nuestra Propuesta (Lienzo Élite)**:
  * Introduce el concepto de **Soberanía del Diseñador** en la administración educativa.
  * El Coordinador simplemente arrastra bloques visuales nativos bajo la estética Premium del sistema.
  * La plataforma se desmarca completamente de la competencia por ofrecer flexibilidad sin cargos adicionales por personalización de reportes.
  * Se posiciona como una herramienta comercial de alto nivel para coordinadores que toman decisiones basadas en métricas en tiempo real.
