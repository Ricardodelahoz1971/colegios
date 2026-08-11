# Arquitectura Híbrida de Calificaciones: Control Institucional vs. Autonomía Docente

Este documento consolida la lluvia de ideas y directrices arquitectónicas para el diseño del motor de evaluación del sistema, buscando el equilibrio perfecto entre el rigor administrativo y la agilidad pedagógica.

> [!NOTE]
> **El Desafío:** ¿Cuánto control exige la institución (Coordinación) y cuánta libertad necesita el Docente (Libertad de Cátedra) para ser eficiente? El estándar de oro en sistemas educativos robustos (LMS/SIS) se basa en un modelo de **Libertad Restringida**.

---

## 🏛️ NIVEL 1: Soberanía Institucional (El Coordinador)
La coordinación debe tener un control inquebrantable sobre la estructura cronológica y los pesos macro (la columna vertebral del sistema) para garantizar la uniformidad en los boletines y reportes oficiales.

*   **Periodos Académicos:** La institución define de manera estricta la cantidad de periodos (ej. 3 o 4) y sus fechas de apertura/cierre. Ningún docente puede alterar esta línea de tiempo fundamental.
*   **Escala de Valoración:** La institución define si se califica de 0.0 a 5.0, o de 1 a 100, y cuál es la nota mínima de aprobación.
*   **Dimensiones Institucionales (Macro-Pesos):** Si el colegio está regido por un sistema formal (como el SIE en Colombia), la coordinación configura las categorías principales y su peso global en el periodo. 
    *   *Ejemplo:* SABER (40%), HACER (30%), SER (30%).

---

## 👨‍🏫 NIVEL 2: Autonomía Docente (Libertad Catedrática)
Dentro de los límites establecidos por el Nivel 1, el docente debe tener libertad total para maniobrar. Restringir la cantidad de notas vuelve al sistema burocrático y torpe.

*   **Cantidad Dinámica de Actividades:** El colegio **NO** debe dictaminar un número fijo de notas (ej. "4 tareas obligatorias por periodo"). El sistema debe ser capaz de promediar matemáticamente 2 o 15 actividades, siempre y cuando el docente las clasifique en la dimensión correcta.
*   **Micro-Pesos (Distribución Interna):** Si el "HACER" vale el 30% del periodo a nivel global, el profesor debe poder decidir internamente cómo fragmentarlo. 
    *   *Ejemplo:* El profesor puede establecer que, de ese bloque, un "Proyecto Final" pese un 80% y las "Tareas menores" el 20% restante.
*   **Evaluación Ágil ("Sobre la Marcha"):** El docente debe poder crear actividades extraordinarias en cualquier momento sin alterar una matriz rígida. Si percibe bajo rendimiento colectivo, puede inyectar un "Taller de Refuerzo" en el sistema. El motor algorítmico redistribuirá automáticamente los pesos y promedios.

---

## 💡 Innovaciones Futuras (Características Élite)

Para llevar el sistema al siguiente nivel de sofisticación y comercialización, se pueden considerar estas funciones avanzadas:

> [!TIP]
> **Botón de "Plan de Mejoramiento" (Recuperación Inteligente)**
> En lugar de promediar una recuperación como si fuera una actividad normal, el docente podría marcar una nota con el atributo especial "Es Recuperación". El motor matemático del sistema buscaría la peor nota histórica del estudiante en esa dimensión y la reemplazaría o promediaría con la nueva calificación, premiando el esfuerzo de manera automatizada.

> [!IMPORTANT]
> **Perfiles de Flexibilidad Institucional (El Interruptor Maestro)**
> Configurar un ajuste maestro que permita comercializar el sistema a diferentes tipos de colegios:
> *   **Modo Estricto:** La coordinación dicta absolutamente todo: % de cada evaluación, fechas exactas de parciales y número fijo de notas (Ideal para instituciones militares o muy conservadoras).
> *   **Modo Universitario (Syllabus Libre):** El docente es dueño y señor de la materia, configurando sus propios porcentajes desde cero sin imposiciones de dimensiones rígidas (Ideal para academias de idiomas o educación superior).

> [!TIP]
> **Modo de Privacidad Transversal para Catedráticos**
> Un interruptor en el Panel de Configuración Global que permita a la Coordinación decidir qué nivel de acceso tienen los profesores especialistas (catedráticos) a las Sábanas de Notas y consolidados:
> *   **Modelo Abierto (Contexto Pedagógico):** El catedrático ve la Sábana completa del curso, incluyendo el desempeño de los alumnos en materias que no dicta, para analizar el estado global y psicológico del estudiante.
> *   **Modelo Estricto (Silo de Datos):** El motor API filtra dinámicamente el JSON desde el servidor. El catedrático visualiza el reporte, pero el sistema solo renderiza de forma exclusiva las columnas correspondientes a las materias que tiene asignadas en su carga académica.

---
*Este documento queda como base conceptual para futuras planificaciones estructurales de la base de datos y flujos de usuario.*
