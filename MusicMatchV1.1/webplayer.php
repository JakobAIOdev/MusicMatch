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
        'limit' => 20,
        'time_range' => 'short_term' // Letzte 4 Wochen
    ]);
    
    // Wenn keine Tracks gefunden wurden, versuche medium_term
    if (count($topTracks->items) === 0) {
        $topTracks = $api->getMyTop('tracks', [
            'limit' => 20,
            'time_range' => 'medium_term'
        ]);
    }
} catch (Exception $e) {
    die('Error fetching top tracks: ' . $e->getMessage());
}

// Extrahiere die Track-URIs für den Player
$trackUris = [];
$trackData = [];
foreach ($topTracks->items as $track) {
    $trackUris[] = $track->uri;
    $trackData[] = [
        'uri' => $track->uri,
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
    <title>Spotify Web Player</title>
    <link rel="stylesheet" type="text/css" href="./style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #121212;
            color: #ffffff;
        }
        
        .player-container {
            background-color: #282828;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        h1, h2 {
            color: #1DB954;
        }
        
        button {
            background-color: #1DB954;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            margin: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        button:hover {
            background-color: #1ed760;
        }
        
        button:disabled {
            background-color: #333;
            cursor: not-allowed;
        }
        
        #current-track {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        #current-track img {
            width: 80px;
            height: 80px;
            margin-right: 15px;
            border-radius: 4px;
        }
        
        .track-info {
            flex-grow: 1;
        }
        
        .track-name {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .track-artist {
            color: #b3b3b3;
            font-size: 14px;
        }
        
        #player-controls {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        #volume-control {
            margin-top: 20px;
            text-align: center;
        }
        
        input[type="range"] {
            width: 200px;
        }
        
        .progress-container {
            margin-top: 20px;
            width: 100%;
            background-color: #333;
            height: 5px;
            border-radius: 5px;
            position: relative;
        }
        
        .progress-bar {
            background-color: #1DB954;
            height: 100%;
            border-radius: 5px;
            width: 0%;
        }
        
        .time-display {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .log {
            background-color: #333;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            height: 100px;
            overflow-y: auto;
            margin-top: 20px;
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
    
    <h1>Deine Lieblingssongs der letzten 4 Wochen</h1>
    
    <div class="player-container">
        <div id="current-track">
            <p>Kein Track geladen</p>
        </div>
        
        <div class="progress-container">
            <div class="progress-bar" id="progress-bar"></div>
        </div>
        <div class="time-display">
            <span id="current-time">0:00</span>
            <span id="total-time">0:00</span>
        </div>
        
        <div id="player-controls">
            <button id="prev-button" disabled>Vorheriger</button>
            <button id="play-button" disabled>Play</button>
            <button id="pause-button" disabled>Pause</button>
            <button id="next-button" disabled>Nächster</button>
        </div>
        
        <div id="volume-control">
            <label for="volume">Lautstärke:</label>
            <input type="range" id="volume" min="0" max="100" value="50" disabled>
        </div>
    </div>
    
    <div class="player-container">
        <h2>Player Status</h2>
        <div class="log" id="player-status">... Player wird initialisiert.
        </div>
    </div>
    
    <script src="https://sdk.scdn.co/spotify-player.js"></script>
    <script>
        // Track-Daten aus PHP
        const tracks = <?php echo $tracksJson; ?>;
        let player;
        let deviceId;
        let currentTrackIndex = 0;
        let isPlaying = false;
        
        // Status-Log-Funktion
        function logStatus(message) {
            const statusElement = document.getElementById('player-status');
            const timestamp = new Date().toLocaleTimeString();
            statusElement.innerHTML += `<div>[${timestamp}] ${message}</div>`;
            statusElement.scrollTop = statusElement.scrollHeight;
        }
        
        // Formatiere Millisekunden in MM:SS Format
        function formatTime(ms) {
            const totalSeconds = Math.floor(ms / 1000);
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            return `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
        
        // Aktualisiere die Anzeige des aktuellen Tracks
        function updateCurrentTrack(track) {
            const currentTrackElement = document.getElementById('current-track');
            
            if (track) {
                currentTrackElement.innerHTML = `
                    <img src="${track.image}" alt="${track.name}">
                    <div class="track-info">
                        <div class="track-name">${track.name}</div>
                        <div class="track-artist">${track.artist}</div>
                        <div class="track-album">${track.album}</div>
                    </div>
                `;
                
                document.getElementById('total-time').textContent = formatTime(track.duration_ms);
            } else {
                currentTrackElement.innerHTML = '<p>Kein Track geladen</p>';
            }
        }
        
        // Spiele einen bestimmten Track ab
        function playTrack(index) {
            if (index < 0 || index >= tracks.length || !deviceId) return;
            
            currentTrackIndex = index;
            const track = tracks[currentTrackIndex];
            
            fetch(`https://api.spotify.com/v1/me/player/play?device_id=${deviceId}`, {
                method: 'PUT',
                body: JSON.stringify({ uris: [track.uri] }),
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer <?php echo $_SESSION['spotify_access_token']; ?>`
                },
            })
            .then(response => {
                if (response.status === 204) {
                    logStatus(`Spiele Track ab: ${track.name}`);
                    isPlaying = true;
                    updateCurrentTrack(track);
                } else {
                    response.json().then(data => {
                        logStatus(`Fehler beim Abspielen: ${JSON.stringify(data)}`);
                    });
                }
            })
            .catch(error => {
                logStatus(`Netzwerkfehler: ${error}`);
            });
        }
        
        // Initialisiere den Spotify Player
        window.onSpotifyWebPlaybackSDKReady = () => {
            logStatus('Spotify Web Playback SDK geladen');
            
            player = new Spotify.Player({
                name: 'MusicMatch Web Player',
                getOAuthToken: cb => { cb('<?php echo $_SESSION['spotify_access_token']; ?>'); },
                volume: 0.5
            });
            
            // Error handling
            player.addListener('initialization_error', ({ message }) => {
                logStatus(`Fehler bei der Initialisierung: ${message}`);
            });
            
            player.addListener('authentication_error', ({ message }) => {
                logStatus(`Authentifizierungsfehler: ${message}`);
            });
            
            player.addListener('account_error', ({ message }) => {
                logStatus(`Kontofehler (Premium erforderlich): ${message}`);
            });
            
            player.addListener('playback_error', ({ message }) => {
                logStatus(`Wiedergabefehler: ${message}`);
            });
            
            // Playback status updates
            player.addListener('player_state_changed', state => {
                if (state) {
                    logStatus('Wiedergabestatus geändert');
                    
                    // Aktualisiere Fortschrittsbalken
                    const progress = state.position / state.duration * 100;
                    document.getElementById('progress-bar').style.width = `${progress}%`;
                    document.getElementById('current-time').textContent = formatTime(state.position);
                    
                    // Wenn der Track zu Ende ist, spiele den nächsten
                    if (state.paused && state.position === 0 && state.duration > 0) {
                        playTrack(currentTrackIndex + 1);
                    }
                    
                    isPlaying = !state.paused;
                }
            });
            
            // Ready
            player.addListener('ready', ({ device_id }) => {
                deviceId = device_id;
                logStatus(`Player bereit mit Device ID: ${device_id}`);
                
                // Aktiviere Steuerelemente
                document.getElementById('play-button').disabled = false;
                document.getElementById('pause-button').disabled = false;
                document.getElementById('prev-button').disabled = false;
                document.getElementById('next-button').disabled = false;
                document.getElementById('volume').disabled = false;
                
                // Zeige den ersten Track an
                if (tracks.length > 0) {
                    updateCurrentTrack(tracks[0]);
                }
            });
            
            // Not Ready
            player.addListener('not_ready', ({ device_id }) => {
                logStatus(`Device ID ist nicht mehr bereit: ${device_id}`);
            });
            
            // Connect to the player
            player.connect();
            
            // Player-Steuerung
            document.getElementById('play-button').addEventListener('click', () => {
                if (tracks.length === 0) return;
                
                if (isPlaying) {
                    player.resume().then(() => {
                        logStatus('Wiedergabe fortgesetzt');
                    });
                } else {
                    playTrack(currentTrackIndex);
                }
            });
            
            document.getElementById('pause-button').addEventListener('click', () => {
                player.pause().then(() => {
                    logStatus('Wiedergabe pausiert');
                    isPlaying = false;
                });
            });
            
            document.getElementById('prev-button').addEventListener('click', () => {
                playTrack(currentTrackIndex - 1);
            });
            
            document.getElementById('next-button').addEventListener('click', () => {
                playTrack(currentTrackIndex + 1);
            });
            
            document.getElementById('volume').addEventListener('change', (e) => {
                const volume = e.target.value / 100;
                player.setVolume(volume).then(() => {
                    logStatus(`Lautstärke auf ${e.target.value}% gesetzt`);
                });
            });
            
            // Aktualisiere den Fortschrittsbalken alle 1000ms
            setInterval(() => {
                if (isPlaying) {
                    player.getCurrentState().then(state => {
                        if (state) {
                            const progress = state.position / state.duration * 100;
                            document.getElementById('progress-bar').style.width = `${progress}%`;
                            document.getElementById('current-time').textContent = formatTime(state.position);
                        }
                    });
                }
            }, 1000);
        };
    </script>
</body>
</html>
