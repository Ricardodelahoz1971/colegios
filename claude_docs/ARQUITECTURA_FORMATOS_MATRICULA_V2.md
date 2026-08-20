# ARQUITECTURA MÓDULO FORMATOS DE MATRÍCULA — REESCRITURA V2

**Estado:** Documento rector. Cualquier agente o desarrollador que toque este módulo DEBE leer esto primero y seguir esta línea, sin desviaciones.

**Regla de oro del proyecto:** si un cambio no está descrito aquí, no se improvisa un camino nuevo — se actualiza este documento primero y luego se implementa.

---

## 1. EL PROBLEMA RAÍZ (una sola causa, no cinco)

Hoy existen **tres renderizadores distintos** del mismo formato de matrícula:

1. **Canvas del diseñador** (`js/modules/formatos_matricula_builder.js`) — dibuja bloques en el navegador con datos ficticios y estilos definidos en JS (`BLOCK_STYLE_CONFIG`).
2. **Preview/Impresión** (`imprimir_matricula.php`) — vuelve a interpretar el `contenido_html` guardado, con su propia copia de estilos (`$BLOCK_STYLE_CONFIG_PHP`) y datos reales de BD.
3. **Exportación PDF** (`html2canvas` + `jsPDF` dentro de `imprimir_matricula.php`) — toma una foto del resultado del paso 2.

Cada uno tiene su propia copia de reglas de estilo y su propia lógica de posicionamiento. Cuando alguien parcha uno, el otro queda desincronizado. **Por eso "diseño una vaina y sale otra".**

**La solución no es una librería nueva. Es eliminar los renderizadores duplicados y dejar UNO SOLO.**

---

## 2. PRINCIPIO ARQUITECTÓNICO ÚNICO

> El HTML que el usuario ve en el canvas del diseñador y el HTML que se imprime **deben ser literalmente el mismo HTML**, generado por la misma función, aplicando el mismo CSS. La única diferencia entre "modo diseño" y "modo impresión" es:
> - En diseño: los bloques son `contenteditable` / arrastrables, y los datos son de un estudiante de muestra (el primero real de la BD, nunca inventado).
> - En impresión: los bloques son estáticos, y los datos son del estudiante real seleccionado.

Esto ya estaba parcialmente resuelto: `formatos_ajax.php` acción `obtener_datos_preview` ya trae un estudiante real de BD (no ficticio) — eso se queda igual, es correcto.

---

## 3. FUENTE ÚNICA DE VERDAD — QUÉ VIVE DÓNDE

| Elemento | Dónde vive | Quién lo consume |
|---|---|---|
| Definición de bloques (tipos, campos, zona permitida) | `php/logica/formatos_bloques_config.php` (nuevo, PHP puro, array asociativo) | Se exporta a JSON para el canvas (`json_encode`) y se usa directo en PHP para render/validación |
| Estilos base por tipo de bloque (fontSize en pt, color, fuente) | El mismo archivo anterior, una sola tabla de estilos | Igual que arriba — nunca se duplica en JS |
| Datos institucionales (colegio, lema, resolución, rector, secretaria) | BD: tabla `ajustes_estetica` + `usuarios` (rol rector/secretaria) — ya existe, vía `FormatosController::obtenerDatosMatricula()` | Único punto de entrada: el controller. Nadie más consulta estas tablas directamente. |
| Datos del estudiante | BD: `estudiantes` + `estudiantes_datos_adicionales` + notas | Mismo controller |
| Estructura visual del formato (posición de bloques, tamaño de página, márgenes) | Tabla `formatos_matricula`, columna `configuracion_json` (JSON de bloques con posición en **mm**) | Única fuente para pintar bloques, tanto en canvas como en impresión |
| HTML compilado final | Se **deja de guardar** como `contenido_html` estático. Se **genera siempre al vuelo** desde `configuracion_json` + datos reales. `contenido_html` pasa a ser solo un caché opcional, nunca la fuente de verdad. |

**Por qué este cambio importa:** hoy `contenido_html` es una foto congelada del canvas al momento de guardar. Si los datos institucionales cambian después (nuevo lema, nuevo rector), la matrícula impresa sigue mostrando lo viejo porque el HTML ya tiene los valores quemados. Al generar siempre al vuelo desde `configuracion_json` (que solo describe posiciones/estilos, no datos), el problema desaparece estructuralmente.

---

## 4. UNIDADES — REGLA ÚNICA SIN EXCEPCIÓN

- **Posición y tamaño de bloques:** siempre **milímetros (mm)**. Nunca px, nunca cm.
- **Tamaño de fuente:** siempre **puntos (pt)**. Nunca px, nunca em/rem dentro del documento imprimible.
- **Conversión mm→px:** ocurre en **un solo lugar**, una función JS (`mmToPx(mm, scale)`) usada únicamente para dibujar en pantalla dentro del canvas. El dato guardado en BD SIEMPRE es mm. Nunca se guarda un valor ya convertido a píxeles.
- **Zoom del canvas:** es puramente visual (CSS `transform: scale()` sobre el contenedor). Nunca recalcula ni reescribe los valores en mm almacenados.

