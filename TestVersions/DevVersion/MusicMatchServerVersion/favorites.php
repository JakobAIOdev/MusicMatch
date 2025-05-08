<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = "Your Spotify Favorites";
$additionalCSS = "<link rel='stylesheet' href='{$BASE_URL}assets/css/favorites.css'>";

require_once './includes/session_handler.php';
include "./templates/header.php";
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';
require_once './templates/components/login_notice.php';


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
$api = getSpotifyApi();
$api->setAccessToken($_SESSION['spotify_access_token']);

try {
    $userData = $api->me();
} catch (Exception $e) {
    die('Error fetching user data: ' . $e->getMessage());
}

$time_range = isset($_GET['time_range']) ? $_GET['time_range'] : 'medium_term';
$view_type = isset($_GET['view']) ? $_GET['view'] : 'artists';

$valid_ranges = ['short_term', 'medium_term', 'long_term'];
$valid_views = ['artists', 'tracks'];

if (!in_array($time_range, $valid_ranges)) {
    $time_range = 'medium_term';
}

if (!in_array($view_type, $valid_views)) {
    $view_type = 'artists';
}

try {
    $topItems = $api->getMyTop($view_type, [
        'limit' => 50,
        'time_range' => $time_range
    ]);
    $savedItems = [];
    foreach ($topItems->items as $item) {
        if ($view_type == 'tracks') {
            $artistNames = array_map(function ($artist) {
                return $artist->name;
            }, $item->artists);

            $savedItems[] = [
                'name' => $item->name,
                'artist' => implode(', ', $artistNames),
                'type' => 'track',
                'spotify_id' => $item->id,
                'spotify_url' => $item->external_urls->spotify
            ];
        } else {
            $savedItems[] = [
                'name' => $item->name,
                'type' => 'artist',
                'spotify_id' => $item->id,
                'spotify_url' => $item->external_urls->spotify
            ];
        }
    }
    $_SESSION['saved_' . $view_type] = $savedItems;
} catch (Exception $e) {
    die('Error fetching top ' . $view_type . ': ' . $e->getMessage());
}

