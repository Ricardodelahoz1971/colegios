# GIT HOOKS VALIDATOR - Regla para IAs Externas

**Aplicable a:** Gemini, Claude, DeepSeek, cualquier IA que trabaje con este proyecto

## Propósito

Validar cumplimiento de CLAUDE.md ANTES de hacer commits a GitHub. Bloquear código que viole reglas.

## Cuando aplicar

- SIEMPRE antes de hacer `git commit`
- SIEMPRE antes de hacer `git push`
- ANTES de editar código en `php/logica/`, `js/modules/`, `php/db.php`

## Las 16 Validaciones Obligatorias (8 Backend + 8 CSS)

---

## 🔴 BACKEND - 8 REGLAS

### 1. ❌ NO `→query()` sin prepared statements

**MALO:**
```php
$stmt = $db->query("SELECT * FROM tabla WHERE id = 1");
```

**CORRECTO:**
```php
$stmt = $db->prepare("SELECT * FROM tabla WHERE id = :id");
$stmt->execute([':id' => 1]);
```

**Dónde buscar:** `php/logica/*.php`

---

### 2. ❌ NO `var` en JavaScript

**MALO:**
```javascript
var quill;
var tiposPregunta = [];
```

**CORRECTO:**
```javascript
let quill;
const tiposPregunta = [];
```

**Dónde buscar:** `js/modules/*.js`

---

### 3. ❌ NO `window.location.reload()`

**MALO:**
```javascript
fetch('/endpoint.php').then(r => r.json()).then(data => {
    window.location.reload();
});
```

**CORRECTO:**
```javascript
const guardar = async () => {
    const response = await fetch('/endpoint.php');
    const data = await response.json();
    // Actualizar DOM sin reload
    document.getElementById('config').innerHTML = data.newContent;
};
guardar();
```

**Dónde buscar:** `js/` (todos los .js)

---

### 4. ❌ NO credenciales hardcodeadas en `php/db.php`

**MALO:**
```php
$user = 'root';
$pass = 'password123';
```

**CORRECTO:**
```php
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
// Variables vienen de .env (no commitear)
```

**Dónde buscar:** `php/db.php` SOLAMENTE

---

### 5. ❌ Todos los PHP deben tener `declare(strict_types=1)`

**MALO:**
```php
<?php
// Falta declare(strict_types=1)
function miFunc() { ... }
```

**CORRECTO:**
```php
<?php
declare(strict_types=1);
function miFunc() { ... }
```

**Dónde buscar:** Todos los `php/logica/*.php`

---

### 6. ❌ CSRF token obligatorio en POST/PUT/DELETE

**MALO:**
```php
if ($_POST['id']) {
    // Sin validar CSRF
    $stmt = $db->prepare(...);
}
```

**CORRECTO:**
```php
proteccion_extrema(); // Valida CSRF automático

if ($_POST['id']) {
    // CSRF ya validado
    $stmt = $db->prepare(...);
}
```

**Dónde buscar:** `php/logica/api_*.php`, `php/logica/guardar_*.php`

---

### 7. ❌ Error handling: try/catch obligatorio

**MALO:**
```php
$stmt = $db->prepare("SELECT * FROM tabla");
$stmt->execute();
$data = $stmt->fetch();
```

**CORRECTO:**
```php
try {
    $stmt = $db->prepare("SELECT * FROM tabla");
    $stmt->execute();
    $data = $stmt->fetch();
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
```

**Dónde buscar:** `php/logica/*.php` (todos los endpoints)

---

### 8. ❌ NO `$_POST`/`$_GET` sin validación

**MALO:**
```php
$id = $_POST['id'];
$stmt = $db->prepare("SELECT * FROM tabla WHERE id = :id");
$stmt->execute([':id' => $id]);
```

**CORRECTO:**
```php
$id = $_POST['id'] ?? null;
if (empty($id) || !is_numeric($id)) {
    throw new Exception('ID inválido');
}
$stmt = $db->prepare("SELECT * FROM tabla WHERE id = :id");
$stmt->execute([':id' => (int)$id]);
```

**Dónde buscar:** `php/logica/*.php` (todos los que usen $_POST/$_GET)

---

## 🎨 CSS - 8 REGLAS

### 1. ❌ NO colores hardcodeados (usar `var()`)

**MALO:**
```css
.button {
    color: #ff0000;
    background: rgb(255, 0, 0);
    border: 1px solid hsl(0, 100%, 50%);
}
```

**CORRECTO:**
```css
.button {
    color: var(--el-primary);
    background: var(--el-primary-bg);
    border: 1px solid var(--el-primary-border);
}
```

**Dónde buscar:** `styles/**/*.css` (excepto `styles/elite_colors.css`)

---

### 2. ❌ NO `!important` (excepto whitelist)

**MALO:**
```css
.button {
    color: var(--el-primary) !important;
}
```

**CORRECTO:**
```css
.button {
    color: var(--el-primary);
}
```

**Excepciones permitidas:**
- `styles/bootstrap_override.css` (blindaje Bootstrap)
- `styles/elite_z_index.css` (z-index hierarchy)
- `styles/elite_animations.css` (animaciones críticas)

**Dónde buscar:** `styles/**/*.css`

---

### 3. ❌ NO inline styles en HTML

**MALO:**
```html
<div style="color: red; margin: 10px;">Texto</div>
<button style="background: blue;">Click</button>
```

**CORRECTO:**
```html
<div class="text-error text-spacer">Texto</div>
<button class="btn btn-primary">Click</button>
```

