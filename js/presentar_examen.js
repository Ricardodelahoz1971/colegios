// 🏛️ BUNKER EXAMEN ENGINE (LÓGICA EXTERNALIZADA ÉLITE)

document.addEventListener('DOMContentLoaded', () => {
    // 1. Inyectar variables CSS de identidad soberana
    const body = document.body;
    if (body) {
        document.documentElement.style.setProperty('--el-primary', body.getAttribute('data-el-primary'));
        document.documentElement.style.setProperty('--el-primary-rgb', body.getAttribute('data-el-primary-rgb'));
        document.documentElement.style.setProperty('--el-accent', body.getAttribute('data-el-accent'));
        document.documentElement.style.setProperty('--el-accent-rgb', body.getAttribute('data-el-accent-rgb'));
        document.documentElement.style.setProperty('--el-font-institutional', body.getAttribute('data-el-font-institutional'));
    }

    // 2. Inicializar estado dinámico
    window.ExamenState = {
        totalPreguntas: parseInt(body.getAttribute('data-total-preguntas')) || 0,
        tipoNavegacion: body.getAttribute('data-tipo-navegacion') || 'secuencial',
        tiempoRestante: parseInt(body.getAttribute('data-tiempo-restante')) || 0,
        csrfToken: body.getAttribute('data-csrf-token') || '',
        asignacionId: body.getAttribute('data-asignacion-id') || '',
        preguntaActual: 1,
        examenEntregado: false,
        advertenciasCentinela: 0,
        snapshot: {}
    };

    iniciarReloj();
    actualizarUI();
    activarMotorCentinela();
});

function activarMotorCentinela() {
    // 1. Bloqueo de Portapapeles
    const eventosBloqueados = ['copy', 'cut', 'paste', 'contextmenu'];
    eventosBloqueados.forEach(evt => {
        document.addEventListener(evt, (e) => {
            e.preventDefault();
            reportarIncidente('PORTAPAPELES', `Intento de evento: ${evt}`, 1);
            Swal.fire({
                title: 'Acción Bloqueada',
                text: 'El Motor Centinela ha registrado un intento de uso del portapapeles o menú contextual.',
                icon: 'warning',
                confirmButtonColor: 'var(--el-primary)'
            });
        });
    });

    // 2. Detección de Pérdida de Foco (Cambio de pestaña o ventana)
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && !window.ExamenState.examenEntregado) {
            window.ExamenState.advertenciasCentinela++;
            reportarIncidente('PERDIDA_FOCO', `El alumno abandonó la pestaña. Advertencia #${window.ExamenState.advertenciasCentinela}`, 2);
        } else if (!document.hidden && !window.ExamenState.examenEntregado) {
            Swal.fire({
                title: '¡ALERTA DE SEGURIDAD!',
                html: `Ha abandonado la ventana del examen.<br><br><b>Esta acción ha sido registrada en su expediente de prueba.</b><br>Advertencias totales: ${window.ExamenState.advertenciasCentinela}`,
                icon: 'error',
                confirmButtonText: 'ENTENDIDO, NO VOLVERÁ A OCURRIR',
                confirmButtonColor: 'rgb(220, 53, 69)',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
        }
    });
}

async function reportarIncidente(tipo, detalles, gravedad) {
    try {
        const fd = new FormData();
        fd.append('accion', 'registrar_incidente');
        fd.append('csrf_token', window.ExamenState.csrfToken);
        fd.append('asignacion_id', window.ExamenState.asignacionId);
        fd.append('tipo', tipo);
        fd.append('detalles', detalles);
        fd.append('gravedad', gravedad);
        
        await fetch('../logica/api_pruebas_estudiante.php', { method: 'POST', body: fd });
    } catch (error) {
        // manejado silenciosamente
    }
}

function iniciarReloj() {
    const reloj = document.getElementById('reloj-bunker');
    if (!reloj) return;
    
    const timer = setInterval(() => {
        if (window.ExamenState.examenEntregado) { clearInterval(timer); return; }
        
        window.ExamenState.tiempoRestante--;
        
        if (window.ExamenState.tiempoRestante <= 0) {
            clearInterval(timer);
            forzarEntrega("El tiempo se ha agotado.");
            return;
        }

        let h = Math.floor(window.ExamenState.tiempoRestante / 3600);
        let m = Math.floor((window.ExamenState.tiempoRestante % 3600) / 60);
        let s = window.ExamenState.tiempoRestante % 60;
        
        let str = (h > 0 ? h.toString().padStart(2, '0') + ':' : '') + 
                  m.toString().padStart(2, '0') + ':' + 
                  s.toString().padStart(2, '0');
        
        reloj.textContent = str;

        if (window.ExamenState.tiempoRestante < 300) reloj.classList.add('timer-warning');
    }, 1000);
}

