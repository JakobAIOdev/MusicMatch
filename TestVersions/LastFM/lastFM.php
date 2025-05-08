<?php
session_start();
require_once 'config.php'; // Lade die API-Keys

// Last.fm API Konfiguration
$apiKey = $LastFmApiKey;
$apiSecret = $LastFmSharedSecret;
$callbackUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

// Funktion zum Generieren einer Signatur für Last.fm API-Anfragen
function generateApiSignature($params, $secret) {
    ksort($params);
    $signature = '';
    foreach ($params as $key => $value) {
        $signature .= $key . $value;
    }
    $signature .= $secret;
    return md5($signature);
}

// Funktion zum Abrufen der empfohlenen Songs
function getRecommendedSongs($username, $apiKey, $sessionKey = null) {
    // Versuche zuerst die Empfehlungen über die offizielle API zu bekommen
    if ($sessionKey) {
        $url = "http://ws.audioscrobbler.com/2.0/?method=user.getRecommendedTracks&api_key={$apiKey}&sk={$sessionKey}&format=json&limit=20";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            // Bei Fehler, versuche die alternative Methode
            curl_close($ch);
        } else {
            curl_close($ch);
            $data = json_decode($response, true);
            
            if (isset($data['recommendations']) && isset($data['recommendations']['track'])) {
                return [
                    'success' => true,
                    'playlist' => $data['recommendations']['track']
                ];
            }
        }
    }
    
    // Alternative Methode: Scraping der Last.fm-Website
    $url = "https://www.last.fm/player/station/user/{$username}/recommended?page=1&ajax=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MusicMatch/1.0)');
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        return ['error' => 'Curl error: ' . curl_error($ch)];
    }
    
    curl_close($ch);
    
    // Dekodiere die JSON-Antwort
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['playlist'])) {
        return ['error' => 'Keine Empfehlungen gefunden oder Fehler beim Dekodieren der Antwort'];
    }
    
    return [
        'success' => true,
        'playlist' => $data['playlist']
    ];
}

// OAuth-Authentifizierungsprozess
$error = '';
$recommendations = null;

// Schritt 1: Benutzer zu Last.fm weiterleiten
if (isset($_GET['connect'])) {
    $authUrl = "http://www.last.fm/api/auth/?api_key={$apiKey}&cb={$callbackUrl}";
    header("Location: {$authUrl}");
    exit;
}

// Schritt 2: Token von Last.fm empfangen und Session erstellen
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Parameter für auth.getSession
    $params = [
        'method' => 'auth.getSession',
        'api_key' => $apiKey,
        'token' => $token
    ];
    
    // Signatur generieren
    $params['api_sig'] = generateApiSignature($params, $apiSecret);
    
    // URL für API-Anfrage erstellen
    $url = 'http://ws.audioscrobbler.com/2.0/?' . http_build_query($params) . '&format=json';
    
    // API-Anfrage senden
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error = 'Fehler bei der API-Anfrage: ' . curl_error($ch);
    } else {
        $data = json_decode($response, true);
        
        if (isset($data['session']) && isset($data['session']['name']) && isset($data['session']['key'])) {
            $_SESSION['lastfm_username'] = $data['session']['name'];
            $_SESSION['lastfm_session_key'] = $data['session']['key'];
            $_SESSION['logged_in'] = true;
            
            // Empfehlungen abrufen
            $recommendations = getRecommendedSongs($_SESSION['lastfm_username'], $apiKey, $_SESSION['lastfm_session_key']);
        } else {
            $error = 'Fehler bei der Authentifizierung: ' . (isset($data['error']) ? $data['message'] : 'Unbekannter Fehler');
        }
    }
    
    curl_close($ch);
}

// Alternative Login-Methode (falls OAuth fehlschlägt)
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    
    if (empty($username)) {
        $error = "Bitte gib deinen Last.fm Benutzernamen ein.";
    } else {
        $_SESSION['lastfm_username'] = $username;
        $_SESSION['logged_in'] = true;
        
        // Empfehlungen abrufen
        $recommendations = getRecommendedSongs($username, $apiKey);
    }
}

// Logout-Funktion
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: lastFM.php');
    exit;
}

