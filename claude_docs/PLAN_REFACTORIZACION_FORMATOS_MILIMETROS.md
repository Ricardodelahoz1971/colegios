# 📋 PLAN DE REFACTORIZACIÓN: MÓDULO FORMATOS A MILÍMETROS

**Ingeniero Ricardo - Plan de Acción Completo**

---

## 🎯 OBJETIVO

Reconstruir el módulo de formatos de matrícula trabajando **TODO EN MILÍMETROS** desde el inicio, eliminando conversiones complicadas píxeles↔milímetros que causan desalineamientos.

---

## 📊 ANÁLISIS DE CÓDIGO ACTUAL

### ✅ QUÉ MANTENER (NO TOCAR)

| Componente | Ubicación | Razón | Estado |
|-----------|-----------|--------|--------|
| Tabla BD | `formatos_matricula` | Almacena formatos | FUNCIONA |
| API GET | `formatos_ajax.php` línea 28-46 | Obtiene formatos | FUNCIONA |
| API DELETE | `formatos_ajax.php` línea 98-116 | Elimina formatos | FUNCIONA |
| API LISTAR | `formatos_ajax.php` línea 20-26 | Lista formatos | FUNCIONA |
| API CAMBIAR_ESTADO | `formatos_ajax.php` línea 118-129 | Activa/desactiva | FUNCIONA |
| Endpoint POST | `formatos_ajax.php` línea 48-96 | Guardado básico | MODIFICAR LIGERAMENTE |

---

### ❌ QUÉ ELIMINAR (LIMPIEZA TOTAL)

#### Frontend - `js/formatos_matricula.js`
- **Líneas 1-50:** Inicializaciones - MANTENER
- **Líneas 17-95:** Funciones UI básicas - MANTENER
- **Líneas 199-273:** `editarFormato()` - **REESCRIBIR** (carga en mm)
- **TODO LO DEMÁS:** MANTENER

#### Frontend - `js/modules/formatos_matricula_builder.js`
**CRÍTICO - Eliminar estas funciones/lógica:**

```
ELIMINAR COMPLETAMENTE:
- Líneas 604-625: Conversión px→mm (factor_px_to_mm, cálculos)
- Líneas 698-701: parseInt/parseFloat de píxeles
- Líneas 727-728: Captura de posiciones en píxeles
- Líneas 845-871: Lógica de arrastre con píxeles
- Función obtenerEscalaLienzo() - Línea 867
- Cualquier referencia a "zoom" o "escala" visual
- Cualquier cálculo con "3.78" (factor mm→px)

REESCRIBIR COMPLETAMENTE:
- insertarBloqueDesdeJSON() - Línea 475 (cargar en mm)
- arrastrarBloque() - Línea 911 (arrastrar en mm)
- guardarFormato() - Línea 649 (guardar en mm)
- iniciarRedimension() - Línea 974 (redimensionar en mm)
```

#### Backend - `imprimir_matricula.php`
```
ELIMINAR COMPLETAMENTE:
- Líneas 604-648: Conversiones px→mm
- Variables: $factor_px_to_mm, $x_mm, $y_mm, $x_mm_ajustado
- Cálculos de márgenes complicados
- Transformaciones de escala

REESCRIBIR:
- Líneas 584-650: Procesamiento de bloques (usar mm directo)
```

---

### ⚠️ QUÉ REESCRIBIR (CORE VISUAL)

| Función | Ubicación | Cambio | Prioridad |
|---------|-----------|--------|-----------|
| `insertarBloqueDesdeJSON()` | formatos_matricula_builder.js:475 | Cargar valores en MM directos | ALTA |
| `arrastrarBloque()` | formatos_matricula_builder.js:911 | Arrastrar en MM, no px | ALTA |
| `guardarFormato()` | formatos_matricula_builder.js:649 | Enviar MM directo a BD | ALTA |
| `iniciarRedimension()` | formatos_matricula_builder.js:974 | Redimensionar en MM | MEDIA |
| Renderizado preview | imprimir_matricula.php:584 | Usar MM sin conversión | ALTA |

---

## 🧹 FASE 0: LIMPIEZA PREVIA (CRÍTICA)

Antes de reescribir, ejecutar limpieza:

### 1. Backup actual
```bash
cp -r js/modules/formatos_matricula_builder.js js/modules/formatos_matricula_builder.js.backup
cp imprimir_matricula.php imprimir_matricula.php.backup
```

### 2. Verificar código que se va a borrar
```bash
# Búsquedas para confirmar qué eliminar
grep -n "factor_px_to_mm\|0.264583\|factor.*3.78" js/modules/formatos_matricula_builder.js
grep -n "parseInt.*style.left\|parseFloat.*px" js/modules/formatos_matricula_builder.js
grep -n "obtenerEscalaLienzo\|zoomFactor" js/modules/formatos_matricula_builder.js
grep -n "factor_px_to_mm\|x_mm_ajustado" imprimir_matricula.php
```