**Dónde buscar:** `php/vistas/*.php` (buscar `style="`)

---

### 4. ❌ NO clases CSS duplicadas

**MALO:**
```css
/* styles/ui_kit.css */
.avatar-elite { width: 40px; }

/* styles/modules/perfil.css */
.avatar-elite { width: 50px; } /* ❌ Duplicada */
```

**CORRECTO:**
```css
/* styles/ui_kit.css */
.avatar-elite { width: 40px; }

/* styles/modules/perfil.css */
.avatar-elite-large { width: 50px; } /* Nombre único */
```

**Dónde buscar:** `styles/**/*.css` (buscar `.` duplicadas)

---

### 5. ❌ BEM naming obligatorio

**MALO:**
```css
.button { }
.button_disabled { }
.button-primary { }
```

**CORRECTO:**
```css
.button { }
.button__icon { }
.button--disabled { }
.button--primary { }
```

**Formato:** `.component`, `.component__element`, `.component--modifier`

**Dónde buscar:** Clases nuevas en `styles/**/*.css`

---

### 6. ❌ @layer structure obligatorio

**MALO:**
```css
.button { color: var(--el-primary); }
.btn { color: var(--el-secondary); }
```

**CORRECTO:**
```css
@layer reset { /* Reset general */ }
@layer base { /* Estilos base */ }
@layer components { .button { color: var(--el-primary); } }
@layer utilities { .text-center { text-align: center; } }
```

**Dónde buscar:** `styles/**/*.css` (excepto base)

---

### 7. ❌ Variables CSS obligatorias para valores dinámicos

**MALO:**
```css
.card {
    padding: 15px;
    border-radius: 12px;
    background: white;
}
```

**CORRECTO:**
```css
.card {
    padding: var(--el-spacing-md);
    border-radius: var(--el-radius-main);
    background: var(--el-white);
}
```

**Variables principales:**
- `--el-primary`, `--el-secondary`, etc. (colores)
- `--el-spacing-*` (espaciado)
- `--el-radius-*` (bordes)
- `--el-font-*` (tipografía)

**Dónde buscar:** `styles/**/*.css` (valores literales)

---

### 8. ❌ NO HEX hardcodeado en nuevo CSS

**MALO:**
```css
/* CSS nuevo */
.modal {
    background: #ffffff;
    border: 1px solid #cccccc;
}
```

**CORRECTO:**
```css
/* CSS nuevo */
.modal {
    background: var(--el-white);
    border: 1px solid var(--el-border);
}
```

**Excepciones:**
- Gradientes complejos (usar `--el-gradient-*`)
- Imágenes base64 (color embedding)

**Dónde buscar:** `styles/**/*.css` (excepto archivos existentes)

---

## Cómo ejecutar validación

### Opción 1: Manualmente antes de commit

```bash
# En Git Bash
grep -r "->query(" php/logica --include="*.php" | grep -v "prepare\|legacy"
# Si no ves nada = OK

grep -r "\bvar\s" js/modules --include="*.js"
# Si no ves nada = OK

grep -r "window.location.reload" js --include="*.js"
# Si no ves nada = OK

grep -E "'root'|'password'" php/db.php | grep -v "ENV\|//\|example"
# Si no ves nada = OK

find php/logica -name "*.php" -exec grep -L "declare(strict_types=1)" {} \;
# Si no ves nada = OK
```

### Opción 2: Hook automático en cada commit

```bash
# Ya está instalado en .git/hooks/pre-commit
# Solo hacer commit y el hook valida automáticamente
git commit -m "mensaje"
```

### Opción 3: Skill de Claude Code (si lo usas)

```bash
/git-hooks-validate
```

---

## Si hay violaciones

**El hook rechaza el commit:**
```
❌ ERROR: Encontré →query() sin prepared statements
❌ VALIDACIÓN FALLIDA — $N errores encontrados
```

**Qué hacer:**
1. Arregla el código violador
2. Haz `git add .`
3. Intenta commit de nuevo

**Para saltarse validación (SOLO con aprobación del lead):**
```bash
git commit --no-verify
```

---

## Archivos excluidos de validación

- `php/logica/legacy/*` — Código antiguo, permitido violar reglas
- `php/logica/db_integridad.php` — Migraciones, permitido `→query()`
- `.env` — Credenciales, NO commitear
- `scratch/` — Archivos de desarrollo, no revisar

---

## Resumen rápido

| Regla | Ubicación | Acción |
|-------|-----------|--------|
| `→query()` → `prepare()` | `php/logica/*.php` | Buscar y reemplazar |
| `var` → `const`/`let` | `js/modules/*.js` | Buscar y reemplazar |
| `reload()` → actualizar DOM | `js/*.js` | Buscar y refactorizar |
| Credenciales → `.env` | `php/db.php` | Usar `$_ENV['DB_USER']` |
| Agregar `declare(strict_types=1)` | `php/logica/*.php` | Primera línea después de `<?php` |

---

## Referencias

- CLAUDE.md — Manifiestos de gobernanza
- BACKEND_MANIFESTO.md — Reglas PHP
- FRONTEND_MANIFESTO.md — Reglas JS
- PLAN_REMEDIACION_4FASES.md — Plan detallado

---

**Versión:** 1.0  
**Fecha:** 2026-08-11  
**Aplicable a:** Todas las IAs que trabajen en este proyecto  
**Obligatorio:** SÍ — No permitir commits que violen estas reglas
