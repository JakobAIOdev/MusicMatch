document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const siteNav = document.getElementById('site-nav');
    const authButtons = document.querySelector('.auth-buttons');
    
    function setupMobileMenu() {
    const mobileAuthButtonsElement = document.querySelector('.mobile-auth-buttons');
    
    if (window.innerWidth <= 992) {
        if (!mobileAuthButtonsElement && authButtons) {
            const mobileAuthButtons = document.createElement('div');
            mobileAuthButtons.className = 'mobile-auth-buttons';
            mobileAuthButtons.innerHTML = authButtons.innerHTML;
            siteNav.appendChild(mobileAuthButtons);
        }
    } else {
        if (mobileAuthButtonsElement) {
            mobileAuthButtonsElement.remove();
        }
    }
}
    setupMobileMenu();
    
    window.addEventListener('resize', setupMobileMenu);
    
    if (mobileMenuToggle && siteNav) {
        mobileMenuToggle.addEventListener('click', function(event) {
            event.stopPropagation();
            this.classList.toggle('menu-open');
            document.body.classList.toggle('menu-open');
            
            if (!siteNav.classList.contains('active')) {
                setTimeout(() => {
                    siteNav.classList.add('active');
                }, 10);
            } else {
                siteNav.classList.remove('active');
            }
        });
        
        document.addEventListener('click', function(event) {
            if (siteNav.classList.contains('active') && 
                !siteNav.contains(event.target) && 
                !mobileMenuToggle.contains(event.target)) {
                
                mobileMenuToggle.classList.remove('menu-open');
                document.body.classList.remove('menu-open');
                siteNav.classList.remove('active');
            }
        });

        siteNav.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    }
    
    const header = document.querySelector('.site-header');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }
});