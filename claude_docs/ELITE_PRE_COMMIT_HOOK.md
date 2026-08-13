# 🛡️ ELITE PRE-COMMIT HOOK - Manual de Funcionamiento

**Versión:** 1.0  
**Ubicación:** `.git/hooks/pre-commit`  
**Activo desde:** 2026-08-13  
**Responsable:** Sistema automático de validación

---

## 📋 ¿QUÉ HACE?

El hook ejecuta **6 validaciones automáticas** ANTES de cada `git commit`. Si alguna falla, **rechaza el commit** y muestra qué corregir.

No hay forma de eludir esto. **Ni con `--no-verify`** (se puede, pero está prohibido en CLAUDE.md).

---

## 🔍 LAS 6 VALIDACIONES

### 1️⃣ HEX HARDCODEADOS EN CSS
**Regla:** Prohibido `#f9f9f9`, `#ccc`, `#e74c3c`, etc.  
**Solución:** Usar `var(--el-light-gray)`, `var(--el-primary)`, etc.

```css
❌ MALO:
.card { background: #f9f9f9; }

✅ BUENO:
.card { background: var(--el-light-gray); }
```

**Excepciones:** `sweetalert2_customization.css`, `bootstrap_override.css`, `elite_print.css` (permitidos por blindaje de librerías).

---

### 2️⃣ !IMPORTANT ILEGAL EN CSS
**Regla:** `!important` permitido SOLO en 3 archivos:
- `sweetalert2_customization.css` (blindaje SweetAlert2)
- `bootstrap_override.css` (blindaje Bootstrap)
- `elite_print.css` (reglas de impresión)

**Solución:** En otros archivos CSS, aumentar especificidad sin `!important`.

```css
❌ MALO (en archivo normal):
.btn { color: var(--el-primary) !important; }

✅ BUENO:
body .btn { color: var(--el-primary); }
/* Aumentar especificidad sin !important */
```

---

### 3️⃣ INLINE STYLES EN PHP/HTML
**Regla:** Prohibido `style="..."` en atributos HTML.  
**Solución:** Crear clase CSS en archivo externo.

```php
❌ MALO:
<div style="border-inline-start: 0.25rem solid var(--el-primary);">

✅ BUENO:
<!-- En PHP -->
<div class="card-accent-primary">

<!-- En CSS externo (styles/modules/archivo.css) -->
.card-accent-primary {
    border-inline-start: 0.25rem solid var(--el-primary);
}
```

---

### 4️⃣ BLOQUES <STYLE> EN PHP
**Regla:** Prohibido `<style>...</style>` dentro de archivos PHP.  
**Solución:** Vincular archivo CSS externo con `<link rel="stylesheet">`.

```php
❌ MALO:
<?php
$html = <<<'HTML'
<html>
<head>
    <style>
        .class { color: red; }
    </style>
</head>

✅ BUENO:
<?php
$html = <<<'HTML'
<html>
<head>
    <link rel="stylesheet" href="/styles/modules/archivo.css">
</head>
```

---

### 5️⃣ CONSOLE.LOG / ALERT EN JS
**Regla:** Prohibido `console.log()` y `alert()` en código de producción.  
**Permitido:** `console.error()` (para errores legítimos).

```javascript
❌ MALO:
function guardar() {
    console.log('Guardando...');
    alert('Guardado');
}

✅ BUENO:
function guardar() {
    if (error) {
        console.error('Error al guardar:', error);
    }
}
```

---

### 6️⃣ VARIABLES CSS LEGACY
**Regla:** Prohibido `--el-success`, `--el-danger`, `--el-info`, `--el-warning`.  
**Razón:** Fueron eliminadas. Todo usa `--el-primary`.

```css
❌ MALO:
.alert { background: var(--el-success); }

✅ BUENO:
.alert { background: var(--el-primary); }
```

---

## 🚀 CÓMO FUNCIONA EN PRACTICA

### Escenario 1: Commit válido ✅

```bash
$ git add styles/nuevos.css
$ git commit -m "feat: agregar nuevos estilos"

[ELITE VALIDATOR] Analizando cambios...
✅ VALIDACIÓN OK

[refactor/formatos-mm 7a8c800] feat: agregar nuevos estilos
 1 file changed, 50 insertions(+)
```

**El commit se ejecuta normalmente.**

---

### Escenario 2: Commit con violación ❌

