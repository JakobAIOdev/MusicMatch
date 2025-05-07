<?php
$pageTitle = "MusicMatch - About";
$additionalCSS = '<link rel="stylesheet" href="./assets/css/features.css">';

require_once './includes/session_handler.php';
include "./templates/header.php";
require_once './vendor/autoload.php';
include_once "./includes/config.php";
require_once './includes/spotify_utils.php';
?>


<section class="hero-section">
    <div class="container">
        <h1>MusicMatch <span>About</span></h1>
        <p class="intro-text">Discover how MusicMatch can enhance your music discovery experience with our powerful features, designed for personalized music exploration and playlist creation.</p>
    </div>
    <div class="down-arrow-container">
        <a href="#music-swiper" class="down-arrow">
            <img src="./assets/img/icons/arrow-down.svg" alt="">
        </a>
    </div>
</section>


<?php include "./templates/footer.php";?>