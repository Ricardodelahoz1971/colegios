<?php
declare(strict_types=1);

/**
 * DEEPSEEK DISPATCHER DE TOKENS SOBERANO
 */

$configFile = __DIR__ . '/../antigravity.config.json';
if (!file_exists($configFile)) {
    echo "ERROR: No existe el archivo de configuración antigravity.config.json\n";
    exit(1);
}

$config = json_decode(file_get_contents($configFile), true);
$apiKey = $config['agent']['apiKey'] ?? '';
$baseUrl = 'https://api.deepseek.com/v1';
$endpoint = $baseUrl . '/chat/completions';

$systemPrompt = "Actúa como un programador de software senior experto en PHP, CSS y Javascript.
Debes devolver la modificación exacta de los archivos solicitados para implementar el salto de página físico y la duplicación de cabeceras en imprimir_matricula.php, de acuerdo al plan de implementación.";

$userPrompt = "Analiza e implementa las siguientes modificaciones físicas en el proyecto:

1. Modifica C:/xampp/htdocs/sistema_escolar/php/vistas/formatos_matricula.php:
   Inserta abajo de la tarjeta interactiva de 'Línea Divisoria' en el catálogo de variables (línea 595 aprox):
   ```html
   <div class=\"ares-var-card\" onclick=\"seleccionarVariableCatalogo('salto_pagina', 'Salto de Página', true)\">
       <i class=\"bi bi-file-earmark-break text-danger\"></i>
       <div><strong>Salto de Página</strong><span>Forzar salto de página físico</span></div>
   </div>
   ```

2. Modifica C:/xampp/htdocs/sistema_escolar/js/modules/formatos_matricula_builder.js:
   En `insertarBloqueEnCanvas` añade soporte para 'salto_pagina':
   ```javascript
   } else if (codigo === 'salto_pagina') {
       wrapper.style.height = '20px';
       wrapper.dataset.height = '20px';
       wrapper.classList.add('ares-bloque-salto-pagina');
       contenido = `
           <div class=\"block-content-wysiwyg p-0\" style=\"height: 100%; display: flex; align-items: center; justify-content: center; border: 1px dashed var(--el-danger); background-color: rgba(var(--el-danger-rgb), 0.05);\">
               <span style=\"font-size: 8px; font-weight: bold; color: var(--el-danger); text-transform: uppercase;\"><i class=\"bi bi-file-earmark-break me-1\"></i>Salto de Página Físico</span>
               <div class=\"bloque-backend-html d-none\" data-type=\"salto_pagina\">[[BLOQUE_SALTO_PAGINA]]</div>
           </div>
       `;
   ```

3. Modifica C:/xampp/htdocs/sistema_escolar/styles/modules/imprimir_matricula.css:
   Añade los estilos para el renderizado del salto de página e impresión independiente:
   ```css
   .ares-salto-pagina-render {
       page-break-before: always !important;
       break-before: page !important;
       height: 0 !important;
       overflow: hidden !important;
       border: none !important;
       margin: 0 !important;
       padding: 0 !important;
   }
   ```

4. Modifica C:/xampp/htdocs/sistema_escolar/imprimir_matricula.php:
   En el renderizador `$renderizador` (aprox línea 536), añade el soporte de retorno:
   ```php
   if ($tipo === 'salto_pagina') {
       $inner_html = '<div class=\"ares-salto-pagina-render\"></div>';
   }
   ```
   Y al final del script, antes de renderizar el layout final HTML, parsea el `$contenido_renderizado` buscando si contiene bloques con data-tipo 'salto_pagina'.
   Si contiene, divide `$contenido_renderizado` usando el tag de salto de página como delimitador y renderiza múltiples contenedores de tipo `<div class=\"print-container\">` (duplicando de forma automática en cada nuevo contenedor los bloques superiores que pertenezcan a la cabecera: 'logo', 'titulo_colegio', 'lema_colegio' y 'metadatos').

Retorna los fragmentos de código modificados en un formato listo para insertar.";

$data = [
    'model' => 'deepseek-chat',
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userPrompt]
    ],
    'temperature' => 0.1,
    'stream' => false
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "ERROR HTTP {$httpCode}: " . $response . "\n";
    exit(1);
}

$resData = json_decode((string)$response, true);
$content = $resData['choices'][0]['message']['content'] ?? '';

file_put_contents(__DIR__ . '/deepseek_codegen_result.txt', $content);
echo "OK: Código generado en scratch/deepseek_codegen_result.txt\n";
