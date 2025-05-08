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
$recommendations = null;
$topTracks = null;
$errorMessage = null;
$usedSeedType = 'top';

try {
    // Versuch 1: Einen einzelnen bekannten Track als Seed verwenden
    try {
        // Bekannter populärer Track (Blinding Lights von The Weeknd)
        $singleSeedTrack = '2takcwOaAZWiXQijPHIx7B';
        
        // Empfehlungen mit einem einzelnen Track abrufen
        $recommendations = $api->getRecommendations([
            'seed_tracks' => [$singleSeedTrack],
            'limit' => 20
        ]);
        
        // Track-Info für die Anzeige holen
        $seedTrackInfo = $api->getTrack($singleSeedTrack);
        $topTracks = new stdClass();
        $topTracks->items = [$seedTrackInfo];
        $usedSeedType = 'single_track';
        
    } catch (Exception $e) {
        // Versuch 2: Kürzlich gespielte Tracks verwenden
        try {
            $usedSeedType = 'recent';
            $recentTracks = $api->getMyRecentTracks([
                'limit' => 5
            ]);
            
            $seedTracks = [];
            foreach ($recentTracks->items as $item) {
                $seedTracks[] = $item->track->id;
            }
            
            // Maximal 5 Seeds verwenden (API-Limit)
            $seedTracks = array_slice($seedTracks, 0, 5);
            
            if (!empty($seedTracks)) {
                $recommendations = $api->getRecommendations([
                    'seed_tracks' => $seedTracks,
                    'limit' => 20
                ]);
                
                // Speichere die kürzlich gespielten Tracks für die Anzeige
                $topTracks = new stdClass();
                $topTracks->items = array_map(function($item) {
                    return $item->track;
                }, $recentTracks->items);
            } else {
                throw new Exception("Keine kürzlich gespielten Tracks gefunden");
            }
            
        } catch (Exception $e2) {
            // Versuch 3: Nur Genres verwenden
            $usedSeedType = 'genres';
            $recommendations = $api->getRecommendations([
                'seed_genres' => ['pop'],
                'limit' => 20
            ]);
        }
    }
    
} catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
    // Spezifischer Spotify API Fehler
    $errorMessage = 'Spotify API Fehler: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')';
} catch (Exception $e) {
    // Allgemeiner Fehler
    $errorMessage = 'Fehler beim Abrufen der Empfehlungen: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Spotify Empfehlungen für dich</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .track-card { display: flex; align-items: center; margin-bottom: 20px; padding: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .album-img { width: 100px; height: 100px; margin-right: 20px; object-fit: cover; }
        .nav-links { margin: 20px 0; }
        .audio-controls { margin-top: 10px; }
        .add-btn { background: #1DB954; color: white; border: none; padding: 5px 10px; border-radius: 20px; cursor: pointer; margin-top: 10px; }
        .seed-tag { background: #eee; padding: 5px 10px; margin-right: 5px; border-radius: 15px; display: inline-block; margin-bottom: 5px; }
        .error-message { background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .info-message { background: #e3f2fd; color: #1565c0; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Spotify Empfehlungen für dich</h1>
    
    <?php if ($errorMessage): ?>
        <div class="error-message">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($usedSeedType === 'single_track'): ?>
        <div class="info-message">
            <p>Wir haben einen populären Track als Basis für deine Empfehlungen verwendet.</p>
        </div>
    <?php elseif ($usedSeedType === 'recent'): ?>
        <div class="info-message">
            <p>Wir haben deine kürzlich gespielten Tracks als Basis für deine Empfehlungen verwendet.</p>
        </div>
    <?php elseif ($usedSeedType === 'genres'): ?>
        <div class="info-message">
            <p>Wir haben populäre Musikgenres als Basis für deine Empfehlungen verwendet.</p>
        </div>
    <?php else: ?>
        <p>Basierend auf deinen Hörgewohnheiten</p>
    <?php endif; ?>
    
    <?php if (isset($recommendations) && isset($recommendations->tracks) && count($recommendations->tracks) > 0): ?>
        
        <?php if ($topTracks && count($topTracks->items) > 0): ?>
            <h2>Basis-Tracks</h2>
            <div>
                <?php foreach ($topTracks->items as $track): ?>
                    <span class="seed-tag">
                        <?php echo htmlspecialchars($track->name); ?> - <?php echo htmlspecialchars($track->artists[0]->name); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <h2>Empfohlene Tracks</h2>
        <?php foreach ($recommendations->tracks as $index => $track): ?>
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
                    <div class="audio-controls">
                        <?php if ($track->preview_url): ?>
                            <audio controls>
                                <source src="<?php echo $track->preview_url; ?>" type="audio/mpeg">
                                Dein Browser unterstützt das Audio-Element nicht.
                            </audio>
                        <?php else: ?>
                            <p><em>Keine Vorschau verfügbar</em></p>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo $track->external_urls->spotify; ?>" target="_blank">Auf Spotify öffnen</a>
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
