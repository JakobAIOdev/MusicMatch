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

// Top-Künstler abrufen
try {
    $topArtists = $api->getMyTop('artists', [
        'limit' => 10,
        'time_range' => $time_range
    ]);
} catch (Exception $e) {
    die('Fehler beim Abrufen der Top-Künstler: ' . $e->getMessage());
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
    <title>Deine Top-Künstler auf Spotify</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .artist-card { display: flex; align-items: center; margin-bottom: 20px; padding: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .artist-img { width: 100px; height: 100px; border-radius: 50%; margin-right: 20px; object-fit: cover; }
        .time-nav { margin: 20px 0; }
        .time-nav a { margin-right: 10px; padding: 5px 10px; text-decoration: none; color: #333; }
        .time-nav a.active { background: #1DB954; color: white; border-radius: 20px; }
        .nav-links { margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Deine Top-Künstler auf Spotify</h1>
    
    <div class="time-nav">
        <a href="?time_range=short_term" class="<?php echo $time_range == 'short_term' ? 'active' : ''; ?>">Kurzfristig</a>
        <a href="?time_range=medium_term" class="<?php echo $time_range == 'medium_term' ? 'active' : ''; ?>">Mittelfristig</a>
        <a href="?time_range=long_term" class="<?php echo $time_range == 'long_term' ? 'active' : ''; ?>">Langfristig</a>
    </div>
    
    <p>Zeitraum: <strong><?php echo $time_descriptions[$time_range]; ?></strong></p>
    
    <?php if (count($topArtists->items) > 0): ?>
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
    <?php else: ?>
        <p>Keine Top-Künstler gefunden. Vielleicht hast du noch nicht genug Musik in diesem Zeitraum gehört.</p>
    <?php endif; ?>
    
    <div class="nav-links">
        <a href="profile.php">Zurück zum Profil</a> | 
        <a href="top_tracks.php">Deine Top-Tracks anzeigen</a>
    </div>
</body>
</html>
