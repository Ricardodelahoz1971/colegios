# PLAN DETALLADO DE REFACTORIZACIÓN A MILÍMETROS

**Fecha:** 2026-08-12  
**Versión:** 2.0 (Plan Ejecutable)  
**Destinatario:** Ingeniero Ricardo  
**Urgencia:** ALTA  
**Estimación:** 3-5 días de desarrollo dedicado

---

## DIAGRAMA DE FLUJO ARQUITECTÓNICO OBJETIVO

```
┌─────────────────────────────────────────────────────────────────────┐
│                   ARQUITECTURA MILÍMETROS - OBJETIVO                 │
└─────────────────────────────────────────────────────────────────────┘

EDITOR (MILÍMETROS PUROS):
───────────────────────────
  ┌─ CANVAS: Viewport CSS (Tamaño Físico A4 = 210×297 mm en pantalla)
  │
  ├─ ENTRADA: Márgenes en MM (input HTML)
  │  └─ margen_superior: 20 mm
  │  └─ margen_inferior: 20 mm
  │  └─ margen_izquierdo: 20 mm
  │  └─ margen_derecho: 20 mm
  │
  ├─ BLOQUES: Posiciones en MM (absoluto al papel)
  │  ├─ left: 25 mm (distancia desde borde izquierdo)
  │  ├─ top: 35 mm (distancia desde borde superior)
  │  ├─ width: 160 mm
  │  ├─ height: auto ó X mm
  │  └─ (sin zoom, sin escala)
  │
  └─ JSON GUARDADO:
     {
       "type": "titulo_colegio",
       "left_mm": 25,
       "top_mm": 35,
       "width_mm": 160,
       "unit": "mm"
     }


BD (ALMACENAMIENTO):
────────────────────
  ┌─ configuracion_json
  │  └─ Array de bloques con posiciones en MM
  │
  └─ margen_superior, inferior, izquierdo, derecho
     └─ Valores en MM


IMPRESIÓN (DIRECTO A PDF/PAPEL):
─────────────────────────────────
  ┌─ Lectura: left_mm, top_mm, width_mm (directo)
  │
  ├─ CSS: position: absolute; left: 25mm; top: 35mm;
  │
  └─ Print: Soporte nativo de @media print (sin conversiones)
```

### Ventajas de Este Diseño

1. **Sin conversiones:** Todo está en la unidad final (mm)
2. **WYSIWYG perfecto:** Lo que ves en pantalla es lo que imprimes
3. **Sincronización automática:** No hay desajustes entre editor e impresión
4. **Mantenible:** Una sola métrica (mm) en todo el flujo
5. **Escalable:** Agregar más tamaños de papel es trivial

---

## CÁLCULOS MATEMÁTICOS FINALES

### Paso 1: Conversión de Escala de Pantalla

**Contexto:**
- Papel Carta: 215.9 mm × 279.4 mm
- Canvas A4 en pantalla: 816 px × 1056 px (ajustado a 96 DPI)
- **Factor:** 1 mm = 3.78 px (para visualización en pantalla)

**Fórmula:**
```
px_a_mm = 1 / 3.78 ≈ 0.2645 mm
mm_a_px = 3.78 px

Pero esto SOLO para pantalla. En la BD y JSON: guardar en MM
```

### Paso 2: Márgenes

**En editor (pantalla):**
```
margen_superior_input = 20 mm (input HTML)
margen_superior_px = 20 * 3.78 = 75.6 px (solo para cálculos visuales)
```

**En BD:**
```
margen_superior = 20 mm (almacenado como número)
```

**En impresión:**
```
CSS: margin-top: 20mm; (navegador aplica directo)
```

### Paso 3: Posiciones de Bloques

**En editor (pantalla):**
```
block.left_mm = 25 mm (del JSON guardado)
block.left_px = 25 * 3.78 = 94.5 px (solo para CSS del editor)
CSS: style="left: 94.5px;" (en el editor)
JSON guardado: { "left_mm": 25 }
```

