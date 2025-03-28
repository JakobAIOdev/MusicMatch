<?php
// Start session at the very beginning
session_start();

$pageTitle = "Discover Your Perfect Music Match";
$additionalCSS = '<link rel="stylesheet" href="./styles/landing-page.css">';

require_once 'vendor/autoload.php';
include_once "config.php";

// Refresh token if needed
if (isset($_SESSION['spotify_access_token']) && isset($_SESSION['spotify_token_expires']) && $_SESSION['spotify_token_expires'] < time() && isset($_SESSION['spotify_refresh_token'])) {
    try {
        $session = new SpotifyWebAPI\Session(
            $CLIENT_ID,
            $CLIENT_SECRET,
            $CALLBACK_URL
        );
        $session->refreshAccessToken($_SESSION['spotify_refresh_token']);
        $_SESSION['spotify_access_token'] = $session->getAccessToken();
        $_SESSION['spotify_token_expires'] = time() + $session->getTokenExpiration();
    } catch (Exception $e) {
        error_log('Failed to refresh token: ' . $e->getMessage());
    }
}

// Check if user is logged in
if (isset($_SESSION['spotify_access_token'])) {
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($_SESSION['spotify_access_token']);
    try {
        $me = $api->me();
        // Make sure user data is properly set
        if (!isset($_SESSION['userData']) || empty($_SESSION['userData'])) {
            $_SESSION['userData'] = [
                'id' => $me->id,
                'display_name' => $me->display_name,
                'email' => $me->email,
                'images' => $me->images
            ];
        }
        error_log('User validated in index: ' . $_SESSION['userData']['display_name']);
    } catch (Exception $e) {
        error_log('API Error in index: ' . $e->getMessage());
        // Clear invalid session data
        unset($_SESSION['userData']);
        unset($_SESSION['spotify_access_token']);
    }
}

include "header.php";
?>

<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Find Your Next Favorite Song</h1>
            <p class="hero-subtitle">MusicMatch uses smart algorithms to recommend music tailored to your taste. Discover, swipe, and create the perfect playlist.</p>
            <div class="hero-buttons">
                <a href="signup.php" class="btn btn-accent">Get Started</a>
                <a href="features.php" class="btn btn-outline">Learn More</a>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Why Choose MusicMatch?</h2>
            <p>Our platform offers a unique way to discover and enjoy music</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-music"></i>
                </div>
                <h3 class="feature-title">Personalized Recommendations</h3>
                <p class="feature-description">Our AI learns your preferences and suggests songs you'll love based on your listening history.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-hand-pointer"></i>
                </div>
                <h3 class="feature-title">Swipe to Discover</h3>
                <p class="feature-description">Our intuitive swipe interface makes finding new music fun and engaging.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headphones-alt"></i>
                </div>
                <h3 class="feature-title">Integrated Web Player</h3>
                <p class="feature-description">Listen to full tracks right in your browser without switching between apps.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-share-alt"></i>
                </div>
                <h3 class="feature-title">Share Your Favorites</h3>
                <p class="feature-description">Create and share playlists with friends and the MusicMatch community.</p>
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
                <h3 class="step-title">Sign Up</h3>
                <p class="step-description">Create your account and connect with Spotify to access your music library.</p>
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

<section class="section testimonials">
    <div class="container">
        <div class="section-title">
            <h2>What Our Users Say</h2>
            <p>Join thousands of satisfied music lovers</p>
        </div>
        
        <div class="testimonial-slider">
            <div class="testimonial-card">
                <p class="testimonial-text">"MusicMatch has completely changed how I discover new music. The recommendations are spot on, and I've found so many great artists I would have never discovered otherwise."</p>
                <div class="testimonial-author">
                    <img src="img/testimonial-1.jpg" alt="Sarah J.">
                    <h4 class="testimonial-name">Sarah J.</h4>
                    <p class="testimonial-role">Music Enthusiast</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Ready to Find Your Next Favorite Song?</h2>
        <p class="cta-description">Join thousands of music lovers discovering new tracks every day with MusicMatch.</p>
        <div class="cta-buttons">
            <a href="signup.php" class="btn btn-accent">Sign Up Now</a>
            <a href="features.php" class="btn btn-outline">Learn More</a>
        </div>
    </div>
</section>

<?php
$additionalScripts = '
<script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick.min.js"></script>
<script>
    $(document).ready(function(){
        $(".testimonial-slider").slick({
            dots: true,
            arrows: false,
            autoplay: true,
            autoplaySpeed: 5000
        });
    });
</script>
';
include "footer.php";
?>
