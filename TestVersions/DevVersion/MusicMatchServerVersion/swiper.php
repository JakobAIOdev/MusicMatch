<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = "Music Swiper";
$additionalCSS = '<link rel="stylesheet" href="./assets/css/swiper.css">';

require_once './includes/session_handler.php';
include "./templates/header.php";
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';
require_once './templates/components/premium_notice.php';
require_once './templates/components/login_notice.php';
require_once './includes/swiperMethod.php';
require_once './includes/lastFM.php';
include './includes/getFavSongs.php';

if (
    !isset($_SESSION['spotify_access_token']) ||
    !isset($_SESSION['spotify_refresh_token'])
) {
    $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
    header('Location: ./auth/login.php');
    exit;
}

$api = new SpotifyWebAPI\SpotifyWebAPI(['auto_retry' => true]);
$api->setAccessToken($_SESSION['spotify_access_token']);

try {
    $me = $api->me();
} catch (Exception $e) {
    try {
        $session = new SpotifyWebAPI\Session(
            $CLIENT_ID,
            $CLIENT_SECRET,
            $CALLBACK_URL
        );

        $session->setRefreshToken($_SESSION['spotify_refresh_token']);
        $refreshResult = $session->refreshAccessToken($_SESSION['spotify_refresh_token']);

        if (!$refreshResult) {
            throw new Exception("Failed to refresh token");
        }

        $_SESSION['spotify_access_token'] = $session->getAccessToken();
        $_SESSION['spotify_token_expires'] = time() + $session->getTokenExpiration();
        $api->setAccessToken($_SESSION['spotify_access_token']);

        $me = $api->me();
    } catch (Exception $refreshError) {
        unset($_SESSION['spotify_access_token']);
        unset($_SESSION['spotify_refresh_token']);

        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
        header('Location: ./auth/login.php');
        exit;
    }
}

if (!isset($me) || !isset($me->product)) {
    showLoginNotice();
    die;
}

$hasPremium = ($me->product === 'premium');
if (!$hasPremium) {
    showPremiumNotice("Music Swiper");
}

if (isset($_GET['swipe-method'])) {
    $swipeMethod = $_GET['swipe-method'];
} else {
    $swipeMethod = 'random';
}