**En impresión:**
```
CSS: position: absolute; left: 25mm; (directo, sin conversión)
```

---

## REFACTORIZACIÓN PASO A PASO

### PASO 1: Preparar Estructura de Datos

**Archivo:** `js/modules/formatos_matricula_builder.js`

**Antes de empezar:**
- Hacer backup: `git checkout -b refactor/formatos-mm`
- Todos los cambios se hacen en esta rama

#### Tarea 1.1: Identificar Entrada de Márgenes

**Ubicación:** `php/vistas/formatos_matricula.php` (NO leída completa)

```html
<!-- En la vista, agregar atributos data-* para guardar MM -->
<input type="number" id="formato-margen-superior" value="20" data-unit="mm">
```

**Acción:** Verificar que los inputs tienen `value` en MM, no en PX.

#### Tarea 1.2: Crear Helper de Conversión (Pantalla)

**Agregar en `js/modules/formatos_matricula_builder.js`:**

```javascript
// NUEVO - Agregar al inicio (después de línea 4)
const UNIT_CONFIG = {
    MM_TO_PX: 3.78,      // Para visualización pantalla
    PX_TO_MM: 1 / 3.78,  // Para cálculos inversos
    CANVAS_WIDTH_PX: 816,
    CANVAS_HEIGHT_PX: 1056,
    CANVAS_WIDTH_MM: 215.9,
    CANVAS_HEIGHT_MM: 279.4
};

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

**Ubicación:** Agregar ANTES de la función `initFormatosBuilder()` (línea 5)

---

### PASO 2: Refactor de `insertarBloqueEnCanvas()`

**Archivo:** `js/modules/formatos_matricula_builder.js`

**Líneas:** 137-222

**Cambios:**

```javascript
// ❌ ELIMINAR LÍNEAS 150-156 (conversión MM→PX)
// const factorMmPx = 3.78;
// const margenSup = Math.round((...) * factorMmPx);
// etc.

