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
$topTracks = null;
$topArtists = null;
$errorMessage = null;

try {
    // Top-Tracks des Benutzers abrufen
    $topTracks = $api->getMyTop('tracks', [
        'limit' => 10,
        'time_range' => 'medium_term'
    ]);
    
    // Top-Künstler des Benutzers abrufen
    $topArtists = $api->getMyTop('artists', [
        'limit' => 5,
        'time_range' => 'medium_term'
    ]);
    
} catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
    $errorMessage = 'Spotify API Fehler: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')';
} catch (Exception $e) {
    $errorMessage = 'Fehler beim Abrufen der Daten: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Deine Spotify Favoriten</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .track-card, .artist-card { display: flex; align-items: center; margin-bottom: 20px; padding: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .album-img, .artist-img { width: 100px; height: 100px; margin-right: 20px; object-fit: cover; }
        .nav-links { margin: 20px 0; }
        .error-message { background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Deine Spotify Favoriten</h1>
    
    <?php if ($errorMessage): ?>
        <div class="error-message">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($topTracks && count($topTracks->items) > 0): ?>
        <h2>Deine Top-Tracks</h2>
        <?php foreach ($topTracks->items as $index => $track): ?>
            <div class="track-card">
                <img 
                    src="<?php echo isset($track->album->images[0]) ? htmlspecialchars($track->album->images[0]->url) : 'https://via.placeholder.com/100'; ?>" 
                    class="album-img" 
                    alt="<?php echo htmlspecialchars($track->album->name); ?>"
                >
                <div>
                    <h3><?php echo ($index + 1) . '. ' . htmlspecialchars($track->name); ?></h3>
                    <p>Künstler: <?php echo htmlspecialchars($track->artists[0]->name); ?></p>
                    <p>Album: <?php echo htmlspecialchars($track->album->name); ?></p>
                    <a href="<?php echo $track->external_urls->spotify; ?>" target="_blank">Auf Spotify öffnen</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if ($topArtists && count($topArtists->items) > 0): ?>
        <h2>Deine Top-Künstler</h2>
        <?php foreach ($topArtists->items as $index => $artist): ?>
            <div class="artist-card">
                <img 
                    src="<?php echo isset($artist->images[0]) ? htmlspecialchars($artist->images[0]->url) : 'https://via.placeholder.com/100'; ?>" 
                    class="artist-img" 
                    alt="<?php echo htmlspecialchars($artist->name); ?>"
                >
                <div>
                    <h3><?php echo ($index + 1) . '. ' . htmlspecialchars($artist->name); ?></h3>
                    <p>Genres: <?php echo implode(', ', $artist->genres); ?></p>
                    <p>Popularität: <?php echo $artist->popularity; ?>/100</p>
                    <a href="<?php echo $artist->external_urls->spotify; ?>" target="_blank">Auf Spotify öffnen</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!$topTracks && !$topArtists): ?>
        <p>Leider konnten wir keine Daten für dich finden. Bitte versuche es später noch einmal.</p>
    <?php endif; ?>
    
    <div class="nav-links">
        <a href="dashboard.php">Zurück zur Übersicht</a>
    </div>
</body>
</html>
