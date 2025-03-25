<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/api.php';

// Debug-Mode
$debug = isset($_GET['debug']);
$debugMessages = [];

// Überprüfen, ob der Benutzer angemeldet ist
if (!isAuthenticated()) {
    header('Location: index.php');
    exit;
}

// Playlist-ID aus der Session abrufen oder neue Playlist erstellen
if (!isset($_SESSION['current_playlist_id'])) {
    $debugMessages[] = "Erstelle neue Playlist...";
    $playlist = createPlaylist('Meine Musik-Match Playlist', 'Erstellt mit Musik-Match App', false);
    if ($playlist && isset($playlist['id'])) {
        $_SESSION['current_playlist_id'] = $playlist['id'];
        $_SESSION['current_playlist_name'] = $playlist['name'];
        $debugMessages[] = "Playlist erstellt: " . $_SESSION['current_playlist_name'];
    } else {
        $debugMessages[] = "Fehler beim Erstellen der Playlist: " . json_encode($playlist);
    }
}

// Bereits gesehene Tracks aus der Session abrufen oder initialisieren
if (!isset($_SESSION['seen_tracks'])) {
    $_SESSION['seen_tracks'] = [];
}

// Liked Tracks aus der Session abrufen oder initialisieren
if (!isset($_SESSION['liked_tracks'])) {
    $_SESSION['liked_tracks'] = [];
}

// Seed-Daten für Empfehlungen sammeln
$seedTracks = [];
$seedArtists = [];
$seedGenres = ['pop', 'rock', 'indie']; // Standard-Fallback-Genres

// 1. Versuche, Top-Tracks des Nutzers zu laden
$debugMessages[] = "Hole Top-Tracks...";
$topTracks = getUserTopTracks(10, 'medium_term');

// Prüfe, ob Top-Tracks erfolgreich geladen wurden
if ($topTracks && isset($topTracks['items']) && count($topTracks['items']) > 0) {
    $debugMessages[] = "Erfolgreich " . count($topTracks['items']) . " Top-Tracks geladen";
    foreach ($topTracks['items'] as $index => $track) {
        if ($index < 5) { // Maximal 5 Tracks als Seeds verwenden
            $seedTracks[] = $track['id'];
        }
        
        // Auch Top-Künstler aus den Tracks sammeln
        if (!empty($track['artists'][0]['id']) && count($seedArtists) < 5) {
            $artistId = $track['artists'][0]['id'];
            if (!in_array($artistId, $seedArtists)) {
                $seedArtists[] = $artistId;
            }
        }
    }
} else {
    // Falls keine Top-Tracks vorhanden sind, versuche es mit short_term
    $debugMessages[] = "Keine Top-Tracks gefunden. Versuche mit kurzfristigen Top-Tracks...";
    $topTracks = getUserTopTracks(10, 'short_term');
    
    if ($topTracks && isset($topTracks['items']) && count($topTracks['items']) > 0) {
        $debugMessages[] = "Erfolgreich " . count($topTracks['items']) . " kurzfristige Top-Tracks geladen";
        foreach ($topTracks['items'] as $index => $track) {
            if ($index < 5) {
                $seedTracks[] = $track['id'];
            }
            
            if (!empty($track['artists'][0]['id']) && count($seedArtists) < 5) {
                $artistId = $track['artists'][0]['id'];
                if (!in_array($artistId, $seedArtists)) {
                    $seedArtists[] = $artistId;
                }
            }
        }
    } else {
        $debugMessages[] = "Keine Top-Tracks gefunden. Verwende Standard-Seeds.";
    }
}

// Empfehlungen mit verschiedenen Seed-Kombinationen abrufen
$recommendations = null;

// Strategie 1: Wenn Tracks und Artists verfügbar sind
if (!empty($seedTracks)) {
    $debugMessages[] = "Hole Empfehlungen mit " . count($seedTracks) . " Tracks als Seeds...";
    $recommendations = getRecommendations(20, $seedTracks, [], []);
    
    // Prüfe, ob Empfehlungen erfolgreich abgerufen wurden
    if ($recommendations && isset($recommendations['tracks']) && count($recommendations['tracks']) > 0) {
        $debugMessages[] = "Erfolgreich " . count($recommendations['tracks']) . " Empfehlungen erhalten";
    }
}

