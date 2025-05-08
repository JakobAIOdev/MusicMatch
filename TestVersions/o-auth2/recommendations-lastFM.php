<?php
require 'vendor/autoload.php';
include "config.php";
session_start();



// Prüfen, ob der Benutzer angemeldet ist
if (!isset($_SESSION['spotify_access_token'])) {
    header('Location: index.php');
    exit;
}

// Spotify API-Client initialisieren (für Benutzerinfos)
$api = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken($_SESSION['spotify_access_token']);

// Benutzerinfos von Spotify abrufen
try {
    $me = $api->me();
    $userName = $me->display_name;
} catch (Exception $e) {
    $userName = "Musikfan";
}

// Last.fm API Client initialisieren
$lastfm = new \GuzzleHttp\Client([
    'base_uri' => 'http://ws.audioscrobbler.com/2.0/',
    'timeout' => 5.0,
]);

// Funktion zum Abrufen von Last.fm API-Daten
function getLastFmData($client, $method, $params, $apiKey) {
    $params['method'] = $method;
    $params['api_key'] = $apiKey;
    $params['format'] = 'json';
    
    try {
        $response = $client->request('GET', '', [
            'query' => $params
        ]);
        
        return json_decode($response->getBody()->getContents(), true);
    } catch (Exception $e) {
        return null;
    }
}

// Variablen für die Ansicht initialisieren
$recommendations = [];
$seedArtists = [];
$errorMessage = null;

try {
    // Top-Künstler von Spotify abrufen
    $topArtists = $api->getMyTop('artists', [
        'limit' => 3,
        'time_range' => 'medium_term'
    ]);
    
    // Wenn keine Top-Künstler gefunden wurden, verwenden wir einige populäre Künstler
    if (empty($topArtists->items)) {
        $seedArtists = ['The Weeknd', 'Dua Lipa', 'Coldplay'];
    } else {
        foreach ($topArtists->items as $artist) {
            $seedArtists[] = $artist->name;
        }
    }
    
    // Für jeden Seed-Künstler ähnliche Künstler von Last.fm abrufen
    foreach ($seedArtists as $artistName) {
        $similarArtists = getLastFmData($lastfm, 'artist.getSimilar', [
            'artist' => $artistName,
            'limit' => 5
        ], $lastfmApiKey);
        
        if ($similarArtists && isset($similarArtists['similarartists']['artist'])) {
            foreach ($similarArtists['similarartists']['artist'] as $similarArtist) {
                // Top-Tracks für jeden ähnlichen Künstler abrufen
                $topTracks = getLastFmData($lastfm, 'artist.getTopTracks', [
                    'artist' => $similarArtist['name'],
                    'limit' => 2
                ], $lastfmApiKey);
                
                if ($topTracks && isset($topTracks['toptracks']['track'])) {
                    foreach ($topTracks['toptracks']['track'] as $track) {
                        // Track-Informationen speichern
                        $recommendations[] = [
                            'name' => $track['name'],
                            'artist' => $similarArtist['name'],
                            'url' => $track['url'],
                            'image' => isset($track['image'][2]['#text']) ? $track['image'][2]['#text'] : null,
                            'listeners' => $track['listeners']
                        ];
                    }
                }
            }
        }
    }
    
    // Duplikate entfernen und auf 20 Empfehlungen begrenzen
    $recommendations = array_slice(array_map("unserialize", array_unique(array_map("serialize", $recommendations))), 0, 20);
    
} catch (Exception $e) {
    $errorMessage = 'Fehler beim Abrufen der Empfehlungen: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Musikempfehlungen für dich</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .track-card { display: flex; align-items: center; margin-bottom: 20px; padding: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .album-img { width: 100px; height: 100px; margin-right: 20px; object-fit: cover; }
        .nav-links { margin: 20px 0; }
        .seed-tag { background: #eee; padding: 5px 10px; margin-right: 5px; border-radius: 15px; display: inline-block; margin-bottom: 5px; }
        .error-message { background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .info-message { background: #e3f2fd; color: #1565c0; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Musikempfehlungen für dich</h1>
    
    <?php if ($errorMessage): ?>
        <div class="error-message">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>
    
    <div class="info-message">
        <p>Basierend auf deinen Lieblingskünstlern haben wir ähnliche Musik für dich gefunden.</p>
    </div>
    
    <?php if (!empty($seedArtists)): ?>
        <h2>Basierend auf diesen Künstlern</h2>
        <div>
            <?php foreach ($seedArtists as $artist): ?>
                <span class="seed-tag">
                    <?php echo htmlspecialchars($artist); ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($recommendations)): ?>
        <h2>Empfohlene Tracks</h2>
        <?php foreach ($recommendations as $index => $track): ?>
            <div class="track-card">
                <img 
                    src="<?php echo $track['image'] ? htmlspecialchars($track['image']) : 'https://via.placeholder.com/100'; ?>" 
                    class="album-img" 
                    alt="<?php echo htmlspecialchars($track['name']); ?>"
                >
                <div>
                    <h3><?php echo ($index + 1) . '. ' . htmlspecialchars($track['name']); ?></h3>
                    <p>Künstler: <?php echo htmlspecialchars($track['artist']); ?></p>
                    <p>Hörer: <?php echo number_format($track['listeners']); ?></p>
                    <a href="<?php echo htmlspecialchars($track['url']); ?>" target="_blank">Auf Last.fm öffnen</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Leider konnten wir keine Empfehlungen für dich finden. Bitte versuche es später noch einmal.</p>
    <?php endif; ?>
    
    <div class="nav-links">
        <a href="dashboard.php">Zurück zur Übersicht</a>
    </div>
</body>
</html>
