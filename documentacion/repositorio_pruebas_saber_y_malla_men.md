# Repositorio Base Tipo Pruebas Saber y Alineación con la Malla Curricular del MEN

**Documento Oficial de Arquitectura Pedagógica y Funcional**  
**Versión:** 1.0  
**Audiencia:** Rectores, Directores Académicos, Coordinadores de Calidad, Docentes y Equipos Técnicos de Instituciones Educativas.

---

## 1. Resumen Ejecutivo

En el ecosistema educativo actual, la capacidad de medir el desarrollo de competencias de manera estandarizada y alineada con los lineamientos del Ministerio de Educación Nacional (MEN) es un diferenciador estratégico. El presente documento técnico-pedagógico detalla la composición, estructura y ventajas del **Repositorio Base tipo Pruebas Saber** integrado dentro del módulo ARES.

Este repositorio no es simplemente un banco de preguntas; es un activo educativo estructurado jerárquicamente que conecta los Derechos Básicos de Aprendizaje (DBA) con las Evidencias de Aprendizaje exigidas en las pruebas estandarizadas (ICFES). A lo largo de este documento, se clarifica la distinción operativa entre la evaluación formativa cotidiana del docente y los simulacros institucionales estandarizados, garantizando una implementación armónica que respeta la autonomía pedagógica mientras se asegura la rigurosidad en la preparación para las Pruebas Saber.

---

## 2. ¿Qué es el Repositorio Base tipo Pruebas Saber?

Es un contenedor estructurado y precargado de reactivos (preguntas) diseñados bajo los parámetros psicométricos y metodológicos del Instituto Colombiano para la Evaluación de la Educación (ICFES). Este repositorio está diseñado para las áreas nucleares evaluadas en los ciclos de **Saber 3°, 5°, 7°, 9° y 11°** (incluyendo los programas "Evaluar para Avanzar" y los "Cuadernillos Saber").

### 2.1 Características Distintivas del Reactivo Estándar
A diferencia de una pregunta de control de lectura simple, los reactivos de este repositorio cumplen con los siguientes atributos técnicos:

| Atributo | Descripción Técnica | Impacto Pedagógico |
| :--- | :--- | :--- |
| **Contexto** | Enunciado basado en situaciones problema, gráficas, tablas o textos continuos/discontinuos. | Evalúa la aplicación del conocimiento, no solo la memoria. |
| **Evidencia** | Cada pregunta está ligada a una acción observable del estudiante (Ej: "Interpreta", "Propone", "Justifica"). | Permite al docente determinar exactamente qué falló en el proceso cognitivo. |
| **Complejidad** | Niveles definidos (Básico, Medio, Avanzado) según la taxonomía de pensamiento. | Facilita la generación de curvas de dificultad por salón y por estudiante. |
| **Distractores** | Opciones de respuesta incorrectas diseñadas con base en errores comunes o procesos incompletos. | Provee información diagnóstica de alta granularidad para la remediación. |

> **Nota clave:** El repositorio está curado y alineado 1 a 1 con la matriz de referencia del ICFES y los DBA del MEN, asegurando que el estudiante se familiarice con la *forma* y el *fondo* de la prueba oficial.

---

## 3. Clarificación Operativa Fundamental

Un pilar del diseño arquitectónico es diferenciar claramente las funciones del docente, evitando la fricción entre la pedagogía activa y la medición estandarizada.

### 3.1 Autonomía Docente Cotidiana (Creación Libre en ARES)
El sistema preserva la capacidad del docente de crear cualquier pregunta, taller o evaluación formativa que considere pertinente para su plan de aula. Esta acción **no requiere** alineación forzosa con la matriz ICFES.

- **Finalidad:** Evaluación formativa, retroalimentación del proceso, exploración pedagógica.
- **Estructura:** Libre, definida por el maestro.
- **Taxonomía:** Flexible, adaptada al contexto del aula.

### 3.2 Banco Precargado Institucional (Simulacros Estandarizados)
Es el módulo de alta rigurosidad. Aquí, la institución activa el repositorio descargado/alojado con licencia oficial.

- **Finalidad:** Evaluación sumativa, simulacros tipo ICFES, diagnóstico institucional.
- **Estructura:** Bloqueada y validada. El docente **no puede** editar la semántica de las preguntas para no romper la validez estadística.
- **Fuente:** Reactivos cargados por el administrador académico o suministrados por el proveedor tecnológico.

#### Tabla Comparativa Operativa

