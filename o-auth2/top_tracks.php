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

// Zeitraum aus der URL holen (falls vorhanden)
$time_range = isset($_GET['time_range']) ? $_GET['time_range'] : 'medium_term';
$valid_ranges = ['short_term', 'medium_term', 'long_term'];
if (!in_array($time_range, $valid_ranges)) {
    $time_range = 'medium_term';
}

// Top-Tracks abrufen
try {
    $topTracks = $api->getMyTop('tracks', [
        'limit' => 10,
        'time_range' => $time_range
    ]);
} catch (Exception $e) {
    die('Fehler beim Abrufen der Top-Tracks: ' . $e->getMessage());
}

// Zeitraum-Beschreibungen
$time_descriptions = [
    'short_term' => 'Letzte 4 Wochen',
    'medium_term' => 'Letzte 6 Monate',
    'long_term' => 'Mehrere Jahre'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Deine Top-Tracks auf Spotify</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .track-card { display: flex; align-items: center; margin-bottom: 20px; padding: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .album-img { width: 100px; height: 100px; margin-right: 20px; }
        .time-nav { margin: 20px 0; }
        .time-nav a { margin-right: 10px; padding: 5px 10px; text-decoration: none; color: #333; }
        .time-nav a.active { background: #1DB954; color: white; border-radius: 20px; }
        .nav-links { margin-top: 20px; }
        .preview-btn { background: #1DB954; color: white; border: none; padding: 5px 10px; border-radius: 20px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Deine Top-Tracks auf Spotify</h1>
    
    <div class="time-nav">
        <a href="?time_range=short_term" class="<?php echo $time_range == 'short_term' ? 'active' : ''; ?>">Kurzfristig</a>
        <a href="?time_range=medium_term" class="<?php echo $time_range == 'medium_term' ? 'active' : ''; ?>">Mittelfristig</a>
        <a href="?time_range=long_term" class="<?php echo $time_range == 'long_term' ? 'active' : ''; ?>">Langfristig</a>
    </div>
    
    <p>Zeitraum: <strong><?php echo $time_descriptions[$time_range]; ?></strong></p>
    
    <?php if (count($topTracks->items) > 0): ?>
        <?php foreach ($topTracks->items as $index => $track): ?>
            <div class="track-card">
                <img 
                    src="<?php echo isset($track->album->images[0]) ? htmlspecialchars($track->album->images[0]->url) : 'https://via.placeholder.com/100'; ?>" 
                    class="album-img" 
                    alt="<?php echo htmlspecialchars($track->album->name); ?>"
                >
                <div>
                    <h3><?php echo ($index + 1) . '. ' . htmlspecialchars($track->name); ?></h3>
                    <p>Künstler: <?php 
                        $artists = array_map(function($artist) {
                            return htmlspecialchars($artist->name);
                        }, $track->artists);
                        echo implode(', ', $artists);
                    ?></p>
                    <p>Album: <?php echo htmlspecialchars($track->album->name); ?></p>
                    <?php if ($track->preview_url): ?>
                        <audio controls>
                            <source src="<?php echo $track->preview_url; ?>" type="audio/mpeg">
                            Dein Browser unterstützt das Audio-Element nicht.
                        </audio>
                    <?php else: ?>
                        <p><em>Keine Vorschau verfügbar</em></p>
                    <?php endif; ?>
                    <a href="<?php echo $track->external_urls->spotify; ?>" target="_blank">Auf Spotify öffnen</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Keine Top-Tracks gefunden. Vielleicht hast du noch nicht genug Musik in diesem Zeitraum gehört.</p>
    <?php endif; ?>
    
    <div class="nav-links">
        <a href="profile.php">Zurück zum Profil</a> | 
        <a href="top_artists.php">Deine Top-Künstler anzeigen</a>
    </div>
</body>
</html>
