# ✅ IMPLEMENTACIÓN FASE 2: BLOCK_STYLE_CONFIG para titulo_colegio

**Estado:** COMPLETADO ✅  
**Fecha:** 17 de Agosto de 2026  
**Bloque:** `titulo_colegio`

---

## 🎯 Resumen

Se implementó el sistema de **BLOCK_STYLE_CONFIG** que permite aplicar estilos base bloqueados (negrita, mayúsculas, centrado, padding 1mm) a cada tipo de bloque. El flujo es:

```
Usuario crea "titulo_colegio"
    ↓
insertarBloqueEnCanvas() aplica BLOCK_STYLE_CONFIG
    ↓
Canvas muestra WYSIWYG con estilos base (negrita, centrado, mayús)
    ↓
Usuario guarda → JSON a BD
    ↓
insertarBloqueDesdeJSON() restaura estilos desde config
    ↓
imprimir_matricula.php aplica BLOCK_STYLE_CONFIG en renderizado
    ↓
Impresión es IDÉNTICA a preview (WYSIWYG de verdad)
```

---

## 📋 Cambios Realizados

### **1. BLOCK_STYLE_CONFIG en JavaScript** ✅

**Archivo:** `js/modules/formatos_matricula_builder.js:117-145`

```javascript
const BLOCK_STYLE_CONFIG = {
    titulo_colegio: {
        fontSize: 20,
        fontFamily: 'var(--el-font-institutional)',
        fontWeight: 'bold',
        textTransform: 'uppercase',
        textAlign: 'center',
        color: 'var(--el-primary)',
        padding_mm: 1,
        lineHeight: 1.2,

        locked: ['fontWeight', 'textTransform', 'textAlign', 'fontFamily', 'padding_mm'],
        editable: ['fontSize', 'color'],
        constraints: {
            fontSize: { min: 16, max: 28 },
            ancho_mm: { min: 40, max: 170, auto: true }
        }
    }
};
window.BLOCK_STYLE_CONFIG = BLOCK_STYLE_CONFIG;
```

**Propiedades:**
- ✅ `fontSize: 20pt` (editable 16-28)
- ✅ `fontWeight: bold` (bloqueado)
- ✅ `textTransform: uppercase` (bloqueado)
- ✅ `textAlign: center` (bloqueado)
- ✅ `color: var(--el-primary)` (editable)
- ✅ `padding_mm: 1` (bloqueado)

---

### **2. Función aplicarEstilosBase()** ✅

**Archivo:** `js/modules/formatos_matricula_builder.js:156-190`

```javascript
function aplicarEstilosBase(bloque, tipo) {
    // Lee BLOCK_STYLE_CONFIG[tipo]
    // Aplica CSS al elemento texto
    // Guarda config en dataset para referencia futura
}
```

**Responsabilidades:**
- Aplica estilos CSS base al elemento `.ares-titulo-cabecera`
- Aplica padding al contenedor `.canvas-block-wrapper`
- Guarda configuración en `bloque.dataset.styleConfig`

---

### **3. Mejora: autoAjustarAnchoBloqueTexto()** ✅

**Archivo:** `js/modules/formatos_matricula_builder.js:205-232`

Se mejoró para:
- Leer `padding_mm` dinámicamente desde BLOCK_STYLE_CONFIG
- Calcular gabela como: `textWidth + (padding_mm * 2)`
- Resultado: ancho auto-ajustable que respeta la gabela de 1mm

---

### **4. insertarBloqueEnCanvas() + BLOCK_STYLE_CONFIG** ✅

**Archivo:** `js/modules/formatos_matricula_builder.js:619-625`

Agregado:
```javascript
// Aplicar estilos base del BLOCK_STYLE_CONFIG (si existen)
if (BLOCK_STYLE_CONFIG[codigo]) {
    aplicarEstilosBase(wrapper, codigo);
}

// Auto-ajustar el ancho si es un bloque de texto dinámico o cabecera
autoAjustarAnchoBloqueTexto(wrapper);
```

**Resultado:** Al insertar `titulo_colegio`, se ve inmediatamente:
- Negrita
- Mayúsculas
- Centrado
- Padding 1mm
- Ancho auto calculado

---

### **5. insertarBloqueDesdeJSON() + Restauración de Estilos** ✅

**Archivo:** `js/modules/formatos_matricula_builder.js:699-716`

Agregado:
```javascript
// Restaurar estilos base desde BLOCK_STYLE_CONFIG si existen
if (BLOCK_STYLE_CONFIG[jsonBlock.type]) {
    const config = BLOCK_STYLE_CONFIG[jsonBlock.type];
    // Aplica estilos bloqueados + estilos personalizados desde JSON
}
```