// Wenn der Benutzer bereits angemeldet ist, hole Empfehlungen
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && !isset($recommendations)) {
    $recommendations = getRecommendedSongs(
        $_SESSION['lastfm_username'], 
        $apiKey, 
        isset($_SESSION['lastfm_session_key']) ? $_SESSION['lastfm_session_key'] : null
    );
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Last.fm Musik-Empfehlungen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f8f8;
            color: #333;
        }
        .login-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        .login-form {
            margin-top: 20px;
            text-align: left;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn {
            background: #d51007;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            display: inline-block;
            text-decoration: none;
        }
        .btn:hover {
            background: #b30000;
        }
        .btn-secondary {
            background: #666;
        }
        .btn-secondary:hover {
            background: #555;
        }
        .error {
            color: #d51007;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .song-list {
            list-style: none;
            padding: 0;
        }
        .song-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            background: #fff;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .song-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .song-image {
            width: 80px;
            height: 80px;
            border-radius: 4px;
            object-fit: cover;
            margin-right: 20px;
        }
        .song-info {
            flex-grow: 1;
        }
        .song-title {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 5px;
            color: #333;
        }
        .song-artist {
            color: #666;
            font-size: 16px;
        }
        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .user-name {
            font-weight: bold;
            font-size: 18px;
        }
        .swipe-container {
            position: relative;
            overflow: hidden;
        }
        .swipe-actions {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 20px;
        }
        .swipe-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .swipe-left {
            background: #ff4d4d;
            color: white;
        }
        .swipe-right {
            background: #4caf50;
            color: white;
        }
        h1, h2 {
            color: #d51007;
        }
        .section-header {
            margin-top: 30px;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .loading {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Last.fm Musik-Empfehlungen</h1>
    
    <?php if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
        <!-- Login-Container -->
        <div class="login-container">
            <h2>Verbinde dich mit Last.fm</h2>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <a href="?connect=1" class="btn">Mit Last.fm verbinden</a>
            
            <div class="login-form">
                <p>Oder gib deinen Last.fm Benutzernamen ein:</p>
                <form action="lastFM.php" method="post">
                    <div class="form-group">
                        <label for="username">Last.fm Benutzername:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <button type="submit" name="login" class="btn">Empfehlungen anzeigen</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Benutzer ist angemeldet -->
        <div class="user-info">
            <div>
                <span>Angemeldet als: </span>
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['lastfm_username']); ?></span>
            </div>
            <a href="lastFM.php?logout=1" class="btn btn-secondary">Abmelden</a>
        </div>
        
        <h2 class="section-header">Deine Empfehlungen</h2>
        
        <?php if (isset($recommendations['error'])): ?>
            <div class="error">
                <?php echo htmlspecialchars($recommendations['error']); ?>
            </div>
        <?php elseif (isset($recommendations['success']) && isset($recommendations['playlist']) && is_array($recommendations['playlist'])): ?>
            <div id="swipe-container" class="swipe-container">
                <ul class="song-list" id="song-list">
                    <?php foreach ($recommendations['playlist'] as $index => $song): ?>
                        <li class="song-item" data-index="<?php echo $index; ?>">
                            <?php 
                            $imageUrl = '';
                            if (isset($song['image']) && !empty($song['image'])) {
                                $imageUrl = $song['image'];
                            } elseif (isset($song['image']) && is_array($song['image'])) {
                                // API-Format für Bilder
                                foreach ($song['image'] as $img) {
                                    if (isset($img['size']) && $img['size'] === 'large' && isset($img['#text'])) {
                                        $imageUrl = $img['#text'];
                                        break;
                                    }
                                }
                            }
                            ?>
                            
                            <?php if (!empty($imageUrl)): ?>
                                <img class="song-image" src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Album Cover">
                            <?php else: ?>
                                <div class="song-image" style="background-color: #ddd; display: flex; align-items: center; justify-content: center;">
                                    <span>🎵</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="song-info">
                                <div class="song-title">
                                    <?php 
                                    if (isset($song['name'])) {
                                        echo htmlspecialchars($song['name']);
                                    } elseif (isset($song['title'])) {
                                        echo htmlspecialchars($song['title']);
                                    } else {
                                        echo 'Unbekannter Titel';
                                    }
                                    ?>
                                </div>
                                <div class="song-artist">
                                    <?php 
                                    if (isset($song['artist']['name'])) {
                                        echo htmlspecialchars($song['artist']['name']);
                                    } elseif (isset($song['artist']) && is_string($song['artist'])) {
                                        echo htmlspecialchars($song['artist']);
                                    } else {
                                        echo 'Unbekannter Künstler';
                                    }
                                    ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="swipe-actions">
                <div class="swipe-btn swipe-left" id="swipe-left">✕</div>
                <div class="swipe-btn swipe-right" id="swipe-right">♥</div>
            </div>
        <?php else: ?>
            <div class="loading">Keine Empfehlungen gefunden. Versuche es später noch einmal.</div>
        <?php endif; ?>
    <?php endif; ?>
    
    <script>
        // Einfache Swipe-Funktionalität für Tinder-ähnliches Erlebnis
        document.addEventListener('DOMContentLoaded', function() {
            const songList = document.getElementById('song-list');
            const swipeLeft = document.getElementById('swipe-left');
            const swipeRight = document.getElementById('swipe-right');
            
            if (!songList || !swipeLeft || !swipeRight) return;
            
            let currentIndex = 0;
            const songs = songList.querySelectorAll('.song-item');
            const likedSongs = [];
            
            // Alle Songs außer dem ersten ausblenden
            for (let i = 1; i < songs.length; i++) {
                songs[i].style.display = 'none';
            }
            
            // Swipe-Links-Funktion (Ablehnen)
            swipeLeft.addEventListener('click', function() {
                if (currentIndex < songs.length - 1) {
                    songs[currentIndex].style.transform = 'translateX(-100%)';
                    songs[currentIndex].style.opacity = '0';
                    
                    setTimeout(() => {
                        songs[currentIndex].style.display = 'none';
                        currentIndex++;
                        songs[currentIndex].style.display = 'flex';
                    }, 300);
                } else {
                    alert('Keine weiteren Empfehlungen verfügbar!');
                }
            });
            
            // Swipe-Rechts-Funktion (Gefällt mir)
            swipeRight.addEventListener('click', function() {
                if (currentIndex < songs.length) {
                    const songTitle = songs[currentIndex].querySelector('.song-title').textContent.trim();
                    const songArtist = songs[currentIndex].querySelector('.song-artist').textContent.trim();
                    
                    likedSongs.push({ title: songTitle, artist: songArtist });
                    console.log('Liked song:', songTitle, 'by', songArtist);
                    
                    songs[currentIndex].style.transform = 'translateX(100%)';
                    songs[currentIndex].style.opacity = '0';
                    setTimeout(() => {
                        songs[currentIndex].style.display = 'none';
                        currentIndex++;
                        
                        if (currentIndex < songs.length) {
                            songs[currentIndex].style.display = 'flex';
                        } else {
                            showLikedSongs();
                        }
                    }, 300);
                } else {
                    showLikedSongs();
                }
            });
            
            // Funktion zum Anzeigen der gemochten Songs
            function showLikedSongs() {
                if (likedSongs.length === 0) {
                    alert('Du hast keine Songs zu deiner Playlist hinzugefügt.');
                    return;
                }
                
                const container = document.getElementById('swipe-container');
                container.innerHTML = '';
                
                const header = document.createElement('h2');
                header.className = 'section-header';
                header.textContent = 'Deine neue Playlist';
                container.appendChild(header);
                
                const playlistUl = document.createElement('ul');
                playlistUl.className = 'song-list';
                
                likedSongs.forEach(song => {
                    const li = document.createElement('li');
                    li.className = 'song-item';
                    
                    const songInfo = document.createElement('div');
                    songInfo.className = 'song-info';
                    
                    const titleDiv = document.createElement('div');
                    titleDiv.className = 'song-title';
                    titleDiv.textContent = song.title;
                    
                    const artistDiv = document.createElement('div');
                    artistDiv.className = 'song-artist';
                    artistDiv.textContent = song.artist;
                    
                    songInfo.appendChild(titleDiv);
                    songInfo.appendChild(artistDiv);
                    li.appendChild(songInfo);
                    playlistUl.appendChild(li);
                });
                
                container.appendChild(playlistUl);
                
                // Verstecke die Swipe-Buttons
                document.querySelector('.swipe-actions').style.display = 'none';
                
                // Füge einen Button zum Speichern der Playlist hinzu
                const saveBtn = document.createElement('button');
                saveBtn.className = 'btn';
                saveBtn.textContent = 'Playlist speichern';
                saveBtn.style.marginTop = '20px';
                saveBtn.onclick = function() {
                    alert('Playlist gespeichert! (Diese Funktion würde in einer echten Anwendung die Playlist in der Datenbank speichern)');
                };
                
                container.appendChild(saveBtn);
            }
            
            // Touch-Swipe-Funktionalität hinzufügen
            let touchStartX = 0;
            let touchEndX = 0;
            
            songList.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            });
            
            songList.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            });
            
            function handleSwipe() {
                const swipeThreshold = 50;
                
                if (touchEndX < touchStartX - swipeThreshold) {
                    // Swipe nach links
                    swipeLeft.click();
                } else if (touchEndX > touchStartX + swipeThreshold) {
                    // Swipe nach rechts
                    swipeRight.click();
                }
            }
        });
    </script>
</body>
</html>