// Strategie 2: Wenn nur Artists verfügbar sind
if ((!$recommendations || !isset($recommendations['tracks']) || count($recommendations['tracks']) < 3) && !empty($seedArtists)) {
    $debugMessages[] = "Keine Tracks-Empfehlungen. Versuche mit " . count($seedArtists) . " Künstlern...";
    $recommendations = getRecommendations(20, [], $seedArtists, []);
    
    if ($recommendations && isset($recommendations['tracks']) && count($recommendations['tracks']) > 0) {
        $debugMessages[] = "Erfolgreich " . count($recommendations['tracks']) . " Empfehlungen über Künstler erhalten";
    }
}

// Strategie 3: Fallback auf Genres
if (!$recommendations || !isset($recommendations['tracks']) || count($recommendations['tracks']) < 3) {
    $debugMessages[] = "Keine Empfehlungen gefunden. Verwende Standard-Genres...";
    $recommendations = getRecommendations(20, [], [], $seedGenres);
    
    if ($recommendations && isset($recommendations['tracks']) && count($recommendations['tracks']) > 0) {
        $debugMessages[] = "Erfolgreich " . count($recommendations['tracks']) . " Empfehlungen über Genres erhalten";
    } else {
        $debugMessages[] = "Fehler beim Abrufen von Empfehlungen: " . json_encode($recommendations);
    }
}

