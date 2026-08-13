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
        'vendor', 'node_modules', '.agents', 'scratch', '.gemini', 'libs',
        'antigravity_auditor.php', 'ares_visual_audit.php', 
        'db_integridad.php', 'elite_showroom.js', 'elite_showroom.css'
    ];
    
    foreach ($ignoreList as $ignore) {
        if (str_contains($path, $ignore)) continue 2;
    }
    if (in_array($ext, ['php', 'js', 'css'])) {
        $contentArray = file($path);
        $fullContent = file_get_contents($path);
        $hasLayer = false;
        if ($ext === 'css') {
            if (str_contains($fullContent, '@layer')) $hasLayer = true;
        }

        $sastExempt = false;
        if ($ext === 'php') {
            $fullContentLow = strtolower($fullContent);
            if (str_contains($fullContent, '@sast-ignore') || str_contains($fullContent, '@sast-exempt')) {
                $sastIgnoreAllowed = [
                    'auth.php', 'auditoria.php', 'aplicacion_pruebas.php', 'api_actividades.php',
                    'asistencia.php', 'calendario.php', 'carga.php', 'configuracion.php', 'cursos.php',
                    'especialidades.php', 'editor_preguntas.php', 'estudiante_examenes.php', 'inicio.php',
                    'khronos.php', 'matriculados.php', 'matricula.php', 'mensajeria.php', 'reporte_listas.php',
                    'zulu.php', 'sabana_calificaciones.php', 'index.php', 'generador_demo_bi.php',
                    'purgar_vuelo_total.php', 'purga_academica_segura.php', 'imprimir_matricula.php'
                ];
                if (!in_array($filename, $sastIgnoreAllowed)) {
                    $reportContent .= "❌ **BYPASS ILEGAL DE SEGURIDAD**: `$filename` intenta ignorar el SAST (@sast-ignore) sin autorización.\n";
                    $violations++;
                } else {
                    $sastExempt = true;
                }
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

            // Bloqueo de justificaciones de bypass falsas
            $hasBypassComment = str_contains($low, '// layout ok') || str_contains($low, '// style ok') || str_contains($low, '// xss ok') || str_contains($low, '// html ok') || str_contains($low, 'html ok ?');
            if ($hasBypassComment) {
                $commentBypassAllowed = [
                    'presentar_examen.php',
                    'sabana_calificaciones.js'
                ];
                if (!in_array($filename, $commentBypassAllowed)) {
                    $reportContent .= "❌ **BYPASS ILEGAL DE ESTILO/XSS**: `$filename:$lineNum` usa comentario de escape de auditoría no autorizado.\n";
                    $violations++;
                }
            }

            // Veto de operaciones matemáticas de notas locales (Anti-parches)
            if ($ext === 'php' && !in_array($filename, ['calculadora_notas.php', 'api_sabana.php', 'db_integridad.php', 'seeder_maestro_v2.php', 'api_actividades.php', 'api_pruebas.php', 'api_pruebas_estudiante.php'])) {
                if (preg_match('/\$(?:calificacion|nota|promedio)[a-zA-Z0-9_]*\s*[\+\-\*\/]/i', $l) || 
                    preg_match('/[\+\-\*\/]\s*\$(?:calificacion|nota|promedio)[a-zA-Z0-9_]*/i', $l) ||
                    preg_match('/\$(?:suma|total_notas|acumulado)\s*\/=\s*\$/i', $l)) {
                    
                    $reportContent .= "❌ **CÁLCULO ACADÉMICO LOCAL**: `$filename:$lineNum` realiza operaciones matemáticas directas sobre calificaciones. Centralizar la lógica en CalculadoraNotas.\n";
                    $violations++;
                }
            }

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
            if (preg_match('/border-radius:\s*([0-9.]+(px|rem|em))/', $low) && !str_contains($low, 'var(') && $filename !== 'ui_kit.css' && $ext !== 'js') {
                $reportContent .= "❌ **RADIO ESTÁTICO**: Geometría fija detectada en `$filename:$lineNum` (Usar tokens)\n";
                $violations++;
            }

            // 6. VETO DE !IMPORTANT (Endurecido v4.5)
            $is_exception = in_array($filename, ['ui_kit.css', 'elite_themes.css', 'utilities.css', 'elite_print.css', 'index.php', 'dashboard.php', 'sweetalert2_customization.css', 'bootstrap_override.css']);
            if (str_contains($low, '!important') && !$is_exception) {
                $reportContent .= "❌ **PARCHE DETECTADO**: `!important` ilegal en `$filename:$lineNum` (Usar @layer para jerarquía)\n";
                $violations++;
            }

            // 8. VETO DE BOOTSTRAP PURO
            if (preg_match('/(?<!el-|-)\bbtn-(primary|secondary|success|danger|warning|info|light|dark)\b/', $low) && $filename !== 'bootstrap_override.css') {
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

            // 13. VETO DE PARCHES MANUALES EN RED (Cache Busters manuales)
            if (($ext === 'js' || $ext === 'php') && preg_match('/\bfetch\s*\(/i', $l)) {
                if (preg_match('/_t=|cache:\s*[\'"]no-store[\'"]/i', $l) && $filename !== 'dashboard.php') {
                    $reportContent .= "❌ **PARCHE DE RED DETECTADO**: `$filename:$lineNum` usa un cache-buster manual en `fetch()`. (El Motor Interceptor lo hace automáticamente, no usar parches locales)\n";
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
                        if ($filename !== 'db.php' && $filename !== 'security.php' && $filename !== 'imprimir_matricula.php' && !str_contains($low, 'die("sesión no válida")')) {
                            $reportContent .= "❌ **DEBUG OLVIDADO (PHP)**: `$filename:$lineNum` contiene debug (`var_dump`/`print_r`/`die`).\n";
                            $violations++;
                        }
                    }
                    // Debugs de JS
                    if (preg_match('/\b(?:console\.log|alert)\s*\(/i', $l)) {
                        if ($filename !== 'dashboard.php' && $filename !== 'elite_showroom.js' && !str_contains($l, 'SweetAlert') && !str_contains($l, 'Swal.fire')) {
                            $reportContent .= "❌ **DEBUG OLVIDADO (JS)**: `$filename:$lineNum` contamina la consola del navegador (`console.log`/`alert`).\n";
                            $violations++;
                        }
                    }

                    // 15. VETO DE CONTROL DE LAYOUT DESDE JAVASCRIPT (Separación de Conceptos)
                    if ($ext === 'js') {
                        if (preg_match('/\.style\.(?:width|minWidth|maxWidth|height|minHeight|maxHeight)\s*=/i', $l) ||
                            preg_match('/\.style\.setProperty\s*\(\s*[\'"](?:width|height|min-width|max-width|min-height|max-height)[\'"]/i', $l)) {
                            
                            $isJustified = str_contains($low, '// layout ok') || str_contains($low, '// style ok');
                            $isComment = str_starts_with($l, '//') || str_starts_with($l, '*') || str_starts_with($l, '/*');
                            
                            if (!$isJustified && !$isComment && 
                                $filename !== 'formatos_matricula_builder.js' && 
                                $filename !== 'ares_canvas_engine.js' && 
                                $filename !== 'ares_select_engine_v5.js' && 
                                $filename !== 'configuracion.js' && 
                                $filename !== 'perseus_engine.js') {
                                $reportContent .= "❌ **MAQUETACIÓN POR JS**: `$filename:$lineNum` modifica dimensiones físicas vía JS. (El diseño debe ser exclusivamente controlado por CSS)\n";
                                $violations++;
                            }
                        }
                    }
                }
            }
        }
        // 16. DETECTOR DE MONOLITOS (Líneas de Código excesivas)
        $lineCount = count($contentArray);
        $locExempt = [
            'ui_kit.css', 'imprimir_matricula.php', 
            'imprimir_examen.php', 'antigravity_auditor.php', 
            'ares_canvas_lab_editor.css', 'ares_canvas.css', 
            'sabana_calificaciones.css', 'formatos_matricula.css',
            'academic_manager.js', 'ares_canvas_engine.js', 
            'ares_canvas_lab_editor.js', 'ares_editor.js', 
            'configuracion.js', 'formatos_matricula_builder.js', 
            'perseus_engine.js', 'api_sabana.php', 
            'formatos_matricula.php', 'sabana_calificaciones.php',
            'sabana_calificaciones.css', 'sabana_calificaciones.js'
        ];
        if ($lineCount > 800 && !in_array($filename, $locExempt)) {
            $reportContent .= "❌ **NUDO MONOLÍTICO**: El archivo `$filename` excede las 800 líneas ($lineCount LOC). Debe ser modularizado.\n";
            $violations++;
        }

        // 17. VETO DE JAVASCRIPT INLINE EN PHP (Separación de Conceptos)
        if ($ext === 'php' && !$sastExempt) {
            $phpJsExempt = [
                'index.php', 'imprimir_matricula.php', 
                'imprimir_examen.php', 'imprimir_codigos.php',
                'security.php', 'agenda.php', 'aula_virtual_estudiante.php',
                'aula_virtual_gestion.php', 'calificar_pruebas.php',
                'constructor_actividades.php', 'constructor_pruebas.php',
                'formatos_matricula.php', 'presentar_examen.php',
                'pruebas_formales.php'
            ];
            if (!in_array($filename, $phpJsExempt)) {
                // Si contiene la etiqueta <script> con código real adentro (más de 20 caracteres)
                if (preg_match('/<script\b[^>]*>(?!.*src=)[^<]{20,}/is', $fullContent)) {
                    $reportContent .= "❌ **JAVASCRIPT INLINE ILEGAL**: `$filename` contiene bloques JavaScript inline. Todo código JS debe residir en archivos .js externos e independientes.\n";
                    $violations++;
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

// Inyectar auditoría de consumo matemático de DeepSeek
$auditLogFile = $projectRoot . '/php/logs/deepseek_audit.log';
$tokensReport = "\n## 📊 REPORTE MATEMÁTICO DE CONSUMO (DEEPSEEK CLOUD)\n";
if (file_exists($auditLogFile)) {
    $logLines = file($auditLogFile);
    $lastLog = end($logLines);
    if ($lastLog) {
        $tokensReport .= "> [!IMPORTANT]\n> **Último Registro de Uso**: " . trim((string)$lastLog) . "\n";
    } else {
        $tokensReport .= "> No hay registros de consumo en el archivo de log de DeepSeek.\n";
    }
} else {
    $tokensReport .= "> No se detectó archivo de log en la ruta esperada.\n";
}

$reportContent .= $tokensReport;
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