try {
    if ($swipeMethod === 'playlist' && isset($_GET['playlist-link'])) {
        $playlistLink = $_GET['playlist-link'];
        $tracksJson = playlistTracks($api, $playlistLink);
    } else if ($swipeMethod === 'lastFM' && isset($_GET['lasFm-username'])) {
        $lastFmUsername = $_GET['lasFm-username'];
        $tracksJson = getRecommendedTracksLastFM($api, $lastFmUsername);
    } else if ($swipeMethod === 'random') {
        $tracksJson = getTopTracks($api);
    } else if (in_array($swipeMethod, ['short_term', 'medium_term', 'long_term'])) {
        $tracksJson = favoritesTracks($api, $swipeMethod);
    } else {
        $tracksJson = getTopTracks($api);
    }
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>

<div class="main-content">
    <div class="maincontainer-swiper">
        <h1 class="text-center">Music Swiper</h1>
        <div class="swipe-instructions">
            <div class="swipe-method-container">
                <form method="GET" action="swiper.php" id="swipe-form">
                    <div class="form-row">
                        <label for="swipe-method">Select Swipe Method:</label>
                        <div class="input-group <?php
                                                if ($swipeMethod === 'playlist') {
                                                    echo 'playlist-mode';
                                                } elseif ($swipeMethod === 'lastFM') {
                                                    echo 'lastfm-mode';
                                                }
                                                ?>">
                            <select id="swipe-method" name="swipe-method">
                                <option value="billboard_hot_100" <?php echo ($swipeMethod === 'billboard_hot_100') ? 'selected' : ''; ?>>Billboard Hot 100</option>
                                <option value="100_most_streamed" <?php echo ($swipeMethod === '100_most_streamed') ? 'selected' : ''; ?>>100 Most Streamed Songs on Spotify</option>
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

                            <div id="lasFm-input-group" class="<?php echo ($swipeMethod === 'lastFM') ? 'visible' : ''; ?>">
                                <input type="text" id="lasFm-username" name="lasFm-username" placeholder="Paste LastFM username"
                                    value="<?php echo isset($_GET['lasFm-username']) ? htmlspecialchars($_GET['lasFm-username']) : ''; ?>"
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
        <div class="control-buttons-row">
            <button id="create-playlist-btn" class="control-btn create-btn">
                <img src="./assets/img/icons/addPlaylist.svg" alt="" class="control-btn-icon">
                <span>Create Playlist</span>
            </button>

            <button id="add-to-playlist-btn" class="control-btn add-btn">
                <img src="./assets/img/icons/createPlaylist.svg" alt="" class="control-btn-icon">
                <span>Add to Playlist</span>
            </button>

            <button id="reset-tracks-btn" class="control-btn reset-btn">
                <img src="./assets/img/icons/reset.svg" alt="" class="control-btn-icon">
                <span>Reset</span>
            </button>
        </div>
        <div id="reset-confirmation" style="display: none;" class="confirmation-panel">
            <p class="text-danger">Are you sure you want to reset all seen tracks? This cannot be undone.</p>
            <div class="confirmation-buttons">
                <button type="button" class="btn btn-sm btn-danger" id="confirm-reset-btn">Reset</button>
                <button type="button" class="btn btn-sm btn-secondary" id="cancel-reset-btn">Cancel</button>
            </div>
        </div>

        <div id="create-playlist-confirmation" style="display: none;" class="confirmation-panel">
            <div class="form-group mb-2">
                <input type="text" id="playlist-name" class="form-control" placeholder="Playlist name" value="My MusicMatch Playlist">
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" id="clear-liked-songs" class="form-check-input">
                <label class="form-check-label" for="clear-liked-songs">Clear liked songs after creating</label>
            </div>
            <div class="confirmation-buttons">
                <button type="button" class="btn btn-sm btn-primary" id="confirm-create-btn">Create Playlist</button>
                <button type="button" class="btn btn-sm btn-secondary" id="cancel-create-btn">Cancel</button>
            </div>
        </div>

        <div id="add-to-playlist-confirmation" style="display: none;" class="confirmation-panel">
            <div class="form-group mb-2">
                <label for="existing-playlist-select" class="form-label">Select playlist:</label>
                <select class="form-select" id="existing-playlist-select">
                    <option value="" disabled selected>Loading playlists...</option>
                </select>
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" id="clear-liked-songs-add" class="form-check-input">
                <label class="form-check-label" for="clear-liked-songs-add">Clear liked songs after adding</label>
            </div>
            <div class="confirmation-buttons">
                <button type="button" class="btn btn-sm btn-primary" id="confirm-add-btn" disabled>Add to Playlist</button>
                <button type="button" class="btn btn-sm btn-secondary" id="cancel-add-btn">Cancel</button>
            </div>
        </div>

        <div class="liked-songs-container">
            <h2 class="text-center">Your Liked Songs</h2>
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
        if (isset($_GET['lasFm-username'])) {
            $lastFmUsername = $_GET['lasFm-username'];
            $tracksJson = getRecommendedTracksLastFM($api, $lastFmUsername);
        } else {
            die('LastFM username is required');
        }
    } elseif ($swipeMethod === 'short_term' || $swipeMethod === 'medium_term' || $swipeMethod === 'long_term') {
        $tracksJson = favoritesTracks($api, $swipeMethod);
    } elseif ($swipeMethod === 'billboard_hot_100'){
        $tracksJson = billboardHot100($api);
    }elseif ($swipeMethod === '100_most_streamed'){
        $tracksJson = mostStreamed100($api);
    }else {
        die('Invalid swipe method');
    }
    ?>
    const spotifyAccessToken = '<?php echo $_SESSION['spotify_access_token']; ?>';
    const tracks = <?php echo $tracksJson; ?>;
    const initialLikedSongs = <?php echo isset($_SESSION['liked_tracks']) ? json_encode($_SESSION['liked_tracks']) : '[]'; ?>;
</script>

<script src="./assets/js/swiper.js"></script>

<?php include "./templates/footer.php"; ?>