| Eje de Acción | Modo: Creación Libre (Profesor) | Modo: Banco Precargado (Institución/ARES) |
| :--- | :--- | :--- |
| **¿Quién crea el contenido?** | El docente en su sesión personal. | El MEN / Editoriales / Arquitecto Curricular. |
| **¿Se puede modificar?** | Sí, 100% editable y versionable. | No (Protegido contra edición para mantener estandarización). |
| **¿Cómo se asigna?** | El docente la envía a su grupo en una fecha específica. | El coordinador lanza un "Simulacro" para toda la institución o grado. |
| **Resultado esperado** | Nota cuantitativa/cualitativa del progreso del tema visto. | Puntaje escalar (0-500) y percentil comparativo. |

---

## 4. Estructura Relacional con la Malla Curricular del MEN

La arquitectura subyacente del repositorio está modelada como un grafo jerárquico que nace en lo macro-curricular y aterriza en lo micro-evaluativo. Esta estructura es la que permite a los analistas (Perseus) generar los semáforos de cobertura.

### 4.1 Diagrama Conceptual de Jerarquía

```text
[ ÁREA ] (Matemáticas, Lenguaje, Ciencias Naturales, Sociales, Inglés)
    │
    └──> [ ASIGNATURA / ESPECIALIDAD ] (Ej: Matemáticas Aritmética vs Geometría)
             │
             └──> [ COMPETENCIA ] (Ej: Comunicación, Razonamiento, Resolución)
                      │
                      └──> [ COMPONENTE ] (Ej: Numérico-Variacional, Geométrico-Métrico)
                               │
                               └──> [ DBA ] (Derecho Básico de Aprendizaje)
                                        │
                                        └──> [ EVIDENCIA DE APRENDIZAJE ] (Acción observable)
                                                 │
                                                 └──> [ REACTIVO / PREGUNTA ]
```

### 4.2 Definición Detallada de Capas

#### Capa 1: Área y Asignatura
Corresponde a la división normativa del currículo (Ley 115). El repositorio se adhiere a las áreas obligatorias evaluadas por el Estado.

#### Capa 2: Competencia
Capacidades generales que el ICFES define como transversales (*Razonamiento*, *Comunicación*, *Resolución de Problemas*, *Indagación*, *Explicación de Fenómenos*, etc.).

#### Capa 3: Componente
Define los subdominios conceptuales específicos dentro de la disciplina.

#### Capa 4: Derechos Básicos de Aprendizaje (DBA)
Enunciados nacionales que estructuran los aprendizajes estructurantes año tras año.

#### Capa 5: Evidencia de Aprendizaje
Desagregación más fina del DBA que describe la manifestación observable del aprendizaje.

---

## 5. Ventajas Estratégicas para las Instituciones Educativas

La adopción de este repositorio estandarizado no solo optimiza el tiempo docente, sino que transforma la gestión de calidad institucional (Decreto 1290).

### 5.1 Diagnóstico Temprano y Predictivo
Gracias a la vinculación con los DBA, la institución no solo sabe si el estudiante aprobó o reprobó, sino **qué competencia específica está fallando desde grado 3° hasta 11°**.

### 5.2 Simulacros en 1 Clic
Los coordinadores pueden generar un cuadernillo oficial en segundos:
1. Seleccionan el Grado (Ej: 9°).
2. Seleccionan el Área (Ej: Lectura Crítica).
3. Seleccionan la modalidad: *Simulacro completo (Tiempo real ICFES)* o *Micro-quiz por DBA*.
4. El sistema baraja, asigna versiones y despliega la prueba digital o imprimible.

### 5.3 Analítica con Semáforos de Cobertura (Plataforma Perseus)
La integración del repositorio con el módulo analítico permite observar la *Cobertura Curricular* en tiempo real:
- **Semáforo Verde:** El plan de aula y las evaluaciones cubren más del 85% de los DBA del periodo.
- **Semáforo Amarillo:** Cobertura entre 60% y 84%. Temáticas críticas sin evaluar.
- **Semáforo Rojo:** Cobertura inferior al 60%. Alerta prioritaria para intervención curricular.

### 5.4 Cumplimiento Normativo (Decreto 1290)
Garantiza que la institución evalúa competencias con herramientas homologables al sistema nacional de evaluación.

---

## 6. Hoja de Ruta para la Adopción Progresiva

1. **Fase 1: Sensibilización y Mapeo (Mes 1):** Capacitar al cuerpo docente en la complementariedad entre la autonomía formativa y el banco diagnóstico.
2. **Fase 2: Pilotaje Diagnóstico (Mes 2 y 3):** Aplicar pruebas diagnósticas en grados piloto (5°, 9°, 11°) para establecer la línea base.
3. **Fase 3: Simulacros Oficiales y Mejora Continua (Mes 4 en adelante):** Institucionalizar simulacros periódicos y remediación focalizada según los semáforos de Perseus.

---

**Aprobado por:**  
*Dirección de Arquitectura y Tecnología Educativa (Ingeniería Ricardo / ARES)*
