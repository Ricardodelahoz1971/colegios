<?php
$files = [
    __DIR__ . '/../js/script.js',
    __DIR__ . '/../js/elite_showroom.js'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $original = $content;

    // Pattern 1: function name(...) { Swal.fire(...).then(...) } 
    // This is hard to regex safely. Instead I will just remove the .then from Swal.fire 
    // Wait, replacing Swal.fire().then((r) => { ... }) with const r = await Swal.fire(); if(r) { ... } requires knowing if the function is async.
    // It's safer to just do it manually with AST, but we don't have AST in PHP.
    
    // Pattern: fetch(...).then(r => r.json()).then(data => {
    //   if (data.status === 'success') ...
    // });
}
