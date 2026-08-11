(function() {
    const bar = document.getElementById('pruebas-progress');
    if (bar) {
        bar.setAttribute('style', 'width: ' + bar.getAttribute('data-width'));
    }
})();