// ✅ REEMPLAZAR CON:
function insertarBloqueEnCanvas(codigo, label, x, y) {
    const canvas = document.getElementById('canvas-builder');
    const idUnico = generarIdUnicoBloque();
    
    const wrapper = document.createElement('div');
    wrapper.className = 'canvas-block-wrapper animate__animated animate__fadeIn';
    wrapper.id = idUnico;
    wrapper.dataset.bloque = codigo;
    
    const emptyState = document.getElementById('canvas-empty-state');
    if (emptyState) emptyState.remove();

    // ✅ USAR MÁRGENES EN MM (NO CONVERTIR)
    const margenes = getMargensInMilimeters();
    const margenesPx = getMargensInPixels();
    
    const canvasW = canvas.offsetWidth || UNIT_CONFIG.CANVAS_WIDTH_PX;
    const canvasH = canvas.offsetHeight || UNIT_CONFIG.CANVAS_HEIGHT_PX;
    
    // Ancho base en PX (para visualización)
    let estW = 300;
    if (codigo === 'logo' || codigo === 'qr_estudiante') estW = 110;
    if (codigo === 'foto_estudiante') estW = 120;
    if (codigo === 'titulo_colegio') estW = 400;
    if (codigo === 'lema_colegio') estW = 360;
    if (codigo === 'metadatos') estW = 500;
    if (codigo === 'ficha' || codigo === 'calificaciones' || codigo === 'texto_certificacion' || codigo === 'linea') 
        estW = canvasW - margenesPx.izquierdo - margenesPx.derecho;
    if (codigo === 'firmas') 
        estW = canvasW - margenesPx.izquierdo - margenesPx.derecho - 100;
    
    const maxAnchoSeguro = canvasW - margenesPx.izquierdo - margenesPx.derecho;
    if (estW > maxAnchoSeguro) estW = maxAnchoSeguro;
    
    const cabeceraAbierta = document.getElementById('switch-edicion-cabecera')?.checked;
    const estH = codigo === 'foto_estudiante' ? 140 : (codigo === 'linea' ? 2 : 90);

    // Posicionamiento (en PX para pantalla)
    if (x === undefined || y === undefined) {
        x = Math.max(margenesPx.izquierdo, Math.round((canvasW - estW) / 2));
        if (cabeceraAbierta) {
            y = 38; // 1cm
        } else {
            y = margenesPx.superior + 15;
        }
    }

    const maxLeft = Math.max(margenesPx.izquierdo, canvasW - margenesPx.derecho - estW);
    x = Math.max(margenesPx.izquierdo, Math.min(x, maxLeft));
    
    if (cabeceraAbierta) {
        const maxTop = Math.max(margenesPx.superior, 250);
        y = Math.max(38, Math.min(y, maxTop));
        wrapper.classList.remove('header-locked');
    } else {
        y = Math.max(margenesPx.superior, y);
        if (y < margenesPx.superior) {
            wrapper.classList.add('header-locked');
        }
    }
    
    x = Math.round(x);
    y = Math.round(y);
    
    wrapper.style.left = x + 'px';
    wrapper.style.top = y + 'px';
    wrapper.style.width = estW + 'px';
    
    // ✅ GUARDAR EN MM (NO EN PX)
    wrapper.dataset.left_mm = pixelsToMm(x).toFixed(2);
    wrapper.dataset.top_mm = pixelsToMm(y).toFixed(2);
    wrapper.dataset.width_mm = pixelsToMm(estW).toFixed(2);
    
    // ... resto del código (HTML de bloques) se mantiene igual ...
}
```

**Resumen de cambios:**
- Eliminar conversión hardcodeada de MM→PX
- Usar helpers `getMargensInMilimeters()` y `getMargensInPixels()`
- Guardar en dataset `left_mm`, `top_mm`, `width_mm` (NO `left`, `top`, `width`)
- CSS de pantalla sigue usando PX (transparente al usuario)

---

### PASO 3: Refactor de `guardarFormato()`

**Archivo:** `js/modules/formatos_builder.js`

**Líneas:** 650-879

**Cambios clave:**

```javascript
async function guardarFormato(e) {
    e.preventDefault();
    
    const id = document.getElementById('formato-id').value;
    const nombre = document.getElementById('formato-nombre').value;
    // ... resto de validaciones ...

    const canvas = document.getElementById('canvas-builder');
    const configJson = [];
    
    const bloques = canvas.querySelectorAll('.canvas-block-wrapper');
    bloques.forEach(bloque => {
        const tipoBloque = bloque.dataset.bloque;

        // ✅ LEER POSICIONES YA EN MM (del dataset)
        const left_mm = parseFloat(bloque.dataset.left_mm) || 0;
        const top_mm = parseFloat(bloque.dataset.top_mm) || 0;
        const width_mm = parseFloat(bloque.dataset.width_mm) || null;

        if (tipoBloque === 'texto') {
            const contenidoCaja = bloque.querySelector('.block-content-texto').cloneNode(true);
            
            // ... limpieza de chips (MANTENER) ...
            
            configJson.push({
                type: 'texto',
                left_mm: left_mm,
                top_mm: top_mm,
                width_mm: width_mm,
                content: contenidoCaja.innerHTML
            });
        } else {
            // Para bloques avanzados
            const htmlBackend = bloque.querySelector('.bloque-backend-html');
            if (htmlBackend) {
                const tipo = htmlBackend.dataset.type;
                
                const jsonBlock = {
                    type: tipo,
                    left_mm: left_mm,
                    top_mm: top_mm,
                    width_mm: width_mm,
                    content: bloque.querySelector('.block-content-wysiwyg')?.innerHTML || null
                };
                
                // ... configuraciones específicas (mantener) ...
                configJson.push(jsonBlock);
            }
        }
    });

    // ✅ Enviar configuración en MM (SIN CONVERSIÓN)
    const formData = new FormData();
    formData.append('action', 'guardar');
    formData.append('id', id);
    formData.append('nombre', nombre);
    formData.append('descripcion', descripcion);
    formData.append('margen_superior', getMargensInMilimeters().superior);
    formData.append('margen_inferior', getMargensInMilimeters().inferior);
    formData.append('margen_izquierdo', getMargensInMilimeters().izquierdo);
    formData.append('margen_derecho', getMargensInMilimeters().derecho);
    formData.append('tipo_documento', tipoDocumento);
    formData.append('tamano_lienzo', tamanoLienzo);
    formData.append('contenido_html', htmlCompilado); // ⚠️ Ver Paso 4
    formData.append('configuracion_json', JSON.stringify(configJson));
    formData.append('csrf_token', window.CSRF_TOKEN || '');

    // ... resto del fetch (MANTENER) ...
}
```

**Resumen:**
- Leer posiciones del dataset (`left_mm`, `top_mm`, `width_mm`)
- NO hacer conversiones en compilación
- Enviar márgenes en MM directamente

---

### PASO 4: Refactor de `arrastrarBloque()`

**Archivo:** `js/modules/formatos_matricula_builder.js`

**Líneas:** 925-976

```javascript
function arrastrarBloque(e) {
    if (!bloqueArrastrando) return;
    
    const canvas = document.getElementById('canvas-builder');
    const canvasRect = canvas.getBoundingClientRect();
    
    // ✅ ELIMINAR: const zoomFactor = obtenerEscalaLienzo(canvas);
    // Usar PX directo sin zoom
    
    const margenesPx = getMargensInPixels();
    const canvasW = canvas.offsetWidth || UNIT_CONFIG.CANVAS_WIDTH_PX;
    
    // Posicionamiento simple (sin zoom)
    let left = (e.clientX - canvasRect.left) - offsetX;
    let top = (e.clientY - canvasRect.top) - offsetY;
    
    const blockW = bloqueArrastrando.offsetWidth;
    const blockH = bloqueArrastrando.offsetHeight;
    
    // Restricciones de márgenes (en PX)
    const cabeceraAbierta = document.getElementById('switch-edicion-cabecera')?.checked;
    const minLeft = margenesPx.izquierdo;
    const maxLeft = Math.max(margenesPx.izquierdo, canvasW - margenesPx.derecho - blockW);
    
    left = Math.max(minLeft, Math.min(left, maxLeft));
    
    let minTop = margenesPx.superior;
    if (cabeceraAbierta) {
        const maxTop = Math.max(margenesPx.superior, 250);
        top = Math.max(38, Math.min(top, maxTop));
    } else {
        top = Math.max(minTop, top);
    }
    
    left = Math.round(left);
    top = Math.round(top);
    
    bloqueArrastrando.style.left = left + 'px';
    bloqueArrastrando.style.top = top + 'px';
    
    // ✅ GUARDAR EN MM
    bloqueArrastrando.dataset.left_mm = pixelsToMm(left).toFixed(2);
    bloqueArrastrando.dataset.top_mm = pixelsToMm(top).toFixed(2);

    ajustarAlturaLienzo();
}
```

**Cambios:**
- Eliminar `zoomFactor`
- Usar directamente PX en cálculos visuales
- Guardar MM en dataset

---

### PASO 5: Refactor de Redimensión

**Archivo:** `js/modules/formatos_matricula_builder.js`

**Líneas:** 988-1107 (`iniciarRedimension()`)

```javascript
function iniciarRedimension(e, handleType, targetWrapper) {
    e.stopPropagation();
    e.preventDefault();
    const wrapper = (targetWrapper && targetWrapper.nodeType === Node.ELEMENT_NODE) ? targetWrapper : e.target.closest('.canvas-block-wrapper');
    if (!wrapper) return;
    
    const startWidth = wrapper.offsetWidth;
    const startHeight = wrapper.offsetHeight;
    const startX = e.clientX;
    const startY = e.clientY;
    const startLeft = parseFloat(wrapper.style.left) || 0;
    const startTop = parseFloat(wrapper.style.top) || 0;
    
    // ✅ ELIMINAR: wrapper.dataset.startAbsoluteScale = ...
    // ✅ NO guardar scale acumulativa
    
    const wysiwygNodes = Array.from(wrapper.querySelectorAll('.block-content-wysiwyg, .block-content-wysiwyg *')).filter(n => !n.classList?.contains('bloque-backend-html'));
    wysiwygNodes.forEach(node => {
        if (!node.dataset.baseFontSize) {
            node.dataset.baseFontSize = parseFloat(window.getComputedStyle(node).fontSize) || 14;
        }
    });
    
    const canvas = document.getElementById('canvas-builder');
    
    function redimensionar(moveEvent) {
        const dx = moveEvent.clientX - startX;
        const dy = moveEvent.clientY - startY;
        
        const margenesPx = getMargensInPixels();
        const canvasW = canvas.offsetWidth || UNIT_CONFIG.CANVAS_WIDTH_PX;
        const maxRightPos = canvasW - margenesPx.derecho;
        
        let newWidth = startWidth;
        if (handleType === 'br' || handleType === 'tr') {
            const limitWidth = maxRightPos - startLeft;
            newWidth = Math.min(limitWidth, startWidth + dx);
        } else if (handleType === 'bl' || handleType === 'tl') {
            const targetLeft = startLeft + dx;
            const newLeft = Math.max(margenesPx.izquierdo, targetLeft);
            const appliedDx = newLeft - startLeft;
            newWidth = startWidth - appliedDx;
            wrapper.style.left = newLeft + 'px';
            wrapper.dataset.left_mm = pixelsToMm(newLeft).toFixed(2);
        }
        newWidth = Math.max(50, newWidth);
        
        // ✅ SIMPLIFICAR: Font-size proporcional simple
        const scaleRatio = newWidth / startWidth;
        wysiwygNodes.forEach(node => {
            const baseSize = parseFloat(node.dataset.baseFontSize);
            const newSize = Math.max(4, baseSize * scaleRatio);
            node.style.setProperty('font-size', newSize + 'px', 'important');
        });
        
        wrapper.style.width = newWidth + 'px';
        wrapper.dataset.width_mm = pixelsToMm(newWidth).toFixed(2);
        
        ajustarAlturaLienzo();
    }
    
    function detenerRedimension() {
        document.removeEventListener('mousemove', redimensionar);
        document.removeEventListener('mouseup', detenerRedimension);
    }
    
    document.addEventListener('mousemove', redimensionar);
    document.addEventListener('mouseup', detenerRedimension);
}
```

**Cambios principales:**
- Eliminar `startAbsoluteScale`
- Simplificar escala a ratio simple (newWidth / startWidth)
- Guardar en dataset `width_mm` (NO `scale`)

---

### PASO 6: Refactor de `insertarBloqueDesdeJSON()`

**Archivo:** `js/modules/formatos_matricula_builder.js`

**Líneas:** 475-565

```javascript
function insertarBloqueDesdeJSON(jsonBlock) {
    const canvas = document.getElementById('canvas-builder');
    document.getElementById('canvas-empty-state')?.remove();

    // ✅ JSON AHORA VIENE EN MM
    const left_mm = parseFloat(jsonBlock.left_mm) || 50;
    const top_mm = parseFloat(jsonBlock.top_mm) || 50;
    const width_mm = jsonBlock.width_mm || null;

    // Convertir a PX solo para visualización en pantalla
    const left = mmToPixels(left_mm);
    const top = mmToPixels(top_mm);
    const width = width_mm ? mmToPixels(width_mm) : null;

    insertarBloqueEnCanvas(jsonBlock.type, null, left, top);

    const insertedNode = canvas.lastElementChild;

    // ✅ APLICAR MM (NO PX)
    insertedNode.dataset.left_mm = left_mm.toFixed(2);
    insertedNode.dataset.top_mm = top_mm.toFixed(2);
    insertedNode.style.left = left + 'px';
    insertedNode.style.top = top + 'px';

    if (width_mm) {
        insertedNode.dataset.width_mm = width_mm;
        insertedNode.style.width = width + 'px';
    }

    // ... resto del código (MANTENER) ...
}
```

**Cambio clave:**
- Esperar `left_mm`, `top_mm`, `width_mm` en el JSON (no `left`, `top`, `width`)
- Convertir a PX solo para CSS de pantalla

---

### PASO 7: Simplificar Impresión

**Archivo:** `imprimir_matricula.php`

**Líneas:** 604-652

```php
// En el regex callback para procesar bloques avanzados:

