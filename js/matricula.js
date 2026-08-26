function initMatricula() {
    const form = document.getElementById('formMatricula');
    if (form && !form.dataset.bound) {
        form.dataset.bound = "true";

        // 1. Cálculo dinámico de edad en tiempo real
        const fechaNacInput = document.getElementById('fecha_nacimiento');
        const edadInput = document.getElementById('edad_estudiante');

        const calcularEdad = (fechaStr) => {
            if (!fechaStr) return '';
            const birthDate = new Date(fechaStr);
            if (isNaN(birthDate.getTime())) return '';
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return age >= 0 ? age : 0;
        };

        if (fechaNacInput && edadInput) {
            const actualizarEdad = () => {
                edadInput.value = calcularEdad(fechaNacInput.value);
            };
            fechaNacInput.addEventListener('input', actualizarEdad);
            fechaNacInput.addEventListener('change', actualizarEdad);
            if (fechaNacInput.value) {
                actualizarEdad();
            }
        }

        // 2. Selects Geográficos en Cascada Reactiva y Auto-Sincronización de Nacionalidad
        // 2. Selects Geográficos en Cascada Reactiva y Auto-Sincronización de Nacionalidad
        const paisSelect = document.getElementById('pais_nacimiento');
        const nacSelect = document.getElementById('nacionalidad_estudiante');
        const deptoSelect = document.getElementById('departamento_nacimiento');
        const munSelect = document.getElementById('municipio_nacimiento');
        const grupoDepto = document.getElementById('grupo_departamento');
        const grupoMun = document.getElementById('grupo_municipio');
        const grupoDeptoLibre = document.getElementById('grupo_depto_libre');
        const grupoCiudadLibre = document.getElementById('grupo_ciudad_libre');
        const deptoLibreInput = document.getElementById('depto_nacimiento_libre');
        const ciudadLibreInput = document.getElementById('ciudad_nacimiento_libre');
        const lugarFinalInput = document.getElementById('lugar_nacimiento_final');

        const sincronizarNacionalidad = () => {
            if (!paisSelect || !nacSelect) return;
            const opt = paisSelect.options[paisSelect.selectedIndex];
            const gentilicio = opt?.dataset.gentilicio || 'Colombiano/a';
            nacSelect.value = gentilicio;
        };

        const actualizarLugarFinal = () => {
            if (!paisSelect || !lugarFinalInput) return;
            const paisNombre = paisSelect.options[paisSelect.selectedIndex]?.dataset.nombre || paisSelect.options[paisSelect.selectedIndex]?.text || '';
            
            // Modo libre para países internacionales (id > 2)
            if (grupoCiudadLibre && !grupoCiudadLibre.classList.contains('d-none')) {
                const deptoLibre = deptoLibreInput?.value.trim() || '';
                const ciudadLibre = ciudadLibreInput?.value.trim() || '';
                if (ciudadLibre && deptoLibre) {
                    lugarFinalInput.value = `${ciudadLibre}, ${deptoLibre} (${paisNombre})`;
                } else if (ciudadLibre) {
                    lugarFinalInput.value = `${ciudadLibre} (${paisNombre})`;
                } else if (deptoLibre) {
                    lugarFinalInput.value = `${deptoLibre} (${paisNombre})`;
                } else {
                    lugarFinalInput.value = paisNombre;
                }
                return;
            }

            // Modo cascada estructurada para Colombia y Venezuela
            const deptoNombre = deptoSelect?.options[deptoSelect.selectedIndex]?.text || '';
            const munNombre = munSelect?.options[munSelect.selectedIndex]?.text || '';

            if (munSelect && munSelect.value && munNombre && !munNombre.includes('Seleccione')) {
                lugarFinalInput.value = `${munNombre}, ${deptoNombre} (${paisNombre})`;
            } else if (deptoSelect && deptoSelect.value && deptoNombre && !deptoNombre.includes('Seleccione')) {
                lugarFinalInput.value = `${deptoNombre} (${paisNombre})`;
            } else {
                lugarFinalInput.value = paisNombre;
            }
        };

        const cargarMunicipios = async (deptoId) => {
            if (!munSelect) return;
            munSelect.innerHTML = '<option value="">Cargando municipios...</option>';
            try {
                const res = await fetch(`logica/obtener_geografia.php?accion=municipios&departamento_id=${deptoId}`);
                const data = await res.json();
                if (data.status === 'success' && data.data && data.data.length > 0) {
                    munSelect.innerHTML = '<option value="">Seleccione Municipio...</option>';
                    data.data.forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m.id;
                        opt.textContent = m.nombre;
                        munSelect.appendChild(opt);
                    });
                    if (data.data.length === 1) {
                        munSelect.selectedIndex = 1;
                    }
                } else {
                    munSelect.innerHTML = '<option value="">Sin municipios registrados</option>';
                }
            } catch (e) {
                munSelect.innerHTML = '<option value="">Error al cargar</option>';
            }
            actualizarLugarFinal();
        };

        const cargarDepartamentos = async (paisId) => {
            if (!deptoSelect) return;
            deptoSelect.innerHTML = '<option value="">Cargando...</option>';
            if (munSelect) munSelect.innerHTML = '<option value="">Seleccione Municipio...</option>';

            try {
                const res = await fetch(`logica/obtener_geografia.php?accion=departamentos&pais_id=${paisId}`);
                const data = await res.json();
                if (data.status === 'success' && data.data && data.data.length > 0) {
                    grupoDepto?.classList.remove('d-none');
                    grupoMun?.classList.remove('d-none');
                    grupoDeptoLibre?.classList.add('d-none');
                    grupoCiudadLibre?.classList.add('d-none');

                    deptoSelect.innerHTML = '<option value="">Seleccione Departamento / Estado...</option>';
                    data.data.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = d.nombre;
                        deptoSelect.appendChild(opt);
                    });

                    if (data.data.length > 0) {
                        deptoSelect.selectedIndex = 1;
                        await cargarMunicipios(deptoSelect.value);
                    }
                } else {
                    // Fallback a modo texto libre para países internacionales
                    grupoDepto?.classList.add('d-none');
                    grupoMun?.classList.add('d-none');
                    grupoDeptoLibre?.classList.remove('d-none');
                    grupoCiudadLibre?.classList.remove('d-none');
                }
            } catch (e) {
                grupoDepto?.classList.add('d-none');
                grupoMun?.classList.add('d-none');
                grupoDeptoLibre?.classList.remove('d-none');
                grupoCiudadLibre?.classList.remove('d-none');
            }
            actualizarLugarFinal();
        };

        if (paisSelect) {
            paisSelect.addEventListener('change', () => {
                sincronizarNacionalidad();
                cargarDepartamentos(paisSelect.value);
            });
            // Carga inicial de Colombia
            sincronizarNacionalidad();
            cargarDepartamentos(paisSelect.value || 1);
        }

        if (deptoSelect) {
            deptoSelect.addEventListener('change', () => {
                if (deptoSelect.value) {
                    cargarMunicipios(deptoSelect.value);
                } else {
                    if (munSelect) munSelect.innerHTML = '<option value="">Seleccione Municipio...</option>';
                    actualizarLugarFinal();
                }
            });
        }

        if (munSelect) {
            munSelect.addEventListener('change', actualizarLugarFinal);
        }

        if (deptoLibreInput) {
            deptoLibreInput.addEventListener('input', actualizarLugarFinal);
        }

        if (ciudadLibreInput) {
            ciudadLibreInput.addEventListener('input', actualizarLugarFinal);
        }

        // 3. Envío y Blindaje del Formulario
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            actualizarLugarFinal();

            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                lanzarToastElite('warning', 'Por favor, complete todos los campos obligatorios requeridos.');
                return;
            }

            const formData = new FormData(this);
            
            fetch('logica/guardar_estudiante.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    lanzarToastElite('success', data.message || 'Estudiante matriculado con éxito');
                    navegarModulo('matriculados');
                } else {
                    lanzarToastElite('danger', data.message || 'Atención: No se pudo guardar la matrícula');
                }
            })
            .catch(error => {
                lanzarToastElite('danger', 'No se pudo conectar con el servidor.');
            });
        });
    }
}

window.initMatricula = initMatricula;

if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initMatricula();
} else {
    document.addEventListener('DOMContentLoaded', initMatricula);
}