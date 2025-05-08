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
        'artist' => implode(', ', array_map(function ($artist) {
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
            --primary-color: #4A6FFF;
            --secondary-color: #191414;
            --accent-color: #FF6B6B;
            --spotify-green: #1DB954;
            /* Reserved only for Spotify buttons */
            --text-color: #333;
            --light-text: #666;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --border-radius: 16px;
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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

        h1,
        h2 {
            color: var(--secondary-color);
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Completely redesigned card container */
        .swipe-container {
            position: relative;
            height: 520px;
            margin: 20px auto;
            perspective: 1000px;
            max-width: 100%;
        }

        /* New card design */
        .card {
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transform-origin: center center;
            transition: transform 0.6s ease-out, opacity 0.6s ease-out;
            display: flex;
            flex-direction: column;
        }

        /* Simplified swipe animations */
        .card.swiped-left {
            transform: translateX(-150%) rotate(-10deg);
            opacity: 0;
        }

        .card.swiped-right {
            transform: translateX(150%) rotate(10deg);
            opacity: 0;
        }

        /* Redesigned card image section */
        .card-image-container {
            position: relative;
            width: 100%;
            height: 60%;
            overflow: hidden;
        }

        .card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .card:hover .card-image {
            transform: scale(1.05);
        }

        /* Overlay gradient on image */
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom,
                    rgba(0, 0, 0, 0.1) 0%,
                    rgba(0, 0, 0, 0.3) 70%,
                    rgba(0, 0, 0, 0.7) 100%);
        }

        /* Redesigned card content */
        .card-content {
            padding: 24px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-color: var(--card-bg);
            position: relative;
            z-index: 1;
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-album {
            font-size: 14px;
            color: var(--light-text);
            font-weight: 400;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Redesigned Spotify button */
        .spotify-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: var(--spotify-green);
            color: white;
            padding: 12px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(29, 185, 84, 0.3);
        }

        .spotify-button:hover {
            background-color: #1ed760;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(29, 185, 84, 0.4);
        }

        /* Redesigned action buttons */
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
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .action-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .dislike-button {
            background-color: var(--accent-color);
            color: white;
        }

        .like-button {
            background-color: var(--primary-color);
            color: white;
        }

        /* Redesigned player controls */
        .player-controls-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 15px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0));
            display: flex;
            align-items: center;
            z-index: 2;
        }

        .player-control-button {
            background-color: white;
            color: var(--secondary-color);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
        }

        .player-control-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.25);
        }

        /* Redesigned progress bar */
        .progress-container {
            flex-grow: 1;
            height: 6px;
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
            overflow: hidden;
            margin-right: 15px;
        }

        .progress-bar {
            height: 100%;
            background-color: white;
            border-radius: 3px;
            width: 0%;
            transition: width 0.1s linear;
        }

        .time-display {
            color: white;
            font-size: 12px;
            font-weight: 500;
            min-width: 60px;
            text-align: right;
        }

        /* Preview info badge */
        .preview-info {
            position: absolute;
            top: 15px;
            left: 15px;
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            z-index: 2;
        }

        /* Volume control */
        .volume-control {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 2;
            display: flex;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.6);
            padding: 6px 12px;
            border-radius: 50px;
        }

        .volume-icon {
            color: white;
            margin-right: 8px;
            font-size: 12px;
        }

        input[type="range"] {
            width: 60px;
            height: 4px;
            -webkit-appearance: none;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            outline: none;
        }

        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: white;
            cursor: pointer;
        }

        /* Liked songs container */
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
            transition: all 0.2s ease;
            border-radius: 8px;
        }

        .liked-song-item:hover {
            background-color: #f5f5f5;
            transform: translateX(5px);
        }

        .liked-song-item:last-child {
            border-bottom: none;
        }

        .liked-song-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            margin-right: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
            transition: all 0.2s ease;
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

        .no-liked-songs {
            text-align: center;
            padding: 20px;
            color: var(--light-text);
            font-size: 15px;
        }

        /* Swipe indicators */
        .swipe-indicator {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
            z-index: 10;
        }

        .swipe-indicator-like {
            right: 20px;
            background-color: var(--primary-color);
            transform: translateY(-50%) scale(0.8);
        }

        .swipe-indicator-dislike {
            left: 20px;
            background-color: var(--accent-color);
            transform: translateY(-50%) scale(0.8);
        }

        .card.dragging-right .swipe-indicator-like {
            opacity: 1;
            transform: translateY(-50%) scale(1);
        }

        .card.dragging-left .swipe-indicator-dislike {
            opacity: 1;
            transform: translateY(-50%) scale(1);
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

            .player-control-button {
                width: 36px;
                height: 36px;
            }
        }
    </style>
</head>

