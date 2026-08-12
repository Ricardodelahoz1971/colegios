# REFERENCIA TÉCNICA - FORMATOS DE MATRÍCULA

**Documento técnico de consulta rápida para desarrolladores.**

---

## TABLA 1: ARCHIVOS Y RESPONSABILIDADES

| Archivo | Líneas | Responsabilidad | Status | Cambios |
|---------|--------|-----------------|--------|---------|
| `js/modules/formatos_matricula_builder.js` | 1489 | Motor de editor drag&drop | 🟡 Refactor | 300+ líneas |
| `js/formatos_matricula.js` | 322 | Orquestación UI | 🟢 Mantener | Ajustes mínimos |
| `php/logica/formatos_ajax.php` | 140 | API CRUD | 🟢 Mantener | 0 cambios |
| `imprimir_matricula.php` | 837 | Renderizado PDF | 🟡 Simplificar | 50-100 líneas |
| `php/vistas/formatos_matricula.php` | 500+ | Template HTML | 🟢 Mantener | Validar |
| `styles/modules/formatos_matricula.css` | 200+ | Estilos | 🟢 Mantener | Agregar @media |
| `styles/modules/imprimir_matricula.css` | ~100 | Print styles | 🔴 Crear | Nueva |

---

## TABLA 2: FUNCIONES CRÍTICAS

### Mantener Sin Cambios

| Función | Archivo | Líneas | Uso | Criticidad |
|---------|---------|--------|-----|-----------|
| `dragStart()` | builder | 42-46 | Preparar datos para drag | ⭐⭐⭐ |
| `generarIdUnicoBloque()` | builder | 129-135 | ID único para bloques | ⭐⭐⭐ |
| `insertarVariable()` | builder | 625-646 | Insertar chips dinámicos | ⭐⭐⭐ |
| `abrirCatalogoVariables()` | builder | 1135-1152 | Modal de variables | ⭐⭐⭐ |
| `guardarAjustesBloque()` | main | 99-158 | Guardar config bloques | ⭐⭐⭐ |
| `editarFormato()` | main | 199-275 | Cargar formato desde BD | ⭐⭐⭐ |
| `renderizador()` | imprimir | 286-569 | Generar HTML bloques | ⭐⭐⭐ |

### Refactorizar

| Función | Archivo | Líneas | Cambio | Tipo |
|---------|---------|--------|--------|------|
| `insertarBloqueEnCanvas()` | builder | 137-222 | Eliminar conv. MM→PX | Medio |
| `guardarFormato()` | builder | 650-879 | Leer MM del dataset | Alto |
| `arrastrarBloque()` | builder | 925-976 | Eliminar zoom | Medio |
| `iniciarRedimension()` | builder | 988-1107 | Simplificar scale | Medio |
| `insertarBloqueDesdeJSON()` | builder | 475-565 | Esperar MM en JSON | Bajo |

### Eliminar

| Función | Archivo | Líneas | Razón |
|---------|---------|--------|-------|
| `obtenerEscalaLienzo()` | builder | 885-896 | Zoom innecesario |
| `diagnostico_formatos.php` | scratch | 1-198 | Debug helper |
| `apply_formatos_matricula_patch.php` | scratch | 1-80+ | Patch antiguo |
| `migrar_formatos_multipaper.php` | scratch | ? | Legacy migration |

---

## TABLA 3: VARIABLES GLOBALES

| Variable | Archivo | Líneas | Tipo | Uso | Estado |
|----------|---------|--------|------|-----|--------|
| `contadorBloquesAres` | builder | 129-130 | Global | ID único | ✅ Mantener |
| `activeConfigNode` | main | 41-42 | Global | Config modal | ✅ Mantener |
| `lastSavedRange` | builder | 603-604 | Global | Selección texto | ✅ Mantener |
| `activeRangeBeforeModal` | builder | 1128-1129 | Global | Rango modal | ✅ Mantener |
| `activeEditableBeforeModal` | builder | 1131-1132 | Global | Editable modal | ✅ Mantener |
| `bloqueArrastrando` | builder | 882-883 | Global | Estado drag | ✅ Mantener |

**Nota:** Todas las variables globales son necesarias. La refactorización NO las elimina.

---

## TABLA 4: CONSTANTES A AGREGAR

