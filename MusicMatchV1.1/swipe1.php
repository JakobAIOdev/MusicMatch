<?php
require 'vendor/autoload.php';
include "config.php";
session_start();

if (!isset($_SESSION['spotify_access_token'])) {
    header('Location: index.php');
    exit;
}

$api = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken($_SESSION['spotify_access_token']);

try {
    $userData = $api->me();
} catch (Exception $e) {
    die('Error fetching user data: ' . $e->getMessage());
}

// Holen der Top-Tracks des Nutzers der letzten 4 Wochen
try {
    $topTracks = $api->getMyTop('tracks', [
        'limit' => 50,
        'time_range' => 'short_term' // Letzte 4 Wochen
    ]);
    
    // Wenn keine Tracks gefunden wurden, versuche medium_term
    if (count($topTracks->items) === 0) {
        $topTracks = $api->getMyTop('tracks', [
            'limit' => 50,
            'time_range' => 'medium_term'
        ]);
    }
} catch (Exception $e) {
    die('Error fetching tracks: ' . $e->getMessage());
}

// Extrahiere die Track-URIs für den Player
$trackUris = [];
$trackData = [];
foreach ($topTracks->items as $track) {
    $trackUris[] = $track->uri;
    $trackData[] = [
        'uri' => $track->uri,
        'id' => $track->id,
        'name' => $track->name,
        'artist' => implode(', ', array_map(function($artist) {
            return $artist->name;
        }, $track->artists)),
        'album' => $track->album->name,
        'image' => $track->album->images[0]->url ?? 'img/default-album.png',
        'duration_ms' => $track->duration_ms
    ];
}

// Konvertiere die Track-Daten zu JSON für JavaScript
$tracksJson = json_encode($trackData);
?>

