# LEGACY CODE - DO NOT USE IN PRODUCTION

Este directorio contiene código antiguo que:
- ❌ No es usado en la versión actual del sistema
- ❌ Puede contener bugs heredados
- ❌ Es mantenido solo para referencia histórica
- ⚠️ NO EJECUTAR ESTOS SCRIPTS EN PRODUCCIÓN

## Estado de los archivos

### Scripts de diagnóstico (check_*.php) — 10 archivos
- `check_ares.php` — Verifica tablas ARES
- `check_columns.php` — Inspecciona eval_incidentes
- `check_estetica.php` — Examina ajustes_estetica
- `check_estudiantes.php` — Inspecciona estructura estudiantes
- `check_json.php` — Valida metadata_json en preguntas
- `check_preguntas.php` — Examina eval_preguntas
- `check_pruebas.php` — Inspecciona eval_pruebas
- `check_respuestas.php` — Verifica eval_respuestas
- `check_table_info.php` — Debug de ares_actividades
- `check_usuarios.php` — Examina tabla usuarios

**⚠️ NUNCA USAR:** Estas herramientas de diagnóstico son reliquias de desarrollo y pueden causar corrupción de datos si se ejecutan sin supervisión.

### Scripts de migración (migracion_*.php) — 2 archivos
- `migracion_elite_v3.php` — Crea índices de optimización
- `migracion_navegacion.php` — Agrega columna tipo_navegacion

**Estado:** Documentados en plan de remediación pero NO ejecutados en el flujo automático. Si necesitas aplicar estas migraciones, consulta con el equipo antes de ejecutarlas.

### Patches de BD (patch_*.php) — 2 archivos
- `patch_database_optimizations.php` — Inyecta índices en tablas ARES/PERSEUS
- `patch_respuestas.php` — Agrega columnas en eval_respuestas

**Estado:** Propuestos como optimizaciones pero NO aplicados. Código experimental.

## ¿Qué hacer si necesito una función aquí?

1. **Verifica si existe una función nueva:** Es probable que la funcionalidad haya sido reemplazada en el código productivo
2. **Abre un issue:** Describe qué necesitas hacer
3. **NO EJECUTES directamente:** Pide revisión del equipo técnico antes de cualquier acción

## Archivos ACTIVOS que SÍ se usan

Los siguientes archivos están **EN PRODUCCIÓN** y funcionan normalmente:

- `check_mensajes.php` — **ENDPOINT ACTIVO** de mensajería en tiempo real (invocado cada 15s)
- `purga_academica_segura.php` — **ENDPOINT ADMINISTRATIVO** de limpieza de datos

## Archivado en

**Fecha:** 2026-08-10  
**Razón:** Consolidación de dead code (Fase 1 del plan de remediación de backend)  
**Autorizado por:** Plan de remediación backend 4 fases

---

*Última actualización: 2026-08-10*
