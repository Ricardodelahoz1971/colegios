(function() {
    const gridRecientes = document.getElementById('estudiante-recientes');
    const gridRecursos = document.getElementById('estudiante-recursos');

    window.cargarDashboardEstudiante = function() {
        // 1. Cargar Biblioteca Principal
        fetch('logica/api_aula.php?action=listar_estudiante')
        .then(r => r.json())
        .then(data => {
            renderRecursos(data);
        });

        // 2. Cargar Smart Summary
        fetch('logica/api_aula.php?action=resumen_pendientes')
        .then(r => r.json())
        .then(data => {
            if (data.length > 0) {
                mostrarResumenPendientes(data);
            }
        });
    };

    function mostrarResumenPendientes(pendientes) {
        const modalEl = document.getElementById('modalResumenPendientes');
        if (!modalEl) return;

        const container = document.getElementById('lista-pendientes-resumen');
        container.innerHTML = '';
        
        pendientes.forEach(p => {
            const item = document.createElement('div');
            item.className = 'd-flex align-items-center justify-content-between p-3 rounded-3 bg-light mb-2';
            item.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="badge bg-primary bg-opacity-10 text-primary p-2 me-3">
                        <i class="bi bi-book"></i>
                    </div>
                    <span class="fw-bold text-dark small">${p.materia}</span>
                </div>
                <span class="badge bg-danger rounded-pill px-2 py-1 fs-nano">${p.total} PENDIENTES</span>
            `;
            container.appendChild(item);
        });

        // Extracción al body para romper el bloqueo del z-index
        if (!modalEl.dataset.moved) {
            document.body.appendChild(modalEl);
            modalEl.dataset.moved = "true";
        }
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function renderRecursos(data) {
        gridRecursos.innerHTML = '';
        if (!Array.isArray(data) || data.length === 0) {
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
                            <div class="icon-box-elite bg-${color} bg-opacity-10 text-${color} rounded-3 p-3 shadow-sm">
                                <i class="bi ${icon} fs-3"></i>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                ${nuevoBadge}
                                ${evaluativoBadge}
                            </div>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">${r.titulo_sincronizado || r.titulo}</h5>
                        <p class="text-secondary small mb-3 text-truncate-2 flex-grow-1">${r.descripcion || 'Sin descripción.'}</p>
                        
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-light text-primary border border-primary border-opacity-25 small fs-nano shadow-sm">${r.nombre_especialidad}</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top gap-2">
                            <div class="d-flex align-items-center gap-2">
                                ${r.visto != 0 ? '<i class="bi bi-check2-all text-success fs-5" title="Visto"></i>' : ''}
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary rounded-circle shadow-sm px-2 py-1" onclick="verRecurso(${r.id}, '${r.url_recurso}', event)" title="Abrir recurso">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </button>
                                ${r.tipo_recurso === 'PDF' ? `
                                <button class="btn btn-sm btn-outline-primary rounded-circle shadow-sm px-2 py-1" onclick="imprimirRecurso(${r.id}, '${r.url_recurso}', event)" title="Imprimir PDF">
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

    function obtenerUrlValida(url) {
        if (!url) return '#';
        if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('../') || url.startsWith('/')) {
            return url;
        }
        if (url.startsWith('uploads/')) {
            return '../' + url;
        }
        return 'https://' + url;
    }

    window.verRecurso = function(id, url, e = null) {
        if (e) e.stopPropagation();
        const fd = new FormData();
        fd.append('recurso_id', id);
        
        const finalUrl = obtenerUrlValida(url);
        
        fetch('logica/api_aula.php?action=marcar_visto', { method: 'POST', body: fd })
        .then(() => {
            window.open(finalUrl, '_blank');
            cargarDashboardEstudiante();
        });
    };

    window.imprimirRecurso = function(id, url, e = null) {
        if (e) e.stopPropagation();
        const fd = new FormData();
        fd.append('recurso_id', id);
        
        const finalUrl = obtenerUrlValida(url);
        
        // 1. Marca como visto
        fetch('logica/api_aula.php?action=marcar_visto', { method: 'POST', body: fd })
        .then(() => {
            // 2. Crea un iframe oculto para impresión nativa
            let iframe = document.getElementById('print-iframe-elite');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'print-iframe-elite';
                iframe.setAttribute('style', 'display: none;');
                document.body.appendChild(iframe);
            }
            
            iframe.onload = function() {
                setTimeout(() => {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                }, 500); // Pequeño delay para asegurar renderizado interno del PDF
            };
            
            // Forzar recarga del iframe
            iframe.src = finalUrl + '#toolbar=0&navpanes=0';
            
            // 3. Recargar stats del dashboard
            cargarDashboardEstudiante();
        });
    };

    cargarDashboardEstudiante();
})();