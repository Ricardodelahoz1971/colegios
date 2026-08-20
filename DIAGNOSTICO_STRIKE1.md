# 🔴 DIAGNÓSTICO PRECISO - STRIKE 1

**Estado:** Análisis completado  
**Fecha:** 17 de Agosto de 2026  
**Nivel:** Crítico - Milimétricos

---

## ⚠️ PROBLEMA 1: NO GUARDA MODIFICACIONES

### Síntoma
Usuario modifica `fontSize` o `color` → guarda → recarga → vuelven valores iniciales

### Causa Raíz
**Archivo:** `js/modules/formatos_matricula_builder.js:1284-1291`

```javascript
if (tipo === 'titulo_colegio' || tipo === 'lema_colegio' || tipo === 'metadatos') {
    const defaultSize = (tipo === 'titulo_colegio') ? '20' : ...;
    const size = bloque.dataset.size || defaultSize;  // ← Solo lee size
    const align = bloque.dataset.align || 'center';   // ← Solo lee align
    extraAttrs += ` data-size="${size}" data-align="${align}"`;
    jsonBlock.size = size;
    jsonBlock.align = align;
    // ❌ NO GUARDA: color, fontWeight, textTransform, padding_mm
}
```

**Falta:** En `configJson.push()`, **NO hay propiedades de estilo** para `titulo_colegio`

### Solución
Agregar al JSON guardado:
```javascript
jsonBlock.color = bloque.dataset.style_color || null;
jsonBlock.fontSize = bloque.dataset.style_fontSize || null;
```

---

## ⚠️ PROBLEMA 2: NO GUARDA MÁRGENES

### Síntoma
Usuario modifica márgenes (superior, inferior, etc.) → guarda → se pierden

### Causa Raíz
**Archivo:** `js/modules/formatos_matricula_builder.js:1184-1187`

```javascript
const margen_superior = document.getElementById('formato-margen-superior').value;
const margen_inferior = document.getElementById('formato-margen-inferior').value;
const margen_izquierdo = document.getElementById('formato-margen-izquierdo').value;
const margen_derecho = document.getElementById('formato-margen-derecho').value;
```

✅ Se leen correctamente en línea 1325-1326
✅ Se envían al servidor en FormData (línea 1337-1340)
❓ **Pregunta:** ¿El backend (`formatos_ajax.php`) está guardando estos valores en BD?

### Hipótesis
El servidor recibe los márgenes pero **no los actualiza en la tabla `formatos_matricula`**

### Validación Necesaria
Revisar `php/logica/formatos_ajax.php` línea ~INSERT/UPDATE para `margen_superior`, etc.

---

## ⚠️ PROBLEMA 3: DOBLE CAJA (PADDING DUPLICADO)

### Síntoma
- Canvas builder: se ve una caja con padding
- Impresión: se ve OTRA caja adicional
- **Total:** 1mm (desde CSS) + 1mm (desde JS) = 2mm

### Causa Raíz 1: CSS
**Archivo:** `styles/modules/formatos_matricula.css:276`

```css
.canvas-block-wrapper {
    padding: 0;  // ← CSS tiene padding: 0, CORRECTO
    ...
}
```

**Pero**: El `.ares-titulo-cabecera` tiene propiedades en línea 421-431, y podría haber herencia.

### Causa Raíz 2: JavaScript
**Archivo:** `js/modules/formatos_matricula_builder.js:182`

```javascript
function aplicarEstilosBase(bloque, tipo) {
    // ...
    bloque.style.padding = config.padding_mm + 'mm';  // ← AQUÍ aplica padding al wrapper
}
```

### Problema Preciso
**El padding se aplica en dos lugares:**
1. ✅ En el elemento `.ares-titulo-cabecera` (via CSS clase)
2. ❌ En el `.canvas-block-wrapper` contenedor (via JS inline)

**Resultado:** padding se duplica

### Solución
**Opción A:** Remover `bloque.style.padding` en `aplicarEstilosBase()` y dejar solo CSS  
**Opción B:** Remover padding de CSS y mantener solo JS  

**Recomendado:** Opción A (CSS > JS para mantenibilidad)

---

## ⚠️ PROBLEMA 4: PREVIEW SOLO EN DISEÑADOR

### Síntoma
- Canvas builder: se ve el `titulo_colegio` con estilos
- Impresión: **NO aparece** el nombre del colegio
- O aparece sin estilos

### Causa Raíz
**Archivo:** `imprimir_matricula.php:479-492`

La función `$renderizador('titulo_colegio')` genera HTML, pero:

1. ❓ ¿El `$renderizador` se está llamando en la salida final?
2. ❓ ¿El HTML generado se está incluyendo en el `.print-document`?

**Verificación:** Ver línea ~1114-1116 donde se renderiza

```php
<?php foreach ($bloques_paginador as $bloque): ?>
    <?php echo $bloque['html']; ?>
<?php endforeach; ?>
```

### Hipótesis
Los bloques se procesan correctamente en PASO 1-2, pero:
- ❓ El contenido de `$bloque['html']` **no contiene el renderizado de titulo_colegio**
- ❓ O se está usando un bloque-backend-html en lugar de bloque-avanzado

---

## 📋 Plan de Arreglo (Orden Milimétrico)

### **Paso 1:** Eliminar padding duplicado
- Remover `bloque.style.padding` de `aplicarEstilosBase()`
- CSS ya maneja el padding via clases

### **Paso 2:** Guardar propiedades de estilo
- Agregar `color` y `fontSize` al JSON de guardado
- Restaurarlos al cargar desde BD

### **Paso 3:** Validar guardado de márgenes
- Revisar `formatos_ajax.php` para verificar INSERT/UPDATE

### **Paso 4:** Validar renderización en impresión
- Verificar que `$renderizador` se ejecuta
- Verificar que HTML se incluye en `$bloques_paginador`

---

## ✅ Diagnóstico = 4 cambios puntuales

No hay que reescribir código. Solo **4 puntos quirúrgicos**:
1. Remover una línea en `aplicarEstilosBase()`
2. Agregar 2 líneas en `guardarFormato()` 
3. Verificar backend (`formatos_ajax.php`)
4. Verificar renderización en `imprimir_matricula.php`

---

**Procesor a arreglos milimétricos, Ingeniero.**