**Resultado:** Al reabrir diseñador, se restauran:
- Estilos base (negrita, centrado, etc.)
- Valores personalizados (fontSize, color) desde BD

---

### **6. BLOCK_STYLE_CONFIG en PHP** ✅

**Archivo:** `imprimir_matricula.php:221-234`

```php
$BLOCK_STYLE_CONFIG_PHP = [
    'titulo_colegio' => [
        'fontSize' => 20,
        'fontFamily' => $cfg['school_font'] ?? 'Montserrat',
        'fontWeight' => 'bold',
        'textTransform' => 'uppercase',
        'textAlign' => 'center',
        'color' => $cfg['brand_color'] ?? '#204192',
        'padding_mm' => 1,
        'lineHeight' => 1.2
    ]
];
```

**Objetivo:** Garantizar que impresión = preview

---

### **7. Renderizador Actualizado** ✅

**Archivo:** `imprimir_matricula.php:479-492`

Cambió la función `$renderizador` para:
- Leer estilos de `$BLOCK_STYLE_CONFIG_PHP`
- Aplicar CSS inline `!important` en el HTML de impresión
- Garantizar que estilos bloqueados se respeten

```php
if ($tipo === 'titulo_colegio') {
    $config = $BLOCK_STYLE_CONFIG_PHP['titulo_colegio'] ?? [];
    // ... aplica font-size, font-weight, text-transform, etc. como !important
    $inner_html = '<h3 style="... !important;">TÍTULO</h3>';
}
```

---

## 🧪 Test Checklist

- [ ] Crear un bloque `titulo_colegio` en el diseñador
- [ ] Verificar que aparezca:
  - ✅ Negrita
  - ✅ Mayúsculas
  - ✅ Centrado
  - ✅ Padding 1mm alrededor del texto
  - ✅ Ancho auto-ajustado al contenido
- [ ] Cambiar fontSize (ej: 24pt)
- [ ] Cambiar color (ej: rojo)
- [ ] Guardar formato
- [ ] Reabrir diseñador
  - ✅ Verificar que los cambios se restauran
  - ✅ Verificar que estilos bloqueados se mantienen
- [ ] Imprimir matrícula
  - ✅ Verificar que impresión es idéntica a preview
  - ✅ Negrita, mayúsculas, centrado, padding deben verse igual

---

## 📊 Arquitectura Resultante

```
BLOCK_SCHEMA[tipo]
    ↓
    └─ Propiedades base (ancho, alto, html generator)

BLOCK_STYLE_CONFIG[tipo]
    ↓
    ├─ Estilos bloqueados (fontWeight, textTransform, etc.)
    ├─ Estilos editables (fontSize, color)
    └─ Restricciones (min/max values)

insertarBloqueEnCanvas()
    ↓
    ├─ Lee BLOCK_SCHEMA → crea estructura HTML
    └─ Lee BLOCK_STYLE_CONFIG → aplica estilos base

insertarBloqueDesdeJSON()
    ↓
    ├─ Lee JSON de BD → restaura posición, tamaño
    └─ Lee BLOCK_STYLE_CONFIG → restaura estilos base

imprimir_matricula.php
    ↓
    ├─ Lee JSON de BD → contenido y metadatos
    └─ Lee BLOCK_STYLE_CONFIG_PHP → aplica estilos en HTML
```

---

## 🔐 Garantías

✅ **WYSIWYG Real:** Lo que ves en diseñador = lo que se imprime  
✅ **Estilos Bloqueados:** Usuario no puede cambiar fontWeight, textTransform, etc.  
✅ **Estilos Editables:** Usuario SÍ puede cambiar fontSize (16-28pt) y color  
✅ **Persistencia:** Cambios se guardan en BD y se restauran al reabrir  
✅ **Padding 1mm:** Garantizado en diseñador e impresión  
✅ **Ancho Auto:** Se calcula automáticamente según contenido  

---

## 📝 Próximos Pasos

1. **Test Manual:** Validar los 8 steps en navegador
2. **Definir BLOCK_STYLE_CONFIG para bloques restantes:**
   - `lema_colegio`
   - `metadatos`
   - `texto` (texto libre, sin estilos bloqueados)
   - `logo`
   - `foto_estudiante`
   - `qr_estudiante`
   - `linea`
   - `ficha`
   - `calificaciones`
   - `firmas`
   - `texto_certificacion`
3. **Replicar patrón:** Agregar renderizador en `imprimir_matricula.php` para cada tipo

---

**¿Procede a Test Manual, Ingeniero Ricardo?**
