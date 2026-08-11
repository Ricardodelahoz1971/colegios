# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## 📋 PROYECTO: SISTEMA ESCOLAR ÉLITE v9.2

**Stack:** PHP vanilla (PDO) + JavaScript ES6+ + CSS puro + MariaDB  
**Metodología:** Arquitectura modular con manifiestos de gobernanza  
**Ubicación:** `c:\xampp\htdocs\sistema_escolar`

---

## 🏗️ ARQUITECTURA GENERAL

### Estructura de carpetas

```
sistema_escolar/
├── index.php                 # Entry point (login + UI dinámica)
├── php/
│   ├── db.php               # Conexión MariaDB + integridad de BD
│   ├── security.php         # CSRF, XSS, autenticación
│   ├── login.php            # Procesamiento de login
│   ├── logica/              # Controladores (200+ archivos)
│   │   ├── api_*.php        # Endpoints de API
│   │   ├── *Controller.php  # Lógica de negocio
│   │   └── db_integridad.php # Migraciones automáticas de schema
│   └── vistas/              # Templates (30+ archivos)
├── js/
│   └── modules/             # 12 módulos ES6+ encapsulados
│       ├── ares_*.js        # Editor visual (ARES system)
│       ├── perseus_*.js     # Sistema evaluación (PERSEUS)
│       ├── academic_*.js    # Lógica académica
│       └── security_core.js # Validaciones cliente
├── styles/                  # CSS modular (46 archivos)
│   ├── elite_*.css          # Base + temas
│   ├── ui_kit.css           # Componentes vitales
│   ├── utilities.css        # Clases auxiliares
│   └── modules/             # (31 archivos) Estilos específicos por funcionalidad
├── assets/
│   └── libs/                # Bootstrap, jQuery, DataTables, Quill, MathLive, etc.
├── database/
│   ├── crear_periodos.sql
│   └── crear_recesos.sql
├── lineamientos/            # Estándares educativos MEN Colombia
├── documentacion/           # Guías y análisis
└── claude_docs/             # Documentación para Claude (auditoría CSS, planes)
```

### Flujo de datos

```
index.php (login)
    ↓
php/db.php (conexión PDO + integridad automática)
    ↓
php/security.php (validación CSRF/XSS/autenticación)
    ↓
php/vistas/*.php (templates dinámicas con datos)
    ↓
js/modules/*.js (lógica interactiva ES6+)
    ↓
Fetch API → php/logica/api_*.php (endpoints JSON)
    ↓
MariaDB (schema_version driven)
```

---

## 🔑 PRINCIPIOS NUCLEARES

### 1. Backend (BACKEND_MANIFESTO.md)

- **PDO mandatory:** `prepare()` / `execute()` obligatorio. Prohibido concatenar variables o `query()` con datos dinámicos.
- **Tipado estricto:** `declare(strict_types=1);` en cada archivo PHP.
- **JSON responses:** Estructura `{ status, data, message }` en todos los endpoints.
- **CSRF tokens:** Requerido en cada `POST/PUT/DELETE`.
- **Seguridad de sesión:** `session_write_close()` en procesos asíncronos para evitar bloqueos SPA.

### 2. Frontend (FRONTEND_MANIFESTO.md)

- **ES6+ mandatory:** `const/let`, arrow functions, destructuring, template literals. **Veto a `var`.**
- **Async/await:** Fetch API con `async/await`. Prohibido `.then()` chaining.
- **Modular:** `import/export`. Sin variables globales desprotegidas.
- **No reload:** Prohibido `window.location.reload()`. Actualizar DOM vía AJAX (Hefesto Engine).
- **Lazy loading:** Imágenes y módulos no críticos.

### 3. Estilos (STYLE_MANIFESTO.md)

- **Variables CSS obligatorias:** Prohibido HEX hardcodeado. Usar `var(--el-primary)`, etc.
- **Sin !important:** Regla de oro (legacy puede existir, pero purga natural).
- **Métrica 44px:** Altura mínima para controles (input, botón, select).
- **Radios:** 12px (controles) / 24px (paneles).
- **Sin inline styles:** Atributo `style` es motivo de purga.
- **BEM-Elite:** `.component`, `.component__element`, `.component--modifier`.
- **@layer structure:** `reset` → `base` → `components` → `utilities`.

