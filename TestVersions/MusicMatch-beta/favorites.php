<?php
// Start session at the very beginning
session_start();

$pageTitle = "Discover Your Perfect Music Match";
$additionalCSS = '<link rel="stylesheet" href="./styles/favorites.css">';

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

if (isset($_SESSION['spotify_access_token'])) {
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($_SESSION['spotify_access_token']);
    try {
        $me = $api->me();
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
        unset($_SESSION['userData']);
        unset($_SESSION['spotify_access_token']);
    }
}

include "header.php";

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
        'limit' => 10,
        'time_range' => $time_range
    ]);
    
    // Save top items in a structured array
    $savedItems = [];
    foreach ($topItems->items as $item) {
        if ($view_type == 'tracks') {
            // For tracks, save song name and artist name
            $artistNames = array_map(function($artist) {
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
            // For artists, save artist name
            $savedItems[] = [
                'name' => $item->name,
                'type' => 'artist',
                'spotify_id' => $item->id,
                'spotify_url' => $item->external_urls->spotify
            ];
        }
    }
    
    // Store in session for potential later use
    $_SESSION['saved_' . $view_type] = $savedItems;
    
    // Debug - Uncomment to test
    //echo '<pre>'; print_r($savedItems); echo '</pre>';
    
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
    <section class="favorites">
        <h1>Your Top <?php echo ucfirst($view_type); ?> on Spotify</h1>
        
        <div class="nav-wrapper">
            <div class="view-nav">
                <a href="?view=artists&time_range=<?php echo $time_range; ?>" class="<?php echo $view_type == 'artists' ? 'active' : ''; ?>">Artists</a>
                <a href="?view=tracks&time_range=<?php echo $time_range; ?>" class="<?php echo $view_type == 'tracks' ? 'active' : ''; ?>">Tracks</a>
            </div>
            
            <div class="time-nav">
                <a href="?view=<?php echo $view_type; ?>&time_range=short_term" class="<?php echo $time_range == 'short_term' ? 'active' : ''; ?>">Short Term</a>
                <a href="?view=<?php echo $view_type; ?>&time_range=medium_term" class="<?php echo $time_range == 'medium_term' ? 'active' : ''; ?>">Medium Term</a>
                <a href="?view=<?php echo $view_type; ?>&time_range=long_term" class="<?php echo $time_range == 'long_term' ? 'active' : ''; ?>">Long Term</a>
            </div>
        </div>
        
        <p>Time Range: <strong><?php echo $time_descriptions[$time_range]; ?></strong></p>
        
        <div class="content-wrapper">
            <?php if (count($topItems->items) > 0): ?>
                <?php foreach ($topItems->items as $index => $item): ?>
                    <div class="card">
                        <img 
                            src="<?php echo isset($item->images[0]) ? htmlspecialchars($item->images[0]->url) : (isset($item->album->images[0]) ? htmlspecialchars($item->album->images[0]->url) : 'https://via.placeholder.com/100'); ?>" 
                            class="image" 
                            alt="<?php echo htmlspecialchars($item->name); ?>"
                        >
                        <div class="item-info">
                            <h3><?php echo ($index + 1) . '. ' . htmlspecialchars($item->name); ?></h3>
                            <?php if ($view_type == 'artists'): ?>
                                <p>Genres: <?php echo implode(', ', $item->genres); ?></p>
                                <p>Popularity: <?php echo $item->popularity; ?>/100</p>
                            <?php else: ?>
                                <p>Artist: <?php 
                                    $artists = array_map(function($artist) {
                                        return htmlspecialchars($artist->name);
                                    }, $item->artists);
                                    echo implode(', ', $artists);
                                ?></p>
                                <p>Album: <?php echo htmlspecialchars($item->album->name); ?></p>
                            <?php endif; ?>
                            <a href="<?php echo $item->external_urls->spotify; ?>" target="_blank" class="spotify-link">Open on Spotify</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No top <?php echo $view_type; ?> found. You might not have listened to enough music in this time range.</p>
            <?php endif; ?>
        </div>
    </section>
</body>

<?php include "footer.php";?>
