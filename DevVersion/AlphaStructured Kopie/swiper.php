<?php
$pageTitle = "Music Swiper";
$additionalCSS = '<link rel="stylesheet" href="./assets/css/swiper.css">';

require_once './includes/session_handler.php';
include "./templates/header.php";
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';
require_once './templates/components/premium_notice.php';
require_once './templates/components/login_notice.php';

if (!isLoggedIn()) {
    showLoginNotice();
    die;
}

$api = getSpotifyApi();
$me = $api->me();

$hasPremium = ($me->product === 'premium');

if (!$hasPremium) {
    showPremiumNotice("Music Swiper");
}

include './includes/getFavSongs.php';
?>

<div class="main-content">
    <div class="maincontainer-swiper">
        <h1>Music Swiper</h1>

        <div class="swipe-instructions">
            <p>Swipe right to like, left to dislike.<br>
                Or use the arrow keys ← → or the buttons below.</p>
        </div>

        <div class="swipe-container" id="swipe-container">
            <!-- Cards will appear here -->
        </div>

        <div class="action-buttons">
            <button class="action-button dislike-button" id="dislike-button">
                <img src="./assets/img/dislikeicon.svg" alt="Dislike">
            </button>
            <button class="action-button like-button" id="like-button">
            <img src="./assets/img/likeicon.svg" alt="Like">
            </button>
        </div>

        <div class="liked-songs-container">
            <h2>Your Liked Songs</h2>
            <ul class="liked-songs-list" id="liked-songs-list">
                <!-- Liked Songs will be stored here -->
                <li class="no-liked-songs">No songs liked yet</li>
            </ul>
        </div>
    </div>
</div>
<!--  Spotify Web Playback SDK library-->
<script src="https://sdk.scdn.co/spotify-player.js"></script>
<!--  Swipe library-->
<script src="https://hammerjs.github.io/dist/hammer.min.js"></script>

<script>
    const tracks = <?php echo $tracksJson; ?>;
    let spotifyAccessToken = <?php echo json_encode($_SESSION['spotify_access_token']); ?>;
</script>
<script src="./assets/js/swiper.js"></script>

<?php include "./templates/footer.php"; ?>