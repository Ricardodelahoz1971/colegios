<?php
$html = file_get_contents('C:/xampp/htdocs/sistema_escolar/scratch/dashboard_output.html');
$doc = new DOMDocument();
@$doc->loadHTML($html);
$xpath = new DOMXPath($doc);
$scripts = $xpath->query("//div[contains(@class, 'flex-fill') and contains(@class, 'overflow-auto')]//script");
$i = 1;
foreach ($scripts as $script) {
    echo "--- SCRIPT $i ---\n";
    echo $script->nodeValue . "\n";
    $i++;
}
