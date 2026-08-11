# 🧠 Arquitectura de Modelos Matemáticos: Perseus Engine (Ares)

Este documento establece la hoja de ruta para la futura implementación de modelos matemáticos y analíticos dentro del ecosistema de evaluación Ares (Módulo de Calificación - Perseus Engine). Su objetivo es elevar el sistema de un calificador básico a una plataforma de inteligencia pedagógica predictiva.

## 1. 🎯 Teoría de Respuesta al Ítem (TRI) - Evaluación Científica
*Aprobado para futura integración: "Un espaldarazo al estudiante"*

**Objetivo:** Transicionar del modelo clásico (Respuestas Correctas / Total) a un modelo de calificación ponderada.

*   **Calibración Dinámica:** El sistema calculará la "Dificultad" y el "Índice de Discriminación" de cada pregunta en el banco de reactivos basándose en el historial de aciertos/errores de los estudiantes.
*   **Ponderación Justa:** Los estudiantes que acierten preguntas de alta dificultad (alineadas a los DBA críticos) recibirán un mayor peso en su puntuación.
*   **Implementación Técnica:** Se construirá en el Backend (PHP Estricto) utilizando una clase dedicada (`AresItemResponseEngine`) que ejecutará los cálculos logísticos sin interferir con el rendimiento de la UI.

## 2. 📊 Regresión y Análisis Predictivo (Riesgo Académico)
*Aprobado como implementación obligatoria ("Sí o sí")*

**Objetivo:** Actuar como un sistema de alerta temprana para docentes y directivos.

*   **Proyección de Desempeño:** Modelos de regresión lineal simple que analicen el progreso del estudiante durante las primeras etapas del periodo académico.
*   **Alertas de Deserción/Reprobación:** El motor proyectará la tendencia matemática y emitirá alertas tempranas (ej. *"Probabilidad del 85% de no alcanzar la competencia básica a fin de año"*).
*   **Implementación Técnica:** Cálculos matemáticos en PHP ejecutados preferiblemente en procesos batch o al consultar los reportes del estudiante, renderizando curvas de tendencia en la interfaz usando variables CSS institucionales (Vitrina 06).

## 3. 🛡️ Análisis de Dispersión y Detección de Fraude (Similitud)
*Requiere implementación meticulosa ("Delicada")*

**Objetivo:** Proteger la integridad de las pruebas y auditar la calidad del diseño de los exámenes.

*   **Campana de Gauss (Auditoría del Examen):** Análisis de la distribución de notas para detectar exámenes mal diseñados (excesivamente fáciles o punitivos).
*   **Detección de Colisiones:** Uso de modelos probabilísticos y vectores de distancia (ej. Distancia de Hamming) para detectar matrices de respuestas sospechosamente idénticas entre estudiantes del mismo grupo.
*   **Consideraciones Éticas:** Esta herramienta servirá **únicamente como alerta para el docente**, no como un juez punitivo automático. Su diseño UX/UI debe ser cuidadoso y no invasivo.

## 4. 📐 Geometría Proyectiva y Álgebra Lineal (Motor OCR/QR Físico)
*Fase de incubación: Para evolución del sistema de escaneo*

**Objetivo:** Perfeccionar la captura de exámenes físicos a través de cámaras web en condiciones reales.

*   **Homografía (Transformación de Perspectiva):** Uso de matrices matemáticas en JavaScript (Canvas) para identificar los 4 puntos de anclaje de la hoja y enderezar la perspectiva de la imagen antes del análisis.
*   **Estado Actual:** El sistema actual opera en un formato "sencillo e incipiente" (descuento de respuestas incorrectas). Este modelo de rectificación óptica se introducirá cuando se implementen formatos de respuesta libre o reactivos más complejos.

---
*Documento generado bajo la doctrina Vitrina 06.*
*Aprobado por: Ingeniero Ricardo*