// ✅ EXTRAER POSICIONES EN MM (ya vienen así del JSON)
$x_mm = parseFloat($x);  // Ya está en MM
$y_mm = parseFloat($y);  // Ya está en MM
$w_mm = $w ? parseFloat($w) : null;

// ✅ MÁRGENES SE APLICAN VÍA CSS @media print
$margen_izq_mm = (float)($formato['margen_izquierdo'] ?? 20);
$margen_sup_mm = (float)($formato['margen_superior'] ?? 20);

// ✅ GENERAR CSS DIRECTO EN MM (sin conversiones)
$style_inline = "position: absolute; left: {$x_mm}mm; top: {$y_mm}mm; ";
if ($w_mm !== null) {
    $style_inline .= "width: {$w_mm}mm; ";
}
$style_inline .= "box-sizing: border-box; overflow: visible;";

// ✅ NO APLICAR MÁRGENES A POSICIONES
// Los márgenes se manejan en el CSS @page o en el body
```

**Cambios:**
- NO restar márgenes de posiciones (ya están calculados en el editor)
- Aplicar márgenes via CSS @page o via body margin
- Todos los valores en MM directo

---

### PASO 8: Agregar CSS para Impresión

**Archivo:** `styles/modules/imprimir_matricula.css` (crear si no existe)

```css
@media print {
    body {
        margin: 0;
        padding: 0;
    }
    
    .print-document {
        margin: 0;
        padding: 0;
    }
    
    @page {
        size: A4;
        margin: 20mm; /* Márgenes de la página físicas */
    }
    
    .ares-paper-sheet {
        position: relative;
        width: 210mm;  /* Ancho Carta */
        height: 297mm; /* Altura Carta */
        margin: 0;
        padding: 0;
        page-break-after: always;
    }
    
    /* Los bloques ya tienen posición absoluta en MM */
    [style*="left:"][style*="mm"] {
        /* El navegador aplica directo */
    }
}
```

---

## CHECKLIST DE EJECUCIÓN

### Pre-Refactorización
- [ ] Crear rama: `git checkout -b refactor/formatos-mm`
- [ ] Leer este documento completamente
- [ ] Hacer backup de BD
- [ ] Anotar formatos existentes (screenshot del listado)

### Paso 1: Estructura
- [ ] Agregar `UNIT_CONFIG` al inicio de builder
- [ ] Agregar helpers `mmToPixels()`, `pixelsToMm()`
- [ ] Agregar `getMargensInPixels()` y `getMargensInMilimeters()`
- [ ] Verificar que compila sin errores

### Paso 2: insertarBloqueEnCanvas()
- [ ] Reescribir función
- [ ] Cambiar dataset a `left_mm`, `top_mm`, `width_mm`
- [ ] Verificar que bloques se crean correctamente
- [ ] Test manual: agregar logo, verificar dataset

### Paso 3: guardarFormato()
- [ ] Cambiar lectura de posiciones (usar dataset MM)
- [ ] Cambiar JSON generado (usar MM)
- [ ] Test manual: guardar formato, verificar JSON en BD

### Paso 4: arrastrarBloque()
- [ ] Eliminar `zoomFactor`
- [ ] Cambiar dataset a MM
- [ ] Test manual: arrastra bloques, verifica posiciones

### Paso 5: Redimensión
- [ ] Reescribir `iniciarRedimension()`
- [ ] Eliminar `startAbsoluteScale`
- [ ] Test manual: redimensiona bloques

### Paso 6: insertarBloqueDesdeJSON()
- [ ] Cambiar a esperar MM en JSON
- [ ] Test manual: cargar formato antiguo (debe convertirse)

### Paso 7-8: Impresión
- [ ] Simplificar conversión en imprimir_matricula.php
- [ ] Agregar CSS @media print
- [ ] Test manual: imprimir, verificar posiciones en mm

### Testing Final
- [ ] [ ] Crear formato nuevo
- [ ] [ ] Agregar 5+ bloques de tipos diferentes
- [ ] [ ] Arrastra y redimensiona cada uno
- [ ] [ ] Guarda formato
- [ ] [ ] Carga formato existente
- [ ] [ ] Elimina y re-agrega bloques
- [ ] [ ] Imprime con estudiante real
- [ ] [ ] Verifica márgenes en impresión
- [ ] [ ] Verifica posiciones en milímetros

### Cleanup
- [ ] [ ] Eliminar `obtenerEscalaLienzo()` completa
- [ ] [ ] Buscar referencias a `zoom` en código
- [ ] [ ] Eliminar archivos scratch
- [ ] [ ] Actualizar CLAUDE.md
- [ ] [ ] Commit final: `git commit -m "refactor: formatos matrícula a milímetros"`

---

## ARCHIVO DE RESPALDO

**Antes de empezar, guardar la versión actual:**

```bash
cp js/modules/formatos_matricula_builder.js js/modules/formatos_matricula_builder.js.bak
cp js/formatos_matricula.js js/formatos_matricula.js.bak
cp imprimir_matricula.php imprimir_matricula.php.bak
cp php/logica/formatos_ajax.php php/logica/formatos_ajax.php.bak

