# 🏛️ PROYECTO: AULA VIRTUAL ARES v1.0 (THE STUDENT COCKPIT)

Este documento detalla la arquitectura técnica y funcional del módulo central de interacción del estudiante, diseñado bajo los estándares de **Ingeniería Élite** y el lenguaje visual **Vitrina 06**.

## 1. VISIÓN ESTRATÉGICA
El Aula Virtual Ares no es un repositorio de archivos; es un **Centro de Inmersión Académica**. Su finalidad es centralizar la soberanía del estudiante, eliminando la dispersión de información y proporcionando una visión en tiempo real de su estado académico.

## 2. PILARES ARQUITECTÓNICOS

### A. El Dashboard de Inmersión (The Grid)
*   **Smart Cards por Asignatura:** Cada materia de la carga académica se presenta como una tarjeta interactiva con efecto de cristal (Glassmorphism).
*   **Métricas de Salud Dinámicas:** Visualización de promedio actual y porcentaje de asistencia mediante micro-gráficos radiales.
*   **Radar de Urgencia:** Sistema de semaforización que destaca tareas y exámenes con vencimiento próximo (rojo: <24h, amarillo: <48h).

### B. El "Deep Dive" (Vista de Asignatura)
Al entrar en una materia, el sistema aplica un filtro contextual absoluto:
*   **Línea de Timeline Unificada:** Un feed vertical que integra recursos didácticos, compromisos (Agenda) y evaluaciones (Ares) en orden cronológico.
*   **Bóveda de Recursos:** Espacio de alta disponibilidad para guías, lecturas, videos y material de apoyo subido por el docente.
*   **Historial de Desempeño:** Acceso inmediato a retroalimentaciones y notas de actividades pasadas.

### C. Motor de Recursos (Backend)
*   **Persistencia:** Implementación de la tabla `aula_recursos` con vinculación a `especialidades` y `cursos`.
*   **Seguridad de Acceso:** Los archivos serán servidos mediante un controlador PHP para validar la matrícula activa antes de la descarga.
*   **Tracking de Consulta:** El sistema registrará cuándo y quién consultó cada recurso, permitiendo al docente auditar el nivel de interacción del grupo.

## 3. ESPECIFICACIONES VISUALES (VITRINA 06)
*   **Métrica 44px:** Todo control interactivo (botones de descarga, pestañas, selectores) cumplirá con la altura estándar de 44px.
*   **Radio de Prestigio:** Paneles maestros con 24px de redondeo y elementos internos con 12px.
*   **Física de Movimiento:** Transiciones suaves utilizando `cubic-bezier(0.4, 0, 0.2, 1)`.
*   **ADN Institucional:** Uso exclusivo de variables CSS (`var(--el-*)`) y tipografía institucional.

## 4. HOJA DE RUTA DE IMPLEMENTACIÓN

| Fase | Tarea Crítica | Estado |
| :--- | :--- | :--- |
| **I** | Evolución de DB (Tabla `aula_recursos`) | Pendiente |
| **II** | API de Gestión de Recursos (Docente) | Pendiente |
| **III** | Interfaz del Cockpit (Estudiante) | Pendiente |
| **IV** | Integración de Notificaciones Contextuales | Pendiente |

---
**INGENIERÍA ÉLITE** - *Soberanía Tecnológica en Educación*
**ING. RICARDO** - *Autoridad en Arquitectura*
