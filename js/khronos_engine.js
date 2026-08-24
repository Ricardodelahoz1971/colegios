(function() {
    let draggedData = null;

    window.dragCarga = function(e) {
        const faltantes = parseInt(e.target.dataset.faltantes || "0");
        if (faltantes <= 0) {
            e.preventDefault();
            return false;
        }
        draggedData = {
            type: 'new',
            especialidad_id: e.target.dataset.especialidadId,
            docente_id: e.target.dataset.docenteId
        };
        e.dataTransfer.setData("text", "khronos");
    };

    window.dragKhronos = function(e) {
        const el = e.target.closest('.khronos-item');
        draggedData = {
            type: 'move',
            id: el.dataset.id,
            especialidad_id: el.dataset.especialidadId,
            docente_id: el.dataset.docenteId,
            ori_dia: el.dataset.oriDia,
            ori_hora: el.dataset.oriHora
        };
        e.dataTransfer.setData("text", "khronos");
    };

    window.allowDropKhronos = function(e) { e.preventDefault(); };

    window.dropKhronos = async function(e) {
        e.preventDefault();
        const slot = e.target.closest('.khronos-slot');
        const localData = draggedData; // 🛡️ CAPTURA DE SEGURIDAD ELITE
        
        document.querySelectorAll('.khronos-slot--active').forEach(s => s.classList.remove('khronos-slot--active'));
        
        if (!slot || !localData) return;
        
        const dia = slot.dataset.dia;
        const hora = slot.dataset.hora;
        const inicio = slot.dataset.inicio;
        const fin = slot.dataset.fin;
        const ocupante = slot.querySelector('.khronos-item');
        const curso_id = new URLSearchParams(window.location.search).get('curso_id');

        const aprobado = await validarAsignacion(localData, dia, hora, curso_id);
        if (!aprobado) return;
        
        const formData = new FormData();
        if (ocupante) {
            if (localData.type === 'move') {
                const dataOcu = { docente_id: ocupante.dataset.docenteId, especialidad_id: ocupante.dataset.especialidadId };
                const aprobadoOcu = await validarAsignacion(dataOcu, localData.ori_dia, localData.ori_hora, curso_id);
                if (!aprobadoOcu) return;
                
                formData.append('accion', 'swap');
                formData.append('id_a', localData.id);
                formData.append('id_b', ocupante.dataset.id);
                formData.append('dia_b', localData.ori_dia);
                formData.append('hora_b', localData.ori_hora);
                formData.append('dia_a', dia);
                formData.append('hora_a', hora);
                await ejecutarProcesamiento(formData);
                return;
            } else {
                formData.append('accion', 'reemplazar');
                formData.append('id_eliminar', ocupante.dataset.id);
            }
        } else {
            formData.append('accion', localData.type === 'new' ? 'asignar' : 'mover');
        }
        
        if (localData.id) formData.append('id', localData.id);
        formData.append('curso_id', curso_id);
        formData.append('docente_id', localData.docente_id);
        formData.append('especialidad_id', localData.especialidad_id);
        formData.append('dia_semana', dia);
        formData.append('hora_numero', hora);
        formData.append('hora_inicio', inicio);
        formData.append('hora_fin', fin);
        
        await ejecutarProcesamiento(formData);
    };

    async function ejecutarProcesamiento(formData) {
        try {
            const curso_id = new URLSearchParams(window.location.search).get('curso_id');
            const res = await fetch('logica/procesar_khronos.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.status === 'success') {
                lanzarToastElite('success', 'Asignación horaria registrada');
                navegarModulo('khronos&curso_id=' + curso_id);
            } else {
                lanzarToastElite('danger', data.message || 'Error en horario', 'Error de Khronos');
            }
        } catch (error) {
            lanzarToastElite('danger', 'No se pudo procesar la asignación.', 'Error de Conexión');
        }
    }

    async function validarAsignacion(data, dia, hora, curso_id) {
        const resCol = await fetch(`logica/verificar_colision.php?docente_id=${data.docente_id}&dia=${dia}&hora=${hora}&curso_id=${curso_id}`);
        const colision = await resCol.json();
        if (colision.existe) {
            await Swal.fire({ icon: 'warning', title: '¡COLISIÓN!', text: `Docente asignado en [${colision.curso}]`, confirmButtonText: 'OK' });
            return false;
        }
        const resFat = await fetch(`logica/verificar_fatiga.php?curso_id=${curso_id}&especialidad_id=${data.especialidad_id}&dia=${dia}`);
        const fatiga = await resFat.json();
        if (fatiga.limite_alcanzado) {
            const confirm = await Swal.fire({ icon: 'info', title: 'Fatiga', text: `Máximo diario alcanzado (${fatiga.max}h). ¿Forzar?`, showCancelButton: true });
            return confirm.isConfirmed;
        }
        return true;
    }

    window.eliminarSlot = async function(id, esEvento = false) {
        const titulo = esEvento ? '¿BORRAR EVENTO?' : '¿Eliminar bloque académico?';
        const texto = esEvento ? 'Esta actividad será eliminada permanentemente.' : 'Se liberará el espacio en la parrilla curricular.';
        
        const r = await Swal.fire({ 
            title: titulo, 
            text: texto,
            icon: 'warning', 
            showCancelButton: true,
            confirmButtonText: 'SÍ, BORRAR',
            cancelButtonText: 'CANCELAR',
            customClass: { popup: 'glass-modal-elite rounded-4' }
        });
        
        if (r.isConfirmed) {
            try {
                const fd = new FormData();
                fd.append('accion', 'eliminar');
                fd.append('id', id);
                await fetch('logica/procesar_khronos.php', { method: 'POST', body: fd });
                const curso_id = new URLSearchParams(window.location.search).get('curso_id');
                lanzarToastElite('success', 'Bloque horario liberado');
                if (typeof navegarModulo === 'function') navegarModulo('khronos&curso_id=' + curso_id);
            } catch (error) {
                lanzarToastElite('danger', 'No se pudo eliminar el bloque.');
            }
        }
    };

    window.cambiarCursoKhronos = function(id) {
        if (id > 0) navegarModulo('khronos&curso_id=' + id);
    };

    window.generarAutomatico = async function(curso_id) {
        const r = await Swal.fire({ title: '¿Auto-generar?', icon: 'question', showCancelButton: true });
        if (r.isConfirmed) {
            Swal.fire({ title: 'Procesando...', didOpen: () => Swal.showLoading() });
            try {
                const res = await fetch('logica/khronos_auto_gen.php?curso_id=' + curso_id);
                const data = await res.json();
                
                lanzarToastElite(data.status === 'success' ? 'success' : 'danger', data.message || 'Horario procesado');
                if (data.status === 'success') navegarModulo('khronos&curso_id=' + curso_id);
            } catch (error) {
                lanzarToastElite('danger', 'Fallo en la generación automática.');
            }
        }
    };

    window.limpiarHorario = async function(curso_id) {
        const r = await Swal.fire({ title: '¿Limpiar PENDIENTE-ARES?', icon: 'error', showCancelButton: true });
        if (r.isConfirmed) {
            try {
                const fd = new FormData();
                fd.append('accion', 'limpiar');
                fd.append('curso_id', curso_id);
                await fetch('logica/procesar_khronos.php', { method: 'POST', body: fd });
                navegarModulo('khronos&curso_id=' + curso_id);
            } catch (error) {
                Swal.fire('Error', 'Fallo al purgar la parrilla.', 'error');
            }
        }
    };

    // 🛡️ SISTEMA DE FEEDBACK VISUAL ÉLITE
    document.addEventListener('dragenter', e => {
        const slot = e.target.closest('.khronos-slot');
        if (slot) {
            if (!slot.classList.contains('khronos-slot--active')) {
                document.querySelectorAll('.khronos-slot--active').forEach(s => s.classList.remove('khronos-slot--active'));
                slot.classList.add('khronos-slot--active');
            }
        }
    });

    document.addEventListener('dragleave', e => {
        const slot = e.target.closest('.khronos-slot');
        if (slot && !slot.contains(e.relatedTarget)) {
            slot.classList.remove('khronos-slot--active');
        }
    });

    document.addEventListener('dragend', () => {
        document.querySelectorAll('.khronos-slot--active').forEach(s => s.classList.remove('khronos-slot--active'));
        draggedData = null;
    });

    document.addEventListener('drop', () => {
        document.querySelectorAll('.khronos-slot--active').forEach(s => s.classList.remove('khronos-slot--active'));
    });
})();
