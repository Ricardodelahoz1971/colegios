# Verificación Práctica - Fase 1
## Checklist para confirmar que todo funcionó

**Fecha:** 2026-08-10  
**Objetivo:** Verificar que .env y dead code están correctamente implementados

---

## ✅ CHECK 1: .env fue creado y tiene los valores correctos

### Paso 1.1: Verifica que .env existe

```bash
# En la carpeta del proyecto
ls -la .env
# Esperado: debe existir y tener permisos 644
```

**Qué buscar:**
```
-rw-r--r--  1 admin  staff   123 Aug 10 14:30 .env
```

### Paso 1.2: Verifica el contenido de .env

```bash
cat .env
```

**Qué buscar:**
```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=sistema_escolar
```

✅ **Si ves esto:** Perfectamente configurado

---

## ✅ CHECK 2: .gitignore contiene .env

### Paso 2.1: Verifica que .gitignore tiene .env

```bash
cat .gitignore | grep -i "\.env"
```

**Qué buscar:**
```
.env
.env.local
```

✅ **Si ves esto:** .env está protegido

### Paso 2.2: Verifica que .gitignore existe

```bash
ls -la .gitignore
```

✅ **Si ves esto:** Archivo existe

---

## ✅ CHECK 3: php/db.php lee desde .env

### Paso 3.1: Verifica que db.php tiene parse_ini_file

```bash
grep -n "parse_ini_file" php/db.php
```

**Qué buscar:**
```
11:    $env_vars = parse_ini_file($env_file);
```

✅ **Si ves esto:** db.php lee .env correctamente

### Paso 3.2: Verifica que db.php NO tiene credenciales hardcodeadas

```bash
grep -n "= 'root'\|= 'localhost'\|= 'sistema_escolar'" php/db.php | head -5
```

**Qué buscar:**
```
(vacío — 0 resultados)
```

✅ **Si ves nada:** Credenciales no están hardcodeadas

### Paso 3.3: Verifica que db.php usa variables de entorno

```bash
grep -n "DB_HOST\|DB_USER\|DB_PASS\|DB_NAME" php/db.php
```

**Qué buscar:**
```
14:    $host = $env_vars['DB_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost';
15:    $dbname = $env_vars['DB_NAME'] ?? $_ENV['DB_NAME'] ?? 'sistema_escolar';
16:    $user = $env_vars['DB_USER'] ?? $_ENV['DB_USER'] ?? 'root';
17:    $pass = $env_vars['DB_PASS'] ?? $_ENV['DB_PASS'] ?? '';
```

✅ **Si ves esto:** Variables de entorno están implementadas

---

## ✅ CHECK 4: Legacy folder fue creado

### Paso 4.1: Verifica que legacy/ existe

```bash
ls -la php/logica/legacy/
```

**Qué buscar:**
```
total 80
drwxr-xr-x  16 admin  staff   512 Aug 10 14:30 .
drwxr-xr-x  500 admin  staff   16384 Aug 10 14:30 ..
-rw-r--r--   1 admin  staff   1234 Aug 10 14:30 README.md
-rw-r--r--   1 admin  staff   234 Aug 10 14:30 check_ares.php
-rw-r--r--   1 admin  staff   345 Aug 10 14:30 check_columns.php
... (14 archivos totales)
```

✅ **Si ves legavy/ con 15 archivos (14 PHP + 1 README):** Directorio creado correctamente

### Paso 4.2: Verifica que legacy/README.md existe

```bash
cat php/logica/legacy/README.md | head -10
```

**Qué buscar:**
```
# LEGACY CODE - DO NOT USE IN PRODUCTION

Este directorio contiene código antiguo que:
- ❌ No es usado en la versión actual del sistema
```

✅ **Si ves esto:** README está en lugar

---

## ✅ CHECK 5: Archivos USED siguen accesibles

### Paso 5.1: Verifica que check_mensajes.php está en php/logica

```bash
ls -la php/logica/check_mensajes.php
```

**Qué buscar:**
```
-rw-r--r--  1 admin  staff   2456 Aug 10 14:30 php/logica/check_mensajes.php
```

✅ **Si ves esto:** check_mensajes.php en lugar correcto

### Paso 5.2: Verifica que purga_academica_segura.php está en php/logica

```bash
ls -la php/logica/purga_academica_segura.php
```

**Qué buscar:**
```
-rw-r--r--  1 admin  staff   3456 Aug 10 14:30 php/logica/purga_academica_segura.php
```

✅ **Si ves esto:** purga_academica_segura.php en lugar correcto

### Paso 5.3: Verifica que NO están en legacy/

```bash
ls php/logica/legacy/check_mensajes.php 2>&1
ls php/logica/legacy/purga_academica_segura.php 2>&1
```

**Qué buscar:**
```
ls: cannot access 'php/logica/legacy/check_mensajes.php': No such file or directory
ls: cannot access 'php/logica/legacy/purga_academica_segura.php': No such file or directory
```

✅ **Si ves esto:** No fueron movidos (correcto)

---

## ✅ CHECK 6: Sistema sigue funcionando

### Paso 6.1: Verifica que no hay errors de conexión

Abre en navegador:
```
http://localhost/sistema_escolar/index.php
```

**Qué buscar:**
- ✅ Página carga sin errores
- ✅ Login visible
- ✅ NO ver: "Error fatal: No se pudo conectar"

### Paso 6.2: Intenta login

1. Usa credenciales de prueba (si tienes)
2. Verifica que conexión a BD funciona
3. Verifica que puedes acceder a una sección

