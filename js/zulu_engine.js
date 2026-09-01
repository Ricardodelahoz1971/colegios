(function() {
    let dragData = null;

    window.handleDragStart = function(e) {
        const el = e.currentTarget;
        dragData = {
            docenteId: el.dataset.docenteId,
            materiaId: el.dataset.materiaId,
            cursoOrig: el.dataset.cursoOrig || 0,
            nombre: el.dataset.docenteNombre,
            materia: el.dataset.materiaNombre
        };
        e.dataTransfer.effectAllowed = 'copyMove';
        el.classList.add('dragging-active');
        let dropdownMenu = el.closest('.dropdown-menu');
        let toggleBtn = el.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
        setTimeout(() => {
            if (dropdownMenu) dropdownMenu.classList.remove('show');
            if (toggleBtn) { toggleBtn.classList.remove('show'); toggleBtn.setAttribute('aria-expanded', 'false'); }
        }, 10);
    };

    window.handleDragStartPozo = function(e) {
        const el = e.currentTarget;
        dragData = {
            docenteId: el.dataset.docenteId,
            materiaId: 0,
            cursoOrig: 0,
            nombre: el.dataset.docenteNombre,
            materia: ''
        };
        e.dataTransfer.effectAllowed = 'copy';
        el.classList.add('dragging-active');
    };

    window.handleDragEnd = function(e) { e.currentTarget.classList.remove('dragging-active'); };
    window.handleDragOver = function(e) { if (e.preventDefault) e.preventDefault(); e.currentTarget.classList.add('drop-over-active'); return false; };
    window.handleDragLeave = function(e) { e.currentTarget.classList.remove('drop-over-active'); };
    window.handleDragOverSlot = function(e) { if (e.preventDefault) e.preventDefault(); e.currentTarget.classList.add('slot-over-active'); return false; };
    window.handleDragLeaveSlot = function(e) { e.currentTarget.classList.remove('slot-over-active'); };

    window.handleDrop = async function(e) {
        if (e.stopPropagation) e.stopPropagation();
        e.currentTarget.classList.remove('drop-over-active');
        const cursoDestId = e.currentTarget.dataset.cursoDest;
        if (!dragData || dragData.cursoOrig === cursoDestId) return;

        if (parseInt(dragData.materiaId) === 0) {
            const faltantesIds = JSON.parse(e.currentTarget.dataset.materiasFaltantes || '[]');
            const faltantesNombres = JSON.parse(e.currentTarget.dataset.materiasNombresFaltantes || '[]');
            if (faltantesIds.length === 0) { Swal.fire('Completo', 'Curso ya asignado.', 'info'); return; }
            if (faltantesIds.length === 1) {
                const r = await Swal.fire({
                    title: 'Confirmar Asignación',
                    html: `¿Deseas asignar a <b>${dragData.nombre}</b> a la materia <b>${faltantesNombres[0]}</b>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, asignar',
                    cancelButtonText: 'Cancelar'
                });
                if (r.isConfirmed) ejecutarAsignacion(cursoDestId, 'asignar', false, faltantesIds[0]);
                return;
            }
            
            const inputOptions = {};
            faltantesIds.forEach((id, idx) => { inputOptions[id] = faltantesNombres[idx]; });
            const { value: materiaId } = await Swal.fire({ title: 'Seleccionar Materia', input: 'select', inputOptions: inputOptions, showCancelButton: true });
            if (materiaId) ejecutarAsignacion(cursoDestId, 'asignar', false, materiaId);
            return;
        }

        const result = await Swal.fire({
            title: 'Acción',
            html: `¿Qué deseas hacer con <b>${dragData.nombre}</b>?`,
            showDenyButton: true, showCancelButton: true,
            confirmButtonText: 'Duplicar', denyButtonText: 'Mover'
        });
        if (result.isConfirmed) ejecutarAsignacion(cursoDestId, 'asignar');
        else if (result.isDenied) ejecutarAsignacion(cursoDestId, 'trasladar');
    };

    window.handleDropSlot = async function(e, cursoDestId, materiaId) {
        if (e.stopPropagation) e.stopPropagation();
        e.currentTarget.classList.remove('slot-over-active');
        if (!dragData || (dragData.cursoOrig === cursoDestId && dragData.materiaId == materiaId)) return;
        ejecutarAsignacion(cursoDestId, 'asignar', false, materiaId);
    };

    window.handleDropTrash = async function(e) {
        if (e.stopPropagation) e.stopPropagation();
        if (!dragData || !dragData.cursoOrig) return;
        const r = await Swal.fire({ title: '¿Eliminar?', icon: 'warning', showCancelButton: true });
        if (r.isConfirmed) ejecutarAsignacion(0, 'eliminar', false, dragData.materiaId);
    };

    async function ejecutarAsignacion(cursoDest, accion, confirmar = false, materiaForzadaId = null) {
        const finalMateriaId = materiaForzadaId || dragData.materiaId;
        try {
            const resData = await fetch('logica/asignar_carga_ajax.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CSRF_TOKEN || ''
                },
                body: JSON.stringify({ 
                    docente_id: dragData.docenteId, 
                    materia_id: finalMateriaId, 
                    curso_orig: dragData.cursoOrig, 
                    curso_dest: cursoDest, 
                    accion: accion, 
                    confirmar: confirmar,
                    csrf_token: window.CSRF_TOKEN || ''
                })
            });
            const res = await resData.json();

            if (res.conflict) {
                const c = await Swal.fire({ title: 'Conflicto', text: res.message, icon: 'warning', showCancelButton: true });
                if (c.isConfirmed) ejecutarAsignacion(cursoDest, accion, true, finalMateriaId);
            } else if (res.success) {
                lanzarToastElite('success', res.message || 'Carga académica actualizada');
                window.forceRefreshElite = true;
                navegarModulo('zulu');
            } else {
                lanzarToastElite('danger', res.message || 'Error al asignar');
            }
        } catch (err) {
            lanzarToastElite('danger', 'Fallo al comunicarse con el servidor.');
        }
    }

    window.seleccionarCursoZulu = function(id) {
        document.querySelectorAll('.card-curso-detalle').forEach(card => {
            card.classList.add('d-none');
        });

        const cardSeleccionada = document.getElementById(`curso-detalle-${id}`);
        if (cardSeleccionada) {
            cardSeleccionada.classList.remove('d-none');
        }

        const selectorCursos = document.getElementById('selector-cursos-zulu');
        if (selectorCursos && selectorCursos.value !== String(id)) {
            selectorCursos.value = String(id);
        }

        document.querySelectorAll('.curso-item-zulu').forEach(button => {
            button.classList.remove('bg-primary-subtle', 'border-primary');
        });

        const buttonActivo = document.getElementById(`curso-item-${id}`);
        if (buttonActivo) {
            buttonActivo.classList.add('bg-primary-subtle', 'border-primary');
        }
    };

    window.filtrarCursosZulu = function() {
        const input = document.getElementById('buscador-cursos-zulu');
        const filter = input ? input.value.toLowerCase().trim() : '';
        const tarjetas = document.querySelectorAll('.card-curso-wrap');
        
        tarjetas.forEach(tarjeta => {
            const textoCurso = (tarjeta.getAttribute('data-curso-nombre') || tarjeta.textContent).toLowerCase();
            const coincide = textoCurso.includes(filter);
            tarjeta.classList.toggle('d-none', !coincide);
        });
    };

    window.filtrarPozo = function() {
        const busqueda = document.getElementById('busqueda-pozo').value.toLowerCase();
        document.querySelectorAll('.docente-pozo-item').forEach(item => item.classList.toggle('d-none', !item.getAttribute('data-docente-nombre').toLowerCase().includes(busqueda)));
    };

    window.limpiarBuscadorZulu = function() {
        const buscador = document.getElementById('buscador-lateral-zulu') || document.getElementById('buscador-cursos-zulu');
        if (buscador) {
            buscador.value = '';
        }
        window.filtrarCursosZulu('');
    };

    window.cargarPlanMaestro = async function(nivel) {
        if (!nivel) {
            document.querySelectorAll('.card-materia-item').forEach(card => card.classList.add('d-none'));
            return;
        }
        
        // Filtrar materias por nivel cliente-side
        const nivelInt = parseInt(nivel);
        document.querySelectorAll('.card-materia-item').forEach(card => {
            const min = parseInt(card.dataset.nivelDesde) || 1;
            const max = parseInt(card.dataset.nivelHasta) || 11;
            const visible = (nivelInt >= min && nivelInt <= max);
            card.classList.toggle('d-none', !visible);
        });

        try {
            const res = await fetch(`logica/zulu_admin_plan.php?nivel=${nivel}`);
            const data = await res.json();
            
            const checks = document.querySelectorAll('.chk-materia-plan');
            checks.forEach(c => c.checked = false);
            
            // Deshabilitar y reiniciar todos los inputs de intensidad
            document.querySelectorAll('.zulu-intensidad-input').forEach(i => { i.value = 1; i.disabled = true; });
            
            data.forEach(item => {
                const chk = document.querySelector(`.chk-materia-plan[value="${item.id}"]`);
                const inp = document.getElementById(`intensidad-mat-${item.id}`);
                if (chk) { chk.checked = true; if (inp) { inp.value = item.h; inp.disabled = false; } }
            });
        } catch (err) {
        }
    };

    window.toggleIntensidad = function(matId, checked) {
        const inp = document.getElementById(`intensidad-mat-${matId}`);
        if (inp) { inp.disabled = !checked; if (!checked) inp.value = 1; }
    };

    window.guardarPlanMaestro = async function() {
        const nivel = document.getElementById('nivel-plan-zulu').value;
        if (!nivel) return lanzarToastElite('warning', 'Por favor selecciona un nivel.');
        const materias = Array.from(document.querySelectorAll('.chk-materia-plan:checked')).map(c => {
            const h = document.getElementById(`intensidad-mat-${c.value}`).value;
            return { id: c.value, h: parseInt(h) };
        });
        try {
            const r = await fetch('logica/zulu_admin_plan.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ nivel, materias }) });
            const res = await r.json();

            if (res.success) {
                lanzarToastElite('success', 'Plan Maestro actualizado correctamente.');
                const modalBus = bootstrap.Modal.getInstance(document.getElementById('modalPlanMaster'));
                if (modalBus) modalBus.hide();
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                window.forceRefreshElite = true;
                navegarModulo('zulu');
            } else {
                lanzarToastElite('danger', res.message || 'Error al actualizar el Plan Maestro');
            }
        } catch (err) {
            lanzarToastElite('danger', 'Fallo al guardar el Plan Maestro.');
        }
    };

    window.abrirPlanMaestroConNivel = function(nivelId) {
        const select = document.getElementById('nivel-plan-zulu');
        if (select) {
            select.value = nivelId;
            window.cargarPlanMaestro(nivelId);
        }
        
        const modalEl = document.getElementById('modalPlanMaster');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    };
})();