Esto ya lo tenías bien decidido (mm para todo salvo fuente en pt) — el documento solo lo formaliza para que ningún agente futuro lo cambie "porque le pareció mejor".

---

## 5. VALIDACIÓN DE BLOQUES POR ZONA

Cada tipo de bloque declara en `formatos_bloques_config.php` en qué zonas puede existir:

```php
'calificaciones' => ['zonas_permitidas' => ['body']],
'firmas'         => ['zonas_permitidas' => ['footer']],
'titulo_colegio' => ['zonas_permitidas' => ['header']],
'ficha_estudiante' => ['zonas_permitidas' => ['body']],
```

El canvas consulta esta tabla antes de aceptar un `drop` en una zona. El backend la vuelve a validar al guardar (`formatos_ajax.php`, acción `guardar`) — **nunca confiar solo en la validación del cliente**.

---

## 6. FLUJO COMPLETO (extremo a extremo)

```
1. Usuario abre diseñador (php/vistas/formatos_matricula.php, tab editor)
   → JS pide GET/POST a formatos_ajax.php?action=obtener_datos_preview
   → Recibe: datos institucionales reales + 1 estudiante real de muestra + configuracion_json del formato (si edita uno existente)

2. Canvas dibuja bloques usando:
   → formatos_bloques_config.php (exportado como JSON embebido) → define estilos/zona por tipo de bloque
   → Los valores de posición/tamaño en mm → convertidos a px solo para pintar

3. Usuario guarda:
   → JS envía SOLO configuracion_json (array de bloques: tipo, zona, x_mm, y_mm, w_mm, h_mm, estilos override)
   → Backend valida zona por tipo, guarda en formatos_matricula.configuracion_json
   → NO se guarda contenido_html como fuente de verdad (puede regenerarse como caché, opcional)

4. Impresión (desde gestión de matriculados → botón imprimir):
   → imprimir_matricula.php?estudiante_id=X&formato_id=Y
   → Llama a FormatosController::obtenerDatosMatricula() (datos reales)
   → Llama a la MISMA función de renderizado que usa el canvas (compartida via un renderer PHP único: formatos_renderer.php)
   → Genera el mismo HTML/CSS que el canvas mostraba, con datos reales del estudiante

5. Exportar PDF:
   → Mismo HTML anterior, capturado por html2canvas + jsPDF (se mantiene esta librería, no es el problema)
   → Como el HTML es idéntico al preview, el PDF coincide con lo diseñado
```

---

## 7. QUÉ SE ELIMINA (código muerto a borrar, no a "comentar")

- `BLOCK_STYLE_CONFIG` duplicado en JS y `$BLOCK_STYLE_CONFIG_PHP` en PHP → reemplazados por `formatos_bloques_config.php` único.
- Nombre de rector hardcodeado (`'RIGOBERTO ANDRÉS NUBIA'`) → siempre viene del controller.
- `contenido_html` como fuente de verdad al imprimir → pasa a ser caché regenerable, nunca autoridad.
- `catch (e) {}` vacíos → todo error se loguea (`console.error` en JS, `error_log` en PHP) y se muestra al usuario vía `{status: 'error', message}`.
- Todo archivo en `scratch/` relacionado a formatos (`apply_formatos_matricula_patch.php`, `debug_formato.php`, `diagnostico_formatos.php`, `migrar_formatos_multipaper.php`) → se borra una vez migrada la lógica útil, si la hay.

## 8. QUÉ SE CONSERVA (no se toca)

- `FormatosController::obtenerDatosMatricula()` — ya está limpio, ya es la fuente correcta de datos reales.
- `html2canvas` + `jsPDF` para exportar PDF — el problema nunca fue la librería.
- Estructura de tabla `formatos_matricula` (columnas ya sirven, solo cambia el uso de `contenido_html`).
- Validación CSRF + permisos ya corregida en `formatos_ajax.php`.

---

## 9. ORDEN DE EJECUCIÓN (para no romper nada a mitad de camino)

1. Crear `php/logica/formatos_bloques_config.php` con la tabla única de tipos de bloque, estilos y zonas permitidas.
2. Crear `php/logica/formatos_renderer.php`: una función `renderizarFormato(array $configuracionJson, array $datos, bool $modoDiseno): string` que genera el HTML. Se usa desde `imprimir_matricula.php` Y se expone vía AJAX para que el canvas la use (o se replica en JS de forma idéntica solo para la parte interactiva — a decidir según si el canvas necesita ser editable en vivo).
3. Migrar `imprimir_matricula.php` para que use `formatos_renderer.php` en vez de su lógica propia de interpolación de `contenido_html`.
4. Migrar `formatos_matricula_builder.js` para que sus estilos vengan de `formatos_bloques_config.php` (inyectado como `window.BLOCK_CONFIG` vía `json_encode` en la vista PHP), eliminando el `BLOCK_STYLE_CONFIG` propio.
5. Actualizar `formatos_ajax.php` acción `guardar` para validar zona por tipo de bloque antes de persistir.
6. Probar: crear formato nuevo → guardar → editarlo → confirmar que el canvas se ve igual que el preview → imprimir un estudiante real → confirmar PDF idéntico.
7. Borrar código muerto listado en sección 7.

**No se avanza al paso siguiente sin probar el anterior en navegador.**
