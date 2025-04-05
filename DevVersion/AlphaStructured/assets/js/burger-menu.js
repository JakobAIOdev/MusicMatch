// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const siteNav = document.getElementById('site-nav');
    
    if (mobileMenuToggle && siteNav) {
        mobileMenuToggle.addEventListener('click', function() {
            siteNav.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!siteNav.contains(event.target) && !mobileMenuToggle.contains(event.target) && siteNav.classList.contains('active')) {
                siteNav.classList.remove('active');
                document.body.classList.remove('menu-open');
            }
        });
    }
    
    // Header scroll effect
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