---

## 📂 PUNTOS DE ENTRADA CLAVE

### Conexión a BD

```php
// En php/db.php:
// - Auto-creación de BD si no existe
// - Auto-migración de schema (schema_version driven)
// - PDO con strict types y error handling

$db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Critical
```

### Seguridad

```php
// En php/security.php:
// - CSRF token validation
// - XSS sanitization
// - Autenticación y permisos por módulo
// - Cookies con HttpOnly, Secure, SameSite=Strict
```

### API endpoints

**Patrón:** `/php/logica/api_*.php`  
**Método:** POST/GET con JSON request/response  
**Validación:** CSRF token + User role check  

Ejemplos:
- `api_preguntas.php` - Gestión de reactivos
- `api_pruebas.php` - Gestión de evaluaciones
- `api_sabana.php` - Calificaciones
- `api_aula.php` - Aula virtual

### Vistas

**Ubicación:** `/php/vistas/*.php`  
**Estructura:** PHP puro + HTML semántico + Clases Bootstrap/Elite  
**Templates dinamicos:** PDO queries directas (datos desde BD)

---

## 🛠️ DESARROLLO COMÚN

### Agregar un nuevo endpoint API

```php
// php/logica/api_nuevo.php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security.php';

// Validar CSRF y autenticación
verificarCSRF($_POST['_csrf_token'] ?? '');
verificarAutenticacion();

// Query con prepared statements
$stmt = $db->prepare("SELECT * FROM tabla WHERE id = :id");
$stmt->execute([':id' => $_POST['id'] ?? 0]);
$data = $stmt->fetch();

// Respuesta estructurada
echo json_encode([
    'status' => 'success',
    'data' => $data,
    'message' => 'Operación completada'
]);
```

### Agregar un nuevo módulo JS

```javascript
// js/modules/nuevo_modulo.js
export const nuevoModulo = {
    init() {
        console.log('Módulo inicializado');
        this.bindEvents();
    },
    
    async fetchData() {
        const response = await fetch('/php/logica/api_nuevo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ _csrf_token: window.csrfToken })
        });
        return await response.json();
    },
    
    bindEvents() {
        // Usar Fetch API + async/await, nunca .then()
    }
};

// En la vista que lo use:
import { nuevoModulo } from '/js/modules/nuevo_modulo.js';
document.addEventListener('DOMContentLoaded', () => nuevoModulo.init());
```

### Agregar estilos

```css
/* styles/modules/nuevo_modulo.css */
@layer modules {

.nuevo-componente {
    padding: 1.5rem;
    border-radius: var(--el-radius-main);
    background: var(--el-white);
    border: 1px solid var(--el-border);
    transition: var(--el-transition-premium);
}

.nuevo-componente:hover {
    border-color: var(--el-primary);
    box-shadow: 0 0.5rem 1rem rgba(var(--el-primary-rgb), 0.1);
}

}

/* En html: <link rel="stylesheet" href="/styles/modules/nuevo_modulo.css"> */
```

---

## 🎯 FLUJO DE DESARROLLO TÍPICO

### 1. Nueva feature educativa

**Paso 1:** Crear estructura en BD  
```php
// database/criar_tabla.sql (si aplica)
// O en php/logica/db_integridad.php (migración automática)
```

**Paso 2:** API endpoint  
```php
// php/logica/api_feature.php
// POST/GET con PDO + CSRF validation
```

**Paso 3:** Vista (template)  
```php
// php/vistas/feature.php
// Con datos desde $db->query() directo
```

**Paso 4:** Módulo JS  
```javascript
// js/modules/feature_engine.js
// Fetch → API endpoint + DOM updates vía AJAX
```

**Paso 5:** Estilos  
```css
/* styles/modules/feature.css */
/* @layer modules con variables CSS */
```

---

## 🧪 TESTING

**No hay test suite configurado.** Testing es manual:

- **Backend:** Verificar endpoints con POST/GET en navegador
- **Frontend:** Abre DevTools → Network/Console
- **Visual:** Prueba responsivo en 3+ tamaños (480px, 768px, 1024px)

### Verificación de seguridad

```bash
# Buscar PDO violations
grep -r "->query(" php/logica/ --include="*.php"
grep -r "\$_POST\[" php/ --include="*.php" | grep -v "prepare"

# Buscar !important
grep -r "!important" styles/ --include="*.css"

# Buscar var/ (JS)
grep -r "\bvar\s" js/modules/ --include="*.js"
```

---

## 📚 DOCUMENTACIÓN DE REFERENCIA

**Estos 3 documentos son la ley:**
1. [BACKEND_MANIFESTO.md](BACKEND_MANIFESTO.md) - Reglas PHP/BD
2. [FRONTEND_MANIFESTO.md](FRONTEND_MANIFESTO.md) - Reglas JS
3. [STYLE_MANIFESTO.md](STYLE_MANIFESTO.md) - Reglas CSS

**Documentación específica:**
- [AGENTS.md](AGENTS.md) - Instrucciones para agentes automáticos
- [GUIA_PRUEBAS_MANUALES.md](GUIA_PRUEBAS_MANUALES.md) - Testing manual
- [claude_docs/AUDITORIA_CSS.md](claude_docs/AUDITORIA_CSS.md) - Auditoría CSS detallada
- [claude_docs/PLAN_HIBRIDIZACION_CSS.md](claude_docs/PLAN_HIBRIDIZACION_CSS.md) - Plan de refactorización CSS

---

## ⚠️ RESTRICCIONES CRÍTICAS

### ❌ PROHIBIDO

- Frameworks (React, Vue, Tailwind, Laravel)
- `var` en JavaScript
- `!important` en CSS nuevo
- HEX hardcodeado en estilos
- `.then()` en lugar de `async/await`
- Concatenación en SQL (usar PDO prepared statements)
- Inline styles (`style="..."`)
- `window.location.reload()`
- Consultas sin CSRF token

### ✅ OBLIGATORIO

- PDO con prepared statements
- CSRF tokens en POST/PUT/DELETE
- `declare(strict_types=1);` en cada PHP
- Variables CSS para todos los valores dinámicos
- Métrica 44px para controles
- @layer para CSS
- async/await para llamadas HTTP
- Respuestas JSON estructuradas: `{ status, data, message }`

---

## 🚀 COMANDOS COMUNES

### Ver logs de PHP/BD

```bash
# Ver últimas líneas de error de PHP (si está configurado)
# En Windows/XAMPP: C:\xampp\apache\logs\error.log

# Ver estado de la conexión BD
# Accede a: http://localhost/sistema_escolar/index.php
# Si hay error, revisa php/db.php y configuración de MariaDB
```

### Limpiar cache de navegador

```javascript
// En la consola del navegador
localStorage.clear();
sessionStorage.clear();
location.reload();
```

### Verificar integridad de BD

```php
// En scratch/check_db_info.php (archivo de debug)
// O agregar en php/logica/ un endpoint de health check
```

---

## 🔄 REFACTORIZACIÓN CSS COMPLETADA ✅

**Estado:** PHASE 1-3 COMPLETADAS | PHASE 4 ENFORCEMENT ACTIVA

**Lo que se logró:**
- ✅ Eliminadas 656 instancias de `!important` (excepto 194 permitidas)
- ✅ Eliminadas variables de estado (success, danger, info, warning)
- ✅ Reemplazadas 153+ ocurrencias por `--el-primary`
- ✅ Creado blindaje Bootstrap override
- ✅ Unificado espesor de línea lateral a 0.25rem
- ✅ **Todos los elementos responden dinámicamente a cambios de paleta**