### Configuración de Unidades

```javascript
const UNIT_CONFIG = {
    // Conversión Pantalla
    MM_TO_PX: 3.78,
    PX_TO_MM: 1 / 3.78,
    
    // Tamaños de Canvas
    CANVAS_WIDTH_PX: 816,
    CANVAS_HEIGHT_PX: 1056,
    CANVAS_WIDTH_MM: 215.9,   // A4
    CANVAS_HEIGHT_MM: 279.4,  // A4
    
    // DPI estándar
    DPI: 96,
    MM_PER_INCH: 25.4
};

// Helpers
function mmToPixels(mm) {
    return mm * UNIT_CONFIG.MM_TO_PX;
}

function pixelsToMm(px) {
    return px * UNIT_CONFIG.PX_TO_MM;
}

function getMargensInPixels() {
    const sup = parseFloat(document.getElementById('formato-margen-superior')?.value || 20);
    const inf = parseFloat(document.getElementById('formato-margen-inferior')?.value || 20);
    const izq = parseFloat(document.getElementById('formato-margen-izquierdo')?.value || 20);
    const der = parseFloat(document.getElementById('formato-margen-derecho')?.value || 20);
    return {
        superior: mmToPixels(sup),
        inferior: mmToPixels(inf),
        izquierdo: mmToPixels(izq),
        derecho: mmToPixels(der)
    };
}

function getMargensInMilimeters() {
    return {
        superior: parseFloat(document.getElementById('formato-margen-superior')?.value || 20),
        inferior: parseFloat(document.getElementById('formato-margen-inferior')?.value || 20),
        izquierdo: parseFloat(document.getElementById('formato-margen-izquierdo')?.value || 20),
        derecho: parseFloat(document.getElementById('formato-margen-derecho')?.value || 20)
    };
}
```

**Ubicación:** Agregar después de línea 4 en `js/modules/formatos_matricula_builder.js`

---

## TABLA 5: ESTRUCTURA JSON - ANTES Y DESPUÉS

### ANTES (Inconsistente - PX Sin Sincronización)

```json
{
  "type": "titulo_colegio",
  "left": 150,
  "top": 50,
  "width": 400,
  "height": null,
  "scale": 1.0,
  "size": "20",
  "content": "<h3>...</h3>"
}
```

**Problemas:**
- Posiciones en PX pero sin documentación clara
- Impresión convierte con factor diferente
- Scale acumulativa confunde redimensión

### DESPUÉS (Milímetros Puros)

```json
{
  "type": "titulo_colegio",
  "left_mm": 25.0,
  "top_mm": 13.2,
  "width_mm": 160.0,
  "height_mm": null,
  "unit": "mm",
  "size": "20",
  "content": "<h3>...</h3>"
}
```

**Ventajas:**
- Unidades explícitas en nombre de propiedad
- Sin conversiones (directo a CSS en impresión)
- Escalable a otros tamaños de papel

### Campos Opcionales por Tipo

| Tipo | Campos Específicos |
|------|------------------|
| `calificaciones` | `diseno`, `filtro`, `columnas` |
| `firmas` | `columnas`, `firmas_data` |
| `texto` | `content`, `align` |
| Otros | — |

---

## TABLA 6: CONVERSIÓN DE COORDENADAS

### Fórmula Actual (CON ZOOM)

```
Editor PX = (Margen MM × 3.78) + (Posición PX)
Guardado = Editor PX (¡SIN CONVERTIR!)
Impresión = Guardado PX × (215.9 / 816) = Guardado PX × 0.264583
```

**Problema:** `1/3.78 ≠ 0.264583` → Desincronización

### Fórmula Refactorizada

```
Editor:
  - Input: Margen MM (20)
  - Pantalla: 20 × 3.78 = 75.6 PX (visual solo)
  - Guardado: 20 MM (en dataset)

BD:
  - JSON: { left_mm: 25, top_mm: 13.2, ... }

Impresión:
  - CSS: left: 25mm; (navegador aplica directo)
  - Print: 25 mm en papel físico
```

**Ventaja:** Sin conversiones, una sola métrica

---

## TABLA 7: DATASET HTML - CONVENCIÓN

### ANTES (Confuso)

