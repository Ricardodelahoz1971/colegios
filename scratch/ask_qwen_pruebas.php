<?php
declare(strict_types=1);

// Definir el prompt para Qwen
$prompt = "Necesito que crees dos archivos para implementar un Test Runner formal sobre los métodos de calificación (CalculadoraNotas::aplicarPoliticaRecuperacion) en PHP y CSS, cumpliendo de forma estricta con el STYLE_MANIFESTO.md, BACKEND_MANIFESTO.md y FRONTEND_MANIFESTO.md de Vitrina 06.

---
REGLAS A CUMPLIR (EXTREMADAMENTE CRÍTICAS):
1. Cero HEX: Prohibido usar el caracter '#' para definir colores. Debes usar exclusivamente var(--el-*) para todo coloreado en CSS.
2. Veto a la inyección CSS: Queda prohibido el bloque <style> dentro de archivos PHP. Por lo tanto, el archivo PHP no debe incluir ninguna etiqueta <style>. Todo el CSS debe ir en el archivo CSS.
3. Veto al atributo style: Prohibido usar style=\"...\" inline en los elementos HTML.
4. Geometría Lógica: Prohibido usar left, right, margin-left, margin-right, padding-left, padding-right en CSS. Debes usar margin-inline-start, margin-inline-end, padding-inline-start, padding-inline-end, inset-inline-start, inset-inline-end.
5. Altura de controles interactivos: Los botones e inputs interactivos deben tener una altura de 44px (puedes aplicarlo en el CSS).
6. Radio de Prestigio: Bordes con border-radius: var(--el-border-radius-m, 12px) para controles y var(--el-border-radius-lg, 24px) para paneles maestros. (No uses valores estáticos como 12px o 24px sin var o sin tokens en el CSS).
7. Transiciones de seda: Usa transitions con cubic-bezier(0.4, 0, 0.2, 1).
8. Tipografía Institucional: Usa font-family: var(--el-font-institutional).
9. Evitar Bootstrap Puro: No uses clases de botones bootstrap puro como btn-primary, btn-success, etc. Usa las clases soberanas del sistema como btn-elite, btn-elite--primary, btn-elite--success, etc.
10. Sanitización XSS: Todo lo que hagas echo o <?= debe estar sanitizado usando htmlspecialchars() o escapeHtml() o intval() o floatval() u obtener la bandera '// xss ok' o '// html ok' al final de la línea.
11. No uses código comentado inerte (zombie), TODOs o debugs (var_dump, print_r, console.log).

---
ARCHIVO 1: php/vistas/pruebas_formales.php
Este archivo cargará 'CalculadoraNotas.php' (si no está declarado) y ejecutará 9 aserciones unitarias automatizadas probando la función CalculadoraNotas::aplicarPoliticaRecuperacion con las 3 políticas:
1. 'reemplazo':
   - Caso 1: Original = 2.0, Recup = 4.5, Aprob = 3.0. Esperado = 4.5.
   - Caso 2: Original = 3.5, Recup = 2.0, Aprob = 3.0. Esperado = 3.5.
   - Caso 3: Original = 2.5, Recup = null, Aprob = 2.5.
2. 'promedio':
   - Caso 4: Original = 2.0, Recup = 4.5, Aprob = 3.0. Esperado = 3.3 (2.0+4.5)/2 = 3.25 redondeado a 3.3.
   - Caso 5: Original = 3.0, Recup = 2.0, Aprob = 3.0. Esperado = 3.0.
   - Caso 6: Original = 1.0, Recup = 5.0, Aprob = 3.0. Esperado = 3.0.
3. 'tope_aprobacion':
   - Caso 7: Original = 2.0, Recup = 4.5, Aprob = 3.0. Esperado = 3.0.
   - Caso 8: Original = 2.0, Recup = 2.5, Aprob = 3.0. Esperado = 2.5.
   - Caso 9: Original = 3.5, Recup = 4.5, Aprob = 3.0. Esperado = 3.5.

El archivo debe consultar y mostrar en pantalla:
- La Escala Institucional Activa actualmente en la base de datos (Nota Mínima, Nota Máxima, Nota de Aprobación).
- La Política de Recuperación Global actual.
- Una tabla BEM con el desglose de los 9 casos de prueba indicando si pasaron o fallaron con badges verdes/rojos dinámicos.
- Un botón soberano de 44px de alto para 'VOLVER A EJECUTAR PRUEBAS' que recarga la página.

---
ARCHIVO 2: styles/modules/pruebas_formales.css
Este archivo contendrá las reglas CSS estructuradas con @layer modules y usando BEM. Implementará:
- .pruebas-formales-container
- .pruebas-formales-card (borde de 24px con variable var(--el-border-radius-lg, 24px))
- .pruebas-formales-table
- .btn-elite--pruebas (altura 44px, border-radius 12px)
- Todos los estilos requeridos para mostrar la vista premium (sombras con matiz primario, sin color HEX, uso de var(--el-*), geometría lógica).

Por favor, devuélveme el contenido completo de ambos archivos separados por la marca:
<<< ARCHIVO 1 >>>
[Contenido de pruebas_formales.php]
<<< ARCHIVO 2 >>>
[Contenido de pruebas_formales.css]
";

$data = [
    'model' => 'qwen2.5-coder:3b',
    'prompt' => $prompt,
    'stream' => false,
    'options' => [
        'num_ctx' => 8192,
        'temperature' => 0.1
    ]
];

$ch = curl_init('http://localhost:11434/api/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
curl_close($ch);

$resData = json_decode((string)$response, true);
$output = $resData['response'] ?? '';

if (empty($output)) {
    echo "Error: No se recibió respuesta de Ollama/Qwen.\n";
    exit(1);
}

// Separar archivos
$parts = explode('<<< ARCHIVO 1 >>>', $output);
if (count($parts) < 2) {
    echo "Error: Formato de salida no válido.\n";
    echo $output;
    exit(1);
}

$contentParts = explode('<<< ARCHIVO 2 >>>', $parts[1]);
$file1 = trim($contentParts[0]);
$file2 = isset($contentParts[1]) ? trim($contentParts[1]) : '';

// Limpiar bloques de código markdown si los hay
$file1 = preg_replace('/^```php/i', '', $file1);
$file1 = preg_replace('/^```html/i', '', $file1);
$file1 = preg_replace('/^```/i', '', $file1);
$file1 = preg_replace('/```$/', '', $file1);
$file1 = trim($file1);

$file2 = preg_replace('/^```css/i', '', $file2);
$file2 = preg_replace('/^```/i', '', $file2);
$file2 = preg_replace('/```$/', '', $file2);
$file2 = trim($file2);

// Crear los directorios si no existen
@mkdir('../php/vistas', 0777, true);
@mkdir('../styles/modules', 0777, true);

// Guardar
file_put_contents('../php/vistas/pruebas_formales.php', $file1);
file_put_contents('../styles/modules/pruebas_formales.css', $file2);

echo "¡Archivos creados con éxito!\n";
echo "Archivo 1 (pruebas_formales.php): " . strlen($file1) . " bytes.\n";
echo "Archivo 2 (pruebas_formales.css): " . strlen($file2) . " bytes.\n";
