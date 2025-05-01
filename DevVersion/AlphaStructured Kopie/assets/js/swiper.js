let player;
let deviceId;
let currentTrackIndex = 0;
let isPlaying = false;
let playbackTimer = null;
let previewDuration = 30000; // 30sec in miliseconds
let currentStartPosition = 0;
let previewEnded = false;
let likedSongs = [];
let currentCard = null;
let isAnimating = false;

console.log(spotifyAccessToken, tracks);

const swipeContainer = document.getElementById("swipe-container");
const likeButton = document.getElementById("like-button");
const dislikeButton = document.getElementById("dislike-button");
const likedSongsList = document.getElementById("liked-songs-list");

function initializeCards() {
    if (tracks.length === 0) {
        swipeContainer.innerHTML =
            '<div class="no-more-songs">No more songs available to swipe</div>';
        return;
    }

    createCard(tracks[currentTrackIndex]);
}

function createCard(track) {
    const card = document.createElement("div");
    card.className = "card";
    card.id = "current-card";

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
                    <img src="./assets/img/playicon.svg" alt="Play/Pause" id="play-icon">
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

    // Hammer.js for swiping
    const hammer = Hammer(card);
    hammer.on('swipeleft', () => handleSwipe('left'));
    hammer.on('swiperight', () => handleSwipe('right'));

    hammer.on('pan', (e) => {
        if(isAnimating) return;

        const xPos = e.deltaX;
        card.style.transform = `translateX(${xPos}px) rotate(${xPos * 0.03}deg)`;

        if(xPos > 50){
            card.classList.add('dragging-right');
            card.classList.remove('dragging-left');
        } else if (xPos < -50){
            card.classList.add('dragging-left');
            card.classList.remove('dragging-right');
        } else{
            card.classList.remove('dragging-right', 'dragging-left');
        }

        if(e.isFinal){
            if(xPos > 100){
                handleSwipe('right');
            } else if (xPos < -100){
                handleSwipe('left');
            } else {
                card.style.transform = '';
                card.classList.remove('dragging-right', 'dragging-left');
            }
        }
    })

    document.getElementById('toggle-play-button').addEventListener('click', togglePlay);
    document.getElementById('volume').addEventListener('input', handleVolumeChange);

    playCurrentTrack();
}

function handleSwipe(direction){
    if(!currentCard || isAnimating) return;
    isAnimating = true;

    currentCard.style.transform = '';
    currentCard.classList.remove('dragging-left', 'dragging-right');

    if(direction === 'left'){
        currentCard.classList.add('swiped-left');
        setTimeout(() => dislikeCurrentTrack(), 100);
    } else if(direction === 'right'){
        currentCard.classList.add('swiped-right');
        setTimeout(() => likeCurrentTrack(), 100);
    }

    setTimeout(() => {
        if(currentCard){
            currentCard.remove();
            currentCard = null;
        }
        currentTrackIndex++;

        if(currentTrackIndex < tracks.length){
            setTimeout(() => {
                createCard(tracks[currentTrackIndex]);
                isAnimating = false;
            }, 150);
        }else{
            swipeContainer.innerHTML = '<div class="no-more-songs">No more songs available to swipe</div>';
            isAnimating = false;
        }
    }, 600);
}

function likeCurrentTrack(){
    const track = tracks[currentTrackIndex];
    likedSongs.push(track);
    updateLikedSongsList();
}

function dislikeCurrentTrack(){}

function updateLikedSongsList(){
    if(likedSongs.length === 0){
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

    document.querySelectorAll('.liked-song-play').forEach(button => {
        button.addEventListener('click', (e) => {
            const index = e.currentTarget.getAttribute('data-index');
            playLikedSong(index);
        });
    });
}

function playLikedSong(index){
    const track = likedSongs[index];
    if(track){
        currentTrackIndex = tracks.findIndex(t => t.id === track.id);
        if(currentTrackIndex !== -1){
            if(currentCard){
                currentCard.remove();
                currentCard = null;
            }
            createCard(track);
            playCurrentTrack();
        }
    }
}

function playCurrentTrack(){
    if(!deviceId) return;
    const track = tracks[currentTrackIndex];
    previewEnded = false;

    if(playbackTimer){
        clearTimeout(playbackTimer);
        playbackTimer = null;
    }

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
            'Authorization': `Bearer ${spotifyAccessToken}`
        },
    })
    .then(response => {
        console.log('Response:', response);
        if (response.status === 204) {
            isPlaying = true;
            updatePlayButton();

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

function updatePlayButton() {
    const playIcon = document.getElementById('play-icon');
    if (isPlaying) {
        playIcon.src = './assets/img/pauseicon.svg';
        playIcon.alt = 'Pause';
    } else {
        playIcon.src = './assets/img/playicon.svg';
        playIcon.alt = 'Play';
    }
}

function handleVolumeChange(e) {
    const volume = e.target.value / 100;
    player.setVolume(volume);
}


// Initialize Spotify Player
window.onSpotifyWebPlaybackSDKReady = () => {
    player = new Spotify.Player({
        name: 'MusicMatch Web Player',
        getOAuthToken: cb => {
            cb(spotifyAccessToken);
        },
        volume: 0.5
    });

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

    player.addListener('player_state_changed', state => {
        if (state && !previewEnded) {
            isPlaying = !state.paused;
            updatePlayButton();

            // Calculate elapsed time since start of 30-second preview
            const elapsedInPreview = Math.min(state.position - currentStartPosition, previewDuration);
            const clampedElapsed = Math.max(0, elapsedInPreview);
            const progress = (clampedElapsed / previewDuration) * 100;
            document.getElementById('progress-bar').style.width = `${progress}%`;
            document.getElementById('current-time').textContent = formatTime(clampedElapsed);

            if (elapsedInPreview >= previewDuration && isPlaying) {
                player.pause().then(() => {
                    isPlaying = false;
                    previewEnded = true;
                    updatePlayButton();
                    document.getElementById('progress-bar').style.width = '100%';
                    document.getElementById('current-time').textContent = '0:30';

                    if (playbackTimer) {
                        clearTimeout(playbackTimer);
                        playbackTimer = null;
                    }
                });
            }
        }
    });

    player.addListener('ready', ({
        device_id
    }) => {
        deviceId = device_id;
        console.log('Player ready with device ID:', device_id);
        initializeCards();
    });
    player.connect();
};

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
