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
}

document.addEventListener('DOMContentLoaded', initAnimations);
window.addEventListener('popstate', initAnimations);
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        initAnimations();
    }
});


document.addEventListener('DOMContentLoaded', function() {
    initScrollAnimations();
    setupSwipeButtonInteractions();
});
function setupSwipeButtonInteractions() {
    const demoCard = document.getElementById('demo-swiper-card');
    
    if (!demoCard) return;
    demoCard.classList.add('auto-animate');
    
    const likeButton = document.getElementById('demo-like');
    const dislikeButton = document.getElementById('demo-dislike');
    
    if (likeButton) {
        likeButton.addEventListener('click', function() {
            demoCard.style.animation = 'none';
            document.querySelector('.like-overlay').style.animation = 'none';
            document.querySelector('.dislike-overlay').style.animation = 'none';
            document.querySelector('.like-indicator').style.animation = 'none';
            document.querySelector('.dislike-indicator').style.animation = 'none';
            
            void demoCard.offsetWidth;
            
            document.querySelector('.like-overlay').style.opacity = '0.8';
            document.querySelector('.like-indicator').style.transform = 'scale(1)';
            document.querySelector('.dislike-overlay').style.opacity = '0';
            document.querySelector('.dislike-indicator').style.transform = 'scale(0.6)';
            
            demoCard.style.transform = 'translateX(150px) rotate(15deg)';
            
            setTimeout(() => {
                demoCard.style.transition = 'none';
                demoCard.style.transform = 'translateX(0) rotate(0deg)';
                
                setTimeout(() => {
                    demoCard.style.transition = 'transform 0.4s ease';
                    document.querySelector('.like-overlay').style.opacity = '0';
                    
                    setTimeout(() => {
                        demoCard.style.animation = '';
                        document.querySelector('.like-overlay').style.animation = '';
                        document.querySelector('.dislike-overlay').style.animation = '';
                        document.querySelector('.like-indicator').style.animation = '';
                        document.querySelector('.dislike-indicator').style.animation = '';
                    }, 500);
                }, 50);
            }, 800);
        });
    }
    
    if (dislikeButton) {
        dislikeButton.addEventListener('click', function() {
            demoCard.style.animation = 'none';
            document.querySelector('.like-overlay').style.animation = 'none';
            document.querySelector('.dislike-overlay').style.animation = 'none';
            document.querySelector('.like-indicator').style.animation = 'none';
            document.querySelector('.dislike-indicator').style.animation = 'none';
            
            void demoCard.offsetWidth;
            
            document.querySelector('.dislike-overlay').style.opacity = '0.8';
            document.querySelector('.dislike-indicator').style.transform = 'scale(1)';
            document.querySelector('.like-overlay').style.opacity = '0';
            document.querySelector('.like-indicator').style.transform = 'scale(0.6)';
            
            demoCard.style.transform = 'translateX(-150px) rotate(-15deg)';
            
            setTimeout(() => {
                demoCard.style.transition = 'none';
                demoCard.style.transform = 'translateX(0) rotate(0deg)';
                
                setTimeout(() => {
                    demoCard.style.transition = 'transform 0.4s ease';
                    document.querySelector('.dislike-overlay').style.opacity = '0';
                    
                    setTimeout(() => {
                        demoCard.style.animation = '';
                        document.querySelector('.like-overlay').style.animation = '';
                        document.querySelector('.dislike-overlay').style.animation = '';
                        document.querySelector('.like-indicator').style.animation = '';
                        document.querySelector('.dislike-indicator').style.animation = '';
                    }, 500);
                }, 50);
            }, 800);
        });
    }
}

function initScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px'
    });
    
    document.querySelectorAll('.animate-on-scroll').forEach(element => {
        observer.observe(element);
    });
}