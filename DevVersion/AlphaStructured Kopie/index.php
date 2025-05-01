<?php
$pageTitle = "MusicMatch - Home";
$additionalCSS = '<link rel="stylesheet" href="../assets/css/index.css">';

require_once './includes/session_handler.php';
include "./templates/header.php";
require_once './vendor/autoload.php';
include_once "./includes/config.php";
require_once './includes/spotify_utils.php';

?>

<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Best <span>MusicSwiper</span> for <br> Tailored Listening Experiences</h1>
            <p class="hero-subtitle">Discover new tracks, create personalized playlists,<br>
            and enjoy seamless Spotify integration all in one place.</p>
            <div class="hero-buttons">
                <?php if (!isset($_SESSION['spotify_access_token'])): ?>
                    <a href="auth/login.php" class="btn btn-accent">Login with Spotify</a>
                <?php else: ?>
                    <a href="features.php" class="btn btn-accent">All Features</a>
                <?php endif; ?>
                <a href="swiper.php" class="btn btn-outline">Swipe Now</a>
            </div>
        </div>
    </div>
</section>

<section class="section" id="features">
    <div class="container">
        <div class="section-title">
            <h2>MusicMatch Features</h2>
            <p>Our platform offers a unique way to discover and enjoy music</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <img src="./assets/img/icons/analytics.svg" alt="Analytics Icon">
                </div>
                <h3 class="feature-title">Personalized Recommendations</h3>
                <p class="feature-description">We analyse your preferences and suggests songs you'll love based on your listening history.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <img src="./assets/img/icons/swipe.svg" alt="Swipe Icon">
                </div>
                <h3 class="feature-title">Swipe to Discover</h3>
                <p class="feature-description">Our intuitive swipe interface makes finding new music fun and engaging.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <img src="./assets/img/icons/discover.svg" alt="Discover Icon">
                </div>
                <h3 class="feature-title">Explore your Favorites</h3>
                <p class="feature-description">Check out your favorite artists and songs for different time periods.</p>
            </div>
        </div>
    </div>
</section>

<section class="section how-it-works">
    <div class="container">
        <div class="section-title">
            <h2>How It Works</h2>
            <p>Getting started with MusicMatch is easy</p>
        </div>

        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3 class="step-title">Login</h3>
                <p class="step-description">Login with your Spotify Account to access your music library.</p>
            </div>

            <div class="step">
                <div class="step-number">2</div>
                <h3 class="step-title">Swipe & Discover</h3>
                <p class="step-description">Use our Music Swiper to find new tracks and build your preference profile.</p>
            </div>

            <div class="step">
                <div class="step-number">3</div>
                <h3 class="step-title">Listen & Enjoy</h3>
                <p class="step-description">Play your discovered tracks and create playlists of your favorites.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Ready to Find Your Next Favorite Song?</h2>
        <p class="cta-description">Join thousands of music lovers discovering new tracks every day with MusicMatch.</p>
        <div class="cta-buttons">
            <?php if (!isset($_SESSION['spotify_access_token'])): ?>
                <a href="auth/login.php" class="btn btn-accent">Login with Spotify</a>
            <?php else: ?>
                <a href="swiper.php" class="btn btn-accent">Go to Swiper</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php

include "./templates/footer.php";
?>