// AJAX-Anfrage zum Hinzufügen eines Tracks zur Playlist
if (isset($_POST['action']) && $_POST['action'] === 'like' && isset($_POST['track_uri'])) {
    $trackUri = $_POST['track_uri'];
    
    // Track zur Playlist hinzufügen
    if (isset($_SESSION['current_playlist_id'])) {
        $result = addTracksToPlaylist($_SESSION['current_playlist_id'], [$trackUri]);
        
        // Track zu den gemochten Tracks hinzufügen
        if (!in_array($trackUri, $_SESSION['liked_tracks'])) {
            $_SESSION['liked_tracks'][] = $trackUri;
        }
        
        // Track zu den gesehenen Tracks hinzufügen
        if (!in_array($trackUri, $_SESSION['seen_tracks'])) {
            $_SESSION['seen_tracks'][] = $trackUri;
        }
        
        // JSON-Antwort senden
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Track zur Playlist hinzugefügt']);
        exit;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Keine aktive Playlist']);
    exit;
}

// AJAX-Anfrage zum Überspringen eines Tracks
if (isset($_POST['action']) && $_POST['action'] === 'dislike' && isset($_POST['track_uri'])) {
    $trackUri = $_POST['track_uri'];
    
    // Track zu den gesehenen Tracks hinzufügen
    if (!in_array($trackUri, $_SESSION['seen_tracks'])) {
        $_SESSION['seen_tracks'][] = $trackUri;
    }
    
    // JSON-Antwort senden
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Track übersprungen']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musik-Match - Entdecken</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .swipe-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            height: 600px;
            margin: 0 auto;
            overflow: hidden;
        }
        
        .card {
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        
        .card-content {
            padding: 20px;
        }
        
        .card-title {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .card-artist {
            color: #666;
            margin-bottom: 15px;
        }
        
        .card-album {
            font-size: 14px;
            color: #888;
            margin-bottom: 15px;
        }
        
        .card-preview {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .buttons {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }
        
        .btn {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .btn-dislike {
            background-color: #ff5252;
            color: white;
        }
        
        .btn-like {
            background-color: #1DB954;
            color: white;
        }
        
        .swipe-left {
            transform: translateX(-150%) rotate(-30deg);
            transition: transform 0.5s;
        }
        
        .swipe-right {
            transform: translateX(150%) rotate(30deg);
            transition: transform 0.5s;
        }
        
        .no-tracks {
            text-align: center;
            padding: 50px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .debug-panel {
            margin-top: 20px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            color: #666;
        }
        
        .action-button {
            display: inline-block;
            background-color: #1DB954;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Musik-Match</h1>
            <nav>
                <a href="profile.php">Profil</a>
                <a href="discover.php" class="active">Entdecken</a>
                <a href="index.php?logout=1">Abmelden</a>
                <?php if ($debug): ?>
                <a href="discover.php">Debug aus</a>
                <?php else: ?>
                <a href="discover.php?debug=1" style="font-size: 0.8em; color: #999;">Debug</a>
                <?php endif; ?>
            </nav>
        </header>
        
        <main>
            <section>
                <h2>Entdecke neue Musik</h2>
                <p>Wische nach rechts, um einen Song zu deiner Playlist hinzuzufügen, oder nach links, um ihn zu überspringen.</p>
                
                <?php if (isset($_SESSION['current_playlist_id'])): ?>
                <p>Aktuelle Playlist: <?php echo $_SESSION['current_playlist_name']; ?></p>
                <?php endif; ?>
                
                <?php if ($recommendations && isset($recommendations['tracks']) && count($recommendations['tracks']) > 0): ?>
                <div class="swipe-container" id="swipeContainer">
                    <?php 
                    $displayedTracks = 0;
                    foreach ($recommendations['tracks'] as $index => $track): 
                        // Überspringe bereits gesehene Tracks
                        if (in_array($track['uri'], $_SESSION['seen_tracks'])) {
                            continue;
                        }
                        $displayedTracks++;
                    ?>
                    <div class="card" data-uri="<?php echo $track['uri']; ?>" style="z-index: <?php echo 100 - $index; ?>">
                        <?php if (isset($track['album']['images']) && count($track['album']['images']) > 0): ?>
                        <img src="<?php echo $track['album']['images'][0]['url']; ?>" alt="<?php echo htmlspecialchars($track['album']['name']); ?>">
                        <?php else: ?>
                        <div style="height: 300px; background-color: #eee; display: flex; align-items: center; justify-content: center;">
                            <span>Kein Albumcover verfügbar</span>
                        </div>
                        <?php endif; ?>
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($track['name']); ?></h3>
                            <p class="card-artist">
                                <?php 
                                $artists = array_map(function($artist) { 
                                    return htmlspecialchars($artist['name']); 
                                }, $track['artists']);
                                echo implode(', ', $artists);
                                ?>
                            </p>
                            <p class="card-album">Album: <?php echo htmlspecialchars($track['album']['name']); ?></p>
                            <?php if (isset($track['preview_url']) && $track['preview_url']): ?>
                            <audio class="card-preview" controls>
                                <source src="<?php echo $track['preview_url']; ?>" type="audio/mpeg">
                                Dein Browser unterstützt das Audio-Element nicht.
                            </audio>
                            <?php else: ?>
                            <p>Keine Vorschau verfügbar</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php 
                        if ($displayedTracks >= 5) break; // Begrenze auf 5 Karten gleichzeitig
                    endforeach; 
                    ?>
                    
                    <?php if ($displayedTracks === 0): ?>
                    <div class="no-tracks">
                        <h3>Keine weiteren Tracks verfügbar</h3>
                        <p>Du hast alle verfügbaren Tracks gesehen. Lade die Seite neu, um neue Empfehlungen zu erhalten.</p>
                        <a href="discover.php" class="action-button">Neu laden</a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="buttons">
                    <button class="btn btn-dislike" id="dislikeBtn">✕</button>
                    <button class="btn btn-like" id="likeBtn">♥</button>
                </div>
                <?php else: ?>
                <div class="no-tracks">
                    <h3>Keine Empfehlungen verfügbar</h3>
                    <p>Es konnten keine Empfehlungen geladen werden. Bitte versuche es später erneut.</p>
                    <a href="discover.php?refresh=1" class="action-button">Nochmal versuchen</a>
                </div>
                <?php endif; ?>
                
                <?php if ($debug): ?>
                <div class="debug-panel">
                    <h3>Debug-Informationen:</h3>
                    <ul>
                        <?php foreach ($debugMessages as $message): ?>
                        <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <h4>Seed-Tracks (<?php echo count($seedTracks); ?>):</h4>
                    <pre><?php echo htmlspecialchars(json_encode($seedTracks)); ?></pre>
                    
                    <h4>Seed-Artists (<?php echo count($seedArtists); ?>):</h4>
                    <pre><?php echo htmlspecialchars(json_encode($seedArtists)); ?></pre>
                    
                    <h4>Seed-Genres:</h4>
                    <pre><?php echo htmlspecialchars(json_encode($seedGenres)); ?></pre>
                    
                    <h4>Session:</h4>
                    <pre><?php 
                        $sessionData = $_SESSION;
                        // Kürze lange Arrays für bessere Übersichtlichkeit
                        if (isset($sessionData['seen_tracks']) && count($sessionData['seen_tracks']) > 5) {
                            $sessionData['seen_tracks'] = array_slice($sessionData['seen_tracks'], 0, 5);
                            $sessionData['seen_tracks'][] = '... und ' . (count($_SESSION['seen_tracks']) - 5) . ' weitere';
                        }
                        echo htmlspecialchars(json_encode($sessionData, JSON_PRETTY_PRINT));
                    ?></pre>
                </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            const likeBtn = document.getElementById('likeBtn');
            const dislikeBtn = document.getElementById('dislikeBtn');
            
            if (cards.length === 0) return;
            
            let currentCardIndex = 0;
            
            // Like-Button-Handler
            likeBtn.addEventListener('click', function() {
                if (currentCardIndex >= cards.length) return;
                
                const currentCard = cards[currentCardIndex];
                const trackUri = currentCard.dataset.uri;
                
                // Swipe-Animation
                currentCard.classList.add('swipe-right');
                
                // AJAX-Anfrage zum Hinzufügen des Tracks zur Playlist
                fetch('discover.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=like&track_uri=' + encodeURIComponent(trackUri)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Erfolg:', data);
                })
                .catch(error => {
                    console.error('Fehler:', error);
                });
                
                // Zur nächsten Karte wechseln
                currentCardIndex++;
                setTimeout(showNextCard, 300);
            });
            
            // Dislike-Button-Handler
            dislikeBtn.addEventListener('click', function() {
                if (currentCardIndex >= cards.length) return;
                
                const currentCard = cards[currentCardIndex];
                const trackUri = currentCard.dataset.uri;
                
                // Swipe-Animation
                currentCard.classList.add('swipe-left');
                
                // AJAX-Anfrage zum Markieren des Tracks als übersprungen
                fetch('discover.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=dislike&track_uri=' + encodeURIComponent(trackUri)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Erfolg:', data);
                })
                .catch(error => {
                    console.error('Fehler:', error);
                });
                
                // Zur nächsten Karte wechseln
                currentCardIndex++;
                setTimeout(showNextCard, 300);
            });
            
            // Funktion zum Anzeigen der nächsten Karte
            function showNextCard() {
                if (currentCardIndex >= cards.length) {
                    // Keine weiteren Karten verfügbar
                    const container = document.getElementById('swipeContainer');
                    const noTracks = document.createElement('div');
                    noTracks.className = 'no-tracks';
                    noTracks.innerHTML = `
                        <h3>Keine weiteren Tracks verfügbar</h3>
                        <p>Du hast alle verfügbaren Tracks gesehen. Lade die Seite neu, um neue Empfehlungen zu erhalten.</p>
                        <a href="discover.php" class="action-button">Neu laden</a>
                    `;
                    container.appendChild(noTracks);
                    
                    // Buttons deaktivieren
                    likeBtn.disabled = true;
                    dislikeBtn.disabled = true;
                }
            }
        });
    </script>
</body>
</html>
