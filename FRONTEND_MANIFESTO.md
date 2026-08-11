# ⚡ FRONTEND_MANIFESTO.md: INTERACTIVIDAD Y DINÁMICA ARES

Reglas de ejecución para la lógica de cliente y experiencia de usuario.

## 1. 🏗️ ESTRUCTURA Y SOBERANÍA JS
- **Estándar ES6+**: Uso mandatorio de `const`, `let`, `arrow functions`, `destructuring` y `template literals`. **Veto absoluto al uso de `var`**.
- **Flujo Asíncrono Maestro**: Uso exclusivo de `Fetch API` y **`async/await`**. Prohibido el encadenamiento de `.then()`.
- **Modularidad Soberana**: Organización mediante `import/export`. Prohibido el código monolítico y las variables globales desprotegidas.
- **Cero Globales**: Encapsulamiento de lógica en objetos o módulos para evitar colisiones.

## 2. 🌀 EXPERIENCIA Y REACTIVIDAD (UX)
- **Sincronización Silenciosa**: Se prohíbe el uso de `window.location.reload()`. La persistencia y actualización del DOM deben ser vía AJAX (Hefesto Engine).
- **Feedback y Micro-animaciones**: Toda acción debe tener respuesta visual inmediata. Uso de transiciones de seda para suavizar elementos.
- **Validación Preventiva**: Predicción de colisiones y feedback instantáneo en interfaces interactivas (D&D).

## 3. 🛡️ SEGURIDAD Y RENDIMIENTO CRÍTICO
- **Higiene de Ejecución**: Prohibición de `eval()` e inyección de HTML crudo sin saneamiento.
- **Rendimiento de Carga**: Implementación de **Lazy Loading** para imágenes y módulos JS no críticos.
- **Optimización de Activos**: Prohibido cargar librerías pesadas completas para funcionalidades mínimas.
- **Hilo Principal**: Prohibidos los procesos síncronos pesados que bloqueen la interfaz.

---
**CERTIFICACIÓN**: Documento integrado con Skills de Interactividad y Rendimiento. Pipod.
