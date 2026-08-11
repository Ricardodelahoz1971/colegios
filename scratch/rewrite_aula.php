<?php
$file = __DIR__ . '/../php/vistas/aula_virtual_estudiante.php';
$content = file_get_contents($file);

$new_render = <<<EOT
    function renderRecursos(data) {
        gridRecursos.innerHTML = '';
        if (data.length === 0) {
            gridRecursos.innerHTML = '<div class="col-12 text-center py-5"><h5 class="text-muted">Aún no hay recursos disponibles en tu curso.</h5></div>';
            return;
        }

        data.forEach(r => {
            const card = document.createElement('div');
            card.className = 'col-md-6 col-lg-4 col-xl-3 mb-4';
            
            let icon = 'bi-link-45deg';
            let color = 'info';
            if (r.tipo_recurso === 'PDF') { icon = 'bi-file-earmark-pdf-fill'; color = 'danger'; }
            if (r.tipo_recurso === 'VIDEO') { icon = 'bi-play-btn-fill'; color = 'primary'; }
            if (r.tipo_recurso === 'DOC') { icon = 'bi-file-earmark-word-fill'; color = 'info'; }
            
            let evaluativoBadge = r.es_evaluativo == 1 ? '<span class="badge bg-warning text-dark border border-warning small fs-nano shadow-sm"><i class="bi bi-star-fill text-dark me-1"></i>CALIFICABLE</span>' : '';
            let nuevoBadge = r.visto == 0 ? '<span class="badge bg-danger rounded-pill px-2 py-1 fs-nano badge-new-pulse shadow-sm">NUEVO</span>' : '';

            card.innerHTML = `
                <div class="card card-elite h-100 shadow-sm border-2">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="icon-box-elite bg-\${color} bg-opacity-10 text-\${color} rounded-3 p-3 shadow-sm">
                                <i class="bi \${icon} fs-3"></i>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                \${nuevoBadge}
                                \${evaluativoBadge}
                            </div>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">\${r.titulo_sincronizado || r.titulo}</h5>
                        <p class="text-secondary small mb-3 text-truncate-2 flex-grow-1">\${r.descripcion || 'Sin descripción.'}</p>
                        
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-light text-primary border border-primary border-opacity-25 small fs-nano shadow-sm">\${r.nombre_especialidad}</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top gap-2">
                            <div class="d-flex align-items-center gap-2">
                                \${r.visto != 0 ? '<i class="bi bi-check2-all text-success fs-5" title="Visto"></i>' : ''}
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary rounded-circle shadow-sm px-2 py-1" onclick="verRecurso(\${r.id}, '\${r.url_recurso}', event)" title="Abrir recurso">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </button>
                                \${r.tipo_recurso === 'PDF' ? `
                                <button class="btn btn-sm btn-outline-primary rounded-circle shadow-sm px-2 py-1" onclick="imprimirRecurso(\${r.id}, '\${r.url_recurso}', event)" title="Imprimir PDF">
                                    <i class="bi bi-printer-fill"></i>
                                </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            gridRecursos.appendChild(card);
        });
    }
EOT;

$start_pos = strpos($content, "function renderMaterias(data) {");
$end_pos = strpos($content, "window.verRecurso = function(id, url, e = null) {");

if ($start_pos !== false && $end_pos !== false) {
    $content = substr_replace($content, $new_render . "\n\n    ", $start_pos, $end_pos - $start_pos);
} else {
    echo "Could not find replacement bounds\n";
    exit;
}

file_put_contents($file, $content);
echo "Replaced render logic successfully\n";