```bash
$ git add styles/nuevos.css  # Contiene #f9f9f9
$ git commit -m "feat: agregar nuevos estilos"

[ELITE VALIDATOR] Analizando cambios...

╔════════════════════════════════════════════════════════════════╗
║ ❌ COMMIT RECHAZADO - VIOLACIONES DETECTADAS                  ║
╚════════════════════════════════════════════════════════════════╝

Total: 1
❌ styles/nuevos.css: 1 HEX hardcodeado(s)

Reglas CONTENCIONES_CSS_FINAL.md:
  • HEX → var(--el-*)
  • !important → solo 3 archivos permitidos
  • style="..." → clase CSS
  • <style> en PHP → archivo externo
  • console.log/alert → eliminar
  • Variables legacy → NO EXISTEN
```

**El commit se rechaza. Nada se guarda en git.**

---

## 🔧 CÓMO CORREGIR VIOLACIONES

1. **Identifica el archivo y la línea** (el hook te dice cuál)
2. **Corrige el código** según la regla
3. **Re-añade el archivo:** `git add archivo.css`
4. **Re-intenta el commit:** `git commit -m "..."`

Ejemplo completo:

```bash
# Paso 1: Intenta commit (falla)
$ git commit -m "feat: estilos"
❌ styles/nuevos.css: 1 HEX hardcodeado(s)

# Paso 2: Abre styles/nuevos.css y cambia #f9f9f9 → var(--el-light-gray)

# Paso 3: Re-añade
$ git add styles/nuevos.css

# Paso 4: Re-intenta
$ git commit -m "feat: estilos"
✅ VALIDACIÓN OK
```

---

## ⚠️ ¿Y SI NECESITO SALTARME EL HOOK?

**NO está permitido.** Está en CLAUDE.md:

> Prohibido: `git commit --no-verify`

**Esto está auditado.** Si alguien lo intenta, quedará registrado en el `pre-commit` fallido.

**Si realmente necesitas saltártelo:**
1. Contacta a Ingeniero Ricardo
2. Justifica por qué
3. Obtén autorización explícita
4. Documenta en commit message: `[OVERRIDE] Razón específica`

---

## 📊 ESTADÍSTICAS DEL HOOK

```bash
# Ver cuántas veces se ejecutó el hook
$ git log --all --grep="OVERRIDE" --oneline

# Ver commits rechazados por el hook
$ git reflog | grep "reject"  # (si lo implementamos)
```

---

## 🔄 FLUJO COMPLETO: ANTES vs DESPUÉS

### ANTES (sin hook):
```
Programador escribe código ❌ violaciones
        ↓
git commit (entra sin validar)
        ↓
Código con violaciones en git
        ↓
Auditor manual reporta 190 violaciones (2 días después)
        ↓
Ingeniero Ricardo los corrige a mano
```

### AHORA (con hook):
```
Programador escribe código ❌ violaciones
        ↓
git commit (HOOK VALIDA)
        ↓
Hook rechaza ❌ "1 HEX hardcodeado"
        ↓
Programador corrige en 30 segundos
        ↓
git commit nuevamente ✅
        ↓
Código limpio en git
```

---

## 🛠️ MANTENIMIENTO DEL HOOK

El hook está en `.git/hooks/pre-commit`. Para modificarlo:

```bash
# Ver el contenido
cat .git/hooks/pre-commit

# Editarlo
nano .git/hooks/pre-commit

# Asegurar que sea ejecutable
chmod +x .git/hooks/pre-commit
```

**Cambios comunes:**
- Agregar nueva extensión de archivo (`.tsx`, `.jsx`)
- Añadir nueva validación
- Cambiar archivos permitidos

---

## 📞 TROUBLESHOOTING

### "El hook no se ejecuta"

```bash
# Verificar que existe y es ejecutable
ls -l .git/hooks/pre-commit

# Debe mostrar: -rwxr-xr-x (la "x" es ejecutable)

# Si no tiene permiso:
chmod +x .git/hooks/pre-commit
```

### "El hook es muy lento"

Es normal si hay muchos archivos. Toma ~1-2 segundos por commit.  
Si es más, revisar si hay archivos muy grandes en staging.

### "Necesito ignorar una validación específica"

**No se puede.** Las validaciones son obligatorias.  
**Opción:** Contactar a Ingeniero Ricardo para excepciones autorizadas.

---

## 📝 RESUMEN

| Validación | Rechaza | Permite | Excepciones |
|-----------|---------|---------|-------------|
| HEX hardcodeado | `#f9f9f9` | `var(--el-*)` | 3 CSS de blindaje |
| !important | CSS normal | 3 archivos permitidos | Solo blindaje |
| style="..." | PHP/HTML con atributos | Clases CSS | Ninguna |
| <style> en PHP | Bloques inline | <link> externo | Ninguna |
| console.log/alert | Ambos | console.error() | Ninguna |
| Variables legacy | --el-success, etc. | --el-primary | Ninguna |

---

**Última actualización:** 2026-08-13  
**Próxima revisión:** 2026-09-13

