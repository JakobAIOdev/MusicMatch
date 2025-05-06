</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-logo">
                    <img src="../assets/img/icons/musicmatch-logo.svg" alt="MusicMatch Logo">
                    <span>MusicMatch</span>
                </div>
                <p>Discover and share your favorite music with MusicMatch. Our platform helps you find new tracks tailored to your taste.</p>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Features</a></li>
                    <li><a href="#">Web Player</a></li>
                    <li><a href="#">Music Swiper</a></li>
                    <li><a href="#">About Us</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Support</h3>
                <ul class="footer-links">
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> MusicMatch. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="../assets/js/notifications.js"></script>
<script src="../assets/js/logout.js"></script>
<script src="../assets/js/burger-menu.js"></script>
<script src="./assets/js/login-cleanup.js"></script>
<?php if (isset($additionalScripts)) echo $additionalScripts; ?>
</body>
</html>