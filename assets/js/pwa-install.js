document.addEventListener('DOMContentLoaded', () => {
    // Initialiser le thème sauvegardé
    initTheme();
    
    // Animer les montants après un court délai (pour index.php)
    setTimeout(animateAmounts, 300);
});

//  THÈME 
function initTheme() {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.body.classList.add('dark-theme');
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            const icon = themeToggle.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        }
    }
}

//ANIMATION MONTANTS
function animateValue(element, start, end, duration) {
    const startTime = performance.now();
    const difference = end - start;
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const current = start + (difference * easeOutQuad(progress));
        element.textContent = formatNumber(current);
        
        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    
    requestAnimationFrame(update);
}

function easeOutQuad(t) {
    return t * (2 - t);
}

function formatNumber(num) {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num);
}

// Animer les montants au chargement (utilisé dans index.php)
function animateAmounts() {
    document.querySelectorAll('.amount').forEach(element => {
        const value = parseFloat(element.textContent.replace(/[^0-9.-]+/g, ''));
        if (!isNaN(value)) {
            element.textContent = '0.00';
            setTimeout(() => animateValue(element, 0, value, 1000), 100);
        }
    });
}

// GRAPHIQUES RESPONSIVES 
// Rendre les graphiques Chart.js responsives
function makeChartsResponsive() {
    const charts = document.querySelectorAll('canvas');
    
    charts.forEach(canvas => {
        const parent = canvas.parentElement;
        if (parent) {
            canvas.style.maxWidth = '100%';
            canvas.style.height = 'auto';
        }
    });
}

// Appliquer au chargement
window.addEventListener('load', makeChartsResponsive);