git add *.bak
git commit -m "backup: versión anterior a refactor milímetros"
```

---

## VALIDACIÓN POST-REFACTOR

### En BD (MySQL)
```sql
SELECT id, nombre, configuracion_json FROM formatos_matricula WHERE id = (SELECT MAX(id) FROM formatos_matricula);

-- Debe mostrar JSON con campos left_mm, top_mm, width_mm
-- NO debe tener left, top, width en PX
```

### En Navegador (F12 Console)
```javascript
// Después de agregar un bloque:
document.querySelector('.canvas-block-wrapper').dataset
// Debe mostrar: { bloque: 'tipo', left_mm: '25', top_mm: '35', width_mm: '160' }
// NO debe mostrar: left, top, width
```

### En Impresión
```
- Abrir DevTools (F12)
- Tab "Elements"
- Inspeccionar bloque impreso
- Debe tener: style="left: 25mm; top: 35mm; ..."
- NO debe tener: style="left: 94.5px; ..."
```

---

## RIESGOS Y MITIGACIONES

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|-----------|
| Formatos antiguos no cargan | MEDIA | ALTO | `insertarBloqueDesdeJSON()` mantiene compatibilidad |
| Márgenes se aplican doble | BAJA | ALTO | Validar CSS @page NO suma márgenes de body |
| Impresión con posiciones incorrectas | BAJA | ALTO | Test de impresión 5+ formatos |
| Zoom real (si se agrega después) | BAJA | MEDIO | Documentar que zoom NO está soportado |

---

**Documento guardado en:** `claude_docs/PLAN_REFACTORIZACION_MILIMETROS.md`  
**Prioridad:** ALTA  
**Asignado a:** Equipo de Desarrollo  
**Revisión:** Ingeniero Ricardo