<!DOCTYPE html>
<html>
<head>
    <title>MusicMatch - Swipe & Match</title>
    <link rel="stylesheet" type="text/css" href="./style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #121212;
            color: #ffffff;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1, h2 {
            color: #1DB954;
            text-align: center;
        }
        
        .swipe-container {
            position: relative;
            height: 500px;
            margin: 20px auto;
            perspective: 1000px;
        }
        
        .card {
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: #282828;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            overflow: hidden;
            transition: transform 0.5s, opacity 0.5s;
            transform-style: preserve-3d;
            cursor: grab;
            display: flex;
            flex-direction: column;
        }
        
        .card.swiped-left {
            transform: translateX(-1000px) rotate(-30deg);
            opacity: 0;
        }
        
        .card.swiped-right {
            transform: translateX(1000px) rotate(30deg);
            opacity: 0;
        }
        
        .card-image {
            width: 100%;
            height: 60%;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .card-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .card-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }
        
        .card-artist {
            font-size: 18px;
            color: #b3b3b3;
            margin: 0 0 10px 0;
        }
        
        .card-album {
            font-size: 14px;
            color: #b3b3b3;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .action-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            margin: 0 15px;
            cursor: pointer;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .action-button:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .dislike-button {
            background-color: #E74C3C;
            color: white;
        }
        
        .like-button {
            background-color: #1DB954;
            color: white;
        }
        
        .progress-container {
            width: 100%;
            background-color: #333;
            height: 5px;
            border-radius: 5px;
            position: absolute;
            bottom: 0;
            left: 0;
        }
        
        .progress-bar {
            background-color: #1DB954;
            height: 100%;
            border-radius: 5px;
            width: 0%;
        }
        
        .time-display {
            position: absolute;
            bottom: 10px;
            right: 10px;
            font-size: 12px;
            background-color: rgba(0,0,0,0.5);
            padding: 3px 8px;
            border-radius: 10px;
        }
        
        .player-controls {
            position: absolute;
            bottom: 10px;
            left: 10px;
            display: flex;
            align-items: center;
        }
        
        .player-control-button {
            background-color: rgba(0,0,0,0.5);
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .player-control-button:hover {
            background-color: rgba(0,0,0,0.7);
        }
        
        .volume-control {
            position: absolute;
            bottom: 10px;
            left: 90px;
            width: 80px;
        }
        
        input[type="range"] {
            width: 100%;
            height: 5px;
            -webkit-appearance: none;
            background: #333;
            border-radius: 5px;
            outline: none;
        }
        
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #1DB954;
            cursor: pointer;
        }
        
        .liked-songs-container {
            background-color: #282828;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .liked-songs-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .liked-song-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #333;
        }
        
        .liked-song-item:last-child {
            border-bottom: none;
        }
        
        .liked-song-image {
            width: 50px;
            height: 50px;
            border-radius: 5px;
            margin-right: 15px;
        }
        
        .liked-song-info {
            flex-grow: 1;
        }
        
        .liked-song-title {
            font-weight: bold;
            margin: 0 0 5px 0;
        }
        
        .liked-song-artist {
            font-size: 14px;
            color: #b3b3b3;
            margin: 0;
        }
        
        .liked-song-play {
            background-color: transparent;
            color: #1DB954;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }
        
        .no-more-songs {
            text-align: center;
            padding: 40px 0;
            font-size: 20px;
            color: #b3b3b3;
        }
        
        .swipe-instructions {
            text-align: center;
            margin-bottom: 20px;
            color: #b3b3b3;
        }
        
        .preview-info {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: rgba(0,0,0,0.5);
            padding: 5px 10px;
            border-radius: 10px;
            font-size: 12px;
        }
        
        @media (max-width: 600px) {
            .swipe-container {
                height: 400px;
            }
            
            .card-title {
                font-size: 20px;
            }
            
            .card-artist {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="site-header">
        <div class="site-logo">
            <h1>MusicMatch</h1>
        </div>
        <div class="nav-user-info">
            <a href="dashboard.php" class="back-to-dashboard">Dashboard</a>
            <a href="profile.php" class="profile-link">
                <img src="<?php echo htmlspecialchars($userData->images[0]->url ?? 'img/default-avatar.png'); ?>" alt="Profile Picture">
                <span><?php echo htmlspecialchars($userData->display_name); ?></span>
            </a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h1>Musik-Swiper</h1>
        
        <div class="swipe-instructions">
            <p>Swipe nach rechts zum Liken, nach links zum Disliken.<br>
            Oder nutze die Pfeiltasten ← → oder die Buttons unten.</p>
        </div>
        
        <div class="swipe-container" id="swipe-container">
            <!-- Karten werden hier dynamisch eingefügt -->
        </div>
        
        <div class="action-buttons">
            <button class="action-button dislike-button" id="dislike-button">
                <i class="fas fa-times"></i>
            </button>
            <button class="action-button like-button" id="like-button">
                <i class="fas fa-heart"></i>
            </button>
        </div>
        
        <div class="liked-songs-container">
            <h2>Deine Liked Songs</h2>
            <ul class="liked-songs-list" id="liked-songs-list">
                <!-- Liked Songs werden hier dynamisch eingefügt -->
                <li class="no-liked-songs">Noch keine Songs geliked</li>
            </ul>
        </div>
    </div>
    
    <script src="https://sdk.scdn.co/spotify-player.js"></script>
    <script src="https://hammerjs.github.io/dist/hammer.min.js"></script>
    <script>
        // Track-Daten aus PHP
        const tracks = <?php echo $tracksJson; ?>;
        let player;
        let deviceId;
        let currentTrackIndex = 0;
        let isPlaying = false;
        let playbackTimer = null;
        let previewDuration = 30000; // 30 Sekunden in Millisekunden
        let currentStartPosition = 0;
        let previewEnded = false;
        let likedSongs = [];
        let currentCard = null;
        
        // DOM-Elemente
        const swipeContainer = document.getElementById('swipe-container');
        const likeButton = document.getElementById('like-button');
        const dislikeButton = document.getElementById('dislike-button');
        const likedSongsList = document.getElementById('liked-songs-list');
        
        // Initialisiere die Karten
        function initializeCards() {
            if (tracks.length === 0) {
                swipeContainer.innerHTML = '<div class="no-more-songs">Keine Songs verfügbar</div>';
                return;
            }
            
            // Erstelle die erste Karte
            createCard(tracks[currentTrackIndex]);
        }
        
        // Erstelle eine neue Karte
        function createCard(track) {
            const card = document.createElement('div');
            card.className = 'card';
            card.id = 'current-card';
            
            card.innerHTML = `
                <div class="card-image" style="background-image: url('${track.image}')">
                    <div class="preview-info">30-Sekunden-Vorschau</div>
                    <div class="progress-container">
                        <div class="progress-bar" id="progress-bar"></div>
                    </div>
                    <div class="time-display">
                        <span id="current-time">0:00</span> / 0:30
                    </div>
                    <div class="player-controls">
                        <button class="player-control-button" id="toggle-play-button">
                            <i class="fas fa-play" id="play-icon"></i>
                        </button>
                    </div>
                    <div class="volume-control">
                        <input type="range" id="volume" min="0" max="100" value="50">
                    </div>
                </div>
                <div class="card-content">
                    <div>
                        <h2 class="card-title">${track.name}</h2>
                        <p class="card-artist">${track.artist}</p>
                        <p class="card-album">${track.album}</p>
                    </div>
                </div>
            `;
            
            swipeContainer.appendChild(card);
            currentCard = card;
            
            // Initialisiere Hammer.js für Swipe-Gesten
            const hammer = new Hammer(card);
            hammer.on('swipeleft', () => handleSwipe('left'));
            hammer.on('swiperight', () => handleSwipe('right'));
            
            // Füge Event-Listener für Play/Pause hinzu
            document.getElementById('toggle-play-button').addEventListener('click', togglePlay);
            
            // Füge Event-Listener für Lautstärkeregler hinzu
            document.getElementById('volume').addEventListener('input', handleVolumeChange);
            
            // Starte die Wiedergabe automatisch
            playCurrentTrack();
        }
        
        // Behandle Swipe-Gesten
        function handleSwipe(direction) {
            if (!currentCard) return;
            
            if (direction === 'left') {
                currentCard.classList.add('swiped-left');
                dislikeCurrentTrack();
            } else if (direction === 'right') {
                currentCard.classList.add('swiped-right');
                likeCurrentTrack();
            }
            
            // Entferne die Karte nach der Animation
            setTimeout(() => {
                currentCard.remove();
                currentCard = null;
                
                // Gehe zum nächsten Track
                currentTrackIndex++;
                
                // Überprüfe, ob noch Tracks vorhanden sind
                if (currentTrackIndex < tracks.length) {
                    createCard(tracks[currentTrackIndex]);
                } else {
                    swipeContainer.innerHTML = '<div class="no-more-songs">Keine weiteren Songs verfügbar</div>';
                }
            }, 500);
        }
        
        // Like den aktuellen Track
        function likeCurrentTrack() {
            const track = tracks[currentTrackIndex];
            likedSongs.push(track);
            updateLikedSongsList();
        }
        
        // Dislike den aktuellen Track (keine Aktion erforderlich)
        function dislikeCurrentTrack() {
            // Hier könnte man disliked Songs speichern, wenn gewünscht
        }
        
        // Aktualisiere die Liste der gelikten Songs
        function updateLikedSongsList() {
            if (likedSongs.length === 0) {
                likedSongsList.innerHTML = '<li class="no-liked-songs">Noch keine Songs geliked</li>';
                return;
            }
            
            likedSongsList.innerHTML = '';
            
            likedSongs.forEach((song, index) => {
                const li = document.createElement('li');
                li.className = 'liked-song-item';
                li.innerHTML = `
                    <img src="${song.image}" alt="${song.name}" class="liked-song-image">
                    <div class="liked-song-info">
                        <h3 class="liked-song-title">${song.name}</h3>
                        <p class="liked-song-artist">${song.artist}</p>
                    </div>
                    <button class="liked-song-play" data-index="${index}">
                        <i class="fas fa-play"></i>
                    </button>
                `;
                likedSongsList.appendChild(li);
            });
            
            // Füge Event-Listener für Play-Buttons hinzu
            document.querySelectorAll('.liked-song-play').forEach(button => {
                button.addEventListener('click', (e) => {
                    const index = e.currentTarget.getAttribute('data-index');
                    playLikedSong(index);
                });
            });
        }
        
        // Spiele einen gelikten Song ab
        function playLikedSong(index) {
            const track = likedSongs[index];
            if (track) {
                currentTrackIndex = tracks.findIndex(t => t.id === track.id);
                if (currentTrackIndex !== -1) {
                    // Entferne die aktuelle Karte und erstelle eine neue für den gelikten Song
                    if (currentCard) {
                        currentCard.remove();
                    }
                    createCard(track);
                    playCurrentTrack();
                }
            }
        }
        
        // Spiele den aktuellen Track ab
        function playCurrentTrack() {
            if (!deviceId) return;
            
            const track = tracks[currentTrackIndex];
            previewEnded = false;
            
            // Berechne die Startposition bei ca. 30-40% des Songs
            const startPercentage = 0.3 + (Math.random() * 0.1); // 30-40%
            currentStartPosition = Math.floor(track.duration_ms * startPercentage);
            
            fetch(`https://api.spotify.com/v1/me/player/play?device_id=${deviceId}`, {
                method: 'PUT',
                body: JSON.stringify({ 
                    uris: [track.uri],
                    position_ms: currentStartPosition
                }),
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer <?php echo $_SESSION['spotify_access_token']; ?>`
                },
            })
            .then(response => {
                if (response.status === 204) {
                    isPlaying = true;
                    updatePlayButton();
                    
                    // Timer setzen, um nach 30 Sekunden zu pausieren
                    playbackTimer = setTimeout(() => {
                        if (isPlaying) {
                            player.pause().then(() => {
                                isPlaying = false;
                                previewEnded = true;
                                updatePlayButton();
                                document.getElementById('progress-bar').style.width = '100%';
                                document.getElementById('current-time').textContent = '0:30';
                            });
                        }
                    }, previewDuration);
                } else {
                    response.json().then(data => {
                        console.error('Fehler beim Abspielen:', data);
                    });
                }
            })
            .catch(error => {
                console.error('Netzwerkfehler:', error);
            });
        }
        
        // Toggle Play/Pause
        function togglePlay() {
            if (isPlaying) {
                player.pause().then(() => {
                    isPlaying = false;
                    updatePlayButton();
                });
            } else {
                if (previewEnded) {
                    playCurrentTrack();
                } else {
                    player.resume().then(() => {
                        isPlaying = true;
                        updatePlayButton();
                    });
                }
            }
        }
        
        // Aktualisiere den Play/Pause-Button
        function updatePlayButton() {
            const playIcon = document.getElementById('play-icon');
            if (isPlaying) {
                playIcon.classList.remove('fa-play');
                playIcon.classList.add('fa-pause');
            } else {
                playIcon.classList.remove('fa-pause');
                playIcon.classList.add('fa-play');
            }
        }
        
        // Behandle Lautstärkeänderungen
        function handleVolumeChange(e) {
            const volume = e.target.value / 100;
            player.setVolume(volume);
        }
        
        // Initialisiere den Spotify Player
        window.onSpotifyWebPlaybackSDKReady = () => {
            player = new Spotify.Player({
                name: 'MusicMatch Web Player',
                getOAuthToken: cb => { cb('<?php echo $_SESSION['spotify_access_token']; ?>'); },
                volume: 0.5
            });
            
            // Error handling
            player.addListener('initialization_error', ({ message }) => {
                console.error('Fehler bei der Initialisierung:', message);
            });
            
            player.addListener('authentication_error', ({ message }) => {
                console.error('Authentifizierungsfehler:', message);
            });
            
            player.addListener('account_error', ({ message }) => {
                console.error('Kontofehler (Premium erforderlich):', message);
            });
            
            player.addListener('playback_error', ({ message }) => {
                console.error('Wiedergabefehler:', message);
            });
            
            // Playback status updates
            player.addListener('player_state_changed', state => {
                if (state) {
                    // Aktualisiere nur, wenn die Vorschau nicht beendet wurde
                    if (!previewEnded) {
                        isPlaying = !state.paused;
                        updatePlayButton();
                        
                        // Berechne die verstrichene Zeit seit Beginn der 30-Sekunden-Vorschau
                        const elapsedInPreview = state.position - currentStartPosition;
                        
                        // Stelle sicher, dass wir nicht über die Vorschaudauer hinausgehen
                        const clampedElapsed = Math.min(Math.max(0, elapsedInPreview), previewDuration);
                        
                        // Aktualisiere Fortschrittsbalken basierend auf der 30-Sekunden-Vorschau
                        const progress = (clampedElapsed / previewDuration) * 100;
                        document.getElementById('progress-bar').style.width = `${progress}%`;
                        document.getElementById('current-time').textContent = formatTime(clampedElapsed);
                        
                        // Wenn wir das Ende der Vorschau erreicht haben, stoppe die Wiedergabe
                        if (elapsedInPreview >= previewDuration && isPlaying) {
                            player.pause().then(() => {
                                isPlaying = false;
                                previewEnded = true;
                                updatePlayButton();
                                document.getElementById('progress-bar').style.width = '100%';
                                document.getElementById('current-time').textContent = '0:30';
                                
                                // Lösche den Timer, da wir ihn nicht mehr brauchen
                                if (playbackTimer) {
                                    clearTimeout(playbackTimer);
                                    playbackTimer = null;
                                }
                            });
                        }
                    }
                }
            });
            
            // Ready
            player.addListener('ready', ({ device_id }) => {
                deviceId = device_id;
                console.log('Player bereit mit Device ID:', device_id);
                initializeCards();
            });
            
            // Connect to the player
            player.connect();
        };
        
        // Event-Listener für Like/Dislike-Buttons
        likeButton.addEventListener('click', () => handleSwipe('right'));
        dislikeButton.addEventListener('click', () => handleSwipe('left'));
        
        // Event-Listener für Pfeiltasten
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                handleSwipe('left');
            } else if (e.key === 'ArrowRight') {
                handleSwipe('right');
            }
        });
        
        // Hilfsfunktion zum Formatieren der Zeit
        function formatTime(ms) {
            const totalSeconds = Math.floor(ms / 1000);
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            return `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
    </script>
</body>
</html>