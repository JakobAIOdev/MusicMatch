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
//require_once './debug_session.php';

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
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
        <h1>Music Swiper</h1>

        <div class="swipe-instructions">
            <p>Swipe right to like, left to dislike.<br>
                Or use the arrow keys ← → or the buttons below.</p>
            <div class="swipe-method-container">
                <form method="GET" action="swiper.php" id="swipe-form">
                    <div class="form-row">
                        <label for="swipe-method">Select Swipe Method:</label>
                        <div class="input-group <?php echo ($swipeMethod === 'playlist') ? 'playlist-mode' : ''; ?>">
                            <select id="swipe-method" name="swipe-method">
                                <option value="lastFM" <?php echo ($swipeMethod === 'lastFM') ? 'selected' : ''; ?>>LastFM</option>
                                <option value="playlist" <?php echo ($swipeMethod === 'playlist') ? 'selected' : ''; ?>>Playlist</option>
                                <option value="random" <?php echo ($swipeMethod === 'random') ? 'selected' : ''; ?>>Random</option>
                                <option value="short_term" <?php echo ($swipeMethod === 'short_term') ? 'selected' : ''; ?>>Favorites 4-Weeks</option>
                                <option value="medium_term" <?php echo ($swipeMethod === 'medium_term') ? 'selected' : ''; ?>>Favorites 6-Months</option>
                                <option value="long_term" <?php echo ($swipeMethod === 'long_term') ? 'selected' : ''; ?>>Favorites All</option>
                            </select>

                            <div id="playlist-input-group" class="<?php echo ($swipeMethod === 'playlist') ? 'visible' : ''; ?>">
                                <input type="url" id="playlist-link" name="playlist-link" placeholder="Paste Spotify playlist link"
                                    value="<?php echo isset($_GET['playlist-link']) ? htmlspecialchars($_GET['playlist-link']) : ''; ?>"
                                    required>
                                <button type="submit" class="btn btn-primary">Apply</button>
                            </div>
                        </div>
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
        <div class="reset-container">
            <a href="reset_seen_tracks.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" class="btn btn-secondary">
                <i class="fas fa-sync"></i> Reset Seen Tracks
            </a>
        </div>
        <div class="create-playlist-container">
            <button id="create-playlist-btn" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Create Playlist from Liked Songs
            </button>
            <button id="add-to-playlist-btn" class="btn btn-outline-primary">
                <i class="fas fa-plus"></i> Add to Existing Playlist
            </button>
        </div>
        <div class="modal fade" id="playlist-modal" tabindex="-1" aria-labelledby="playlist-modal-label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="playlist-modal-label">Select a Playlist</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Playlists will appear here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
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
    } elseif ($swipeMethod === 'short_term' || $swipeMethod === 'medium_term' || $swipeMethod === 'long_term') {
        $tracksJson = favoritesTracks($api, $swipeMethod);
    } else {
        die('Invalid swipe method');
    }
    ?>

    const tracks = <?php echo $tracksJson; ?>;
    let spotifyAccessToken = <?php echo json_encode($_SESSION['spotify_access_token']); ?>;

    // Make liked songs from session available to JavaScript
    const initialLikedSongs = <?php echo isset($_SESSION['liked_tracks']) ? json_encode($_SESSION['liked_tracks']) : '[]'; ?>;
</script>

<script src="./assets/js/swiper.js"></script>