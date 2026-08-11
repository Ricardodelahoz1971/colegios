function borrarCarga(id, materia) {
    Swal.fire({
        title: '¿Confirmar Retiro?',
        text: `¿Deseas desvincular la asignatura "${materia}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, retirar',
        cancelButtonText: 'No, cancelar',
        customClass: {
            confirmButton: 'btn-elite bg-danger border-danger px-4 mx-2',
            cancelButton: 'btn-elite btn-elite--outline-secondary px-4 mx-2',
            popup: 'rounded-4 shadow-lg border-0',
            title: 'fs-4 fw-bold text-dark w-100',
            actions: 'w-100 d-flex justify-content-center mt-4'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            enviarPostElite('logica/borrar_carga.php', { id: id });
        }
    });
}
window.borrarCarga = borrarCarga;