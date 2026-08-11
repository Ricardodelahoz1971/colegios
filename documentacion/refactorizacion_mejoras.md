# Plan Maestro de Refactorización y Estandarización (Nivel Élite)

Este documento detalla la hoja de ruta para elevar todo el sistema escolar al estándar técnico y visual logrado en el módulo de Mensajería. No se trata de "arreglar errores", sino de pasar de un sistema funcional a un software de **Grado Profesional**.

## ¿El sistema es "malo"?
**No.** El sistema es robusto porque resuelve los problemas del mundo real (mensajes, asistencia, notas). Sin embargo, tiene **Deuda Técnica**. Resolver esta deuda hará que la aplicación sea:
1. **Invulnerable:** Protegida contra inyecciones SQL en cada rincón.
2. **Instantánea:** Sin bloqueos de sesión que "congelen" el navegador.
3. **Fluida:** Sin el "Hipo" visual (flicker) al navegar.

---

## Pilares de la Refactorización

### 1. Eliminación del Bloqueo de Sesión (Session Unlocking)
*   **Acción:** Inyectar `session_write_close()` en todos los archivos de la carpeta `php/logica/` que no lo tengan.
*   **Impacto:** Permite que el usuario abra múltiples pestañas o que las notificaciones carguen mientras el usuario escribe una tarea sin que una cosa trabe a la otra.

### 2. Navegación SPA Fluida (Zero Hipo)
*   **Acción:** Exportar la lógica de `pushState` y `fetch` de `mensajeria.php` a una función global en `script.js`.
*   **Impacto:** Los cambios de curso en la Agenda, o de fecha en Asistencia, se sentirán como una sola aplicación suave, eliminando las recargas blancas de pantalla.

### 3. Blindaje SQL (Prepared Statements)
*   **Acción:** Reemplazar todas las interpolaciones de variables directas en SQL por sentencias preparadas (`$db->prepare`).
*   **Impacto:** Seguridad absoluta. Nada de lo que un usuario escriba podrá romper o hackear la base de datos.

### 4. Unificación de Identidad (ID Maestro)
*   **Acción:** Asegurar que `usuario_id` sea la llave primaria en todos los módulos, dejando las IDs de tablas específicas (como `estudiante_id`) solo para metadatos.
*   **Impacto:** Consistencia total en el historial y reportes.

---

## Módulos en Orden de Prioridad

| Prioridad | Módulo | Problema Actual | Mejora Proyectada |
| :--- | :--- | :--- | :--- |
| **0** | **Mensajería** | 🟢 Completado | Estándar de Oro (Referencia) |
| **1** | **Agenda Académica** | Hipo visual y Riesgo de bloqueo de sesión. | Carga instantánea de tareas por curso sin recargar. |
| **2** | **Asistencia Digital** | Inyección de variables directa y recarga de página. | Marcación de asistencia con feedback visual SPA y seguridad reforzada. |
| **3** | **Matrícula y Carga** | Estilos inconsistentes y lógica pesada de recarga. | Dashboard de gestión centralizado con diseño unificado. |
| **4** | **Configuración** | Estilos ad-hoc (Frankenstein). | Panel de control centralizado con sistema de diseño (Design System). |

---

**Filosofía de Trabajo:** No tocaremos nada que funcione hasta que tengamos el reemplazo "Élite" listo para inyectar. Cero riesgos de romper la funcionalidad actual.
