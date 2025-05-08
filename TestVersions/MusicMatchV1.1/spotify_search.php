<?php
require 'vendor/autoload.php';
include "config.php";
session_start();

// Check if the user is logged in to Spotify
$isLoggedIn = isset($_SESSION['spotify_access_token']);

// Initialize the array to store Spotify data
$recommended_songs_spotify_data = [];

// Function to search a song on Spotify
function searchSongOnSpotify($api, $title, $artist) {
    try {
        // Create a search query with both title and artist for better results
        $query = "track:{$title} artist:{$artist}";
        $results = $api->search($query, 'track', ['limit' => 1]);
        
        if (isset($results->tracks->items[0])) {
            return $results->tracks->items[0];
        }
        
        // If no results, try a more generic search
        $query = "{$title} {$artist}";
        $results = $api->search($query, 'track', ['limit' => 1]);
        
        if (isset($results->tracks->items[0])) {
            return $results->tracks->items[0];
        }
        
        return null;
    } catch (Exception $e) {
        return null;
    }
}

// Process the LastFM recommendations if user is logged in
if ($isLoggedIn && isset($_SESSION['lastfm_username'])) {
    // Initialize the Spotify API
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($_SESSION['spotify_access_token']);
    
    // Get the LastFM recommendations if they exist
    if (isset($_SESSION['recommended_songs']) && is_array($_SESSION['recommended_songs'])) {
        $lastfm_recommendations = $_SESSION['recommended_songs'];
    } else {
        // Include LastFM functionality to get recommendations
        require_once 'lastFM.php';
        $lastfm_recommendations = $recommendedSongsArray;
    }
    
    // Loop through LastFM recommendations and search on Spotify
    foreach ($lastfm_recommendations as $song) {
        $title = $song['title'] ?? '';
        $artist = $song['artist'] ?? '';
        
        if (!empty($title) && !empty($artist)) {
            $spotify_track = searchSongOnSpotify($api, $title, $artist);
            
            if ($spotify_track) {
                // Create a new object with Spotify data
                $spotify_data = [
                    'lastfm_title' => $title,
                    'lastfm_artist' => $artist,
                    'lastfm_image' => $song['image'] ?? '',
                    'spotify_id' => $spotify_track->id,
                    'spotify_title' => $spotify_track->name,
                    'spotify_artist' => isset($spotify_track->artists[0]) ? $spotify_track->artists[0]->name : '',
                    'spotify_album' => $spotify_track->album->name,
                    'spotify_image' => isset($spotify_track->album->images[0]) ? $spotify_track->album->images[0]->url : '',
                    'spotify_preview_url' => $spotify_track->preview_url,
                    'spotify_url' => $spotify_track->external_urls->spotify,
                    'popularity' => $spotify_track->popularity,
                    'duration_ms' => $spotify_track->duration_ms
                ];
                
                $recommended_songs_spotify_data[] = $spotify_data;
            }
        }
    }
    
    // Store the Spotify data in session for future use
    $_SESSION['recommended_songs_spotify_data'] = $recommended_songs_spotify_data;
}

// Handle API requests (if needed)
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjaxRequest) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $recommended_songs_spotify_data
    ]);
    exit;
}

// If not an AJAX request, include HTML content
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./style.css">
    <title>Spotify Recommendations</title>
</head>
<body>
    <div class="dashboard-container">
        <div class="site-header">
            <div class="site-logo">
                <h1>MusicMatch</h1>
            </div>
            <div class="nav-user-info">
                <a href="dashboard.php" class="back-to-dashboard">Dashboard</a>
                <?php if ($isLoggedIn): ?>
                    <a href="logout.php" class="logout-btn">Logout</a>
                <?php endif; ?>
            </div>
        </div>

        <h1>LastFM Recommendations on Spotify</h1>
        
        <?php if (!$isLoggedIn): ?>
            <div class="login-container">
                <p>You need to be logged in to Spotify to use this feature.</p>
                <a href="login.php" class="login-btn">Login with Spotify</a>
            </div>
        <?php elseif (empty($recommended_songs_spotify_data)): ?>
            <div class="content-wrapper">
                <p>No recommendations found. Please make sure you have recommendations from LastFM.</p>
                <a href="lastFM.php" class="back-to-dashboard">Go to LastFM Recommendations</a>
            </div>
        <?php else: ?>
            <div class="content-wrapper">
                <ul class="song-list">
                    <?php foreach ($recommended_songs_spotify_data as $song): ?>
                        <li class="song-item card">
                            <?php if (!empty($song['spotify_image'])): ?>
                                <img class="song-image image" src="<?php echo htmlspecialchars($song['spotify_image']); ?>" alt="Album Cover">
                            <?php else: ?>
                                <div class="song-image image default-image">
                                    <span>🎵</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="song-info item-info">
                                <h3 class="song-title"><?php echo htmlspecialchars($song['spotify_title']); ?></h3>
                                <p class="song-artist"><?php echo htmlspecialchars($song['spotify_artist']); ?></p>
                                <p>Album: <?php echo htmlspecialchars($song['spotify_album']); ?></p>
                                <p>Popularity: <?php echo htmlspecialchars($song['popularity']); ?>/100</p>
                                
                                <?php if (!empty($song['spotify_preview_url'])): ?>
                                    <audio controls>
                                        <source src="<?php echo htmlspecialchars($song['spotify_preview_url']); ?>" type="audio/mpeg">
                                        Your browser does not support the audio element.
                                    </audio>
                                <?php endif; ?>
                                
                                <a href="<?php echo htmlspecialchars($song['spotify_url']); ?>" target="_blank" class="spotify-link">Open in Spotify</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <script>
            // Store the Spotify data in JavaScript
            const recommendedSongsSpotifyData = <?php echo json_encode($recommended_songs_spotify_data); ?>;
            console.log('Spotify Data:', recommendedSongsSpotifyData);
        </script>
    </div>
</body>
<?php include "footer.php"; ?>
</html>
