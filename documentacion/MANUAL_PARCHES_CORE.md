# 🛡️ MANUAL DE PARCHES DE NÚCLEO Y LIBRERÍAS DE TERCEROS

Este documento registra todas las intervenciones quirúrgicas realizadas sobre librerías de terceros (vendor/libs) para adaptarlas a la arquitectura estricta del Sistema Escolar Élite.

## 1. MathLive (`ml_engine.js`)
**Fecha de Intervención:** 06 de Junio de 2026
**Ubicación:** `assets/libs/ml/ml_engine.js` y `php/dashboard.php`
**Motivo del Parche:** 
En navegadores con alta protección de rastreo (Opera, Edge, Brave), la función interna heurística `kg()` de MathLive fallaba al intentar adivinar su propio directorio analizando el `new Error().stack`. Esto generaba una ruta vacía (`""`) y provocaba que MathLive intentara cargar assets asíncronos relativos a la página activa, resultando en peticiones 404 (ej. `/php/x`) y errores fatales de consola (`Uncaught SyntaxError: Unexpected token '<'`).

**Solución Implementada:**
1. **Asignación de Identidad:** Se inyectó `id="ml_engine_script"` al tag de carga en `dashboard.php`.
2. **Reescritura de Contexto Base:** Se reemplazó la asignación de la variable `fc` en el núcleo minificado de MathLive para extraer la ruta directamente del DOM, saltándose la heurística insegura.

**Código del Parche (Variable `fc`):**
```javascript
// ANTES (Propenso a fallos):
var fc=((Tp=(Ep=globalThis==null?void 0:globalThis.document)==null?void 0:Ep.currentScript)==null?void 0:Tp.src)||(document.querySelector('script[src*="ml_engine.js"]')&&document.querySelector('script[src*="ml_engine.js"]').src)||kg();

// DESPUÉS (Certero y absoluto):
var fc=(document.getElementById("ml_engine_script")&&document.getElementById("ml_engine_script").src)||(window.location.origin+"/sistema_escolar/assets/libs/ml/ml_engine.js");
```

**Nota de Actualización Futura:** 
Si en el futuro se actualiza `ml_engine.js` a una versión más reciente, el sistema podría volver a presentar el error 404 en navegadores con escudos de privacidad. Si esto sucede, se debe buscar la variable `fc=` o la función heurística que determina la base, y forzar la lectura desde `document.getElementById("ml_engine_script").src`.
