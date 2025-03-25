<?php
require 'vendor/autoload.php';
include 'config.php';
session_start();

if (!isset($_SESSION['spotify_access_token'])) {
    header('Location: index.php');
    die();
}

$api = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken($_SESSION['spotify_access_token']);

// Initialize variables
$userTopTracks = null;
$seedTracks = [];
$recommendations = null;
$error = null;

try {
    // Get user's top tracks
    $userTopTracks = $api->getMyTop('tracks', [
        'limit' => 5,
        'time_range' => 'medium_term' // Options: short_term, medium_term, long_term
    ]);
    
    // Extract seed track IDs
    if (!empty($userTopTracks->items)) {
        foreach ($userTopTracks->items as $track) {
            $seedTracks[] = $track->id;
        }
        
        // Make sure we have at least one track but no more than 5
        $seedTracks = array_slice($seedTracks, 0, 5);
        
        if (!empty($seedTracks)) {
            // Get recommendations based on seed tracks
            $recommendations = $api->getRecommendations([
                'seed_tracks' => implode(',', $seedTracks),
                'limit' => 20
            ]);
        } else {
            $error = "Keine Seed-Tracks gefunden.";
        }
    } else {
        // If no top tracks, try a popular genre as fallback
        $recommendations = $api->getRecommendations([
            'seed_genres' => 'pop',
            'limit' => 20
        ]);
    }
} catch (Exception $e) {
    $error = "Fehler: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusicMatch - Empfehlungen</title>
    <link rel="stylesheet" href="./Style/style.css">
</head>
<body>
    <div class="container">
        <h1>MusicMatch - Empfehlungen</h1>
        
        <div class="recommendations">
            <h2>Basierend auf deinen Top-Tracks</h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($recommendations && !empty($recommendations->tracks)): ?>
                <ul class="recommendation-list">
                    <?php foreach ($recommendations->tracks as $track): ?>
                        <li>
                            <strong><?= htmlspecialchars($track->name) ?></strong> 
                            von 
                            <strong><?= htmlspecialchars(implode(', ', array_map(fn($artist) => $artist->name, $track->artists))) ?></strong>
                            
                            <?php if (isset($track->album->images[0])): ?>
                                <img src="<?= htmlspecialchars($track->album->images[count($track->album->images)-1]->url) ?>" 
                                     alt="Album Cover" class="mini-cover">
                            <?php endif; ?>
                            
                            <a href="<?= htmlspecialchars($track->external_urls->spotify) ?>" 
                               target="_blank" class="spotify-link">Auf Spotify anhören</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Keine Empfehlungen verfügbar.</p>
            <?php endif; ?>
        </div>
        
        <div class="navigation">
            <a href="./Pages/dashboard.php" class="btn">Zurück zum Dashboard</a>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </div>
</body>
</html>