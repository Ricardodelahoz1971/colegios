<?php
$file = 'c:/xampp/htdocs/sistema_escolar/antigravity_auditor.php';
$content = <<<'EOD'
<?php
/**
 * 🛡️ ANTIGRAVITY INQUISITOR v4.5 - CERTIFICACIÓN DE CASCADA
 * "Cero deuda técnica. Cero compromiso. Soberanía Absoluta."
 */

$projectRoot = __DIR__;
$reportFile = $projectRoot . '/ELITE_AUDIT_REPORT.md';
$violations = 0;
$reportContent = "# 🏛️ REPORTE DE INQUISICIÓN ÉLITE\n\n";
$reportContent .= "Generado el: " . date('Y-m-d H:i:s') . "\n";
$reportContent .= "Estado: **SISTEMA BAJO CERTIFICACIÓN DE PUREZA**\n";
$reportContent .= "⚠️ **REGLA DE ORO**: \"A partir de hoy, en el nuevo código que se escriba, está prohibido usar `!important`\".\n\n";

echo "\n\033[1;32m🌟 INICIANDO CERTIFICACIÓN DE CASCADA (V4.5)...\033[0m\n";

$layeredFiles = [];
$allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectRoot));

foreach ($allFiles as $file) {
    if ($file->isDir()) continue;
    
    $path = $file->getPathname();
    $filename = $file->getFilename();
    $ext = strtolower($file->getExtension());

    // 0. SALVOCONDUCTO: Ignorar herramientas de auditoría, terceros, sistema y configuración maestra
    $ignoreList = [
        'vendor', 'node_modules', '.agents', 'scratch', '.gemini', 
        'antigravity_auditor.php', 'ares_visual_audit.php', 
        'db_integridad.php', 'elite_showroom.js', 'elite_showroom.css'
    ];
    
    foreach ($ignoreList as $ignore) {
        if (str_contains($path, $ignore)) continue 2;
    }

    if (in_array($ext, ['php', 'js', 'css'])) {
        $contentArray = file($path);
        $hasLayer = false;
        if ($ext === 'css') {
            $fullContent = file_get_contents($path);
            if (str_contains($fullContent, '@layer')) $hasLayer = true;
        }

        $sastExempt = false;
        if ($ext === 'php') {
            $fullContent = file_get_contents($path);
            $fullContentLow = strtolower($fullContent);
            if (str_contains($fullContent, '@sast-ignore') || str_contains($fullContent, '@sast-exempt')) {
                $sastExempt = true;
            }
            
            // 9. VERIFICACIÓN DE SESIÓN ACTIVA (Session Start Enforcement)
            if (!$sastExempt && str_contains($fullContent, '$_SESSION')) {
                $isView = str_contains($path, 'php\\vistas\\') || str_contains($path, 'php/vistas/');
                $hasSessionStart = str_contains($fullContentLow, 'session_start');
                $hasAuthInclude = str_contains($fullContentLow, 'auth.php') || str_contains($fullContentLow, 'auth_controller');
                
                if (!$isView && !$hasSessionStart && !$hasAuthInclude && $filename !== 'auth.php') {
                    $reportContent .= "❌ **SESIÓN INCOMPLETA**: `$filename` usa `\$_SESSION` pero no inicializa con `session_start()` ni incluye `auth.php` (Posible bypass de seguridad)\n";
                    $violations++;
                }
            }
        }

        foreach ($contentArray as $index => $line) {
            $lineNum = $index + 1;
            $l = trim($line);
            $low = strtolower($l);

            // 1. VETO DE COLORES HEXADECIMALES (Discernimiento v4.0)
            if (preg_match('/#[a-fA-F0-9]{3,6}/', $l, $matches)) {
                $hexValue = $matches[0];
                $isPhpDefault = preg_match('/\?\?\s*[\'"]#/', $l);
                $isJsDefault = preg_match('/\|\|\s*[\'"]#/', $l);
                $isHtmlValue = preg_match('/value=[\'"]#/', $low);
                
                // Indulto por necesidad técnica o comentarios de soberanía
                $isJustified = false;
                if (str_contains($l, '//')) {
                    $parts = explode('//', $l);
                    if (str_contains($parts[1], $hexValue)) $isJustified = true;
                    $comment = strtolower($parts[1]);
                    if (preg_match('/(qr|escane|soberan|necesario|institucional|identidad)/', $comment)) $isJustified = true;
                }
                
                $isFullComment = str_starts_with($l, '/*') || str_starts_with($l, '*') || str_starts_with($l, '//');

                if (!$isPhpDefault && !$isJsDefault && !$isHtmlValue && !$isJustified && !$isFullComment && $filename !== 'ui_kit.css' && $filename !== 'elite_themes.css') {
                    $reportContent .= "❌ **COLOREADO ILEGAL**: HEX detectado en `$filename:$lineNum` -> `$l` (Migrar a `var(--el-*)`)\n";
                    $violations++;
                }
            }

            // 2. VETO DE SOMBRAS NEGRAS
            if ((str_contains($low, 'rgba(0,0,0') || str_contains($low, 'rgba(0, 0, 0')) && $filename !== 'ui_kit.css' && $filename !== 'elite_themes.css') {
                $reportContent .= "❌ **SOMBRA SIN ALMA**: Sombra negra detectada en `$filename:$lineNum` (Usar matiz primario)\n";
                $violations++;
            }

            // 3. VETO DE ESTILOS INLINE
            if (str_contains($low, 'style=') && $ext === 'php') {
                $reportContent .= "❌ **ESTILO INLINE**: Atributo `style` prohibido en `$filename:$lineNum`\n";
                $violations++;
            }

            // 4. VETO DE BLOQUES <STYLE> EN PHP
            if (str_contains($low, '<style') && $ext === 'php' && !in_array($filename, ['index.php', 'dashboard.php'])) {
                $reportContent .= "❌ **INYECCIÓN CSS**: Bloque `<style>` detectado en `$filename:$lineNum`\n";
                $violations++;
            }

            // 5. VETO DE RADIOS FIJOS
            if (preg_match('/border-radius:\s*([0-9.]+(px|rem|em))/', $low) && !str_contains($low, 'var(') && $filename !== 'ui_kit.css') {
                $reportContent .= "❌ **RADIO ESTÁTICO**: Geometría fija detectada en `$filename:$lineNum` (Usar tokens)\n";
                $violations++;
            }

            // 6. VETO DE !IMPORTANT (Endurecido v4.5)
            $is_exception = in_array($filename, ['ui_kit.css', 'elite_themes.css', 'utilities.css', 'elite_print.css', 'index.php', 'dashboard.php']);
            if (str_contains($low, '!important') && !$is_exception) {
                $reportContent .= "❌ **PARCHE DETECTADO**: `!important` ilegal en `$filename:$lineNum` (Usar @layer para jerarquía)\n";
                $violations++;
            }

            // 8. VETO DE BOOTSTRAP PURO
            if (preg_match('/\bbtn-(primary|secondary|success|danger|warning|info|light|dark)\b/', $low)) {
                $reportContent .= "❌ **LEGACY BOOTSTRAP**: Clase de botón Bootstrap en `$filename:$lineNum`\n";
                $violations++;
            }

            // 10. VETO DE CONSULTAS SQL DINÁMICAS (Inyección SQL Prevention)
            if ($ext === 'php' && !$sastExempt) {
                if (preg_match('/->(?:prepare|query)\s*\(\s*["\']\s*(?:SELECT|INSERT|UPDATE|DELETE)[^"\']+\$[a-zA-Z0-9_]+/i', $l) ||
                    preg_match('/->(?:prepare|query)\s*\(\s*["\'][^"\']*(?:SELECT|INSERT|UPDATE|DELETE)[^"\']*(?:\.\s*\$[a-zA-Z0-9_]|\$[a-zA-Z0-9_]\s*\.)/i', $l)) {
                    
                    $isComment = str_starts_with($l, '//') || str_starts_with($l, '*') || str_starts_with($l, '/*') || str_contains($l, '*');
                    if (!$isComment) {
                        $reportContent .= "❌ **SQL DINÁMICA EXPUESTA**: `$filename:$lineNum` realiza consulta dinámica directa (Usar Prepared Statements con Bindings (? o :param))\n";
                        $violations++;
                    }
                }
            }

            // 11. VETO DE INCLUSIONES DINÁMICAS EXPUESTAS (LFI Prevention)
            if ($ext === 'php' && !$sastExempt) {
                if (preg_match('/\b(?:include|require)(?:_once)?\b\s*\(?\s*[^;]*(?:\$_GET|\$_POST|\$_REQUEST)/i', $l)) {
                    $isComment = str_starts_with($l, '//') || str_starts_with($l, '*') || str_starts_with($l, '/*');
                    if (!$isComment) {
                        $reportContent .= "❌ **INCLUSIÓN EXPUESTA (LFI)**: `$filename:$lineNum` incluye dinámicamente archivos usando superglobales (Riesgo de Inclusión de Archivos Locales)\n";
                        $violations++;
                    }
                }
            }

            // 12. VETO DE RENDERIZADO SIN SANITIZAR XSS
            if ($ext === 'php' && !$sastExempt) {
                $isViewFile = str_contains($path, 'php\\vistas\\') || str_contains($path, 'php/vistas/') || $filename === 'index.php';
                if ($isViewFile) {
                    if (preg_match('/(?:echo\s+\$[a-zA-Z0-9_\[\]\'"]+|<\?=\s*\$[a-zA-Z0-9_\[\]\'"]+)/i', $l)) {
                        $hasSanitization = preg_match('/\b(?:htmlspecialchars|escapeHtml|e|intval|floatval|count|number_format|implode|htmlspecialchars_decode)\b/i', $l);
                        $isJustified = str_contains($low, '// xss ok') || str_contains($low, '// html ok');
                        $isComment = str_starts_with($l, '//') || str_starts_with($l, '*') || str_starts_with($l, '/*');
                        
                        if (!$hasSanitization && !$isJustified && !$isComment) {
                            $reportContent .= "❌ **SALIDA XSS RIESGOSA**: `$filename:$lineNum` renderiza variable directa sin sanitizar (Usar e() o escapeHtml())\n";
                            $violations++;
                        }
                    }
                }
            }

            // 13. VETO DE FETCH LOCAL (Naked Fetch)
            if (($ext === 'js' || $ext === 'php') && preg_match('/\bfetch\s*\(/i', $l)) {
                $isCoreFile = in_array($filename, ['dashboard.php', 'ui_navigator.js', 'perseus_engine.js']);
                $isComment = str_starts_with($l, '//') || str_starts_with($l, '*') || str_starts_with($l, '/*');
                if (!$isCoreFile && !$isComment) {
                    $reportContent .= "❌ **PARCHE DE RED DETECTADO**: `$filename:$lineNum` usa `fetch()` directamente. (Delegar al Motor Interceptor global)\n";
                    $violations++;
                }
            }

            // 14. VETO DE CÓDIGO INERTE Y DEBUGS
            if ($ext === 'php' || $ext === 'js') {
                $isComment = str_starts_with(trim($l), '//');
                
                if ($isComment) {
                    $commentContent = trim(substr(trim($l), 2));
                    // Regla de código comentado
                    if (preg_match('/^(?:\$[a-zA-Z0-9_]+\s*=|echo\s|console\.log|var_dump|print_r|fetch\s*\(|function\s+[a-zA-Z0-9_]+\s*\()/', $commentContent)) {
                        // Ignorar directivas especiales
                        if (!str_contains($commentContent, 'xss ok') && !str_contains($commentContent, 'html ok') && !str_contains($commentContent, 'MÓDULO PURIFICADO')) {
                            $reportContent .= "❌ **CÓDIGO INERTE (ZOMBIE)**: `$filename:$lineNum` contiene código comentado. (Eliminar líneas muertas)\n";
                            $violations++;
                        }
                    }
                    // Regla de TODOs
                    if (preg_match('/\b(?:TODO|FIXME|PARCHE)\b/i', $commentContent)) {
                        $reportContent .= "❌ **DEUDA TÉCNICA (TODO)**: `$filename:$lineNum` contiene un TODO/FIXME. (Resolver la deuda ahora)\n";
                        $violations++;
                    }
                } else {
                    // Debugs de PHP
                    if ($ext === 'php' && preg_match('/\b(?:var_dump|print_r|die)\s*\(/i', $l)) {
                        // Ignorar die en ciertos casos críticos
                        if ($filename !== 'db.php' && $filename !== 'security.php' && !str_contains($low, 'die("sesión no válida")')) {
                            $reportContent .= "❌ **DEBUG OLVIDADO (PHP)**: `$filename:$lineNum` contiene debug (`var_dump`/`print_r`/`die`).\n";
                            $violations++;
                        }
                    }
                    // Debugs de JS
                    if (preg_match('/\b(?:console\.log|console\.warn|console\.error|alert)\s*\(/i', $l)) {
                        if ($filename !== 'dashboard.php' && $filename !== 'elite_showroom.js' && !str_contains($l, 'SweetAlert') && !str_contains($l, 'Swal.fire')) {
                            $reportContent .= "❌ **DEBUG OLVIDADO (JS)**: `$filename:$lineNum` contamina la consola del navegador (`console.log`/`alert`).\n";
                            $violations++;
                        }
                    }
                }
            }
        }

        // 7. VETO DE ARCHIVOS CSS SIN CAPAS (Alineación de la Cascada)
        if ($ext === 'css' && !$hasLayer && !in_array($filename, ['elite_layers.css', 'elite_themes.css'])) {
            $reportContent .= "❌ **CASCADA SIN ALINEAR**: El archivo `$filename` no utiliza `@layer` (ADN Vitrina 06 Obligatorio)\n";
            $violations++;
        }
    }
}

$reportContent .= "\n---\n**VEREDICTO FINAL**: " . ($violations === 0 ? "CERTIFICACIÓN CONCEDIDA - 0 VIOLACIONES" : "$violations VIOLACIONES RESTANTES") . ".\n";

file_put_contents($reportFile, $reportContent);
echo "------------------------------------------------\n";
if ($violations === 0) {
    echo "\033[1;32m✅ ARQUITECTURA CERTIFICADA: ELITE PURENESS ACHIEVEMENT UNLOCKED.\033[0m\n";
} else {
    echo "\033[1;31m❌ SE HAN ENCONTRADO $violations VIOLACIONES GRAVES.\033[0m\n";
    echo "\033[1;33m👉 REVISAR ELITE_AUDIT_REPORT.md PARA LAS CORRECCIONES.\033[0m\n";
}
echo "------------------------------------------------\n\n";
EOD;
file_put_contents($file, $content);
