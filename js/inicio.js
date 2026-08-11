(function() {
    let cargasPerseus = [];
    let miRolPerseus = 0;

    function initPerseusCfg() {
        const bridge = document.getElementById('perseus-cfg-bridge');
        if (bridge) {
            try {
                const cfgRaw = bridge.getAttribute('data-perseus-cfg');
                if (cfgRaw) {
                    const cfg = JSON.parse(cfgRaw);
                    cargasPerseus = cfg.cargas || [];
                    miRolPerseus = cfg.miRol || 0;
                }
            } catch (e) {
                /* Silencio */
            }
        }
    }

    window.filtrarDocentesPerseus = function() {
        initPerseusCfg();
        const cursoSelector = document.getElementById('perseus-curso-selector');
        const docenteSelector = document.getElementById('perseus-docente-selector');
        if (!cursoSelector || !docenteSelector) return;

        const selectedCursoId = parseInt(cursoSelector.value, 10);

        // Limpiar opciones previas
        docenteSelector.innerHTML = '';

        // Opción por defecto
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = (miRolPerseus === 1 || miRolPerseus === 2 || miRolPerseus === 3)
            ? 'Seleccione Docente / Materia...'
            : 'Seleccione una materia...';
        docenteSelector.appendChild(defaultOpt);

        // Filtrar asignaciones asociadas al curso
        const filtradas = cargasPerseus.filter(c => parseInt(c.curso_id, 10) === selectedCursoId);

        filtradas.forEach(c => {
            const opt = document.createElement('option');
            opt.value = `${c.especialidad_id}-${c.curso_id}-${c.docente_id}`;
            if (miRolPerseus === 1 || miRolPerseus === 2 || miRolPerseus === 3) {
                opt.textContent = `${c.docente_nombre} | ${c.nombre_especialidad}`;
            } else {
                opt.textContent = c.nombre_especialidad;
            }
            docenteSelector.appendChild(opt);
        });

        // Actualizar el semáforo para la nueva selección activa
        actualizarPerseus();
    };

    window.actualizarPerseus = async function() {
        const selector = document.getElementById('perseus-docente-selector');
        if (!selector || !selector.value) {
            // Estado neutro cuando no hay selección válida
            const progressCircle = document.getElementById('perseus-progress');
            const percentTxt = document.getElementById('perseus-percent');
            if (progressCircle && percentTxt) {
                progressCircle.setAttribute('style', 'stroke-dashoffset: 283; stroke: var(--el-primary);');
                percentTxt.setAttribute('style', 'color: var(--el-primary);');
                percentTxt.innerText = '0%';
            }
            const dbaEval = document.getElementById('dba-evaluados');
            if (dbaEval) dbaEval.innerText = '0';
            const dbaTot = document.getElementById('dba-totales');
            if (dbaTot) dbaTot.innerText = '0';
            const evidEval = document.getElementById('evidencias-evaluadas');
            if (evidEval) evidEval.innerText = '0';
            const evidTot = document.getElementById('evidencias-totales');
            if (evidTot) evidTot.innerText = '0';
            const evidPct = document.getElementById('evidencias-porcentaje');
            if (evidPct) evidPct.innerText = '0';
            
            const badgeContainer = document.getElementById('perseus-status-badge');
            if (badgeContainer) badgeContainer.innerHTML = '<span class="badge-elite badge-elite--neutral">PENDIENTE</span>';
            
            const sugerenciaTxt = document.getElementById('perseus-sugerencia');
            if (sugerenciaTxt) sugerenciaTxt.innerText = 'Seleccione una opción para auditar la cobertura curricular.';
            
            const btnContainer = document.getElementById('perseus-btn-container');
            if (btnContainer) btnContainer.classList.add('d-none');
            return;
        }

        const [materiaId, cursoId, docenteId] = selector.value.split('-');

        // Feedback Visual Inicial
        const badgeContainer = document.getElementById('perseus-status-badge');
        if (badgeContainer) {
            badgeContainer.innerHTML = '<span class="badge-elite badge-elite--neutral animate__animated animate__pulse animate__infinite">ANALIZANDO...</span>';
        }

        try {
            const response = await fetch(`api_perseus.php?docente_id=${docenteId}&materia_id=${materiaId}&curso_id=${cursoId}`);
            const res = await response.json();

            if (res.status === 'success') {
                const data = res.data;
                const percent = data.cobertura_porcentaje;
                const dashoffset = 283 - (283 * percent / 100);

                // Actualizar Anillo y Porcentaje
                const progressCircle = document.getElementById('perseus-progress');
                const percentTxt = document.getElementById('perseus-percent');
                
                if (progressCircle) {
                    const colors = {
                        'rojo': 'var(--el-danger)',
                        'amarillo': 'var(--el-accent)',
                        'verde': 'var(--el-success)'
                    };
                    const color = colors[data.estado_semaforo] || 'var(--el-primary)';
                    progressCircle.setAttribute('style', `stroke-dashoffset: ${dashoffset}; stroke: ${color};`);
                    if (percentTxt) {
                        percentTxt.setAttribute('style', `color: ${color};`);
                        percentTxt.innerText = `${percent}%`;
                    }
                }

                // Actualizar Contadores
                document.getElementById('dba-evaluados').innerText = data.dba_evaluados;
                document.getElementById('dba-totales').innerText = data.dba_totales;
                
                document.getElementById('evidencias-evaluadas').innerText = data.evidencias_evaluadas;
                document.getElementById('evidencias-totales').innerText = data.evidencias_totales;
                document.getElementById('evidencias-porcentaje').innerText = data.cobertura_evidencias_porcentaje;

                // Actualizar Badge y Sugerencia
                const sugerenciaTxt = document.getElementById('perseus-sugerencia');
                const btnContainer = document.getElementById('perseus-btn-container');

                if (data.estado_semaforo === 'rojo') {
                    if (badgeContainer) badgeContainer.innerHTML = '<span class="badge-elite badge-elite--danger">COBERTURA CRÍTICA</span>';
                    if (sugerenciaTxt) sugerenciaTxt.innerText = "Se requiere inyectar nuevos reactivos del Banco Universal para cubrir los DBA faltantes.";
                    if (btnContainer) btnContainer.classList.remove('d-none');
                } else if (data.estado_semaforo === 'amarillo') {
                    if (badgeContainer) badgeContainer.innerHTML = '<span class="badge-elite badge-elite--warning">COBERTURA EN PROGRESO</span>';
                    if (sugerenciaTxt) sugerenciaTxt.innerText = "Buen avance. Refuerce los aprendizajes pendientes en la próxima evaluación.";
                    if (btnContainer) btnContainer.classList.remove('d-none');
                } else {
                    if (badgeContainer) badgeContainer.innerHTML = '<span class="badge-elite badge-elite--success">EXCELENCIA ACADÉMICA</span>';
                    if (sugerenciaTxt) sugerenciaTxt.innerText = "¡Felicidades! Ha cubierto los estándares exigidos para este periodo.";
                    if (btnContainer) btnContainer.classList.add('d-none');
                }

            }
        } catch (e) {
        }
    };

    // Ejecución inicial coordinada con efecto suave
    setTimeout(window.filtrarDocentesPerseus, 600);

    // Autodetectar y forzar recarga si el motor Ares JS está desactualizado en memoria (Caché Buster Élite)
    // NOTA: Esta es una validación crítica de integridad del motor. Solo recarga si el cache es inválido.
    if (typeof AresSelectEngine === 'undefined' || !AresSelectEngine.isDefinitiveCacheBusted) {
        // Caché inválido: recargar página completa es necesario para sincronizar motor Ares
        window.location.href = window.location.href;
    }
})();