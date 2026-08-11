<?php
// 1. Iniciar sesión y guardar cookies
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/sistema_escolar/php/login.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'usuario' => 'admin',
    'password' => '1234'
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$res = curl_exec($ch);
curl_close($ch);

// 2. Fetch configuracion raw HTML
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/sistema_escolar/php/dashboard.php?p=configuracion&raw=1');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$html = curl_exec($ch);
curl_close($ch);

// 3. Buscar el tag de input en el HTML
$lines = explode("\n", $html);
$found = false;
foreach ($lines as $line) {
    if (strpos($line, 'khronos_inicio') !== false && strpos($line, '<input') !== false) {
        echo "HTML RECIBIDO POR EL CLIENTE:\n" . trim($line) . "\n";
        $found = true;
    }
}

if (!$found) {
    echo "Fallo: No se encontró el tag en el HTML devuelto por el servidor.\n";
    echo "Respuesta del Login:\n" . substr($res, 0, 500) . "\n";
}

@unlink('cookies.txt');