**Qué buscar:**
- ✅ Login acepta credenciales
- ✅ Redirige a página principal
- ✅ Página carga datos desde BD

---

## ✅ CHECK 7: Documentación fue creada

### Paso 7.1: Verifica que DEAD_CODE_AUDIT existe

```bash
ls -la claude_docs/DEAD_CODE_AUDIT_2026_08_10.md
```

**Qué buscar:**
```
-rw-r--r--  1 admin  staff   15678 Aug 10 14:30 claude_docs/DEAD_CODE_AUDIT_2026_08_10.md
```

✅ **Si ves esto:** Documentación existe

### Paso 7.2: Verifica contenido de auditoría

```bash
grep -i "USED\|UNUSED" claude_docs/DEAD_CODE_AUDIT_2026_08_10.md | head -5
```

**Qué buscar:**
```
### Scripts USED (MANTENER EN PRODUCCIÓN)
- `check_mensajes.php` — ENDPOINT CRÍTICO DE MENSAJERÍA
- `purga_academica_segura.php` — ENDPOINT ADMINISTRATIVO
### Categoría A: Scripts de diagnóstico (10 archivos)
```

✅ **Si ves esto:** Documentación tiene análisis

---

## ✅ CHECK 8: Sin referencias a archivos movidos

### Paso 8.1: Busca si algún archivo intenta incluir legacy/

```bash
grep -r "php/logica/check_\|php/logica/patch_\|php/logica/migracion_" php/ js/ index.php 2>/dev/null | grep -v legacy
```

**Qué buscar:**
```
(vacío — 0 resultados)
```

✅ **Si ves nada:** No hay referencias a archivos movidos (seguro)

### Paso 8.2: Verifica que db.php NO incluye legacy

```bash
grep -n "legacy" php/db.php
```

**Qué buscar:**
```
(vacío — 0 resultados)
```

✅ **Si ves nada:** db.php no intenta cargar legacy (correcto)

---

## ✅ CHECK 9: Verificaciones de seguridad

### Paso 9.1: Verifica que .env NO está en git

```bash
git status .env 2>&1
```

**Qué buscar:**
```
fatal: not a git repository
O
On branch main
nothing to commit
```

✅ **Si .env NO aparece:** No fue commiteado (seguro)

### Paso 9.2: Verifica que .env.example existe (opcional, pero recomendado)

```bash
ls -la .env.example
```

**Qué buscar:**
```
-rw-r--r--  1 admin  staff   123 Aug 10 14:30 .env.example
```

✅ **Si ves esto:** Template de .env disponible para nuevos devs

---

## ✅ CHECK 10: Métricas finales

### Paso 10.1: Cuenta archivos en php/logica

```bash
find php/logica -maxdepth 1 -name "*.php" | wc -l
```

**Qué buscar:**
```
201 (o similar — fewer than before)
```

✅ **Si ves número menor que antes:** Archivos se movieron correctamente

### Paso 10.2: Cuenta archivos en legacy/

```bash
find php/logica/legacy -maxdepth 1 -name "*.php" | wc -l
```

**Qué buscar:**
```
14
```

✅ **Si ves 14:** Todos los archivos UNUSED fueron movidos

---

## 🎯 RESUMEN DE VERIFICACIÓN

Si TODOS estos checks pasan ✅, entonces:

| Check | Estado | Significa |
|-------|--------|-----------|
| ✅ .env existe | OK | Configuración lista |
| ✅ .env en .gitignore | OK | Credenciales protegidas |
| ✅ db.php lee .env | OK | Conexión usa .env |
| ✅ Sin credenciales hardcoded | OK | Seguro para git |
| ✅ legacy/ existe | OK | Dead code segregado |
| ✅ Archivos USED accesibles | OK | Funcionalidad preservada |
| ✅ Sistema funciona | OK | Cero regresiones |
| ✅ Documentación existe | OK | Conocimiento preservado |
| ✅ Sin referencias rotas | OK | Nada apunta a legacy |
| ✅ Métricas esperadas | OK | Limpieza exitosa |

---

## 🚨 SI ALGO FALLA

### Si .env NO existe:
```bash
# Crea uno manualmente
cat > .env << 'EOF'
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=sistema_escolar
EOF
```

### Si db.php no lee .env:
```bash
# Verifica línea 11 en php/db.php
# Debe tener: $env_vars = parse_ini_file($env_file);
# Si no la tiene, el cambio no se guardó
```

### Si legacy/ NO existe:
```bash
# Crea carpeta y mueve archivos manualmente
mkdir -p php/logica/legacy
mv php/logica/check_*.php php/logica/legacy/ 2>/dev/null || true
mv php/logica/patch_*.php php/logica/legacy/ 2>/dev/null || true
mv php/logica/migracion_*.php php/logica/legacy/ 2>/dev/null || true
```

### Si sistema no carga:
```bash
# Verifica que .env tiene valores correctos
cat .env

# Verifica que BD existe
# En navegador: http://localhost/phpmyadmin
# Busca base de datos "sistema_escolar"
```

---

## 📞 CONTACTO

Si encuentras problemas:
1. Revisa este documento (VERIFICACION_FASE_1.md)
2. Ejecuta los checks en orden
3. Si aún hay problema, revisa:
   - [DEAD_CODE_AUDIT_2026_08_10.md](DEAD_CODE_AUDIT_2026_08_10.md) — Auditoría completa
   - [PLAN_REMEDIACION_4FASES.md](PLAN_REMEDIACION_4FASES.md) — Plan original

---

**Creado:** 2026-08-10  
**Propósito:** Verificación práctica de Fase 1  
**Última actualización:** 2026-08-10