// Time range descriptions
$time_descriptions = [
    'short_term' => 'Last 4 weeks',
    'medium_term' => 'Last 6 months',
    'long_term' => 'Several years'
];
?>
<section>
    <h1>Your Top <?php echo ucfirst($view_type); ?> on Spotify</h1>

    <div class="nav-wrapper">
        <div class="nav-content">
            <div class="view-nav">
                <a href="?view=artists&time_range=<?php echo $time_range; ?>" class="<?php echo $view_type == 'artists' ? 'active' : ''; ?>">Artists</a>
                <a href="?view=tracks&time_range=<?php echo $time_range; ?>" class="<?php echo $view_type == 'tracks' ? 'active' : ''; ?>">Tracks</a>
            </div>

            <?php if ($view_type == 'tracks'): ?>
                <div class="action-nav">
                    <button id="create-top-tracks-playlist-btn" class="control-btn create-btn">
                        <img src="./assets/img/icons/addPlaylist.svg" alt="" class="control-btn-icon">
                        <span>Create Playlist</span>
                    </button>
                    <div id="create-playlist-form" style="display: none;" class="playlist-creation-form">
                        <div class="form-group">
                            <input type="text" id="playlist-name" class="form-control" placeholder="Playlist name"
                                value="<?php
                                if ($time_range == 'short_term') {
                                    echo 'My Top Tracks - Last 4 Weeks';
                                } elseif ($time_range == 'medium_term') {
                                    echo 'My Top Tracks - Last 6 Months';
                                } else {
                                    echo 'My Top Tracks - All Time';
                                }?>">
                        </div>
                        <div class="form-buttons">
                            <button type="button" class="btn btn-primary" id="confirm-create-btn">Create Playlist</button>
                            <button type="button" class="btn btn-secondary" id="cancel-create-btn">Cancel</button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="spacer"></div>
            <?php endif; ?>

            <div class="time-nav">
                <a href="?view=<?php echo $view_type; ?>&time_range=short_term" class="<?php echo $time_range == 'short_term' ? 'active' : ''; ?>">Short Term</a>
                <a href="?view=<?php echo $view_type; ?>&time_range=medium_term" class="<?php echo $time_range == 'medium_term' ? 'active' : ''; ?>">Medium Term</a>
                <a href="?view=<?php echo $view_type; ?>&time_range=long_term" class="<?php echo $time_range == 'long_term' ? 'active' : ''; ?>">Long Term</a>
            </div>
        </div>
    </div>

    <p class="time-range-info">Time Range: <strong><?php echo $time_descriptions[$time_range]; ?></strong></p>

    <div class="content-wrapper">
        <?php if (count($topItems->items) > 0): ?>
            <?php foreach ($topItems->items as $index => $item): ?>
                <div class="card" data-index="<?php echo $index; ?>" data-uri="<?php echo $item->uri; ?>">
                    <img
                        src="<?php echo isset($item->images[0]) ? htmlspecialchars($item->images[0]->url) : (isset($item->album->images[0]) ? htmlspecialchars($item->album->images[0]->url) : 'https://via.placeholder.com/100'); ?>"
                        class="image"
                        alt="<?php echo htmlspecialchars($item->name); ?>">
                    <div class="item-info">
                        <h3><?php echo ($index + 1) . '. ' . htmlspecialchars($item->name); ?></h3>
                        <?php if ($view_type == 'artists'): ?>
                            <p>Genres: <?php echo implode(', ', $item->genres); ?></p>
                            <p>Popularity: <?php echo $item->popularity; ?>/100</p>
                        <?php else: ?>
                            <p>Artist: <?php
                                        $artists = array_map(function ($artist) {
                                            return htmlspecialchars($artist->name);
                                        }, $item->artists);
                                        echo implode(', ', $artists);
                                        ?></p>
                            <p>Album: <?php echo htmlspecialchars($item->album->name); ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo $item->external_urls->spotify; ?>" class="spotify-button" target="_blank">
                        <img class="spotify-icon" src="./assets/img/icons/spotify-primary-white.svg" alt="Spotify">
                        <span>Listen on Spotify</span>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No top <?php echo $view_type; ?> found. You might not have listened to enough music in this time range.</p>
        <?php endif; ?>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('js-enabled');
        const createBtn = document.getElementById('create-top-tracks-playlist-btn');
        const confirmCreateBtn = document.getElementById('confirm-create-btn');
        const cancelCreateBtn = document.getElementById('cancel-create-btn');
        const createForm = document.getElementById('create-playlist-form');

        if (createBtn && createForm) {
            createBtn.addEventListener('click', function() {
                createForm.style.display = 'block';
                createBtn.style.display = 'none';
            });

            cancelCreateBtn.addEventListener('click', function() {
                createForm.style.display = 'none';
                createBtn.style.display = 'inline-flex';
            });

            confirmCreateBtn.addEventListener('click', function() {
                const playlistName = document.getElementById('playlist-name').value;

                if (!playlistName.trim()) {
                    showNotification("Please enter a playlist name", 'error');
                    return;
                }

                confirmCreateBtn.disabled = true;
                confirmCreateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';

                const trackUris = [];
                document.querySelectorAll('.card[data-uri]').forEach(track => {
                    const uri = track.getAttribute('data-uri');
                    if (uri) trackUris.push(uri);
                });

                if (trackUris.length === 0) {
                    showNotification("No tracks found to add to playlist", 'error');
                    confirmCreateBtn.disabled = false;
                    confirmCreateBtn.innerHTML = "Create Playlist";
                    return;
                }

                fetch("./includes/create_playlist.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            name: playlistName,
                            tracks: trackUris,
                            clear_liked_songs: false,
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(`Playlist "${playlistName}" created successfully!`, 'success');
                            createForm.style.display = 'none';
                            createBtn.style.display = 'inline-flex';
                        } else {
                            showNotification("Failed to create playlist: " + data.message, 'error');
                        }
                        confirmCreateBtn.disabled = false;
                        confirmCreateBtn.innerHTML = "Create Playlist";
                    })
                    .catch(error => {
                        console.error("Error creating playlist:", error);
                        showNotification("Failed to create playlist: " + error.message, 'error');
                        confirmCreateBtn.disabled = false;
                        confirmCreateBtn.innerHTML = "Create Playlist";
                    });
            });
        }

        document.querySelectorAll('.card').forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('visible');
            }, 50 * index);
        });

        animateNavigation();
    });

    function animateNavigation() {
        const navElements = document.querySelectorAll('.view-nav, .time-nav');
        navElements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(10px)';

            setTimeout(() => {
                el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 100 * (index + 1));
        });
    }
</script>

<script src="./assets/js/animations.js"></script>
<script src="./assets/js/notifications.js"></script>

<?php include "./templates/footer.php" ?>