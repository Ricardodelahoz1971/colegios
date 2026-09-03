(function() {
    let dragData = null;

    window.handleDragStart = function(e) {
        const el = e.currentTarget;
        dragData = {
            docenteId: el.dataset.docenteId,
            materiaId: el.dataset.materiaId,
            cursoOrig: el.dataset.cursoOrig || 0,
            nombre: el.dataset.docenteNombre,
            materia: el.dataset.materiaNombre,
            jornada: el.dataset.docenteJornada || 'Completa'
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
        if (typeaheadTimeout) clearTimeout(typeaheadTimeout);
        dragData = {
            docenteId: el.dataset.docenteId,
            especialidadId: parseInt(el.dataset.especialidadId) || 0,
            materiaId: 0,
            cursoOrig: 0,
            nombre: el.dataset.docenteNombre,
            materia: el.dataset.docenteMateria || '',
            jornada: el.dataset.docenteJornada || 'Completa'
        };
        e.dataTransfer.effectAllowed = 'copy';
        el.classList.add('dragging-active');
    };

    window.handleDragEnd = function(e) { 
        e.currentTarget.classList.remove('dragging-active'); 
        if (typeaheadBuffer.length > 0) {
            if (typeaheadTimeout) clearTimeout(typeaheadTimeout);
            typeaheadTimeout = setTimeout(resetearFiltroTypeahead, 4000);
        }
    };
    window.handleDragOver = function(e) { if (e.preventDefault) e.preventDefault(); e.currentTarget.classList.add('drop-over-active'); return false; };
    window.handleDragLeave = function(e) { e.currentTarget.classList.remove('drop-over-active'); };
    window.handleDragOverSlot = function(e) { if (e.preventDefault) e.preventDefault(); e.currentTarget.classList.add('slot-over-active'); return false; };
    window.handleDragLeaveSlot = function(e) { e.currentTarget.classList.remove('slot-over-active'); };

    async function validarJornadaDocente(cursoDestId, docenteObj) {
        const cardDest = document.querySelector(`.card-zulu-elite[data-curso-dest="${cursoDestId}"]`);
        if (!cardDest || !docenteObj || !docenteObj.jornada || docenteObj.jornada === 'Completa') {
            return true;
        }
        const cursoJornada = cardDest.dataset.cursoJornada || 'Mañana';
        if (cursoJornada !== 'Completa' && docenteObj.jornada.toLowerCase() !== cursoJornada.toLowerCase()) {
            const c = await Swal.fire({
                title: 'Incompatibilidad de Jornada',
                html: `
                    <div class="text-start">
                        <p class="small text-muted mb-2">El docente <strong class="text-dark">${docenteObj.nombre}</strong> está contratado para Jornada <span class="badge badge-elite badge-elite--info">${docenteObj.jornada}</span>.</p>
                        <p class="small text-danger mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> El curso seleccionado pertenece a la Jornada <strong class="text-dark">${cursoJornada}</strong>.</p>
                        <p class="small fw-bold text-dark mt-3 mb-0">¿Deseas autorizar esta asignación de todas formas?</p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, autorizar',
                cancelButtonText: 'Cancelar'
            });
            return c.isConfirmed;
        }
        return true;
    }

    window.handleDrop = async function(e) {
        if (e.stopPropagation) e.stopPropagation();
        
        // 1. CAPTURAR DATOS DEL DOM DE FORMA SÍNCRONA ANTES DE CUALQUIER AWAIT
        const dropCard = e.currentTarget;
        dropCard.classList.remove('drop-over-active');
        const cursoDestId = dropCard.dataset.cursoDest;
        const faltantesRaw = dropCard.dataset.materiasFaltantes || '[]';
        const faltantesNombresRaw = dropCard.dataset.materiasNombresFaltantes || '[]';
        
        if (!dragData || dragData.cursoOrig === cursoDestId) return;
        const docenteActual = { ...dragData };

        // 2. VALIDAR COMPATIBILIDAD DE JORNADA
        const autorizaJornada = await validarJornadaDocente(cursoDestId, docenteActual);
        if (!autorizaJornada) return;

        // 3. PROCESAR ASIGNACIÓN
        let faltantesIds = [];
        let faltantesNombres = [];
        try { faltantesIds = JSON.parse(faltantesRaw); } catch(err) { faltantesIds = []; }
        try { faltantesNombres = JSON.parse(faltantesNombresRaw); } catch(err) { faltantesNombres = []; }

        if (parseInt(docenteActual.materiaId) === 0) {
            const espDocente = parseInt(docenteActual.especialidadId) || 0;
            let materiaIdObjetivo = null;
            let materiaNombreObjetivo = docenteActual.materia || '';

            if (espDocente > 0 && faltantesIds.includes(espDocente)) {
                materiaIdObjetivo = espDocente;
                const idx = faltantesIds.indexOf(espDocente);
                materiaNombreObjetivo = faltantesNombres[idx] || materiaNombreObjetivo;
            } else if (faltantesIds.length === 1) {
                materiaIdObjetivo = faltantesIds[0];
                materiaNombreObjetivo = faltantesNombres[0];
            } else if (faltantesIds.length > 1) {
                const inputOptions = {};
                faltantesIds.forEach((id, idx) => { inputOptions[id] = faltantesNombres[idx]; });
                const { value: matSel } = await Swal.fire({ 
                    title: 'Seleccionar Materia', 
                    input: 'select', 
                    inputOptions: inputOptions, 
                    inputValue: (espDocente > 0 && inputOptions[espDocente]) ? espDocente : faltantesIds[0],
                    showCancelButton: true,
                    cancelButtonText: 'Cancelar'
                });
                if (!matSel) return;
                materiaIdObjetivo = matSel;
                materiaNombreObjetivo = inputOptions[matSel];
            } else {
                Swal.fire('Curso Completo', 'Este curso ya tiene todas las materias asignadas. Para reemplazar un docente, suéltelo sobre la materia en "Ver carga".', 'info');
                return;
            }

            const cursosFinales = await solicitarCursosReplicacion(cursoDestId, materiaIdObjetivo, materiaNombreObjetivo, docenteActual);
            if (cursosFinales) {
                ejecutarAsignacion(cursosFinales, 'asignar', false, materiaIdObjetivo, docenteActual);
            }
            return;
        }

        const result = await Swal.fire({
            title: 'Acción',
            html: `¿Qué deseas hacer con <b>${docenteActual.nombre}</b>?`,
            showDenyButton: true, 
            showCancelButton: true,
            confirmButtonText: 'Duplicar', 
            denyButtonText: 'Mover',
            cancelButtonText: 'Cancelar'
        });
        if (result.isConfirmed) {
            const cursosFinales = await solicitarCursosReplicacion(cursoDestId, docenteActual.materiaId, docenteActual.materia, docenteActual);
            if (cursosFinales) ejecutarAsignacion(cursosFinales, 'asignar', false, null, docenteActual);
        } else if (result.isDenied) {
            ejecutarAsignacion([cursoDestId], 'trasladar', false, null, docenteActual);
        }
    };

    window.handleDropSlot = async function(e, cursoDestId, materiaId) {
        if (e.stopPropagation) e.stopPropagation();
        const dropSlot = e.currentTarget;
        dropSlot.classList.remove('slot-over-active');
        if (!dragData || (dragData.cursoOrig === cursoDestId && dragData.materiaId == materiaId)) return;

        const docenteActual = { ...dragData };

        const autorizaJornada = await validarJornadaDocente(cursoDestId, docenteActual);
        if (!autorizaJornada) return;

        const slotMateriaNombre = dropSlot.dataset.materiaNombre || docenteActual.materia || 'Asignatura';
        const cursosFinales = await solicitarCursosReplicacion(cursoDestId, materiaId, slotMateriaNombre, docenteActual);
        if (cursosFinales) {
            ejecutarAsignacion(cursosFinales, 'asignar', false, materiaId, docenteActual);
        }
    };

    window.handleDropTrash = async function(e) {
        if (e.stopPropagation) e.stopPropagation();
        if (!dragData || !dragData.cursoOrig) return;
        const docenteActual = { ...dragData };
        const r = await Swal.fire({ title: '¿Eliminar?', icon: 'warning', showCancelButton: true });
        if (r.isConfirmed) ejecutarAsignacion([0], 'eliminar', false, docenteActual.materiaId, docenteActual);
    };

    async function solicitarCursosReplicacion(cursoDestId, materiaId, materiaNombre, docenteObj) {
        const dObj = docenteObj || dragData;
        const cardDest = document.querySelector(`.card-zulu-elite[data-curso-dest="${cursoDestId}"]`);
        if (!cardDest) return [parseInt(cursoDestId)];

        const nivelId = cardDest.dataset.nivelId;
        const cursoNombre = cardDest.dataset.cursoNombre || `Curso #${cursoDestId}`;
        
        const cardsHermanos = Array.from(document.querySelectorAll(`.card-zulu-elite[data-nivel-id="${nivelId}"]`))
            .filter(c => c.dataset.cursoDest !== String(cursoDestId));

        if (cardsHermanos.length === 0) {
            return [parseInt(cursoDestId)];
        }

        const hermanosData = cardsHermanos.map(c => ({
            id: parseInt(c.dataset.cursoDest),
            nombre: c.dataset.cursoNombre || `Curso #${c.dataset.cursoDest}`
        }));

        const htmlContent = `
            <div class="text-start mt-2">
                <p class="small text-muted mb-2">Docente: <strong class="text-dark">${dObj.nombre}</strong></p>
                <p class="small text-muted mb-3">Materia: <strong class="text-primary">${materiaNombre || dObj.materia || 'Asignatura'}</strong></p>
                <div class="p-2 mb-2 rounded-3 bg-light border">
                    <span class="fs-nano text-uppercase text-secondary fw-bold">Curso asignado:</span>
                    <div class="fw-bold text-dark small">${cursoNombre}</div>
                </div>
                <p class="small fw-bold text-dark mb-2">¿Deseas aplicar también a los demás cursos del grado?</p>
                <div class="d-flex flex-column gap-1 max-h-200 overflow-auto p-2 border rounded-3 bg-white">
                    ${hermanosData.map(h => `
                        <label class="d-flex align-items-center p-2 rounded-2 bg-light border cursor-pointer m-0">
                            <input type="checkbox" class="form-check-input-elite chk-curso-replicar me-2" value="${h.id}" checked>
                            <span class="small fw-bold text-dark">${h.nombre}</span>
                        </label>
                    `).join('')}
                </div>
            </div>
        `;

        const res = await Swal.fire({
            title: 'Replicador por Grado',
            html: htmlContent,
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: '<i class="bi bi-check2-all me-1"></i> Asignar a seleccionados',
            denyButtonText: 'Solo a este curso',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                return Array.from(document.querySelectorAll('.chk-curso-replicar:checked')).map(cb => parseInt(cb.value));
            }
        });

        if (res.isConfirmed) {
            const seleccionados = res.value || [];
            return [parseInt(cursoDestId), ...seleccionados];
        } else if (res.isDenied) {
            return [parseInt(cursoDestId)];
        } else {
            return null;
        }
    }

    async function ejecutarAsignacion(cursosDest, accion, confirmar = false, materiaForzadaId = null, docenteObj = null) {
        const dObj = docenteObj || dragData;
        const finalMateriaId = materiaForzadaId || dObj.materiaId;
        const cursosArray = Array.isArray(cursosDest) ? cursosDest : [cursosDest];

        const inputCurso = document.getElementById('buscador-cursos-zulu');
        if (inputCurso && inputCurso.value.trim()) {
            window.ZULU_CURSO_FILTRO = inputCurso.value.trim();
        }

        try {
            const resData = await fetch('logica/asignar_carga_ajax.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CSRF_TOKEN || ''
                },
                body: JSON.stringify({ 
                    docente_id: dObj.docenteId, 
                    materia_id: finalMateriaId, 
                    curso_orig: dObj.cursoOrig, 
                    cursos_dest: cursosArray, 
                    accion: accion, 
                    confirmar: confirmar,
                    csrf_token: window.CSRF_TOKEN || ''
                })
            });
            const res = await resData.json();

            if (res.conflict) {
                const c = await Swal.fire({ 
                    title: 'Conflicto / Horas Extras', 
                    text: res.message, 
                    icon: 'warning', 
                    showCancelButton: true, 
                    confirmButtonText: 'Autorizar', 
                    cancelButtonText: 'Cancelar' 
                });
                if (c.isConfirmed) ejecutarAsignacion(cursosArray, accion, true, finalMateriaId, dObj);
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
        const filter = input ? input.value.trim() : '';
        window.ZULU_CURSO_FILTRO = filter;
        const tarjetas = document.querySelectorAll('.card-curso-wrap');
        
        if (!filter) {
            tarjetas.forEach(tarjeta => tarjeta.classList.remove('d-none'));
            return;
        }

        const tokens = filter
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .split(/\s+/)
            .filter(t => t.length > 0);

        tarjetas.forEach(tarjeta => {
            const rawSearch = tarjeta.getAttribute('data-curso-search') || tarjeta.getAttribute('data-curso-nombre') || tarjeta.textContent;
            const normalizedText = (rawSearch || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');

            const coincide = tokens.every(token => normalizedText.includes(token));
            tarjeta.classList.toggle('d-none', !coincide);
        });
    };

    window.filtrarPozo = function() {
        const input = document.getElementById('busqueda-pozo');
        const busqueda = input ? input.value.trim() : '';
        sessionStorage.setItem('zulu_filtro_pozo', busqueda);
        const items = document.querySelectorAll('.docente-pozo-item');

        if (!busqueda) {
            items.forEach(item => item.classList.remove('d-none'));
            return;
        }

        const tokens = busqueda
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .split(/\s+/)
            .filter(t => t.length > 0);

        items.forEach(item => {
            const nom = item.getAttribute('data-docente-nombre') || '';
            const mat = item.getAttribute('data-docente-materia') || '';
            const jor = item.getAttribute('data-docente-jornada') || '';
            const target = `${nom} ${mat} ${jor}`
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');

            const coincide = tokens.every(token => target.includes(token));
            item.classList.toggle('d-none', !coincide);
        });
    };

    window.limpiarBuscadorZulu = function() {
        const buscador = document.getElementById('buscador-lateral-zulu') || document.getElementById('buscador-cursos-zulu');
        if (buscador) {
            buscador.value = '';
        }
        window.ZULU_CURSO_FILTRO = '';
        window.filtrarCursosZulu();
    };

    window.restaurarFiltrosZulu = function() {
        const inputCurso = document.getElementById('buscador-cursos-zulu');
        if (inputCurso) {
            if (window.ZULU_CURSO_FILTRO) {
                inputCurso.value = window.ZULU_CURSO_FILTRO;
                window.filtrarCursosZulu();
            } else {
                inputCurso.value = '';
            }
        }

        const filtroPozo = sessionStorage.getItem('zulu_filtro_pozo');
        const inputPozo = document.getElementById('busqueda-pozo');
        if (filtroPozo && inputPozo) {
            inputPozo.value = filtroPozo;
            window.filtrarPozo();
        }
    };

    window.tabPlanActivo = 'todas';

    window.filtrarPlanTab = function(areaId) {
        window.tabPlanActivo = areaId || 'todas';
        document.querySelectorAll('.zulu-tab-pill').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.areaTab === window.tabPlanActivo);
        });
        window.aplicarFiltrosPlanMaestro();
    };

    window.aplicarFiltrosPlanMaestro = function() {
        const nivel = document.getElementById('nivel-plan-zulu')?.value;
        const nivelInt = parseInt(nivel) || 0;
        const tab = window.tabPlanActivo || 'todas';
        const areasPrincipales = ['1', '2', '3', '4', '8', '9'];

        document.querySelectorAll('.card-materia-item').forEach(card => {
            if (!nivelInt) {
                card.classList.add('d-none');
                return;
            }

            const min = parseInt(card.dataset.nivelDesde) || 1;
            const max = parseInt(card.dataset.nivelHasta) || 11;
            const visibleNivel = (nivelInt >= min && nivelInt <= max);

            const cardArea = (card.dataset.areaId || '0').toString();
            let visibleArea = true;

            if (tab !== 'todas') {
                if (tab === 'otras') {
                    visibleArea = !areasPrincipales.includes(cardArea);
                } else {
                    visibleArea = (cardArea === tab);
                }
            }

            card.classList.toggle('d-none', !(visibleNivel && visibleArea));
        });
    };

    window.recalcularTotalHorasPlan = function() {
        let total = 0;
        document.querySelectorAll('.chk-materia-plan:checked').forEach(chk => {
            const inp = document.getElementById(`intensidad-mat-${chk.value}`);
            if (inp && !inp.disabled) {
                total += parseInt(inp.value) || 0;
            }
        });
        const badgeVal = document.getElementById('total-horas-plan-val');
        if (badgeVal) {
            badgeVal.textContent = total.toString();
        }
    };

    window.cargarPlanMaestro = async function(nivel) {
        if (!nivel) {
            document.querySelectorAll('.card-materia-item').forEach(card => card.classList.add('d-none'));
            const badgeVal = document.getElementById('total-horas-plan-val');
            if (badgeVal) badgeVal.textContent = '0';
            return;
        }
        
        window.aplicarFiltrosPlanMaestro();

        try {
            const res = await fetch(`logica/zulu_admin_plan.php?nivel=${nivel}`);
            const data = await res.json();
            
            const checks = document.querySelectorAll('.chk-materia-plan');
            checks.forEach(c => c.checked = false);
            
            document.querySelectorAll('.zulu-intensidad-input').forEach(i => { i.value = 1; i.disabled = true; });
            
            data.forEach(item => {
                const chk = document.querySelector(`.chk-materia-plan[value="${item.id}"]`);
                const inp = document.getElementById(`intensidad-mat-${item.id}`);
                if (chk) { chk.checked = true; if (inp) { inp.value = item.h; inp.disabled = false; } }
            });

            window.recalcularTotalHorasPlan();
        } catch (err) {
        }
    };

    window.toggleIntensidad = function(matId, checked) {
        const inp = document.getElementById(`intensidad-mat-${matId}`);
        if (inp) { 
            inp.disabled = !checked; 
            if (!checked) inp.value = 1; 
        }
        window.recalcularTotalHorasPlan();
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
                lanzarToastElite('success', 'Intensidad horaria guardada correctamente.');
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

    // ─── QUICK-FILTER TYPEAHEAD PARA POZO DE DOCENTES (VITRINA 06) ───
    let typeaheadBuffer = '';
    let typeaheadTimeout = null;
    let typeaheadHud = null;

    function getOrCreateTypeaheadHud() {
        if (!typeaheadHud || !typeaheadHud.isConnected) {
            typeaheadHud = document.createElement('div');
            typeaheadHud.className = 'zulu-filter-hud';
            const pozo = document.getElementById('pozo-docentes');
            if (pozo) pozo.appendChild(typeaheadHud);
        }
        return typeaheadHud;
    }

    function normalizarBusqueda(str) {
        return (str || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function aplicarFiltroTypeahead(query) {
        const pozo = document.getElementById('pozo-docentes');
        if (!pozo) return;

        const hud = getOrCreateTypeaheadHud();
        const items = pozo.querySelectorAll('.docente-pozo-item');
        const queryNorm = normalizarBusqueda(query);

        if (!queryNorm) {
            items.forEach(it => {
                it.classList.remove('d-none');
            });
            if (hud) {
                hud.classList.remove('zulu-filter-hud-visible');
                hud.textContent = '';
            }
            return;
        }

        let visibles = 0;
        items.forEach(it => {
            const nom = normalizarBusqueda(it.dataset.docenteNombre);
            const mat = normalizarBusqueda(it.dataset.docenteMateria);
            if (nom.includes(queryNorm) || mat.includes(queryNorm)) {
                it.classList.remove('d-none');
                visibles++;
            } else {
                it.classList.add('d-none');
            }
        });

        if (hud) {
            hud.innerHTML = `<i class="bi bi-search me-1"></i> "${query}" (${visibles})`;
            hud.classList.add('zulu-filter-hud-visible');
        }
    }

    function resetearFiltroTypeahead() {
        typeaheadBuffer = '';
        if (typeaheadTimeout) clearTimeout(typeaheadTimeout);
        typeaheadTimeout = null;
        aplicarFiltroTypeahead('');
    }

    document.addEventListener('keydown', function(e) {
        const active = document.activeElement;
        if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.isContentEditable)) {
            return;
        }

        const desplegable = document.getElementById('desplegableDocentes');
        const pozo = document.getElementById('pozo-docentes');
        if (!desplegable || !pozo) return;

        const estaAbierto = desplegable.classList.contains('show');
        if (!estaAbierto) return;

        if (e.key === 'Escape') {
            resetearFiltroTypeahead();
            return;
        }

        if (e.key === 'Backspace') {
            if (typeaheadBuffer.length > 0) {
                e.preventDefault();
                typeaheadBuffer = typeaheadBuffer.slice(0, -1);
                aplicarFiltroTypeahead(typeaheadBuffer);
                if (typeaheadTimeout) clearTimeout(typeaheadTimeout);
                if (typeaheadBuffer.length > 0) {
                    typeaheadTimeout = setTimeout(resetearFiltroTypeahead, 4000);
                } else {
                    resetearFiltroTypeahead();
                }
            }
            return;
        }

        if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey && /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ ]$/.test(e.key)) {
            typeaheadBuffer += e.key;
            aplicarFiltroTypeahead(typeaheadBuffer);

            if (typeaheadTimeout) clearTimeout(typeaheadTimeout);
            typeaheadTimeout = setTimeout(resetearFiltroTypeahead, 4000);
        }
    });

    const desplegableEl = document.getElementById('desplegableDocentes');
    if (desplegableEl) {
        desplegableEl.addEventListener('hidden.bs.collapse', resetearFiltroTypeahead);
    }

    // Restaurar filtros automáticamente al cargar la vista
    window.restaurarFiltrosZulu();
    document.addEventListener('DOMContentLoaded', () => {
        window.restaurarFiltrosZulu();
    });
})();

