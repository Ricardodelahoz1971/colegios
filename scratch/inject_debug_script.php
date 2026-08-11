<?php
$file_php = 'C:\\xampp\\htdocs\\sistema_escolar\\php\\vistas\\configuracion.php';
$content_php = file_get_contents($file_php);

$debug_code = <<<HTML

<!-- 🐛 DEBUGGER DE ESTILOS EN VIVO -->
<div id="debug-styles-result" style="background: #fee; border: 1px solid #f00; padding: 10px; margin: 10px 0; color: #b00; font-family: monospace; font-size: 12px; border-radius: 8px;">Cargando debug de estilos...</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const runDebug = () => {
        const el = document.getElementById('khronos_inicio_mañana') || document.getElementById('khronos_inicio') || document.querySelector('input[type="time"]');
        const dbg = document.getElementById('debug-styles-result');
        if (el && dbg) {
            const style = window.getComputedStyle(el);
            dbg.innerText = "ELEMENTO: " + el.outerHTML + "\\n\\n" +
                            "ESTILOS COMPUTADOS:\\n" +
                            "Width: " + style.width + "\\n" +
                            "Min-Width: " + style.minWidth + "\\n" +
                            "Max-Width: " + style.maxWidth + "\\n" +
                            "Display: " + style.display + "\\n" +
                            "Margin: " + style.margin + "\\n" +
                            "Padding: " + style.padding + "\\n" +
                            "Box-Sizing: " + style.boxSizing + "\\n" +
                            "Appearance: " + style.appearance + "\\n" +
                            "Webkit-Appearance: " + style.webkitAppearance + "\\n" +
                            "Align-Self: " + style.alignSelf + "\\n" +
                            "Flex-Grow: " + style.flexGrow + "\\n" +
                            "Parent display: " + window.getComputedStyle(el.parentElement).display + "\\n" +
                            "Parent align-items: " + window.getComputedStyle(el.parentElement).alignItems;
        } else if (dbg) {
            dbg.innerText = 'Elemento de input de tiempo no encontrado en el DOM!';
        }
    };
    setTimeout(runDebug, 1500);
    // Ejecutar también al hacer click en pestañas por si cambia el DOM
    document.querySelectorAll('.nav-link, button').forEach(b => {
        b.addEventListener('click', () => setTimeout(runDebug, 500));
    });
});
</script>
HTML;

$content_php .= $debug_code;
file_put_contents($file_php, $content_php);
echo "Exito: Debugger de estilos inyectado en configuracion.php.\n";
