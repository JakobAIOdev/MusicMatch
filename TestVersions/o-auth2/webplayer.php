<?php
require 'vendor/autoload.php';
include "config.php";
session_start();

// Prüfen, ob der Benutzer angemeldet ist
if (!isset($_SESSION['spotify_access_token'])) {
    header('Location: index.php');
    exit;
}

// API-Client initialisieren
$api = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken($_SESSION['spotify_access_token']);

// Variablen für die Ansicht initialisieren
$tracks = [];
$errorMessage = null;
$searchQuery = isset($_GET['q']) ? $_GET['q'] : '';

try {
    // Wenn eine Suchanfrage vorhanden ist, führe die Suche durch
    if (!empty($searchQuery)) {
        $searchResults = $api->search($searchQuery, 'track', [
            'limit' => 10
        ]);
        
        if (isset($searchResults->tracks->items)) {
            $tracks = $searchResults->tracks->items;
        }
    } else {
        // Andernfalls zeige einige populäre Tracks an
        $topTracks = $api->getMyTop('tracks', [
            'limit' => 10,
            'time_range' => 'medium_term'
        ]);
        
        if (isset($topTracks->items) && count($topTracks->items) > 0) {
            $tracks = $topTracks->items;
        } else {
            // Fallback: Neue Veröffentlichungen
            $newReleases = $api->getNewReleases([
                'limit' => 10,
                'country' => 'DE'
            ]);
            
            if (isset($newReleases->albums->items)) {
                foreach ($newReleases->albums->items as $album) {
                    $albumTracks = $api->getAlbumTracks($album->id, [
                        'limit' => 1
                    ]);
                    
                    if (isset($albumTracks->items[0])) {
                        $tracks[] = $api->getTrack($albumTracks->items[0]->id);
                    }
                }
            }
        }
    }
} catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
    $errorMessage = 'Spotify API Fehler: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')';
} catch (Exception $e) {
    $errorMessage = 'Fehler beim Abrufen der Daten: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Spotify Web Player</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 20px; 
            background-color: #f5f5f5;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .search-form {
            margin-bottom: 20px;
        }
        .search-input {
            padding: 10px;
            width: 70%;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .search-button {
            padding: 10px 15px;
            background-color: #1DB954;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .track-card { 
            display: flex; 
            align-items: center; 
            margin-bottom: 20px; 
            padding: 15px; 
            border-radius: 8px; 
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            transition: transform 0.2s;
        }
        .track-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .album-img { 
            width: 80px; 
            height: 80px; 
            margin-right: 20px; 
            border-radius: 4px;
            object-fit: cover; 
        }
        .track-info {
            flex-grow: 1;
        }
        .track-title {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .track-artist {
            margin: 0 0 10px 0;
            color: #666;
        }
        .audio-controls { 
            margin-top: 10px; 
        }
        audio {
            width: 100%;
            height: 35px;
        }
        .error-message { 
            background: #ffebee; 
            color: #c62828; 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
        }
        .nav-links { 
            margin-top: 30px; 
            text-align: center;
        }
        .nav-links a {
            display: inline-block;
            padding: 10px 15px;
            background-color: #1DB954;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
        .no-preview {
            color: #999;
            font-style: italic;
        }
        .spotify-link {
            display: inline-block;
            margin-top: 5px;
            color: #1DB954;
            text-decoration: none;
        }
        .spotify-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Spotify Web Player</h1>
    </div>
    
    <form class="search-form" method="GET" action="webplayer.php">
        <input type="text" name="q" placeholder="Suche nach Songs, Künstlern..." value="<?php echo htmlspecialchars($searchQuery); ?>" class="search-input">
        <button type="submit" class="search-button">Suchen</button>
    </form>
    
    <?php if ($errorMessage): ?>
        <div class="error-message">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>
    
    <?php if (count($tracks) > 0): ?>
        <h2><?php echo empty($searchQuery) ? 'Beliebte Tracks' : 'Suchergebnisse für "' . htmlspecialchars($searchQuery) . '"'; ?></h2>
        
        <?php foreach ($tracks as $track): ?>
            <div class="track-card">
                <img 
                    src="<?php echo isset($track->album->images[0]) ? htmlspecialchars($track->album->images[0]->url) : 'https://via.placeholder.com/80'; ?>" 
                    class="album-img" 
                    alt="<?php echo htmlspecialchars($track->album->name); ?>"
                >
                <div class="track-info">
                    <h3 class="track-title"><?php echo htmlspecialchars($track->name); ?></h3>
                    <p class="track-artist"><?php 
                        $artists = array_map(function($artist) {
                            return htmlspecialchars($artist->name);
                        }, $track->artists);
                        echo implode(', ', $artists);
                    ?></p>
                    
                    <?php if ($track->preview_url): ?>
                        <audio controls>
                            <source src="<?php echo $track->preview_url; ?>" type="audio/mpeg">
                            Dein Browser unterstützt das Audio-Element nicht.
                        </audio>
                    <?php else: ?>
                        <p class="no-preview">Keine Vorschau verfügbar</p>
                    <?php endif; ?>
                    
                    <a href="<?php echo $track->external_urls->spotify; ?>" target="_blank" class="spotify-link">Auf Spotify öffnen</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <?php if (!empty($searchQuery)): ?>
            <p>Keine Tracks für "<?php echo htmlspecialchars($searchQuery); ?>" gefunden.</p>
        <?php else: ?>
            <p>Keine Tracks verfügbar. Versuche, nach einem Künstler oder Song zu suchen.</p>
        <?php endif; ?>
    <?php endif; ?>
    
    <div class="nav-links">
        <a href="dashboard.php">Zurück zur Übersicht</a>
    </div>
</body>
</html>
