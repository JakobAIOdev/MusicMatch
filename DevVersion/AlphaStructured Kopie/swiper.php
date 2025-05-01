<?php
$pageTitle = "Music Swiper";
$additionalCSS = '<link rel="stylesheet" href="./assets/css/swiper.css">';

require_once './includes/session_handler.php';
include "./templates/header.php";
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';
require_once './templates/components/premium_notice.php';
require_once './templates/components/login_notice.php';
require_once './includes/swiperMethod.php';

if (isset($_GET['swipe-method'])) {
    $swipeMethod = $_GET['swipe-method'];
} else {
    $swipeMethod = 'random';
}

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
            <div class="swipe-method-container">
                <form method="GET" action="swiper.php" id="swipe-form">
                    <label for="swipe-method">Select Swipe Method:</label>
                    <select id="swipe-method" name="swipe-method">
                        <option value="lastFM" <?php echo ($swipeMethod === 'lastFM') ? 'selected' : ''; ?>>LastFM</option>
                        <option value="playlist" <?php echo ($swipeMethod === 'playlist') ? 'selected' : ''; ?>>Playlist</option>
                        <option value="random" <?php echo ($swipeMethod === 'random') ? 'selected' : ''; ?>>Random</option>
                    </select>
                    <div id="playlist-input-group" style="display:<?php echo ($swipeMethod === 'playlist') ? 'block' : 'none'; ?>; margin-top:10px;">
                        <label for="playlist-link">Playlist Link:</label>
                        <input type="url" id="playlist-link" name="playlist-link" placeholder="Paste playlist link here"
                            value="<?php echo isset($_GET['playlist-link']) ? htmlspecialchars($_GET['playlist-link']) : ''; ?>"
                            required>
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="swipe-container" id="swipe-container">
            <!-- Cards will appear here -->
        </div>

        <div class="action-buttons">
            <button class="action-button dislike-button" id="dislike-button">
                <img src="./assets/img/icons/dislike.svg" alt="Dislike">
            </button>
            <button class="action-button like-button" id="like-button">
                <img src="./assets/img/icons/like.svg" alt="Like">
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
    <?php
    if ($swipeMethod === 'random') {
        $tracksJson = getTopTracks($api);
    } elseif ($swipeMethod === 'playlist') {
        if (isset($_GET['playlist-link'])) {
            $playlistLink = $_GET['playlist-link'];
            $tracksJson = playlistTracks($api, $playlistLink);
        } else {
            die('Playlist link is required');
        }
    } elseif ($swipeMethod === 'lastFM') {
        $tracksJson = getTopTracks($api);
    } else {
        die('Invalid swipe method');
    }
    ?>
    const tracks = <?php echo $tracksJson; ?>;
    let spotifyAccessToken = <?php echo json_encode($_SESSION['spotify_access_token']); ?>;
</script>
<script src="./assets/js/swiper.js"></script>

<?php include "./templates/footer.php" ?>