# Prueba en Navegador - Fase 1 ✅

**Objetivo:** Verificar que el sistema funciona correctamente después de Fase 1

**Tiempo:** 2 minutos

---

## ✅ PASO 1: Abre el navegador

**URL:**
```
http://localhost/sistema_escolar/index.php
```

---

## ✅ PASO 2: Verifica que carga sin errores

### ¿QUÉ DEBERÍA VER?

✅ **Página carga** — Sin "Cargando..." infinito  
✅ **Login visible** — Formulario con campos usuario/contraseña  
✅ **Sin errores en rojo** — Si hay texto rojo tipo "Error de conexión", ver "Paso 4"  
✅ **Estilos cargados** — Página se ve bien formateada, no rompa  

### ¿QUÉ NO DEBERÍA VER?

❌ "Error fatal: No se pudo conectar a la base de datos"  
❌ "Parse error" o errores de PHP  
❌ "Connection refused" o similar  
❌ Página completamente blanca (sin contenido)

---

## ✅ PASO 3: (Opcional) Intenta hacer login

Si tienes credenciales válidas:

1. **Usa usuario:** admin (o similar)
2. **Usa contraseña:** (la que uses normalmente)
3. **Haz clic en Login**

### Si funciona:
✅ Login acepta credenciales  
✅ Redirige a página principal  
✅ Página carga datos desde BD  
✅ **TODO FUNCIONA CORRECTAMENTE**

### Si falla:
- Podrían ser credenciales incorrectas (problema aparte)
- O la BD no tiene usuarios configurados
- **Pero el sistema de conexión funciona**

---

## 🔧 ¿QUÉ SIGNIFICA SI ALGO FALLA?

### "Error de conexión a BD"

**Significa:** php/db.php no puede conectar a MariaDB

**Comprueba:**
```bash
# 1. Verifica que MariaDB está corriendo
# Windows: Services → MySQL80 (debe estar verde)
# Mac/Linux: brew services list

# 2. Verifica que .env tiene valores correctos
cat .env
# Debe ver:
# DB_HOST=localhost
# DB_USER=root
# DB_PASS=
# DB_NAME=sistema_escolar

# 3. Verifica que php/db.php leyó .env
grep -n "parse_ini_file" php/db.php
# Debe ver línea como:
# 11:        $env_vars = parse_ini_file($env_file);
```

---

### Página completamente blanca

**Significa:** Hay error PHP pero está oculto

**Solución:**
1. Abre DevTools (F12)
2. Ve a **Console** (pestaña)
3. Busca mensajes de error rojo
4. Si ves PHP errors, reporta aquí

---

### "Parse error" o "Fatal error"

**Significa:** Hay problema de sintaxis

**Solución:**
```bash
# Verifica que php/db.php está bien escrito
php -l php/db.php
# Esperado: No errors detected
```

---

## ✅ RESUMEN DE VERIFICACION

| Elemento | Esperado | Estado |
|----------|----------|--------|
| Página carga | Sí | ✅ |
| Login visible | Sí | ✅ |
| Estilos cargados | Sí | ✅ |
| Sin errores | Sí | ✅ |
| Conexión BD | Funciona | ✅ |

Si todos ✅, **FASE 1 está correcta**.

---

## 📋 CHECKLIST FINAL

- [ ] Abrí index.php en navegador
- [ ] Página cargó sin errores
- [ ] Vi el login
- [ ] No hay mensajes de error
- [ ] (Opcional) Intenté login y funcionó
- [ ] **Confirmo que todo funciona**

---

**Creado:** 2026-08-10  
**Propósito:** Verificación en navegador de Fase 1

Si todo funciona, puedes proceder con confianza. ✅
