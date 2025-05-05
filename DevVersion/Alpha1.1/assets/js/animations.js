function initAnimations() {
    document.body.classList.add('js-enabled');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.15,// Trigger when element is 15% visible
        rootMargin: '0px 0px -20px 0px'
    });
    document.querySelectorAll('.content-wrapper .card').forEach((card, index) => {
        card.dataset.index = index % 6;
        observer.observe(card);
    });
    
    const otherElements = document.querySelectorAll('.section, .nav-wrapper, section > p, section > h1');
    otherElements.forEach(element => {
        observer.observe(element);
    });
    
    console.log('Animations initialized');
}

document.addEventListener('DOMContentLoaded', initAnimations);
window.addEventListener('popstate', initAnimations);
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        initAnimations();
    }
});

document.addEventListener('DOMContentLoaded', initAnimations);
window.addEventListener('popstate', initAnimations);

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        initAnimations();
    }
});

document.addEventListener('DOMContentLoaded', initAnimations);
window.addEventListener('popstate', initAnimations);
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        initAnimations();
    }
});

document.addEventListener('DOMContentLoaded', initAnimations);
window.addEventListener('popstate', initAnimations);
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        initAnimations();
    }
});