<body>
    <div class="site-header">
        <div class="site-logo">
            <h1>MusicMatch</h1>
        </div>
        <div class="nav-user-info"> <a href="dashboard.php" class="back-to-dashboard">Dashboard</a> <a href="profile.php" class="profile-link"> <img src="<?php echo htmlspecialchars($userData->images->url ?? 'img/default-avatar.png'); ?>" alt="Profile Picture"> <span><?php echo htmlspecialchars($userData->display_name); ?></span> </a> <a href="logout.php" class="logout-btn">Logout</a> </div>
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
        let isAnimating = false;

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
            <div class="card-image-container">
                <img src="${track.image}" alt="${track.name}" class="card-image">
                <div class="image-overlay"></div>
                
                <div class="preview-info">30-second preview</div>
                
                <div class="volume-control">
                    <i class="fas fa-volume-up volume-icon"></i>
                    <input type="range" id="volume" min="0" max="100" value="50">
                </div>
                
                <div class="swipe-indicator swipe-indicator-like">
                    <i class="fas fa-heart"></i>
                </div>
                
                <div class="swipe-indicator swipe-indicator-dislike">
                    <i class="fas fa-times"></i>
                </div>
                
                <div class="player-controls-container">
                    <button class="player-control-button" id="toggle-play-button">
                        <i class="fas fa-play" id="play-icon"></i>
                    </button>
                    <div class="progress-container">
                        <div class="progress-bar" id="progress-bar"></div>
                    </div>
                    <div class="time-display">
                        <span id="current-time">0:00</span> / 0:30
                    </div>
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

            // Add pan event for drag indicators
            hammer.on('pan', (e) => {
                if (isAnimating) return;

                const xPos = e.deltaX;

                // Apply rotation and movement during pan
                card.style.transform = `translateX(${xPos}px) rotate(${xPos * 0.03}deg)`;

                // Show appropriate indicator based on drag direction
                if (xPos > 50) {
                    card.classList.add('dragging-right');
                    card.classList.remove('dragging-left');
                } else if (xPos < -50) {
                    card.classList.add('dragging-left');
                    card.classList.remove('dragging-right');
                } else {
                    card.classList.remove('dragging-right', 'dragging-left');
                }

                if (e.isFinal) {
                    // If dragged far enough, trigger swipe
                    if (xPos > 100) {
                        handleSwipe('right');
                    } else if (xPos < -100) {
                        handleSwipe('left');
                    } else {
                        // Reset transform if not enough to trigger swipe
                        card.style.transform = '';
                        card.classList.remove('dragging-right', 'dragging-left');
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
            if (!currentCard || isAnimating) return;

            isAnimating = true;

            // Reset any inline transforms
            currentCard.style.transform = '';
            currentCard.classList.remove('dragging-left', 'dragging-right');

            if (direction === 'left') {
                currentCard.classList.add('swiped-left');
                setTimeout(() => dislikeCurrentTrack(), 100);
            } else if (direction === 'right') {
                currentCard.classList.add('swiped-right');
                setTimeout(() => likeCurrentTrack(), 100);
            }

            // Remove card after animation
            setTimeout(() => {
                if (currentCard) {
                    currentCard.remove();
                    currentCard = null;
                }

                // Move to next track
                currentTrackIndex++;

                // Check if there are more tracks
                if (currentTrackIndex < tracks.length) {
                    setTimeout(() => {
                        createCard(tracks[currentTrackIndex]);
                        isAnimating = false;
                    }, 150);
                } else {
                    swipeContainer.innerHTML = '<div class="no-more-songs">No more songs available</div>';
                    isAnimating = false;
                }
            }, 600);
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
                        currentCard = null;
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
                getOAuthToken: cb => {
                    cb('<?php echo $_SESSION['spotify_access_token']; ?>');
                },
                volume: 0.5
            });

            // Error handling
            player.addListener('initialization_error', ({
                message
            }) => {
                console.error('Initialization error:', message);
            });

            player.addListener('authentication_error', ({
                message
            }) => {
                console.error('Authentication error:', message);
            });

            player.addListener('account_error', ({
                message
            }) => {
                console.error('Account error (Premium required):', message);
            });

            player.addListener('playback_error', ({
                message
            }) => {
                console.error('Playback error:', message);
            });

            // Playback status updates
            player.addListener('player_state_changed', state => {
                if (state && !previewEnded) {
                    isPlaying = !state.paused;
                    updatePlayButton();

                    // Calculate elapsed time since start of 30-second preview
                    const elapsedInPreview = Math.min(state.position - currentStartPosition, previewDuration);

                    // Ensure we don't go below zero
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
            player.addListener('ready', ({
                device_id
            }) => {
                deviceId = device_id;
                console.log('Player ready with device ID:', device_id);
                initializeCards();
            });

            // Connect to the player
            player.connect();
        };

        // Event listeners for Like/Dislike buttons
        likeButton.addEventListener('click', () => {
            if (!isAnimating) {
                handleSwipe('right');
            }
        });

        dislikeButton.addEventListener('click', () => {
            if (!isAnimating) {
                handleSwipe('left');
            }
        });

        // Event listeners for arrow keys
        document.addEventListener('keydown', (e) => {
            if (isAnimating) return;

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
</body>

</html>