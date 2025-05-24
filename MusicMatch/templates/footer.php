</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-logo">
                    <img src="./assets/img/icons/musicmatch-logo.svg" alt="MusicMatch Logo">
                    <span>MusicMatch</span>
                </div>
                <p>Discover and share your favorite music with MusicMatch. Our platform helps you find new tracks tailored to your taste.</p>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="./index.php">Home</a></li>
                    <li><a href="./features.php">Features</a></li>
                    <li><a href="./favorites.php">Favorites</a></li>
                    <li><a href="./swiper.php">Music Swiper</a></li>
                    <li><a href="./impressum.php">Impressum</a></li>
                    <li><a href="./privacy-policy.php">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> MusicMatch.</p>
        </div>
    </div>
</footer>

<script src="./assets/js/notifications.js"></script>
<script src="./assets/js/logout.js"></script>
<script src="./assets/js/burger-menu.js"></script>
<script src="./assets/js/login-cleanup.js"></script>
<?php if (isset($additionalScripts)) echo $additionalScripts; ?>
</body>
</html>