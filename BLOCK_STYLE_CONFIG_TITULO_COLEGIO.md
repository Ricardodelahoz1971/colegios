# 📐 BLOCK_STYLE_CONFIG: titulo_colegio

**Estado:** Propuesta de Fase 2  
**Bloque:** `titulo_colegio`  
**Alcance:** Definir la base de diseño de este tipo de bloque

---

## 📋 Definición Base

### **Bloque: NOMBRE DEL COLEGIO**

Representa el título principal del colegio en documentos de matrícula, certificados, etc.

---

## 🎨 Propiedades de Diseño Base

### **Tipografía**
```javascript
{
    fontSize: 20,           // pt (puntos tipográficos)
    fontFamily: 'var(--el-font-institutional)',  // Montserrat por defecto
    fontWeight: 'bold',     // 700 (negrita)
    textTransform: 'uppercase',  // MAYÚSCULAS
    textAlign: 'center',    // Centrado
    lineHeight: 1.2,        // Espaciado entre líneas
    letterSpacing: 0.5      // px (espaciado entre letras, opcional)
}
```

### **Dimensiones**
```javascript
{
    ancho_mm: 'auto',       // Auto-ajustable al contenido
    alto_mm: 'auto',        // Auto-ajustable al contenido
    minAncho_mm: 40,        // Mínimo 40mm
    maxAncho_mm: 170,       // Máximo 170mm (respeta márgenes)
    padding_mm: 1           // 1mm de gabela alrededor del texto
}
```

### **Color & Bordes**
```javascript
{
    color: 'var(--el-primary)',      // Color primario del tema
    backgroundColor: 'transparent',   // Sin fondo
    border: 'none',                   // Sin borde
    borderRadius: '0',                // Sin redondeo
}
```

### **Espaciado Vertical (dentro de bloque)**
```javascript
{
    marginTop: 0,
    marginBottom: 0,
    paddingInline: '1mm',   // Gabela horizontal
    paddingBlock: '1mm'     // Gabela vertical
}
```

---

## 🔐 Propiedades BLOQUEADAS (No Editable Usuario)

- ✅ `fontWeight` → SIEMPRE bold
- ✅ `textTransform` → SIEMPRE uppercase
- ✅ `textAlign` → SIEMPRE center
- ✅ `fontFamily` → SIEMPRE var(--el-font-institutional)
- ✅ `padding` → SIEMPRE 1mm
- ❌ `color` → Sí editable (puede cambiar a otro color del tema)
- ❌ `fontSize` → Sí editable (puede aumentar/disminuir tamaño)

---

## 📊 JSON Schema (BLOCK_SCHEMA[titulo_colegio])

```javascript
const BLOCK_SCHEMA = {
    titulo_colegio: {
        // Propiedades ya existentes
        ancho_mm: 50,
        alto_mm: 12,
        anchoCompleto: false,
        editable: false,
        html: (si) => `<h3 class="ares-titulo-cabecera">${(si.name || 'Nombre del Colegio').toUpperCase()}</h3>`,
        extraData: {},
        
        // NUEVAS: BLOCK_STYLE_CONFIG
        style: {
            fontSize: 20,
            fontFamily: 'var(--el-font-institutional)',
            fontWeight: 'bold',
            textTransform: 'uppercase',
            textAlign: 'center',
            color: 'var(--el-primary)',
            padding_mm: 1,
            lineHeight: 1.2
        },
        
        // Validaciones y restricciones
        constraints: {
            ancho_mm: { min: 40, max: 170, auto: true },
            fontSize: { min: 16, max: 28 },
            color: { type: 'palette-color' }  // Solo colores del tema
        },
        
        // Qué es bloqueado
        locked: {
            fontWeight: true,
            textTransform: true,
            textAlign: true,
            fontFamily: true,
            padding_mm: true
        }
    }
};
```

---

## 💾 JSON Guardado en BD (configuracion_json)

Cuando el usuario crea un bloque `titulo_colegio`, se guarda así:

```json
{
    "type": "titulo_colegio",
    "zone": "header",
    "left_mm": 20.0,
    "top_mm": 10.0,
    "width_mm": null,        // null = auto-calculado
    "height_mm": null,       // null = auto-calculado
    "size": 20,              // fontSize en pt
    "align": "center",       // SIEMPRE center (bloqueado)
    "content": "<h3 class=\"ares-titulo-cabecera\">NOMBRE DEL COLEGIO</h3>",
    "color": "var(--el-primary)",    // Editable
    "padding_mm": 1,                  // Bloqueado
    "fontWeight": "bold",             // Bloqueado
    "textTransform": "uppercase"      // Bloqueado
}
```

---

## 🖼️ Renderizado en DISEÑADOR (Canvas)

### Vista Previa EN VIVO:

```
┌─────────────────────────────────┐
│  NOMBRE DEL COLEGIO             │  ← 20pt, negrita, mayús, centrado
│                                 │  ← 1mm padding arriba/abajo/lados
└─────────────────────────────────┘
```

**Cálculo de ancho:**
- El navegador mide el `<h3>` con las propiedades aplicadas
- Se suma 2mm (1mm a cada lado) de padding
- **width_mm = textWidth_mm + 2**

---

## 🖨️ Renderizado en IMPRESIÓN (imprimir_matricula.php)

En `imprimir_matricula.php`, la función `$renderizador('titulo_colegio')` debe:

1. Leer el bloque desde `$formato['configuracion_json']`
2. Extraer propiedades de estilo (size, color, etc.)
3. Aplicar BLOCK_STYLE_CONFIG[titulo_colegio]
4. Renderizar con estilos base bloqueados:

```php
$tipo = 'titulo_colegio';
$size = $block['size'] ?? BLOCK_STYLE_CONFIG['titulo_colegio']['style']['fontSize'];
$color = $block['color'] ?? 'var(--el-primary)';

$html = '<h3 style="
    font-size: ' . $size . 'pt !important;
    font-weight: bold !important;
    text-transform: uppercase !important;
    text-align: center !important;
    color: ' . $color . ';
    padding: 1mm;
    font-family: var(--el-font-institutional);
">' . htmlspecialchars($school_name, ENT_QUOTES, 'UTF-8') . '</h3>';
```

---

## ✅ Checklist de Implementación

- [ ] Crear BLOCK_STYLE_CONFIG en formatos_matricula_builder.js
- [ ] Extender BLOCK_SCHEMA['titulo_colegio'] con propiedades de style
- [ ] Modificar insertarBloqueEnCanvas() para aplicar BLOCK_STYLE_CONFIG al renderizar
- [ ] Crear función calculateAutoWidth() que mida el ancho del contenido
- [ ] Modificar insertarBloqueDesdeJSON() para aplicar estilos guardados desde BD
- [ ] Actualizar imprimir_matricula.php para aplicar BLOCK_STYLE_CONFIG en renderizador
- [ ] Crear tests: verificar que preview === impresión
- [ ] Documentar cambios en CLAUDE.md

---

## 📝 Notas

1. **El "auto ancho" se calcula en tiempo real** mientras el usuario ve la preview
2. **Los estilos bloqueados nunca se pueden cambiar** (fontWeight, textTransform, textAlign, fontFamily)
3. **El color SÍ se puede cambiar** (solo colores del tema disponible)
4. **El fontSize SÍ se puede cambiar** (rango 16-28pt)
5. **WYSIWYG de verdad**: Lo que ves en el diseñador = lo que sale en la impresión

---

**Próximo:** Definir BLOCK_STYLE_CONFIG para los otros 12 tipos de bloque
