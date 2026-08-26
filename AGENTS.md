# Instrucciones del Desarrollador
- Idioma de respuesta: Responde SIEMPRE en español, de forma ultra corta, directa, sin ejemplos ni explicaciones técnicas.
- Comentarios: Todos los comentarios en el código generado deben estar en español.
- Trato obligatorio y exclusivo como **Ingeniero Ricardo**.

# Reglas Tecnológicas
- Stack: PHP estructurado/nativo, JavaScript Vanilla (ES6+), CSS puro y MariaDB.
- Frameworks: Queda estrictamente PROHIBIDO usar frameworks (no React, no Vue, no Tailwind, no Laravel).
- Base de datos: Usa consultas SQL limpias con Prepared Statements (PDO o MySQLi) para evitar inyección SQL.
- Estilos: Usa Flexbox o CSS Grid nativo para el diseño visual.

---

## 🏛️ PROTOCOLO DE COMPUERTA DURA Y DELEGACIÓN ABSOLUTA EN DEEPSEEK

### ARTÍCULO I. COMPUERTA DURA (HARD GATE PRE-EDICIÓN)
**1.1.** Queda terminantemente vetada, sin excepción de ninguna naturaleza, la ejecución de las herramientas `replace_file_content`, `multi_replace_file_content` o `write_to_file` sobre cualquier archivo del repositorio sin que exista, **en el turno inmediatamente anterior dentro de la misma sesión conversacional**, una llamada obligatoria e ineludible a la API oficial de DeepSeek mediante el conector MCP `deepseek_chat` (modelo `deepseek-v4-pro`), cuyo propósito exclusivo sea el análisis del contexto y la generación del código exacto, íntegro y definitivo a aplicar.

**1.2.** La ausencia de dicha llamada previa inhabilita de forma absoluta e irreversible la capacidad del asistente para efectuar cualquier modificación. No existe subsanación retroactiva: la llamada a DeepSeek debe preceder cronológicamente a la edición.

**1.3.** Cada edición exige su propia consulta singular, contextualizada y específica.

---

### ARTÍCULO II. PROHIBICIÓN ABSOLUTA DEL SESGO DE INMEDIATEZ
**2.1.** Queda categóricamente prohibida cualquier forma de edición local directa sin la mediación previa de DeepSeek. Esta prohibición se extiende a:
- Errores sintácticos evidentes detectados por el asistente.
- Errores tipográficos (typos) de cualquier índole.
- Ajustes de una sola línea, de un solo carácter, espacios o formato.
- Correcciones de estilo, nomenclatura o convenciones de código.
- Cualquier intervención considerada trivial, obvia o de bajo riesgo.

**2.2.** La disciplina procedimental prevalece sobre cualquier consideración de inmediatez o velocidad.

---

### ARTÍCULO III. INVALIDACIÓN DE ACCIÓN Y CONSECUENCIAS
**3.1.** Toda edición realizada sin la llamada previa obligatoria a `deepseek_chat` será considerada como falta grave y traición al protocolo.
**3.2.** Dicha infracción produce la invalidación automática de las ediciones efectuadas y anula la validez del trabajo entregado.

---

### ARTÍCULO IV. REPORTE MATEMÁTICO DE TOKENS INQUEBRANTABLE
**4.1.** Toda respuesta del asistente que implique generación o modificación de código deberá incorporar, de forma obligatoria e incondicional, el conteo exacto de tokens suministrado por la API de DeepSeek:
- **Prompt Tokens:** número exacto de entrada.
- **Completion Tokens:** número exacto de salida.
- **Total Tokens:** suma aritmética entregada por la API.

---

### ARTÍCULO V. OPTIMIZACIÓN DE RESPUESTAS
Toda respuesta al Ingeniero Ricardo debe ser extremadamente corta, concisa y limitada a confirmar o informar el resultado de la acción, eliminando por completo explicaciones técnicas y ejemplos.

**CUMPLIMIENTO OBLIGATORIO. NO NEGOCIABLE. SIN EXCEPCIONES.**