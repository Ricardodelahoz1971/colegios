document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formMatricula');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                
                // Alertar al usuario de forma clara
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos Inválidos o Incompletos',
                    text: 'Por favor, revise los campos marcados en rojo. Asegúrese de que el número de celular empiece con 3 y tenga 10 dígitos, y de seleccionar el tipo de documento y el grado.',
                    confirmButtonText: 'Entendido',
                    customClass: {
                        confirmButton: 'btn-elite btn-elite--primary px-4'
                    }
                });
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
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message,
                        confirmButtonText: 'Entendido',
                        customClass: {
                            confirmButton: 'btn-elite btn-elite--primary px-4'
                        }
                    }).then(() => {
                        navegarModulo('matriculados');
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Atención',
                        text: data.message,
                        confirmButtonText: 'Revisar'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Sistema',
                    text: 'No se pudo conectar con el servidor.'
                });
            });
        });
    }
});