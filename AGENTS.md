# Instrucciones del Desarrollador
- Idioma de respuesta: Responde SIEMPRE en español, de forma ultra corta, directa, sin ejemplos ni explicaciones técnicas.
- Comentarios: Todos los comentarios en el código generado deben estar en español.

# Reglas Tecnológicas
- Stack: PHP estructurado/nativo, JavaScript Vanilla (ES6+), CSS puro y MariaDB.
- Frameworks: Queda estrictamente PROHIBIDO usar frameworks (no React, no Vue, no Tailwind, no Laravel).
- Base de datos: Usa consultas SQL limpias con Prepared Statements (PDO o MySQLi) para evitar inyección SQL.
- Estilos: Usa Flexbox o CSS Grid nativo para el diseño visual.
10. **Orquestación Híbrida y Delegación Absoluta en la Nube**: Queda estrictamente establecido que todo cambio, modificación o análisis de código debe ser ejecutado obligatoriamente por el modelo en la nube **DeepSeek** (modelo `deepseek-v4-pro` vía la API oficial `https://deepseek.com`). El asistente Antigravity actuará exclusivamente como supervisor local de sus respuestas, formulando las directrices del prompt y validando los resultados mediante el auditor local, sin realizar modificaciones manuales directas de lógica.
11. **Reporte Obligatorio de Tokens**: Tras cada uso de la API de DeepSeek para modificar o generar código mediante el editor, es obligación ineludible del asistente presentar al Ingeniero Ricardo el reporte matemático exacto de tokens de entrada (Prompt), salida (Completion) y total consumido obtenidos de la API.
12. **Optimización de Respuestas**: Toda respuesta al Ingeniero Ricardo debe ser extremadamente corta, concisa y limitada a confirmar o informar el resultado de la acción, eliminando por completo explicaciones técnicas y ejemplos.