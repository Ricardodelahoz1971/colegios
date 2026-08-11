document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('zulu-container-master');
    if (container) {
        window.CSRF_TOKEN = container.getAttribute('data-csrf-token') || '';
    }
});