### 3. Listar funciones a reescribir
```bash
grep -n "function insertarBloqueDesdeJSON\|function arrastrarBloque\|function guardarFormato\|function iniciarRedimension" js/modules/formatos_matricula_builder.js
```

---

## 📐 ESPECIFICACIONES TÉCNICAS

### Papel Carta
```
Ancho:  215.9 mm
Alto:   279.4 mm
Mínimo margen: 10 mm
```

### Unidades
- **EDITOR:** TODO en milímetros
- **BD:** TODO en milímetros
- **PREVIEW/IMPRESIÓN:** TODO en milímetros
- **NO CONVERSIONES:** Directas mm ↔ mm

### Bloques
- Posicionamiento: `left` (mm), `top` (mm)
- Dimensiones: `width` (mm), `height` (mm)
- Todos escalables
- Texto adaptativo (cambia tamaño del contenedor, no font-size)

### JSON Guardado
```json
{
  "type": "logo",
  "left": 113.5,    // milímetros (NO píxeles)
  "top": 38.2,      // milímetros
  "width": 27.8,    // milímetros (NO píxeles)
  "height": null,
  "content": "..."
}
```

---

## 🔨 FASE 1: PREPARACIÓN DEL EDITOR

### Paso 1.1: Eliminar conversiones y factores

**Archivo:** `js/modules/formatos_matricula_builder.js`

```javascript
// ELIMINAR ESTAS LÍNEAS:
const factor_px_to_mm = 0.264583;
const factor_px_to_mm = 3.78;
const zoomFactor = obtenerEscalaLienzo(canvas);
const zoomScale = obtenerEscalaLienzo(canvas);
/ Conversión exacta a mm (1px = 0.264583mm)
/ El canvas en el editor NO tiene los márgenes aplicados
/ Los píxeles en el editor YA incluyen posición desde el borde
```

### Paso 1.2: Eliminar funciones de zoom

```javascript
// ELIMINAR COMPLETAMENTE:
function obtenerEscalaLienzo(canvas) { ... }
```

### Paso 1.3: Limpiar lógica de arrastre

En función `arrastrarBloque()`:
- Eliminar todos los cálculos con `zoomFactor`
- Eliminar restricciones de márgenes (no aplican en editor)
- Las posiciones se guardan directamente en mm

---

## 🔌 FASE 2: REESCRIBIR EDITOR VISUAL

### Función: `insertarBloqueDesdeJSON(jsonBlock)`

**ANTES (con conversiones):**
```javascript
const left = parseFloat(jsonBlock.left) || 50;  // ¿en qué unidad?
const constrainedLeft = Math.max(margenIzq, Math.min(left, maxLeft));
insertedNode.style.left = constrainedLeft + 'px';
```

**DESPUÉS (puro milímetros):**
```javascript
const left_mm = parseFloat(jsonBlock.left) || 10;  // milímetros
const top_mm = parseFloat(jsonBlock.top) || 10;    // milímetros

// Convertir MM a PX SOLO PARA VISUALIZACIÓN EN NAVEGADOR
// 215.9 mm = 816 px (en nuestro canvas)
const factor_mm_a_px = 816 / 215.9;  // 3.782 px/mm

insertedNode.style.left = (left_mm * factor_mm_a_px) + 'px';
insertedNode.style.top = (top_mm * factor_mm_a_px) + 'px';

// GUARDAR los valores ORIGINALES en MM
insertedNode.dataset.left_mm = left_mm;
insertedNode.dataset.top_mm = top_mm;
```

### Función: `arrastrarBloque(e)`

**REESCRIBIR:** Usar SOLO milímetros internamente
```javascript
function arrastrarBloque(e) {
    if (!bloqueArrastrando) return;
    
    const factor_mm_a_px = 816 / 215.9;  // solo para visualización
    
    // Calcular posición en MM (nunca en píxeles)
    let left_mm = (e.clientX - rect.left) / factor_mm_a_px;
    let top_mm = (e.clientY - rect.top) / factor_mm_a_px;
    
    // Restricción: mínimo 10 mm desde borde
    left_mm = Math.max(10, left_mm);
    top_mm = Math.max(10, top_mm);
    
    // Guardar en MM
    bloqueArrastrando.dataset.left_mm = left_mm;
    bloqueArrastrando.dataset.top_mm = top_mm;
    
    // Visualizar en PX (solo para navegador)
    bloqueArrastrando.style.left = (left_mm * factor_mm_a_px) + 'px';
    bloqueArrastrando.style.top = (top_mm * factor_mm_a_px) + 'px';
}
```

### Función: `guardarFormato(e)`

