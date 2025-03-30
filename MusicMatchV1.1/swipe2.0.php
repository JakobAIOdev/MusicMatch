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

// Fetch user's top tracks from the last 4 weeks
try {
    $topTracks = $api->getMyTop('tracks', [
        'limit' => 50,
        'time_range' => 'short_term' // Last 4 weeks
    ]);
    
    // If no tracks found, try medium_term
    if (count($topTracks->items) === 0) {
        $topTracks = $api->getMyTop('tracks', [
            'limit' => 50,
            'time_range' => 'medium_term'
        ]);
    }
} catch (Exception $e) {
    die('Error fetching tracks: ' . $e->getMessage());
}

// Extract track URIs for the player
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
        'duration_ms' => $track->duration_ms,
        'spotify_url' => $track->external_urls->spotify ?? '#'
    ];
}

// Convert track data to JSON for JavaScript
$tracksJson = json_encode($trackData);
?>

<!DOCTYPE html>
<html>
<head>
    <title>MusicMatch - Swipe & Match</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4A6FFF; /* New primary blue color */
            --secondary-color: #191414;
            --accent-color: #FF6B6B; /* New accent color */
            --spotify-green: #1DB954; /* Reserved only for Spotify buttons */
            --text-color: #333;
            --light-text: #666;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --shadow: 0 8px 16px rgba(0,0,0,0.1);
            --border-radius: 12px;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            line-height: 1.6;
        }
        
        .site-header {
            background-color: var(--secondary-color);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .site-logo h1 {
            margin: 0;
            font-size: 24px;
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .nav-user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .nav-user-info a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .nav-user-info a:hover {
            color: var(--primary-color);
        }
        
        .profile-link {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .profile-link img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
            object-fit: cover;
        }
        
        .main-content {
            display: flex;
            justify-content: center;
            flex-grow: 1;
            padding: 30px 20px;
            width: 100%;
        }
        
        .container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        
        h1, h2 {
            color: var(--secondary-color);
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .swipe-container {
            position: relative;
            height: 520px;
            margin: 20px auto;
            perspective: 1000px;
            max-width: 100%;
        }
        
        .card {
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275), 
                        opacity 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform-style: preserve-3d;
            cursor: grab;
            display: flex;
            flex-direction: column;
            will-change: transform, opacity; /* Hardware acceleration */
        }
        
        .card.swiped-left {
            transform: translateX(-1000px) rotate(-30deg);
            opacity: 0;
            transition: transform 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55), 
                        opacity 0.3s ease-out;
        }
        
        .card.swiped-right {
            transform: translateX(1000px) rotate(30deg);
            opacity: 0;
            transition: transform 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55), 
                        opacity 0.3s ease-out;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-size: 30px;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
            z-index: 10;
        }
        
        .card.partial-left::before {
            left: 20px;
            background-color: rgba(255, 107, 107, 0.9);
            background-image: url('img/dislike-icon.svg');
            opacity: 0.8;
            transform: scale(1);
        }
        
        .card.partial-right::before {
            right: 20px;
            background-color: rgba(74, 111, 255, 0.9);
            background-image: url('img/like-icon.svg');
            opacity: 0.8;
            transform: scale(1);
        }
        
        .card-image {
            width: 100%;
            height: 60%;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .card-content {
            padding: 24px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
            background: linear-gradient(to bottom, rgba(255,255,255,0.95), rgba(255,255,255,1));
        }
        
        .card-title {
            font-size: 22px;
            font-weight: 600;
            margin: 0 0 5px 0;
            color: var(--text-color);
        }
        
        .card-artist {
            font-size: 18px;
            color: var(--light-text);
            margin: 0 0 10px 0;
            font-weight: 500;
        }
        
        .card-album {
            font-size: 14px;
            color: var(--light-text);
            font-weight: 400;
        }
        
        .spotify-button {
            display: inline-block;
            background-color: var(--spotify-green);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 15px;
            transition: background-color 0.2s, transform 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 80%;
            margin: 15px auto 0;
        }
        
        .spotify-button:hover {
            background-color: #1ed760;
            transform: translateY(-2px);
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            margin-top: 25px;
            gap: 30px;
        }
        
        .action-button {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), 
                        box-shadow 0.3s ease, 
                        background-color 0.3s ease;
        }
        
        .action-button:hover {
            transform: scale(1.1) translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        .dislike-button {
            background-color: var(--accent-color);
            color: white;
        }
        
        .like-button {
            background-color: var(--primary-color);
            color: white;
        }
        
        .progress-container {
            width: 100%;
            background-color: rgba(255, 255, 255, 0.3);
            height: 4px;
            border-radius: 2px;
            position: absolute;
            bottom: 0;
            left: 0;
            overflow: hidden;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
            height: 100%;
            border-radius: 2px;
            width: 0%;
            transition: width 0.1s linear;
        }
        
        .time-display {
            position: absolute;
            bottom: 10px;
            right: 10px;
            font-size: 12px;
            background-color: rgba(0,0,0,0.6);
            padding: 4px 10px;
            border-radius: 50px;
            color: white;
            font-weight: 500;
        }
        
        .player-controls {
            position: absolute;
            bottom: 10px;
            left: 10px;
            display: flex;
            align-items: center;
        }
        
        .player-control-button {
            background-color: rgba(0,0,0,0.6);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-right: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s, transform 0.2s;
        }
        
        .player-control-button:hover {
            background-color: var(--primary-color);
            transform: scale(1.1);
        }
        
        .volume-control {
            position: absolute;
            bottom: 10px;
            left: 60px;
            width: 80px;
        }
        
        input[type="range"] {
            width: 100%;
            height: 4px;
            -webkit-appearance: none;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            outline: none;
        }
        
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--primary-color);
            cursor: pointer;
            border: 2px solid white;
        }
        
        .liked-songs-container {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-top: 40px;
            box-shadow: var(--shadow);
        }
        
        .liked-songs-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .liked-song-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
            border-radius: 8px;
        }
        
        .liked-song-item:hover {
            background-color: #f5f5f5;
        }
        
        .liked-song-item:last-child {
            border-bottom: none;
        }
        
        .liked-song-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            margin-right: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            object-fit: cover;
        }
        
        .liked-song-info {
            flex-grow: 1;
        }
        
        .liked-song-title {
            font-weight: 600;
            margin: 0 0 5px 0;
            color: var(--text-color);
        }
        
        .liked-song-artist {
            font-size: 14px;
            color: var(--light-text);
            margin: 0;
        }
        
        .liked-song-play {
            background-color: transparent;
            color: var(--primary-color);
            border: none;
            font-size: 20px;
            cursor: pointer;
            transition: transform 0.2s;
            margin-right: 10px;
        }
        
        .liked-song-play:hover {
            transform: scale(1.2);
        }
        
        .liked-song-spotify {
            background-color: var(--spotify-green);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.2s;
        }
        
        .liked-song-spotify:hover {
            background-color: #1ed760;
            transform: scale(1.1);
        }
        
        .no-more-songs {
            text-align: center;
            padding: 60px 0;
            font-size: 20px;
            color: var(--light-text);
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }
        
        .swipe-instructions {
            text-align: center;
            margin-bottom: 25px;
            color: var(--light-text);
            background-color: var(--card-bg);
            padding: 15px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            font-size: 15px;
        }
        
        .preview-info {
            position: absolute;
            top: 15px;
            left: 15px;
            background-color: rgba(0,0,0,0.6);
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            color: white;
            font-weight: 500;
        }
        
        .no-liked-songs {
            text-align: center;
            padding: 20px;
            color: var(--light-text);
            font-size: 15px;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 10px;
            }
            
            .swipe-container {
                height: 450px;
            }
            
            .card-title {
                font-size: 20px;
            }
            
            .card-artist {
                font-size: 16px;
            }
            
            .action-button {
                width: 56px;
                height: 56px;
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
<div class="main-content">
    <div class="container">
        <h1>Music Swiper</h1>
        
        <div class="swipe-instructions">
            <p>Swipe right to like, left to dislike.<br>
            Or use the arrow keys ← → or the buttons below.</p>
        </div>
        
        <div class="swipe-container" id="swipe-container">
            <!-- Cards will be dynamically inserted here -->
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
            <h2>Your Liked Songs</h2>
            <ul class="liked-songs-list" id="liked-songs-list">
                <!-- Liked Songs will be dynamically inserted here -->
                <li class="no-liked-songs">No songs liked yet</li>
            </ul>
        </div>
    </div>
</div>

<script src="https://sdk.scdn.co/spotify-player.js"></script>
<script src="https://hammerjs.github.io/dist/hammer.min.js"></script>
<script>
    // Track data from PHP
    const tracks = <?php echo $tracksJson; ?>;
    let player;
    let deviceId;
    let currentTrackIndex = 0;
    let isPlaying = false;
    let playbackTimer = null;
    let previewDuration = 30000; // 30 seconds in milliseconds
    let currentStartPosition = 0;
    let previewEnded = false;
    let likedSongs = [];
    let currentCard = null;
    let dragOffset = { x: 0, y: 0 };
    
    // DOM elements
    const swipeContainer = document.getElementById('swipe-container');
    const likeButton = document.getElementById('like-button');
    const dislikeButton = document.getElementById('dislike-button');
    const likedSongsList = document.getElementById('liked-songs-list');
    
    // Initialize the cards
    function initializeCards() {
        if (tracks.length === 0) {
            swipeContainer.innerHTML = '<div class="no-more-songs">No songs available</div>';
            return;
        }
        
        // Create the first card
        createCard(tracks[currentTrackIndex]);
    }

    // Create a new card
    function createCard(track) {
        const card = document.createElement('div');
        card.className = 'card';
        card.id = 'current-card';
        
        card.innerHTML = `
            <div class="card-image" style="background-image: url('${track.image}')">
                <div class="preview-info">30-second preview</div>
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
                    <a href="${track.spotify_url}" class="spotify-button" target="_blank">
                        <i class="fab fa-spotify"></i> Listen on Spotify
                    </a>
                </div>
            </div>
        `;
        
        swipeContainer.appendChild(card);
        currentCard = card;
        
        // Initialize Hammer.js for swipe gestures
        const hammer = new Hammer(card);
        hammer.on('swipeleft', () => handleSwipe('left'));
        hammer.on('swiperight', () => handleSwipe('right'));
        
        // Add pan event for partial swipe indicators
        hammer.on('pan', (e) => {
            const xPos = e.deltaX;
            
            // Add partial swipe indicators
            if (xPos > 50) {
                card.classList.add('partial-right');
                card.classList.remove('partial-left');
            } else if (xPos < -50) {
                card.classList.add('partial-left');
                card.classList.remove('partial-right');
            } else {
                card.classList.remove('partial-right', 'partial-left');
            }
            
            // Apply rotation and movement during pan
            card.style.transform = `translateX(${xPos}px) rotate(${xPos * 0.05}deg)`;
            
            if (e.isFinal) {
                // Reset transform if not enough to trigger swipe
                if (Math.abs(xPos) < 100) {
                    card.style.transform = '';
                    card.classList.remove('partial-right', 'partial-left');
                }
            }
        });
        
        // Add event listener for play/pause
        document.getElementById('toggle-play-button').addEventListener('click', togglePlay);
        
        // Add event listener for volume control
        document.getElementById('volume').addEventListener('input', handleVolumeChange);
        
        // Start playback automatically
        playCurrentTrack();
    }

    // Handle swipe gestures
    function handleSwipe(direction) {
        if (!currentCard) return;
        
        if (direction === 'left') {
            currentCard.classList.add('swiped-left');
            setTimeout(() => dislikeCurrentTrack(), 100); // Small delay for smoother feel
        } else if (direction === 'right') {
            currentCard.classList.add('swiped-right');
            setTimeout(() => likeCurrentTrack(), 100); // Small delay for smoother feel
        }
        
        // Reset inline transform to let CSS animations take over
        currentCard.style.transform = '';
        
        // Remove card after animation
        setTimeout(() => {
            currentCard.remove();
            currentCard = null;
            
            // Move to next track
            currentTrackIndex++;
            
            // Check if there are more tracks
            if (currentTrackIndex < tracks.length) {
                setTimeout(() => createCard(tracks[currentTrackIndex]), 150); // Staggered card creation
            } else {
                swipeContainer.innerHTML = '<div class="no-more-songs">No more songs available</div>';
            }
        }, 800); // Match the animation duration
    }

    // Like current track
    function likeCurrentTrack() {
        const track = tracks[currentTrackIndex];
        likedSongs.push(track);
        updateLikedSongsList();
    }

    // Dislike current track (no action required)
    function dislikeCurrentTrack() {
        // You could store disliked songs here if desired
    }

    // Update the list of liked songs
    function updateLikedSongsList() {
        if (likedSongs.length === 0) {
            likedSongsList.innerHTML = '<li class="no-liked-songs">No songs liked yet</li>';
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
                <a href="${song.spotify_url}" class="liked-song-spotify" target="_blank">
                    <i class="fab fa-spotify"></i>
                </a>
            `;
            likedSongsList.appendChild(li);
        });
        
        // Add event listeners for play buttons
        document.querySelectorAll('.liked-song-play').forEach(button => {
            button.addEventListener('click', (e) => {
                const index = e.currentTarget.getAttribute('data-index');
                playLikedSong(index);
            });
        });
    }

    // Play a liked song
    function playLikedSong(index) {
        const track = likedSongs[index];
        if (track) {
            currentTrackIndex = tracks.findIndex(t => t.id === track.id);
            if (currentTrackIndex !== -1) {
                // Remove current card and create a new one for the liked song
                if (currentCard) {
                    currentCard.remove();
                }
                createCard(track);
                playCurrentTrack();
            }
        }
    }

    // Play current track
    function playCurrentTrack() {
        if (!deviceId) return;
        
        const track = tracks[currentTrackIndex];
        previewEnded = false;
        
        // Clear any existing timer
        if (playbackTimer) {
            clearTimeout(playbackTimer);
            playbackTimer = null;
        }
        
        // Calculate start position at about 30-40% of the song
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
                
                // Set timer to pause after 30 seconds
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
                    console.error('Error playing track:', data);
                });
            }
        })
        .catch(error => {
            console.error('Network error:', error);
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

    // Update Play/Pause button
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

    // Handle volume changes
    function handleVolumeChange(e) {
        const volume = e.target.value / 100;
        player.setVolume(volume);
    }

    // Initialize Spotify Player
    window.onSpotifyWebPlaybackSDKReady = () => {
        player = new Spotify.Player({
            name: 'MusicMatch Web Player',
            getOAuthToken: cb => { cb('<?php echo $_SESSION['spotify_access_token']; ?>'); },
            volume: 0.5
        });
        
        // Error handling
        player.addListener('initialization_error', ({ message }) => {
            console.error('Initialization error:', message);
        });
        
        player.addListener('authentication_error', ({ message }) => {
            console.error('Authentication error:', message);
        });
        
        player.addListener('account_error', ({ message }) => {
            console.error('Account error (Premium required):', message);
        });
        
        player.addListener('playback_error', ({ message }) => {
            console.error('Playback error:', message);
        });
        
        // Playback status updates
        player.addListener('player_state_changed', state => {
            if (state && !previewEnded) {
                isPlaying = !state.paused;
                updatePlayButton();
                
                // Calculate elapsed time since start of 30-second preview
                const elapsedInPreview = Math.min(state.position - currentStartPosition, previewDuration);
                
                // Ensure we don't go beyond preview duration
                const clampedElapsed = Math.max(0, elapsedInPreview);
                
                // Update progress bar based on 30-second preview
                const progress = (clampedElapsed / previewDuration) * 100;
                document.getElementById('progress-bar').style.width = `${progress}%`;
                document.getElementById('current-time').textContent = formatTime(clampedElapsed);
                
                // If we've reached the end of the preview, stop playback
                if (elapsedInPreview >= previewDuration && isPlaying) {
                    player.pause().then(() => {
                        isPlaying = false;
                        previewEnded = true;
                        updatePlayButton();
                        document.getElementById('progress-bar').style.width = '100%';
                        document.getElementById('current-time').textContent = '0:30';
                        
                        // Clear timer as we don't need it anymore
                        if (playbackTimer) {
                            clearTimeout(playbackTimer);
                            playbackTimer = null;
                        }
                    });
                }
            }
        });
        
        // Ready
        player.addListener('ready', ({ device_id }) => {
            deviceId = device_id;
            console.log('Player ready with device ID:', device_id);
            initializeCards();
        });
        
        // Connect to the player
        player.connect();
    };

    // Event listeners for Like/Dislike buttons
    likeButton.addEventListener('click', () => handleSwipe('right'));
    dislikeButton.addEventListener('click', () => handleSwipe('left'));

    // Event listeners for arrow keys
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            handleSwipe('left');
        } else if (e.key === 'ArrowRight') {
            handleSwipe('right');
        }
    });

    // Helper function to format time
    function formatTime(ms) {
        const totalSeconds = Math.floor(ms / 1000);
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }
</script>
</body> </html>