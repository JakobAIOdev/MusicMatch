<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/api.php';

// Überprüfen, ob der Benutzer angemeldet ist
if (!isAuthenticated()) {
    header('Location: index.php');
    exit;
}

// Playlist-ID aus der Session abrufen oder neue Playlist erstellen
if (!isset($_SESSION['current_playlist_id'])) {
    $playlist = createPlaylist('Meine Musik-Match Playlist', 'Erstellt mit Musik-Match App', false);
    if ($playlist && isset($playlist['id'])) {
        $_SESSION['current_playlist_id'] = $playlist['id'];
        $_SESSION['current_playlist_name'] = $playlist['name'];
    }
}

// Top-Tracks des Benutzers abrufen für bessere Empfehlungen
$topTracks = getUserTopTracks(5);
$seedTracks = [];
if ($topTracks && isset($topTracks['items'])) {
    foreach ($topTracks['items'] as $track) {
        $seedTracks[] = $track['id'];
    }
}

// Empfehlungen abrufen
$recommendations = getRecommendations(20, $seedTracks);

// Bereits gesehene Tracks aus der Session abrufen
if (!isset($_SESSION['seen_tracks'])) {
    $_SESSION['seen_tracks'] = [];
}

// Liked Tracks aus der Session abrufen
if (!isset($_SESSION['liked_tracks'])) {
    $_SESSION['liked_tracks'] = [];
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

// Wenn es keine AJAX-Anfrage ist, die Seite normal anzeigen
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musik-Match - Entdecken</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
        }
        nav a {
            margin-left: 15px;
            text-decoration: none;
            color: #666;
            font-weight: 500;
        }
        nav a.active, nav a:hover {
            color: #1DB954;
        }
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
                        <img src="<?php echo $track['album']['images'][0]['url']; ?>" alt="<?php echo $track['album']['name']; ?>">
                        <?php endif; ?>
                        <div class="card-content">
                            <h3 class="card-title"><?php echo $track['name']; ?></h3>
                            <p class="card-artist">
                                <?php 
                                $artists = array_map(function($artist) { 
                                    return $artist['name']; 
                                }, $track['artists']);
                                echo implode(', ', $artists);
                                ?>
                            </p>
                            <p class="card-album">Album: <?php echo $track['album']['name']; ?></p>
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
                        <a href="discover.php" class="btn">Neu laden</a>
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
                        <a href="discover.php" class="btn">Neu laden</a>
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