**Documentación:**
- [claude_docs/AUDITORIA_CSS.md](claude_docs/AUDITORIA_CSS.md) - Problemas identificados (antes)
- [claude_docs/PLAN_HIBRIDIZACION_CSS.md](claude_docs/PLAN_HIBRIDIZACION_CSS.md) - Plan de ejecución
- [claude_docs/CONTENCIONES_CSS_FINAL.md](claude_docs/CONTENCIONES_CSS_FINAL.md) - **RESTRICCIONES OBLIGATORIAS**
- [claude_docs/CSS_WORKFLOW.md](claude_docs/CSS_WORKFLOW.md) - Workflow para agregar CSS
- [claude_docs/CSS_COMPONENTS.md](claude_docs/CSS_COMPONENTS.md) - Referencia de componentes

---

## 🛡️ VALIDACIÓN AUTOMÁTICA DE CSS (PHASE 4)

**⚠️ CRÍTICO: El agente DEBE leer y validar contra esto SIEMPRE**

Antes de escribir cualquier CSS:
1. **LEE:** `claude_docs/CONTENCIONES_CSS_FINAL.md`
2. **VALIDA:** Tu código contra el checklist
3. **RECHAZA:** Código que viole las reglas
4. **CORRIGE:** Automáticamente si es necesario

**Ejemplo de auto-validación:**
```
Usuario: "Agrega un botón rojo"
Agente Lee: CONTENCIONES_CSS_FINAL.md
Agente intenta: .btn { color: #ff0000; }
Agente detecta: ❌ Hardcode #ff0000
Agente corrige: .btn { color: var(--el-primary); }
Agente reporta: ✅ Botón creado con var(--el-primary)
```

**Restricciones Clave:**
- ❌ Colores hardcodeados (`#`, `hsl()`, `rgb()`)
- ❌ Tamaños hardcodeados (excepto valores estándar)
- ❌ `!important` excepto en 3 archivos permitidos
- ❌ Variables de estado (success, danger, info, warning) - NO EXISTEN
- ✅ SIEMPRE usar variables CSS
- ✅ SIEMPRE `border-inline-start: 0.25rem solid var(--el-primary)` en componentes principales

---

## 💡 NOTAS ESPECIALES

### Sistema multi-tenant educativo

Este proyecto soporta **múltiples colegios/instituciones** con:
- Schema dinámico (periodos académicos, recesos, estándares MEN)
- Roles variados (admin, docente, estudiante, coordinador, tutor)
- Evaluaciones adaptativas (ARES canvas editor)
- Seguimiento en tiempo real (PERSEUS analytics)

### Auto-migración de schema

En `php/db.php` hay lógica de versionado (`schema_version`):
```php
if ($schema_version < 99) {
    include_once __DIR__ . '/logica/db_integridad.php';
}
```

Si agregas tablas nuevas, incrementa el version number y agrega lógica en `db_integridad.php`.

### Seguridad en producción

Los valores en `index.php`:
```php
$host = 'localhost';
$user = 'root';
$pass = '';
```

Están hardcodeados para desarrollo local. **En producción:** usar `.env` y variables de entorno.

---

## 🤝 CONVENCIONES DE NOMBRES

| Tipo | Convención | Ejemplo |
|------|-----------|---------|
| PHP files | snake_case | `api_preguntas.php`, `calculadora_notas.php` |
| PHP classes | PascalCase | `SistemaSalud`, `AuthController` |
| PHP methods | camelCase | `obtenerDiagnostico()`, `verificarCSRF()` |
| JS modules | snake_case | `ares_editor.js`, `perseus_engine.js` |
| JS objects | camelCase | `aresEditor`, `perseusEngine` |
| CSS classes | kebab-case + BEM | `.ares-editor`, `.ares-editor__item`, `.ares-editor--active` |
| CSS variables | --el-descriptive | `--el-primary`, `--el-height-elite` |
| DB tables | snake_case | `estudiantes`, `carga_academica`, `eval_periodos_academicos` |

---

## 📞 SOPORTE INTERNO

**Documentación de agentes:**
- Ver [AGENTS.md](AGENTS.md)

**Reglas de respuesta:**
- Idioma: **Español** siempre
- Tono: Técnico, directo, sin fluff
- Comentarios en código: Español
- Respuestas: Concisas (sin repetir lo obvio)

---

**Última actualización:** Hoy  
**Versión:** 1.0  
**Mantenedor:** Sistema integrado con manifiestos de gobernanza