**REESCRIBIR:** Enviar valores en MM a BD
```javascript
async function guardarFormato(e) {
    const configJson = [];
    const bloques = canvas.querySelectorAll('.canvas-block-wrapper');
    
    bloques.forEach(bloque => {
        // LEER VALORES EN MM (guardados en dataset)
        const left_mm = parseFloat(bloque.dataset.left_mm) || 10;
        const top_mm = parseFloat(bloque.dataset.top_mm) || 10;
        const width_mm = bloque.dataset.width_mm ? parseFloat(bloque.dataset.width_mm) : null;
        const height_mm = bloque.dataset.height_mm ? parseFloat(bloque.dataset.height_mm) : null;
        
        configJson.push({
            type: bloque.dataset.bloque,
            left: left_mm,      // MILÍMETROS
            top: top_mm,        // MILÍMETROS
            width: width_mm,    // MILÍMETROS
            height: height_mm,  // MILÍMETROS
            // ... otros datos
        });
    });
    
    // Enviar a servidor
    formData.append('configuracion_json', JSON.stringify(configJson));
    // ...
}
```

---

## 🖼️ FASE 3: REESCRIBIR PREVIEW/IMPRESIÓN

**Archivo:** `imprimir_matricula.php`

### Eliminar conversiones complicadas (líneas 604-648)

**DESPUÉS:** Usar directamente los milímetros
```php
// El JSON ya viene en milímetros desde el frontend
$x_mm = (float)$bloque['left'];     // Directo, sin conversión
$y_mm = (float)$bloque['top'];      // Directo, sin conversión
$w_mm = (float)$bloque['width'];    // Directo, sin conversión

// Aplicar márgenes directamente
$margen_izq = (float)($formato['margen_izquierdo'] ?? 10);
$margen_sup = (float)($formato['margen_superior'] ?? 10);

// Posición final: considerar márgenes
$left_final = $x_mm + $margen_izq;  // En MM
$top_final = $y_mm + $margen_sup;   // En MM

$style_inline = "position: absolute; left: {$left_final}mm; top: {$top_final}mm; ...";
```

---

## ✅ FASE 4: TESTING Y VALIDACIÓN

### Test 1: Guardado correcto
```
1. Editor: Crear formato, posicionar logo en left=50mm, top=20mm
2. BD: Verificar que `configuracion_json` tiene {"left":50, "top":20}
3. Resultado: ✅ PASS si coinciden valores en MM
```

### Test 2: Carga correcta
```
1. Cargar formato guardado
2. Editor: Verificar que logo aparece exactamente donde estaba
3. Resultado: ✅ PASS si posiciones coinciden
```

### Test 3: Preview sincronizado
```
1. Editor: Logo en left=50mm, top=20mm
2. Preview: Logo debe aparecer en EXACTAMENTE la misma posición
3. Resultado: ✅ PASS si no hay desalineamiento
```

### Test 4: Impresión correcta
```
1. Imprimir estudiante con formato
2. Medir posiciones físicas del logo en papel
3. Resultado: ✅ PASS si coinciden con milímetros especificados
```

---

## 📋 CHECKLIST DE EJECUCIÓN

- [ ] **FASE 0:** Backups creados, búsquedas verificadas
- [ ] **FASE 1:** Conversiones eliminadas, código limpio
- [ ] **FASE 2:** `insertarBloqueDesdeJSON()` reescrito
- [ ] **FASE 2:** `arrastrarBloque()` reescrito
- [ ] **FASE 2:** `guardarFormato()` reescrito
- [ ] **FASE 3:** `imprimir_matricula.php` simplificado
- [ ] **FASE 4:** Test 1 - Guardado ✅
- [ ] **FASE 4:** Test 2 - Carga ✅
- [ ] **FASE 4:** Test 3 - Preview ✅
- [ ] **FASE 4:** Test 4 - Impresión ✅
- [ ] **BONUS:** Salto de página automático
- [ ] **BONUS:** Repetición de cabecera en páginas

---

## ⚡ COMANDOS ÚTILES

```bash
# Ver líneas a eliminar
grep -n "factor_px_to_mm\|0.264583\|3.78\|zoomFactor" js/modules/formatos_matricula_builder.js | head -20

# Ver funciones principales
grep -n "^function" js/modules/formatos_matricula_builder.js | grep -E "insertarBloque|arrastrarBloque|guardarFormato|iniciarRedimension"

# Verificar cambios en BD
SELECT id, nombre, LEFT(configuracion_json, 100) FROM formatos_matricula WHERE nombre='Prueba';
```

---

## 🎯 NOTAS FINALES

- **BD:** No cambia, solo validar que almacena mm correctamente
- **API:** Mínimos cambios, solo validación
- **Frontend:** REESCRITURA TOTAL del visual (editor + preview)
- **Unidades:** SIEMPRE milímetros en BD y JSON
- **Visualización:** Solo para el navegador, convertir mm→px cuando sea necesario
- **No destruir:** Métodos de guardado/carga, estructura tabla

---

**Plan creado para Ingeniero Ricardo**  
**Respeta especificaciones: 215.9×279.4 mm, 10mm mín, TODO en MM**