```javascript
wrapper.dataset.left = 150;       // PX
wrapper.dataset.top = 50;         // PX
wrapper.dataset.width = 400;      // PX
wrapper.dataset.scale = 1.2;      // Factor (confuso)
wrapper.style.left = left + 'px'; // CSS inline
```

### DESPUÉS (Claro)

```javascript
wrapper.dataset.left_mm = 25.0;     // MM almacenado
wrapper.dataset.top_mm = 13.2;      // MM almacenado
wrapper.dataset.width_mm = 160.0;   // MM almacenado
wrapper.dataset.bloque = 'titulo';  // Tipo (mantener)
wrapper.style.left = mmToPixels(25.0) + 'px'; // CSS solo visual
```

**Convención:** `data-*_mm` para milímetros almacenados, `style.*` solo para pantalla

---

## TABLA 8: CAMBIOS EN BD

### Estructura Actual (Compatible)

```sql
CREATE TABLE formatos_matricula (
  id INT,
  nombre VARCHAR,
  descripcion TEXT,
  contenido_html LONGTEXT,
  configuracion_json LONGTEXT,  -- ← Cambio aquí
  margen_superior INT DEFAULT 20,  -- En MM
  margen_inferior INT DEFAULT 20,  -- En MM
  margen_izquierdo INT DEFAULT 20, -- En MM
  margen_derecho INT DEFAULT 20,   -- En MM
  tipo_documento VARCHAR DEFAULT 'matricula',
  tamano_lienzo VARCHAR DEFAULT 'carta',
  ...
);
```

**Cambio en JSON:**
- Antes: `left`, `top`, `width` (PX no documentados)
- Después: `left_mm`, `top_mm`, `width_mm` (MM explícito)

**Compatibilidad:** Leer antiguo JSON y convertir automáticamente

```php
// En imprimir_matricula.php:
if (isset($jsonBlock['left'])) {
    // Formato antiguo: convertir PX→MM
    $jsonBlock['left_mm'] = $jsonBlock['left'] * (215.9 / 816);
}
```

---

## TABLA 9: BLOQUES SOPORTADOS

| Tipo | Posición | Ancho | Alto | Config | Notas |
|------|----------|-------|------|--------|-------|
| `texto` | Absoluto | ✅ Variable | Auto | align | Texto libre editable |
| `logo` | Absoluto | ✅ Variable | Auto | — | Imagen escuela |
| `titulo_colegio` | Absoluto | ✅ Auto | Auto | size | Nombre institución |
| `lema_colegio` | Absoluto | ✅ Auto | Auto | — | Motto escuela |
| `foto_estudiante` | Absoluto | ✅ Variable | ✅ Fijo | — | Foto 3×4 |
| `qr_estudiante` | Absoluto | ✅ Variable | Auto | — | QR validación |
| `metadatos` | Absoluto | ✅ Variable | Auto | size | "MATRÍCULA AÑO..." |
| `ficha` | Absoluto | ✅ Ancho máximo | Auto | — | Tabla datos estudiante |
| `calificaciones` | Absoluto | ✅ Ancho máximo | Auto | diseno, filtro, columnas | Tabla notas |
| `firmas` | Absoluto | ✅ Ancho máximo | Auto | columnas, firmas_data | Firmas rector/acudiente |
| `linea` | Absoluto | ✅ Ancho máximo | 1.5pt | height | Línea divisoria |
| `texto_certificacion` | Absoluto | ✅ Variable | Auto | — | Párrafo certificación |

---

## TABLA 10: PUNTOS DE INTEGRACIÓN

### API Backend

```
POST /php/logica/formatos_ajax.php
  action=guardar      → INSERT/UPDATE (aceptar JSON en MM)
  action=obtener      → SELECT (devolver JSON en MM)
  action=listar       → SELECT * (devolver lista)
  action=eliminar     → DELETE
  action=cambiar_estado → UPDATE
```

### Impresión

```
GET /imprimir_matricula.php?estudiante_id=X&formato_id=Y
  → Lectura: JSON (MM) + márgenes (MM)
  → Renderizado: CSS con unidades MM
  → Print: @media print (navegador maneja)
```

### Guardado

