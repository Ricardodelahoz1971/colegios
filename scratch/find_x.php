<?php
$lines=file('php/vistas/editor_preguntas.php'); 
foreach($lines as $i=>$l) { 
    if(preg_match('/\bx\b/', $l)) echo ($i+1).': '.$l; 
}
