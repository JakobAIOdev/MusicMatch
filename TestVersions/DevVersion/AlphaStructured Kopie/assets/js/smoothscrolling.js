document.addEventListener('DOMContentLoaded', function() {
    const downArrow = document.querySelector('.down-arrow');
    
    downArrow.addEventListener('click', function(e) {
        e.preventDefault(); 
        
        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        
        targetElement.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    });
});