```
Editor → JSON (MM) → FormData → API → BD
  ├─ JSON: configuracion_json (contiene left_mm, top_mm, width_mm)
  ├─ Márgenes: 4 campos separados (superior, inferior, izquierdo, derecho)
  ├─ HTML: contenido_html (HTML compilado de bloques)
  └─ Metadata: tipo_documento, tamano_lienzo
```

---

## TABLA 11: CHECKLIST DE TESTING

### Test Unitarios (Por Función)

- [ ] `mmToPixels(20)` → 75.6
- [ ] `pixelsToMm(75.6)` → 20
- [ ] `getMargensInPixels()` → { superior: 75.6, ... }
- [ ] `getMargensInMilimeters()` → { superior: 20, ... }

### Test de Editor

- [ ] Crear bloque → dataset tiene `left_mm`, `top_mm`, `width_mm`
- [ ] Arrastrar bloque → dataset actualiza MM
- [ ] Redimensionar bloque → width_mm cambia
- [ ] Guardar formato → JSON tiene campos MM (no PX)
- [ ] Cargar formato → Bloques se posicionan correctamente

### Test de Impresión

- [ ] Imprimir estudiante → posiciones en mm correctas
- [ ] Márgenes → respetados en papel
- [ ] Bloques dinámicos → tamaño correcto en print
- [ ] Salto de página → si aplica

### Test de Compatibilidad

- [ ] Cargar formato antiguo (PX) → convierte a MM
- [ ] Editar formato antiguo → guarda en MM
- [ ] Impresión antigua → funciona sin cambios

---

## TABLA 12: ERRORES COMUNES A EVITAR

| Error | Ubicación | Síntoma | Solución |
|-------|-----------|---------|----------|
| Usar PX en dataset | builder | dataset.left en lugar de dataset.left_mm | Usar siempre `_mm` |
| Olvidar conversión visual | builder | bloques no aparecen en pantalla | Usar mmToPixels() en style |
| Duplicar márgenes | impresión | márgenes aplicados 2× | NO restar márgenes de posiciones |
| Zoom en arrastre | builder | bloque se mueve errado con zoom | Eliminar zoomFactor, usar PX directo |
| Scale acumulativa | redimensión | font-size crece infinito | Usar ratio simple (newW/startW) |
| JSON antiguo quebrado | carga | bloque no carga | Verificar conversión en insertarBloqueDesdeJSON() |

---

## TABLA 13: HERRAMIENTAS DE DEBUG

### Verificar Dataset

```javascript
// En consola F12:
document.querySelector('.canvas-block-wrapper').dataset
// Debe mostrar: { bloque: '...', left_mm: '25.0', top_mm: '13.2', width_mm: '160.0' }
```

### Verificar JSON en BD

```sql
SELECT id, nombre, JSON_EXTRACT(configuracion_json, '$[0].left_mm') as left_mm 
FROM formatos_matricula 
WHERE id = 1;
```

### Verificar CSS en Impresión

```javascript
// En F12 → Elements, inspeccionar bloque impreso:
// Debe tener: style="left: 25mm; top: 13.2mm; ..."
// NO debe tener: style="left: 94.5px; ..."
```

### Log de Guardado

```javascript
// En guardarFormato(), antes de fetch:
console.log('📍 Configuración JSON:', configJson);
console.log('📐 Márgenes MM:', getMargensInMilimeters());
// Verificar que posiciones están en MM
```

---

## REFERENCIAS RÁPIDAS

### DPI y Conversión

```
Estándar: 96 DPI (web)
1 inch = 25.4 mm
1 inch = 96 px

Fórmula:
  1 mm = 96 / 25.4 = 3.78 px ✅

Verificación:
  A4 width = 210 mm = 210 × 3.78 = 793.8 px ≈ 816 px (con ajustes)
```

### Tamaños de Papel Soportados

| Nombre | Ancho × Altura | CSS |
|--------|---|---|
| Carta (A4) | 215.9 × 279.4 mm | `size: A4;` |
| Media Carta | 139.7 × 215.9 mm | `size: A5;` |
| Carné V | 85.6 × 128 mm | `size: 85.6mm 128mm;` |
| Carné H | 128 × 85.6 mm | `size: 128mm 85.6mm;` |

---

**Documento:** Referencia Técnica  
**Versión:** 1.0  
**Destinatario:** Equipo Desarrollo  
**Última Actualización:** 2026-08-12

