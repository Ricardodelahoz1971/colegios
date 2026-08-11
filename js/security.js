(function() {
    const container = document.getElementById('lockdown-container');
    if (container) {
        const color = container.getAttribute('data-lockdown-color');
        const colorRgb = container.getAttribute('data-lockdown-color-rgb');
        document.documentElement.setAttribute('style', `--lockdown-color: ${color}; --lockdown-color-rgb: ${colorRgb};`);
    }

    let c = 10;
    const t = document.getElementById('timer');
    if (t) {
        const i = setInterval(() => {
            c--;
            t.textContent = c;
            if (c <= 0) {
                clearInterval(i);
                window.location.href = 'https://www.google.com/search?q=como+dejar+de+ser+un+pendejo'; 
            }
        }, 1000);
    }
})();