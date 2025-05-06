function initAnimations() {
    document.body.classList.add('js-enabled');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.target.closest('.hero-section')) {
                return;
            }
            
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                
                if (entry.target.classList.contains('section')) {
                    const featureCards = entry.target.querySelectorAll('.feature-card');
                    const steps = entry.target.querySelectorAll('.step');
                    
                    featureCards.forEach(card => card.classList.add('visible'));
                    steps.forEach(step => step.classList.add('visible'));
                }
            } 
            else {
                entry.target.classList.remove('visible');
                if (entry.target.classList.contains('section')) {
                    const featureCards = entry.target.querySelectorAll('.feature-card');
                    const steps = entry.target.querySelectorAll('.step');
                    
                    featureCards.forEach(card => card.classList.remove('visible'));
                    steps.forEach(step => step.classList.remove('visible'));
                }
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: '0px 0px -10px 0px'
    });
    
    document.querySelectorAll('.content-wrapper .card').forEach((card, index) => {
        card.dataset.index = index % 6;
        observer.observe(card);
    });
    document.querySelectorAll('.section:not(.hero-section), .nav-wrapper, section > p, section > h1').forEach(element => {
        observer.observe(element);
    });
    
    console.log('Animations initialized with hero section exclusion');
}

document.addEventListener('DOMContentLoaded', initAnimations);
window.addEventListener('popstate', initAnimations);
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        initAnimations();
    }
});