document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.getElementById('chat-elite-wrapper');
    if (wrapper) {
        try {
            window.listaCanalesGlobal = JSON.parse(wrapper.getAttribute('data-todos-los-canales')) || [];
        } catch (e) {
            window.listaCanalesGlobal = [];
        }
        window.cfg_logo = wrapper.getAttribute('data-school-logo') || '';
        window.cfg_brand = wrapper.getAttribute('data-brand-color') || '#0a044d';
    }
});