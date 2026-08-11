<?php
$file = __DIR__ . '/../js/script.js';
$content = file_get_contents($file);

// 1. borrarRol
$content = preg_replace(
    "/function borrarRol\(id, nombre\) \{(.+?)Swal\.fire\(\{(.+?)\}\)\.then\(\(result\) => \{(.+?)\}\);/s",
    "async function borrarRol(id, nombre) {\\1const result = await Swal.fire({\\2});if (result.isConfirmed) {\\3}",
    $content
);

// 2. borrarPersonal
$content = preg_replace(
    "/function borrarPersonal\(id, nombre\) \{(.+?)Swal\.fire\(\{(.+?)\}\)\.then\(\(result\) => \{(.+?)\}\);/s",
    "async function borrarPersonal(id, nombre) {\\1const result = await Swal.fire({\\2});if (result.isConfirmed) {\\3}",
    $content
);

// 3. verificarMensajes
// Replacing the fetch(...).then().then() with async/await
$verificarMensajesRegex = "/function verificarMensajes\(\) \{(.+?)fetch\('logica\/check_mensajes\.php\?_t=' \+ new Date\(\)\.getTime\(\), \{ cache: 'no-store', credentials: 'same-origin' \}\)\s*\.then\(response => response\.json\(\)\)\s*\.then\(data => \{(.+?)Swal\.fire\(\{(.+?)\}\)\.then\(\(result\) => \{(.+?)\}\);(.+?)fetch\(`logica\/obtener_mensajes_ajax\.php\?chat_id=\\\$\{chatId\}&chat_type=\\\$\{chatType\}&_t=` \+ new Date\(\)\.getTime\(\), \{ cache: 'no-store', credentials: 'same-origin' \}\)\s*\.then\(r => r\.json\(\)\)\s*\.then\(res => \{(.+?)\}\);\s*\}(.+?)\}\)\s*\.catch\(error => \{(.*?)\}\);/s";

if (preg_match($verificarMensajesRegex, $content)) {
    $content = preg_replace_callback($verificarMensajesRegex, function($matches) {
        $start = $matches[1];
        $body1 = $matches[2];
        $swalArgs = $matches[3];
        $swalThen = $matches[4];
        $body2 = $matches[5];
        $fetchRes = $matches[6];
        $end = $matches[7];
        $catch = $matches[8];

        $swalFixed = "const result = await Swal.fire({" . $swalArgs . "});" . $swalThen;
        $fetchFixed = "const r = await fetch(`logica/obtener_mensajes_ajax.php?chat_id=\${chatId}&chat_type=\${chatType}&_t=` + new Date().getTime(), { cache: 'no-store', credentials: 'same-origin' });\n            const res = await r.json();\n            " . $fetchRes;

        return "async function verificarMensajes() {\n    try {" . $start . "const response = await fetch('logica/check_mensajes.php?_t=' + new Date().getTime(), { cache: 'no-store', credentials: 'same-origin' });\n    const data = await response.json();\n    " . $body1 . $swalFixed . $body2 . $fetchFixed . "}\n    " . $end . "\n    } catch (error) {" . $catch . "}\n";
    }, $content);
}

file_put_contents($file, $content);
echo "script.js patched.\n";

$file2 = __DIR__ . '/../js/elite_showroom.js';
$content2 = file_get_contents($file2);

// Replace function deleteTheme(id, nombre)
$content2 = preg_replace(
    "/function deleteTheme\(id, nombre\) \{(.+?)Swal\.fire\(\{(.+?)\}\)\.then\(\(result\) => \{(.+?)\}\);/s",
    "async function deleteTheme(id, nombre) {\\1const result = await Swal.fire({\\2});if (result.isConfirmed) {\\3}",
    $content2
);

// Replace applyTheme(...) function which has fetch().then()
$content2 = preg_replace_callback(
    "/function applyTheme\(themeId\) \{(.+?)fetch\('logica\/aplicar_tema\.php', \{(.+?)\}\)\s*\.then\(response => response\.json\(\)\)\s*\.then\(data => \{(.+?)Swal\.fire\(\{(.+?)\}\)\.then\(\(\) => \{(.+?)\}\);\s*\}(.+?)\}\)\s*\.catch\(error => \{(.+?)\}\);/s",
    function($m) {
        return "async function applyTheme(themeId) {" . $m[1] . "try {\nconst response = await fetch('logica/aplicar_tema.php', {" . $m[2] . "});\nconst data = await response.json();\n" . $m[3] . "await Swal.fire({" . $m[4] . "});\n" . $m[5] . "\n" . $m[6] . "\n} catch (error) {" . $m[7] . "}\n";
    },
    $content2
);

// Replace renderThemesList which has fetch().then()
$content2 = preg_replace_callback(
    "/function renderThemesList\(\) \{(.+?)fetch\('logica\/elite_theme_manager\.php\?action=list'\)\s*\.then\(r => r\.json\(\)\)\s*\.then\(data => \{(.+?)\}\)\s*\.catch\(e => \{(.+?)\}\);/s",
    function($m) {
        return "async function renderThemesList() {" . $m[1] . "try {\nconst r = await fetch('logica/elite_theme_manager.php?action=list');\nconst data = await r.json();\n" . $m[2] . "\n} catch (e) {" . $m[3] . "}\n";
    },
    $content2
);

// Form submission logic inside DOMContentLoaded
$content2 = preg_replace_callback(
    "/themeForm\.addEventListener\('submit', function \(e\) \{(.+?)fetch\('logica\/elite_theme_manager\.php', \{(.+?)\}\)\s*\.then\(r => r\.json\(\)\)\s*\.then\(data => \{(.+?)Swal\.fire\(\{(.+?)\}\)\.then\(\(\) => \{(.+?)\}\);\s*\}(.+?)\}\)\s*\.catch\(err => \{(.+?)\}\);/s",
    function($m) {
        return "themeForm.addEventListener('submit', async function (e) {" . $m[1] . "try {\nconst r = await fetch('logica/elite_theme_manager.php', {" . $m[2] . "});\nconst data = await r.json();\n" . $m[3] . "await Swal.fire({" . $m[4] . "});\n" . $m[5] . "\n" . $m[6] . "\n} catch (err) {" . $m[7] . "}\n";
    },
    $content2
);

file_put_contents($file2, $content2);
echo "elite_showroom.js patched.\n";