function registrarRespuesta(num) {
    const navBtn = document.getElementById('navbtn_' + num);
    if(navBtn) navBtn.classList.add('answered');

    const card = document.getElementById('card_' + num);
    if (!card) return;
    const qid = card.dataset.qid;
    
    let val = '';
    const radios = card.querySelectorAll('input[type="radio"]');
    if(radios.length > 0) {
        const checked = card.querySelector('input[type="radio"]:checked');
        if(checked) {
            val = checked.getAttribute('data-idx') || checked.value;
        }
    } else if (card.querySelector('.select-match')) {
        const selects = card.querySelectorAll('.select-match');
        const matches = Array.from(selects).map(s => s.value);
        val = JSON.stringify(matches);
    } else if (card.querySelector('.input-fill-blank')) {
        const inputs = card.querySelectorAll('.input-fill-blank');
        const fills = Array.from(inputs).map(i => i.value.trim());
        val = JSON.stringify(fills);
    } else {
        const input = card.querySelector('input[type="text"], textarea');
        if(input) val = input.value;
    }

    if(val.trim() !== '') {
        window.ExamenState.snapshot[qid] = val;
    }

    const progresoTxt = document.getElementById('progreso-txt');
    if (progresoTxt) {
        progresoTxt.innerText = Object.keys(window.ExamenState.snapshot).length;
    }
}

function cambiarPregunta(delta) {
    saltarA(window.ExamenState.preguntaActual + delta);
}

function saltarA(num) {
    if (num < 1 || num > window.ExamenState.totalPreguntas) return;
    
    if (window.ExamenState.tipoNavegacion !== 'lineal') {
        document.querySelectorAll('.question-card').forEach(c => c.classList.remove('active'));
        const card = document.getElementById('card_' + num);
        if (card) card.classList.add('active');
    }

    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('is-active-bunker'));
    const navBtn = document.getElementById('navbtn_' + num);
    if(navBtn) navBtn.classList.add('is-active-bunker');

    window.ExamenState.preguntaActual = num;
    actualizarUI();
    
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function actualizarUI() {
    if (window.ExamenState.tipoNavegacion === 'lineal') return;
    
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');

    if(btnPrev) {
        btnPrev.classList.toggle('u-block', window.ExamenState.preguntaActual !== 1);
        btnPrev.classList.toggle('u-hidden', window.ExamenState.preguntaActual === 1);
    }
    if(btnNext) {
        if (window.ExamenState.preguntaActual === window.ExamenState.totalPreguntas) {
            btnNext.innerHTML = 'FINALIZAR <i class="bi bi-check-circle ms-2"></i>';
            btnNext.className = 'btn-elite btn-elite--success px-5 py-3';
            btnNext.onclick = finalizarExamenManual;
        } else {
            btnNext.innerHTML = 'SIGUIENTE <i class="bi bi-arrow-right ms-2"></i>';
            btnNext.className = 'btn-elite btn-elite--primary px-5 py-3';
            btnNext.onclick = () => cambiarPregunta(1);
        }
    }
}

async function finalizarExamenManual() {
    const res = await Swal.fire({
        title: '¿Entregar Prueba?',
        text: "Ha respondido " + Object.keys(window.ExamenState.snapshot).length + " de " + window.ExamenState.totalPreguntas + " preguntas.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, ENTREGAR AHORA',
        cancelButtonText: 'Revisar más',
        confirmButtonColor: 'rgb(40, 167, 69)'
    });

    if (res.isConfirmed) procesarEntrega();
}

function forzarEntrega(razon) {
    Swal.fire({ title: '¡TIEMPO AGOTADO!', text: razon, icon: 'info', allowOutsideClick: false, showConfirmButton: false });
    procesarEntrega();
}

async function procesarEntrega() {
    window.ExamenState.examenEntregado = true;
    Swal.fire({
        title: 'Sellando Examen...',
        text: 'La Bóveda está recibiendo sus respuestas y calculando su puntaje parcial...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const fd = new FormData();
        fd.append('accion', 'entregar_examen');
        fd.append('csrf_token', window.ExamenState.csrfToken);
        fd.append('asignacion_id', window.ExamenState.asignacionId);
        fd.append('respuestas', JSON.stringify(window.ExamenState.snapshot));

        const resp = await fetch('../logica/api_pruebas_estudiante.php', { method: 'POST', body: fd });
        const d = await resp.json();

        if (d.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: '¡Examen Entregado!',
                text: 'Sus respuestas han sido aseguradas. El docente publicará su nota final pronto.',
                confirmButtonText: 'VOLVER AL PANEL',
                allowOutsideClick: false
            }).then(() => {
                window.location.href = '../dashboard.php';
            });
        } else {
            throw new Error(d.message || 'Error en la entrega.');
        }
    } catch (e) {
        window.ExamenState.examenEntregado = false;
        Swal.fire('Error Crítico', e.message, 'error');
    }
}

// Vinculación al scope global para compatibilidad con eventos inline
window.finalizarExamenManual = finalizarExamenManual;
window.registrarRespuesta = registrarRespuesta;
window.saltarA = saltarA;
window.cambiarPregunta = cambiarPregunta;