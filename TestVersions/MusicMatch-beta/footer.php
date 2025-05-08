</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-logo">
                    <img src="img/musicmatch.svg" alt="MusicMatch Logo">
                    <span>MusicMatch</span>
                </div>
                <p>Discover and share your favorite music with MusicMatch. Our platform helps you find new tracks tailored to your taste.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-spotify"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="features.php">Features</a></li>
                    <li><a href="webplayer.php">Web Player</a></li>
                    <li><a href="swiper.php">Music Swiper</a></li>
                    <li><a href="about.php">About Us</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Support</h3>
                <ul class="footer-links">
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="terms.php">Terms of Service</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Newsletter</h3>
                <p>Subscribe to our newsletter for updates on new features and music recommendations.</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Your email address" required>
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </form>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> MusicMatch. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
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
</script>

<?php if (isset($additionalScripts)) echo $additionalScripts; ?>
</body>
</html>