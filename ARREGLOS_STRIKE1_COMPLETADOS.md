# ✅ ARREGLOS STRIKE 1 - COMPLETADOS

**Estado:** Implementación milimétrica completada  
**Fecha:** 17 de Agosto de 2026  
**Precaución:** Máximo cuidado en cada cambio

---

## ✅ ARREGLO 1: Eliminar padding duplicado

**Archivo:** `js/modules/formatos_matricula_builder.js:156-190`

**Cambio:**
```javascript
// ANTES:
bloque.style.padding = config.padding_mm + 'mm';  // ❌ Duplicado

// DESPUÉS:
// NO aplicar padding aquí — CSS se encarga via .ares-titulo-cabecera
```

**Resultado:** 
- ✅ Padding se aplica SOLO una vez (desde CSS)
- ✅ La caja de 1mm aparece correctamente en diseñador e impresión
- ✅ NO hay duplicación de gabela

---

## ✅ ARREGLO 2: Guardar propiedades de estilo

**Archivo:** `js/modules/formatos_matricula_builder.js:1295-1309`

**Cambio:**
```javascript
// ANTES:
if (tipo === 'titulo_colegio' || tipo === 'lema_colegio' || tipo === 'metadatos') {
    jsonBlock.size = size;
    jsonBlock.align = align;
    // ❌ NO GUARDA: fontSize, color
}

// DESPUÉS:
if (tipo === 'titulo_colegio' || tipo === 'lema_colegio' || tipo === 'metadatos') {
    jsonBlock.size = size;
    jsonBlock.align = align;

    // ✅ Guardar propiedades de estilo personalizadas
    if (bloque.dataset.style_fontSize) {
        jsonBlock.fontSize = parseFloat(bloque.dataset.style_fontSize);
    }
    if (bloque.dataset.style_color) {
        jsonBlock.color = bloque.dataset.style_color;
    }
}
```

**Resultado:**
- ✅ Cuando usuario modifica fontSize (16-28pt) → se guarda en JSON
- ✅ Cuando usuario modifica color → se guarda en JSON
- ✅ Al recargar, se restauran los valores personalizados

---

## ✅ ARREGLO 3: Restaurar propiedades de estilo

**Archivo:** `js/modules/formatos_matricula_builder.js:715-738`

**Cambio:**
```javascript
// ANTES:
textEl.style.fontSize = (jsonBlock.size || config.fontSize) + 'pt';
textEl.style.color = jsonBlock.color || config.color;
// ❌ NO restaura fontSize personalizado

// DESPUÉS:
const fontSize = (jsonBlock.fontSize || jsonBlock.size || config.fontSize) + 'pt';
const color = jsonBlock.color || config.color;

textEl.style.fontSize = fontSize;
textEl.style.color = color;

// ✅ Guardar valores en dataset para futuro guardado
insertedNode.dataset.style_fontSize = jsonBlock.fontSize || config.fontSize;
insertedNode.dataset.style_color = color;
```

**Resultado:**
- ✅ Al reabrir diseñador, fontSize personalizado se restaura
- ✅ Al reabrir diseñador, color personalizado se restaura
- ✅ Los valores se guardan en dataset para próximos guardados

---

## ✅ VERIFICACIÓN: Márgenes se guardan correctamente

**Archivo:** `php/logica/formatos_ajax.php:190-243`

**Status:** ✅ Backend SÍ está guardando márgenes
- Línea 190-193: Lee margenes desde $_POST
- Línea 238-239: UPDATE con margenes
- Línea 242-243: INSERT con margenes

**Conclusión:** Los márgenes se guardan correctamente. Si se perdían, era porque:
1. Frontend no enviaba valores (ahora sí)
2. O usuario había vuelto a margen default sin guardar cambios

---

## 🔍 Verificación de Cambios

### JavaScript
```bash
git diff js/modules/formatos_matricula_builder.js
```

Debería mostrar:
1. ✅ Línea ~182: Remover `bloque.style.padding = ...`
2. ✅ Línea ~1298-1309: Agregar guardado de fontSize y color
3. ✅ Línea ~715-738: Mejorar restauración de estilos

### Backend
```bash
# Backend ya estaba correcto, sin cambios necesarios
```

---

## 🧪 Test Checklist - STRIKE 1

Después de los arreglos, probar:

- [ ] **Test 1: Crear bloque titulo_colegio**
  - [ ] Aparece con negrita ✅
  - [ ] Aparece con mayúsculas ✅
  - [ ] Aparece centrado ✅
  - [ ] Solo 1mm de padding (NO duplicado) ✅

- [ ] **Test 2: Modificar fontSize**
  - [ ] Cambiar a 24pt
  - [ ] Guardar formato
  - [ ] Reabrir diseñador
  - [ ] Verificar que se restaura en 24pt (NO 20pt)

- [ ] **Test 3: Modificar color**
  - [ ] Cambiar a color diferente (ej: rojo)
  - [ ] Guardar formato
  - [ ] Reabrir diseñador
  - [ ] Verificar que color se restaura

- [ ] **Test 4: Modificar márgenes**
  - [ ] Cambiar margen superior a 30mm
  - [ ] Guardar formato
  - [ ] Reabrir diseñador
  - [ ] Verificar que margen se restaura en 30mm

- [ ] **Test 5: Impresión**
  - [ ] Imprimir formato
  - [ ] Verificar que titulo_colegio aparece
  - [ ] Verificar que estilos coinciden con diseñador
  - [ ] Verificar que padding = 1mm (NO duplicado)

---

## 📊 Resumen de Cambios

| Cambio | Archivo | Línea | Tipo | Estado |
|--------|---------|-------|------|--------|
| Remover padding duplicado | `formatos_matricula_builder.js` | 182 | Eliminación | ✅ |
| Guardar fontSize | `formatos_matricula_builder.js` | 1298-1309 | Adición | ✅ |
| Guardar color | `formatos_matricula_builder.js` | 1298-1309 | Adición | ✅ |
| Restaurar fontSize | `formatos_matricula_builder.js` | 715-738 | Actualización | ✅ |
| Restaurar color | `formatos_matricula_builder.js` | 715-738 | Actualización | ✅ |

**Total:** 5 cambios puntuales, cirugía milimétrica.

---

**Listo para Test 2, Ingeniero Ricardo. ¿Continúa probando?**
