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
let progressUpdateTimer = null;
let isCreatingPlaylist = false;
let typingTimer;
const doneTypingInterval = 300; // Wait time after user stops typing
const BASE_API_URL =
    window.location.hostname.includes("localhost") ||
    window.location.hostname === "127.0.0.1"
        ? "/"
        : "/~fhs52920/mmp1/";

const swipeContainer = document.getElementById("swipe-container");
const likeButton = document.getElementById("like-button");
const dislikeButton = document.getElementById("dislike-button");
const likedSongsList = document.getElementById("liked-songs-list");

document.addEventListener("DOMContentLoaded", function () {
    likedSongs = loadLikedSongs();

    if (likedSongs.length > 0) {
        updateLikedSongsList();
        saveLikedSongs(likedSongs);
    }

    setupResetButton();
    setInitialState();
    initializeCards();
    initializeEventListeners();

    const swipeMethod = document.getElementById("swipe-method");
    const playlistInputGroup = document.getElementById("playlist-input-group");
    const lastFmInputGroup = document.getElementById("lasFm-input-group");
    const form = document.getElementById("swipe-form");

    if (swipeMethod) {
        swipeMethod.addEventListener("change", function (e) {
            const shouldSubmit = updateFormInputs();
            if (!shouldSubmit) {
                e.preventDefault();
            }
        });
    }

    if (lastFmInputGroup) {
        const lastFmSubmitBtn = lastFmInputGroup.querySelector("button");
        if (lastFmSubmitBtn) {
            lastFmSubmitBtn.addEventListener("click", function (e) {
                e.preventDefault();
                
                const lastFmInput = document.getElementById("lasFm-username");
                if (!lastFmInput || !lastFmInput.value.trim()) {
                    if (lastFmInput) lastFmInput.focus();
                    return false;
                }

                const timestampField = document.querySelector('input[name="timestamp"]');
                if (timestampField) {
                    timestampField.value = Date.now();
                } else {
                    const newTimestamp = document.createElement("input");
                    newTimestamp.type = "hidden";
                    newTimestamp.name = "timestamp";
                    newTimestamp.value = Date.now();
                    document.getElementById("swipe-form").appendChild(newTimestamp);
                }
                
                showLoadingOverlay(`Loading LastFM recommendations for ${lastFmInput.value}...`);

                setTimeout(() => {
                    document.getElementById("swipe-form").submit();
                }, 50);
            });
        }
    }

    const artistInputGroup = document.getElementById("artist-input-group");
    if (artistInputGroup) {
        const artistSubmitBtn = artistInputGroup.querySelector("button");
        if (artistSubmitBtn) {
            artistSubmitBtn.addEventListener("click", function (e) {
                const artistInput = document.getElementById("artist-name");
                if (!artistInput || !artistInput.value.trim()) {
                    e.preventDefault();
                    if (artistInput) artistInput.focus();
                    return false;
                }
                return true;
            });
        }
    }
});

const swipeForm = document.getElementById("swipe-form");
if (swipeForm) {
    swipeForm.addEventListener("submit", function (e) {
        if (likedSongs && likedSongs.length > 0) {
            localStorage.setItem(
                "musicmatch_liked_songs",
                JSON.stringify(likedSongs)
            );

            fetch(BASE_API_URL + "includes/create_playlist.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ songs: likedSongs }),
                keepalive: true,
            });
        }
    });
}

function showLoadingOverlay(message) {
    let loadingOverlay = document.getElementById("loading-overlay");
    if (!loadingOverlay) {
        loadingOverlay = document.createElement("div");
        loadingOverlay.id = "loading-overlay";
        loadingOverlay.className = "loading-overlay";
        loadingOverlay.innerHTML = `
            <div class="spinner"></div>
            <p class="loading-text">${message}</p>
        `;
        document.body.appendChild(loadingOverlay);
    } else {
        loadingOverlay.querySelector('.loading-text').innerText = message;
    }
    sessionStorage.setItem('musicmatch_loading', 'true');
    sessionStorage.setItem('musicmatch_loading_message', message);
    

    setTimeout(() => {
        loadingOverlay.classList.add("active");
    }, 10);
}

function updateFormInputs() {
    const swipeMethod = document.getElementById("swipe-method");
    const playlistInputGroup = document.getElementById("playlist-input-group");
    const lastFmInputGroup = document.getElementById("lasFm-input-group");
    const artistInputGroup = document.getElementById("artist-input-group");
    const form = document.getElementById("swipe-form");
    const inputGroup = document.querySelector(".input-group");
    const playlistInput = document.getElementById("playlist-link");
    const lastFmInput = document.getElementById("lasFm-username");
    const artistInput = document.getElementById("artist-name");

    if (playlistInput) playlistInput.removeAttribute("required");
    if (lastFmInput) lastFmInput.removeAttribute("required");
    if (artistInput) artistInput.removeAttribute("required");

    if (playlistInputGroup) playlistInputGroup.classList.remove("visible");
    if (lastFmInputGroup) lastFmInputGroup.classList.remove("visible");
    if (artistInputGroup) artistInputGroup.classList.remove("visible");

    if (inputGroup) {
        inputGroup.classList.remove("playlist-mode");
        inputGroup.classList.remove("lastfm-mode");
        inputGroup.classList.remove("artist-mode");
    }

    if (swipeMethod.value === "playlist" && playlistInputGroup) {
        playlistInputGroup.classList.add("visible");
        if (inputGroup) inputGroup.classList.add("playlist-mode");
        if (playlistInput) playlistInput.setAttribute("required", "required");
        return false;
    } else if (swipeMethod.value === "lastFM" && lastFmInputGroup) {
        lastFmInputGroup.classList.add("visible");
        if (inputGroup) inputGroup.classList.add("lastfm-mode");
        if (lastFmInput) lastFmInput.setAttribute("required", "required");
        return false;
    } else if (swipeMethod.value === "artist" && artistInputGroup) {
        artistInputGroup.classList.add("visible");
        if (inputGroup) inputGroup.classList.add("artist-mode");
        if (artistInput) artistInput.setAttribute("required", "required");
        return false;
    } else if (form) {
        form.submit();
        return true;
    }

    return false;
}

function initializeCards() {
    if (tracks.length === 0) {
        swipeContainer.innerHTML =
            '<div class="no-more-songs">No more songs available to swipe</div>';
        return;
    }
    swipeContainer.innerHTML = "";
    createCard(tracks[currentTrackIndex]);
    currentCard = document.getElementById("current-card");

    if (deviceId) {
        playCurrentTrack();
    } else {
        const progressBar = document.getElementById("progress-bar");
        if (progressBar) progressBar.style.width = "0%";

        const currentTime = document.getElementById("current-time");
        if (currentTime) currentTime.textContent = "Loading...";

        console.log("Waiting for Spotify player to initialize...");
    }
}

function createCard(track) {
    swipeContainer.innerHTML = "";

    const card = document.createElement("div");
    card.className = "card";
    card.id = "current-card";

    card.innerHTML = `
        <div class="card-image-container">
            <img src="${track.image}" alt="${track.name}" class="card-image">
            <div class="image-overlay"></div>
                
            <div class="preview-info">30-second preview</div>
                
            <div class="volume-control">
                <img src="${BASE_API_URL}assets/img/icons/volume.svg" alt="Volume">
                <input type="range" id="volume" min="0" max="100" value="50">
            </div>
                
            <div class="swipe-indicator swipe-indicator-like">
                <img src="${BASE_API_URL}assets/img/icons/like.svg" alt="Like">
            </div>
                
            <div class="swipe-indicator swipe-indicator-dislike">
                <img src="${BASE_API_URL}assets/img/icons/dislike.svg" alt="Dislike">
            </div>
                
            <div class="player-controls-container">
                <button class="player-control-button" id="toggle-play-button">
                    <img src="${BASE_API_URL}assets/img/icons/play.svg" alt="Play/Pause" id="play-icon">
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
                <h2 class="card-title text-center">${track.name}</h2>
                <p class="card-artist">${track.artist}</p>
                <p class="card-album">${track.album}</p>
                <a href="${track.spotify_url}" class="spotify-button" target="_blank">
                    <img class="spotify-icon" src="${BASE_API_URL}assets/img/icons/spotify-primary-white.svg" alt="Spotify">
                    <span>Listen on Spotify</span>
                </a>
            </div>
        </div>
    `;

    swipeContainer.appendChild(card);
    currentCard = card;

    const playButton = document.getElementById("toggle-play-button");
    if (playButton) {
        playButton.addEventListener("click", togglePlay);
    }

    const volumeSlider = document.getElementById("volume");
    if (volumeSlider) {
        volumeSlider.addEventListener("input", handleVolumeChange);

        volumeSlider.addEventListener(
            "touchstart",
            function (e) {
                e.stopPropagation();
            },
            { passive: false }
        );

        volumeSlider.addEventListener(
            "touchmove",
            function (e) {
                e.stopPropagation();
            },
            { passive: false }
        );

        volumeSlider.addEventListener("mousedown", function (e) {
            e.stopPropagation();
        });

        volumeSlider.addEventListener("click", function (e) {
            e.stopPropagation();
        });
    }

    const volumeControl = card.querySelector(".volume-control");
    if (volumeControl) {
        volumeControl.addEventListener(
            "touchstart",
            function (e) {
                e.stopPropagation();
            },
            { passive: false }
        );

        volumeControl.addEventListener(
            "touchmove",
            function (e) {
                e.stopPropagation();
            },
            { passive: false }
        );

        volumeControl.addEventListener("mousedown", function (e) {
            e.stopPropagation();
        });

        volumeControl.addEventListener("click", function (e) {
            e.stopPropagation();
        });
    }

    const hammer = new Hammer(card);

    hammer.get("pan").set({
        direction: Hammer.DIRECTION_HORIZONTAL,
        threshold: 10,
    });

    hammer.get("swipe").set({
        direction: Hammer.DIRECTION_HORIZONTAL,
        threshold: 30,
        velocity: 0.3,
    });

    let isScrolling = false;
    let startY = 0;
    let startX = 0;

    card.addEventListener(
        "touchstart",
        function (e) {
            startY = e.touches[0].clientY;
            startX = e.touches[0].clientX;
            isScrolling = false;
        },
        { passive: true }
    );

    card.addEventListener(
        "touchmove",
        function (e) {
            if (isScrolling) return;

            const currentY = e.touches[0].clientY;
            const currentX = e.touches[0].clientX;
            const deltaY = Math.abs(currentY - startY);
            const deltaX = Math.abs(currentX - startX);

            if (deltaY > deltaX * 1.5 && deltaY > 20) {
                isScrolling = true;
            }
        },
        { passive: true }
    );

    hammer.on("swipeleft", () => {
        if (!isScrolling) handleSwipe("left");
    });

    hammer.on("swiperight", () => {
        if (!isScrolling) handleSwipe("right");
    });

    hammer.on("pan", (e) => {
        if (isAnimating || isScrolling) return;
        if (Math.abs(e.deltaY) > Math.abs(e.deltaX) * 1.8) {
            return;
        }

        const xPos = e.deltaX;
        card.style.transform = `translateX(${xPos}px) rotate(${
            xPos * 0.03
        }deg)`;
        if (xPos > 40) {
            card.classList.add("dragging-right");
            card.classList.remove("dragging-left");
        } else if (xPos < -40) {
            card.classList.add("dragging-left");
            card.classList.remove("dragging-right");
        } else {
            card.classList.remove("dragging-right", "dragging-left");
        }

        if (e.isFinal) {
            if (xPos > 80) {
                handleSwipe("right");
            } else if (xPos < -80) {
                handleSwipe("left");
            } else {
                card.style.transform = "";
                card.classList.remove("dragging-right", "dragging-left");
            }
        }
    });
    playCurrentTrack();
}

function handleSwipe(direction) {
    if (!currentCard || isAnimating) return;
    isAnimating = true;
    currentCard.style.transform = "";
    currentCard.classList.remove("dragging-left", "dragging-right");

    if (direction === "left") {
        currentCard.classList.add("swiped-left");
        dislikeCurrentTrack();
    } else if (direction === "right") {
        currentCard.classList.add("swiped-right");
        likeCurrentTrack();
    }
    const cardToRemove = currentCard;
    currentCard = null;

    setTimeout(() => {
        if (cardToRemove && cardToRemove.parentElement) {
            cardToRemove.parentElement.removeChild(cardToRemove);
        }

        document
            .querySelectorAll("#swipe-container > .card")
            .forEach((oldCard) => {
                oldCard.parentElement.removeChild(oldCard);
            });

        currentTrackIndex++;
        if (currentTrackIndex < tracks.length) {
            createCard(tracks[currentTrackIndex]);
            isAnimating = false;
        } else {
            swipeContainer.innerHTML =
                '<div class="no-more-songs">No more songs available to swipe</div>';
            isAnimating = false;
        }
    }, 600);
}

function likeCurrentTrack() {
    const track = tracks[currentTrackIndex];

    if (!likedSongs.some((song) => song.id === track.id)) {
        likedSongs.push(track);
        updateLikedSongsList();
        saveLikedSongs(likedSongs);
    }
}

function dislikeCurrentTrack() {}

function updateLikedSongsList() {
    if (likedSongs.length === 0) {
        likedSongsList.innerHTML =
            '<li class="no-liked-songs">No songs liked yet</li>';
        return;
    }

    likedSongsList.innerHTML = "";
    likedSongs.forEach((song, index) => {
        const li = document.createElement("li");
        li.className = "liked-song-item";
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
                <img class="spotify-icon-sm" src="${BASE_API_URL}assets/img/icons/spotify-primary-green.svg" alt="Open on Spotify">
            </a>
            <button class="liked-song-remove" data-index="${index}">
                <img src="${BASE_API_URL}assets/img/icons/remove.svg" alt="Remove" width="16" height="16">
            </button>
        `;

        likedSongsList.appendChild(li);
    });

    document.querySelectorAll(".liked-song-play").forEach((button) => {
        button.addEventListener("click", (e) => {
            const index = e.currentTarget.getAttribute("data-index");
            playLikedSong(index);
        });
    });

    document.querySelectorAll(".liked-song-remove").forEach((button) => {
        button.addEventListener("click", (e) => {
            const index = parseInt(e.currentTarget.getAttribute("data-index"));
            removeLikedSong(index);
        });
    });
}

function removeLikedSong(index) {
    if (index >= 0 && index < likedSongs.length) {
        const songName = likedSongs[index].name;
        likedSongs.splice(index, 1);
        updateLikedSongsList();
        saveLikedSongs(likedSongs);
        showNotification(`"${songName}" removed from liked songs`, "info");
    }
}

function playLikedSong(index) {
    const track = likedSongs[index];
    if (track) {
        currentTrackIndex = tracks.findIndex((t) => t.id === track.id);
        if (currentTrackIndex !== -1) {
            if (currentCard) {
                currentCard.remove();
                currentCard = null;
            }
            createCard(track);
            playCurrentTrack();
        }
    }
}

function playCurrentTrack() {
    /*
    if (!deviceId) {
        console.error("No device ID available");
        return;
    }
    */

    if (!tracks || currentTrackIndex >= tracks.length) {
        console.error("No tracks available or invalid track index");
        return;
    }

    if (playbackTimer) {
        clearTimeout(playbackTimer);
        playbackTimer = null;
    }

    if (progressUpdateTimer) {
        clearInterval(progressUpdateTimer);
        progressUpdateTimer = null;
    }

    const track = tracks[currentTrackIndex];
    previewEnded = false;

    const startPercentage = 0.3 + Math.random() * 0.1;
    currentStartPosition = Math.floor(track.duration_ms * startPercentage);

    console.log(
        `Playing track: ${track.name} (${track.uri}) from position ${currentStartPosition}ms`
    );

    // Play the track
    fetch(`https://api.spotify.com/v1/me/player/play?device_id=${deviceId}`, {
        method: "PUT",
        body: JSON.stringify({
            uris: [track.uri],
            position_ms: currentStartPosition,
        }),
        headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${spotifyAccessToken}`,
        },
    })
        .then((response) => {
            if (response.status === 204) {
                isPlaying = true;
                updatePlayButton();
                startProgressUpdates();

                playbackTimer = setTimeout(() => {
                    stopPreview();
                }, previewDuration + 2000);
            } else {
                return response.json().then((data) => {
                    throw new Error(
                        `Error playing track: ${JSON.stringify(data)}`
                    );
                });
            }
        })
        .catch((error) => {
            console.error("Playback error:", error);
            isPlaying = false;
            updatePlayButton();
        });
}

function stopPreview() {
    if (!isPlaying) return;

    player
        .pause()
        .then(() => {
            isPlaying = false;
            previewEnded = true;
            updatePlayButton();

            const progressBar = document.getElementById("progress-bar");
            if (progressBar) progressBar.style.width = "100%";

            const currentTime = document.getElementById("current-time");
            if (currentTime) currentTime.textContent = "0:30";

            if (progressUpdateTimer) {
                clearInterval(progressUpdateTimer);
                progressUpdateTimer = null;
            }

            if (playbackTimer) {
                clearTimeout(playbackTimer);
                playbackTimer = null;
            }
        })
        .catch((err) => {
            console.error("Error stopping playback:", err);
        });
}

function updatePlayButton() {
    const playIcon = document.getElementById("play-icon");
    if (!playIcon) return;

    if (isPlaying) {
        playIcon.src = "./assets/img/icons/pause.svg";
        playIcon.alt = "Pause";
    } else {
        playIcon.src = "./assets/img/icons/play.svg";
        playIcon.alt = "Play";
    }
}

function togglePlay() {
    if (isPlaying) {
        player
            .pause()
            .then(() => {
                isPlaying = false;
                updatePlayButton();
            })
            .catch((error) => {
                console.error("Error pausing:", error);
            });
    } else {
        if (previewEnded) {
            playCurrentTrack();
        } else {
            player
                .resume()
                .then(() => {
                    isPlaying = true;
                    updatePlayButton();
                })
                .catch((error) => {
                    console.error("Error resuming:", error);
                });
        }
    }
}

function handleVolumeChange(e) {
    const volume = e.target.value / 100;
    player.setVolume(volume);
}

// Initialize Spotify Player
function initializeSpotifyPlayer() {
    player = new Spotify.Player({
        name: "MusicMatch Web Player",
        getOAuthToken: (cb) => {
            cb(spotifyAccessToken);
        },
        volume: 0.5,
    });

    player.addListener("initialization_error", ({ message }) => {
        console.error("Initialization error:", message);
    });

    player.addListener("authentication_error", ({ message }) => {
        console.error("Authentication error:", message);
    });

    player.addListener("account_error", ({ message }) => {
        console.error("Account error (Premium required):", message);
    });

    player.addListener("playback_error", ({ message }) => {
        console.error("Playback error:", message);
    });

    player.addListener("player_state_changed", (state) => {
        if (!state || previewEnded) return;

        isPlaying = !state.paused;
        updatePlayButton();

        const elapsedInPreview = Math.min(
            state.position - currentStartPosition,
            previewDuration
        );

        if (state.paused && state.position === 0) {
            console.log("Track ended naturally by Spotify");
            stopPreview();
            return;
        }
        if (elapsedInPreview >= previewDuration && isPlaying) {
            stopPreview();
        }
    });

    player.addListener("ready", ({ device_id }) => {
        deviceId = device_id;
        console.log("Player ready with device ID:", device_id);

        if (currentCard && !isPlaying) {
            playCurrentTrack(); // Start playing when device is ready
        } else {
            initializeCards();
        }
    });

    player.connect();
};

if (typeof Spotify !== 'undefined') {
    console.log("Spotify already loaded, initializing player");
    initializeSpotifyPlayer();
}

function formatTime(ms) {
    const totalSeconds = Math.floor(ms / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${minutes}:${seconds.toString().padStart(2, "0")}`;
}

function startProgressUpdates() {
    if (progressUpdateTimer) {
        clearInterval(progressUpdateTimer);
    }
    progressUpdateTimer = setInterval(() => {
        player
            .getCurrentState()
            .then((state) => {
                if (!state) {
                    console.log("No playback state available");
                    return;
                }

                if (isPlaying && !previewEnded) {
                    const elapsedInPreview = Math.min(
                        state.position - currentStartPosition,
                        previewDuration
                    );
                    const clampedElapsed = Math.max(0, elapsedInPreview);
                    const progress = (clampedElapsed / previewDuration) * 100;

                    const progressBar = document.getElementById("progress-bar");
                    const currentTime = document.getElementById("current-time");

                    if (progressBar) {
                        progressBar.style.width = `${progress}%`;
                    }

                    if (currentTime) {
                        currentTime.textContent = formatTime(clampedElapsed);
                    }

                    // Stop at 30 seconds
                    if (elapsedInPreview >= previewDuration) {
                        stopPreview();
                    }
                }
            })
            .catch((err) => {
                console.error("Error getting player state:", err);
            });
    }, 100);
}

function searchArtists(query) {
    const suggestionsContainer = document.getElementById("artist-suggestions");

    fetch(
        `${BASE_API_URL}includes/search_artists.php?q=${encodeURIComponent(
            query
        )}`
    )
        .then((response) => response.json())
        .then((data) => {
            suggestionsContainer.innerHTML = "";

            if (data.artists && data.artists.length > 0) {
                data.artists.forEach((artist) => {
                    const suggestion = document.createElement("div");
                    suggestion.className = "artist-suggestion";
                    suggestion.innerHTML = `
                        <img src="${
                            artist.image || "./assets/img/default-avatar.png"
                        }" alt="${artist.name}" class="artist-suggestion-image">
                        <span>${artist.name}</span>
                    `;
                    suggestion.addEventListener("click", function () {
                        document.getElementById("artist-name").value =
                            artist.name;
                        document.getElementById("artist-id").value = artist.id;
                        suggestionsContainer.style.display = "none";
                    });
                    suggestionsContainer.appendChild(suggestion);
                });
                suggestionsContainer.style.display = "block";
            } else {
                suggestionsContainer.style.display = "none";
            }
        })
        .catch((error) => {
            console.error("Error searching artists:", error);
            suggestionsContainer.style.display = "none";
        });
}

window.addEventListener("beforeunload", function () {
    if (likedSongs && likedSongs.length > 0) {
        localStorage.setItem(
            "musicmatch_liked_songs",
            JSON.stringify(likedSongs)
        );
    }
});

function saveLikedSongs(songs) {
    // Save to localStorage
    try {
        localStorage.setItem("musicmatch_liked_songs", JSON.stringify(songs));
    } catch (error) {
        console.error("Error saving to localStorage:", error);
    }

    // Save to session via AJAX
    return fetch(BASE_API_URL + "includes/save_liked_track.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ songs: songs }),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .catch((error) => {
            console.error("Error saving liked tracks:", error);
        });
}

window.addEventListener("beforeunload", function () {
    if (likedSongs && likedSongs.length > 0) {
        localStorage.setItem(
            "musicmatch_liked_songs",
            JSON.stringify(likedSongs)
        );
    }
});

function loadLikedSongs() {
    let loadedSongs = [];

    try {
        const savedSongs = localStorage.getItem("musicmatch_liked_songs");
        if (savedSongs) {
            const parsedSongs = JSON.parse(savedSongs);
            if (Array.isArray(parsedSongs) && parsedSongs.length > 0) {
                loadedSongs = parsedSongs;
                console.log(
                    `Loaded ${loadedSongs.length} songs from localStorage`
                );
            }
        }
    } catch (error) {
        console.error("Error loading songs from localStorage:", error);
    }

    if (
        loadedSongs.length === 0 &&
        typeof initialLikedSongs !== "undefined" &&
        initialLikedSongs &&
        initialLikedSongs.length > 0
    ) {
        loadedSongs = [...initialLikedSongs];
        console.log(`Loaded ${loadedSongs.length} songs from session`);
    }

    return loadedSongs;
}

document.addEventListener("DOMContentLoaded", function () {
    const resetBtn = document.getElementById("reset-tracks-btn");
    const resetConfirmation = document.getElementById("reset-confirmation");
    const cancelResetBtn = document.getElementById("cancel-reset-btn");

    const createBtn = document.getElementById("create-playlist-btn");
    const createConfirmation = document.getElementById(
        "create-playlist-confirmation"
    );
    const cancelCreateBtn = document.getElementById("cancel-create-btn");
    const confirmCreateBtn = document.getElementById("confirm-create-btn");

    const addBtn = document.getElementById("add-to-playlist-btn");
    const addConfirmation = document.getElementById(
        "add-to-playlist-confirmation"
    );
    const cancelAddBtn = document.getElementById("cancel-add-btn");
    const confirmAddBtn = document.getElementById("confirm-add-btn");
    const playlistSelect = document.getElementById("existing-playlist-select");

    function hideAllConfirmations() {
        resetConfirmation.style.display = "none";
        createConfirmation.style.display = "none";
        addConfirmation.style.display = "none";

        resetBtn.classList.remove("button-hidden");
        createBtn.classList.remove("button-hidden");
        addBtn.classList.remove("button-hidden");
    }

    if (resetBtn) {
        resetBtn.addEventListener("click", function () {
            hideAllConfirmations();
            resetConfirmation.style.display = "block";

            resetBtn.classList.add("button-hidden");
            createBtn.classList.add("button-hidden");
            addBtn.classList.add("button-hidden");
        });
    }

    if (cancelResetBtn) {
        cancelResetBtn.addEventListener("click", function () {
            resetConfirmation.style.display = "none";

            resetBtn.classList.remove("button-hidden");
            createBtn.classList.remove("button-hidden");
            addBtn.classList.remove("button-hidden");
        });
    }

    if (createBtn) {
        createBtn.addEventListener("click", function () {
            if (likedSongs.length === 0) {
                showNotification("You need to like some songs first!", "error");
                return;
            }

            hideAllConfirmations();
            createConfirmation.style.display = "block";
            createBtn.classList.add("button-hidden");
            addBtn.classList.add("button-hidden");
            resetBtn.classList.add("button-hidden");
        });
    }

    if (cancelCreateBtn) {
        cancelCreateBtn.addEventListener("click", function () {
            createConfirmation.style.display = "none";
            createBtn.classList.remove("button-hidden");
            addBtn.classList.remove("button-hidden");
            resetBtn.classList.remove("button-hidden");
        });
    }

    if (addBtn) {
        addBtn.addEventListener("click", function () {
            if (likedSongs.length === 0) {
                showNotification("You need to like some songs first!", "error");
                return;
            }

            hideAllConfirmations();
            addConfirmation.style.display = "block";

            addBtn.classList.add("button-hidden");
            createBtn.classList.add("button-hidden");
            resetBtn.classList.add("button-hidden");

            playlistSelect.innerHTML =
                '<option value="" disabled selected>Loading playlists...</option>';
            confirmAddBtn.disabled = true;

            fetch(BASE_API_URL + "includes/get_playlists.php")
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(
                            "Server returned status " + response.status
                        );
                    }
                    return response.json();
                })
                .then((data) => {
                    playlistSelect.innerHTML =
                        '<option value="" disabled selected>Select a playlist...</option>';

                    if (
                        data.success &&
                        data.playlists &&
                        data.playlists.length > 0
                    ) {
                        data.playlists.forEach((playlist) => {
                            const option = document.createElement("option");
                            option.value = playlist.id;
                            option.textContent = `${playlist.name} (${playlist.tracks} tracks)`;
                            option.dataset.playlistName = playlist.name;
                            playlistSelect.appendChild(option);
                        });
                    } else {
                        playlistSelect.innerHTML =
                            '<option value="" disabled selected>No playlists available</option>';
                    }
                })
                .catch((error) => {
                    console.error("Error loading playlists:", error);
                    playlistSelect.innerHTML =
                        '<option value="" disabled selected>Error loading playlists</option>';
                });
        });
        const artistInput = document.getElementById("artist-name");
        const suggestionsContainer =
            document.getElementById("artist-suggestions");

        if (artistInput) {
            artistInput.addEventListener("input", function () {
                clearTimeout(typingTimer);

                if (this.value.length < 2) {
                    suggestionsContainer.innerHTML = "";
                    suggestionsContainer.style.display = "none";
                    return;
                }

                typingTimer = setTimeout(
                    () => searchArtists(this.value),
                    doneTypingInterval
                );
            });
            document.addEventListener("click", function (e) {
                if (
                    e.target !== artistInput &&
                    e.target !== suggestionsContainer
                ) {
                    suggestionsContainer.style.display = "none";
                }
            });
        }
        const artistInputGroup = document.getElementById("artist-input-group");
        if (artistInputGroup) {
            const artistSubmitBtn = artistInputGroup.querySelector("button");
            if (artistSubmitBtn) {
                artistSubmitBtn.addEventListener("click", function (e) {
                    e.preventDefault();

                    const artistInput = document.getElementById("artist-name");
                    if (!artistInput || !artistInput.value.trim()) {
                        if (artistInput) artistInput.focus();
                        return false;
                    }
                    const timestampField = document.querySelector(
                        'input[name="timestamp"]'
                    );
                    if (timestampField) {
                        timestampField.value = Date.now();
                    } else {
                        const newTimestamp = document.createElement("input");
                        newTimestamp.type = "hidden";
                        newTimestamp.name = "timestamp";
                        newTimestamp.value = Date.now();
                        document
                            .getElementById("swipe-form")
                            .appendChild(newTimestamp);
                    }
                    showLoadingOverlay(
                        `Loading tracks for ${artistInput.value}...`
                    );

                    setTimeout(() => {
                        document.getElementById("swipe-form").submit();
                    }, 50);
                });
            }
        }
    }

    if (cancelAddBtn) {
        cancelAddBtn.addEventListener("click", function () {
            addConfirmation.style.display = "none";

            addBtn.classList.remove("button-hidden");
            createBtn.classList.remove("button-hidden");
            resetBtn.classList.remove("button-hidden");
        });
    }

    if (playlistSelect) {
        playlistSelect.addEventListener("change", function () {
            confirmAddBtn.disabled = !this.value;
        });
    }

    if (confirmAddBtn) {
        confirmAddBtn.addEventListener("click", function () {
            const selectedOption =
                playlistSelect.options[playlistSelect.selectedIndex];
            const playlistId = playlistSelect.value;
            const playlistName = selectedOption.dataset.playlistName;

            if (!playlistId) {
                showNotification("Please select a playlist first", "error");
                return;
            }

            const clearLikes = document.getElementById(
                "clear-liked-songs-add"
            ).checked;

            confirmAddBtn.disabled = true;
            confirmAddBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';

            fetch(BASE_API_URL + "includes/create_playlist.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    playlist_id: playlistId,
                    name: playlistName,
                    tracks: likedSongs.map((song) => song.uri),
                    clear_liked_songs: clearLikes,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        showNotification(
                            `Songs added to "${playlistName}" successfully!`,
                            "success"
                        );

                        if (data.cleared_likes) {
                            likedSongs = [];
                            localStorage.removeItem("musicmatch_liked_songs");
                            updateLikedSongsList();
                        }

                        addConfirmation.style.display = "none";

                        addBtn.classList.remove("button-hidden");
                        createBtn.classList.remove("button-hidden");
                        resetBtn.classList.remove("button-hidden");
                    } else {
                        showNotification(
                            "Failed to add songs: " + data.message,
                            "error"
                        );
                    }
                    confirmAddBtn.disabled = false;
                    confirmAddBtn.innerHTML = "Add to Playlist";
                })
                .catch((error) => {
                    console.error("Error adding songs:", error);
                    showNotification(
                        "Failed to add songs: " + error.message,
                        "error"
                    );
                    confirmAddBtn.disabled = false;
                    confirmAddBtn.innerHTML = "Add to Playlist";
                });
        });
    }

    if (confirmCreateBtn) {
        confirmCreateBtn.addEventListener("click", function () {
            const playlistName = document.getElementById("playlist-name").value;
            if (!playlistName.trim()) {
                showNotification("Please enter a playlist name", "error");
                return;
            }

            const clearLikes =
                document.getElementById("clear-liked-songs").checked;

            confirmCreateBtn.disabled = true;
            confirmCreateBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';

            fetch(BASE_API_URL + "includes/create_playlist.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    name: playlistName,
                    tracks: likedSongs.map((song) => song.uri),
                    clear_liked_songs: clearLikes,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        showNotification(
                            `Playlist "${playlistName}" created successfully!`,
                            "success"
                        );

                        if (data.cleared_likes) {
                            likedSongs = [];
                            localStorage.removeItem("musicmatch_liked_songs");
                            updateLikedSongsList();
                        }

                        createConfirmation.style.display = "none";
                        createBtn.classList.remove("button-hidden");
                        addBtn.classList.remove("button-hidden");
                        resetBtn.classList.remove("button-hidden");
                    } else {
                        showNotification(
                            "Failed to create playlist: " + data.message,
                            "error"
                        );
                    }
                    confirmCreateBtn.disabled = false;
                    confirmCreateBtn.innerHTML = "Create Playlist";
                })
                .catch((error) => {
                    console.error("Error creating playlist:", error);
                    showNotification(
                        "Failed to create playlist: " + data.message,
                        "error"
                    );
                    confirmCreateBtn.disabled = false;
                    confirmCreateBtn.innerHTML = "Create Playlist";
                });
        });
    }
});

function setInitialState() {
    const swipeMethod = document.getElementById("swipe-method");
    const playlistInputGroup = document.getElementById("playlist-input-group");
    const lastFmInputGroup = document.getElementById("lasFm-input-group");
    const artistInputGroup = document.getElementById("artist-input-group");
    const inputGroup = document.querySelector(".input-group");

    if (swipeMethod.value === "playlist") {
        playlistInputGroup.classList.add("visible");
        inputGroup.classList.add("playlist-mode");
    } else if (swipeMethod.value === "lastFM") {
        lastFmInputGroup.classList.add("visible");
        inputGroup.classList.add("lastfm-mode");
    } else if (swipeMethod.value === "artist") {
        artistInputGroup.classList.add("visible");
        inputGroup.classList.add("artist-mode");
    } else {
        playlistInputGroup.classList.remove("visible");
        lastFmInputGroup.classList.remove("visible");
        artistInputGroup.classList.remove("visible");
        inputGroup.classList.remove("playlist-mode");
        inputGroup.classList.remove("lastfm-mode");
        inputGroup.classList.remove("artist-mode");
    }
}

function initializeEventListeners() {
    document
        .getElementById("like-button")
        .addEventListener("click", () => handleSwipe("right"));
    document
        .getElementById("dislike-button")
        .addEventListener("click", () => handleSwipe("left"));

    const resetBtn = document.getElementById("reset-tracks-btn");
    const createBtn = document.getElementById("create-playlist-btn");
    const addBtn = document.getElementById("add-to-playlist-btn");

    if (resetBtn) {
        resetBtn.addEventListener("click", showResetConfirmation);
    }

    if (createBtn) {
        createBtn.addEventListener("click", showCreateConfirmation);
    }

    if (addBtn) {
        addBtn.addEventListener("click", showAddConfirmation);
    }

    setupConfirmationDialogs();
}

function showResetConfirmation() {
    const resetConfirmation = document.getElementById("reset-confirmation");
    const createBtn = document.getElementById("create-playlist-btn");
    const addBtn = document.getElementById("add-to-playlist-btn");
    const resetBtn = document.getElementById("reset-tracks-btn");

    hideAllConfirmations();

    resetConfirmation.style.display = "block";
    resetBtn.classList.add("button-hidden");
    createBtn.classList.add("button-hidden");
    addBtn.classList.add("button-hidden");
}

function showCreateConfirmation() {
    if (likedSongs.length === 0) {
        showNotification("You need to like some songs first!", "error");
        return;
    }

    const createConfirmation = document.getElementById(
        "create-playlist-confirmation"
    );
    const createBtn = document.getElementById("create-playlist-btn");
    const addBtn = document.getElementById("add-to-playlist-btn");
    const resetBtn = document.getElementById("reset-tracks-btn");

    hideAllConfirmations();

    createConfirmation.style.display = "block";
    createBtn.classList.add("button-hidden");
    addBtn.classList.add("button-hidden");
    resetBtn.classList.add("button-hidden");
}

function showAddConfirmation() {
    if (likedSongs.length === 0) {
        showNotification("You need to like some songs first!", "error");
        return;
    }

    const addConfirmation = document.getElementById(
        "add-to-playlist-confirmation"
    );
    const createBtn = document.getElementById("create-playlist-btn");
    const addBtn = document.getElementById("add-to-playlist-btn");
    const resetBtn = document.getElementById("reset-tracks-btn");

    hideAllConfirmations();

    addConfirmation.style.display = "block";
    createBtn.classList.add("button-hidden");
    addBtn.classList.add("button-hidden");
    resetBtn.classList.add("button-hidden");
    loadUserPlaylists();
}

function hideAllConfirmations() {
    const confirmations = document.querySelectorAll(".confirmation-panel");
    confirmations.forEach((panel) => {
        panel.style.display = "none";
    });

    const createBtn = document.getElementById("create-playlist-btn");
    const addBtn = document.getElementById("add-to-playlist-btn");
    const resetBtn = document.getElementById("reset-tracks-btn");

    createBtn.classList.remove("button-hidden");
    addBtn.classList.remove("button-hidden");
    resetBtn.classList.remove("button-hidden");
}

function setupConfirmationDialogs() {
    document
        .getElementById("cancel-reset-btn")
        ?.addEventListener("click", hideAllConfirmations);
    document
        .getElementById("cancel-create-btn")
        ?.addEventListener("click", hideAllConfirmations);
    document
        .getElementById("cancel-add-btn")
        ?.addEventListener("click", hideAllConfirmations);

    document
        .getElementById("confirm-reset-btn")
        ?.addEventListener("click", resetTracksAndLikes);
    document
        .getElementById("confirm-add-btn")
        ?.addEventListener("click", addToPlaylist);
}

function loadUserPlaylists() {
    const playlistSelect = document.getElementById("existing-playlist-select");
    playlistSelect.innerHTML =
        '<option value="" disabled selected>Loading playlists...</option>';

    fetch(BASE_API_URL + "includes/get_playlists.php")
        .then((response) => response.json())
        .then((data) => {
            if (data.success && data.playlists && data.playlists.length > 0) {
                playlistSelect.innerHTML =
                    '<option value="" disabled selected>Select a playlist...</option>';

                data.playlists.forEach((playlist) => {
                    const option = document.createElement("option");
                    option.value = playlist.id;
                    option.textContent = `${playlist.name} (${playlist.tracks} tracks)`;
                    option.dataset.playlistName = playlist.name;
                    playlistSelect.appendChild(option);
                });

                document.getElementById("confirm-add-btn").disabled = true;
                playlistSelect.addEventListener("change", function () {
                    document.getElementById("confirm-add-btn").disabled =
                        !this.value;
                });
            } else {
                playlistSelect.innerHTML =
                    '<option value="" disabled selected>No playlists available</option>';
            }
        })
        .catch((error) => {
            console.error("Error loading playlists:", error);
            playlistSelect.innerHTML =
                '<option value="" disabled selected>Error loading playlists</option>';
        });
}

function createPlaylist() {
    if (isCreatingPlaylist) {
        console.log("Already creating playlist, request ignored");
        return;
    }

    const playlistName = document.getElementById("playlist-name").value;
    if (!playlistName.trim()) {
        showNotification("Please enter a playlist name", "error");
        return;
    }

    const clearLikes = document.getElementById("clear-liked-songs").checked;
    const confirmCreateBtn = document.getElementById("confirm-create-btn");

    if (confirmCreateBtn.disabled) {
        return;
    }
    isCreatingPlaylist = true;
    resetCreatePlaylistFlag();

    confirmCreateBtn.disabled = true;
    confirmCreateBtn.innerHTML = "Creating...";

    fetch(BASE_API_URL + "includes/create_playlist.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            name: playlistName,
            tracks: likedSongs.map((song) => song.uri),
            clear_liked_songs: clearLikes,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            isCreatingPlaylist = false;

            if (data.success) {
                showNotification(
                    `Playlist "${playlistName}" created successfully!`,
                    "success"
                );

                if (data.cleared_likes) {
                    likedSongs = [];
                    localStorage.removeItem("musicmatch_liked_songs");
                    updateLikedSongsList();
                }

                hideAllConfirmations();
                setTimeout(() => {
                    confirmCreateBtn.disabled = false;
                    confirmCreateBtn.innerHTML = "Create Playlist";
                }, 2000);
            } else {
                showNotification(
                    "Failed to create playlist: " + data.message,
                    "error"
                );
                confirmCreateBtn.disabled = false;
                confirmCreateBtn.innerHTML = "Create Playlist";
            }
        })
        .catch((error) => {
            isCreatingPlaylist = false;

            console.error("Error creating playlist:", error);
            showNotification(
                "Failed to create playlist: " + error.message,
                "error"
            );
            confirmCreateBtn.disabled = false;
            confirmCreateBtn.innerHTML = "Create Playlist";
        });
}

function resetCreatePlaylistFlag() {
    setTimeout(() => {
        if (isCreatingPlaylist) {
            isCreatingPlaylist = false;
            console.log("Reset createPlaylist flag after timeout");

            const confirmCreateBtn =
                document.getElementById("confirm-create-btn");
            if (confirmCreateBtn && confirmCreateBtn.disabled) {
                confirmCreateBtn.disabled = false;
                confirmCreateBtn.innerHTML = "Create Playlist";
            }
        }
    }, 10000);
}

function addToPlaylist() {
    const playlistSelect = document.getElementById("existing-playlist-select");
    const playlistId = playlistSelect.value;
    const playlistName =
        playlistSelect.options[playlistSelect.selectedIndex].dataset
            .playlistName;

    if (!playlistId) {
        showNotification("Please select a playlist first", "error");
        return;
    }

    const clearLikes = document.getElementById("clear-liked-songs-add").checked;

    document.getElementById("confirm-add-btn").disabled = true;
    document.getElementById("confirm-add-btn").innerHTML = "Adding...";

    fetch(BASE_API_URL + "includes/create_playlist.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            playlist_id: playlistId,
            name: playlistName,
            tracks: likedSongs.map((song) => song.uri),
            clear_liked_songs: clearLikes,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                showNotification(
                    `Songs added to "${playlistName}" successfully!${
                        data.skipped_tracks > 0
                            ? " (" +
                              data.skipped_tracks +
                              " duplicate(s) skipped)"
                            : ""
                    }`,
                    "success"
                );

                if (data.cleared_likes) {
                    likedSongs = [];
                    localStorage.removeItem("musicmatch_liked_songs");
                    updateLikedSongsList();
                    //showNotification('All saved tracks have been reset!', 'success');
                }

                hideAllConfirmations();
            } else {
                showNotification(
                    "Note: " +
                        (data.message ||
                            "Some tracks were already in the playlist"),
                    "info"
                );
            }

            document.getElementById("confirm-add-btn").disabled = false;
            document.getElementById("confirm-add-btn").innerHTML =
                "Add to Playlist";
        })
        .catch((error) => {
            console.error("Error adding songs:", error);
            showNotification("Failed to add songs: " + error.message, "error");
            document.getElementById("confirm-add-btn").disabled = false;
            document.getElementById("confirm-add-btn").innerHTML =
                "Add to Playlist";
        });
}

function setupResetButton() {
    const resetBtn = document.getElementById("reset-tracks-btn");
    const resetConfirmation = document.getElementById("reset-confirmation");
    const cancelResetBtn = document.getElementById("cancel-reset-btn");

    if (!resetBtn || !resetConfirmation || !cancelResetBtn) {
        return;
    }

    resetBtn.addEventListener("click", function () {
        const createBtn = document.getElementById("create-playlist-btn");
        const addBtn = document.getElementById("add-to-playlist-btn");

        resetBtn.classList.add("button-hidden");
        if (createBtn) createBtn.classList.add("button-hidden");
        if (addBtn) addBtn.classList.add("button-hidden");

        resetConfirmation.style.display = "block";
    });

    cancelResetBtn.addEventListener("click", function () {
        resetConfirmation.style.display = "none";
        resetBtn.classList.remove("button-hidden");

        const createBtn = document.getElementById("create-playlist-btn");
        const addBtn = document.getElementById("add-to-playlist-btn");

        if (createBtn) createBtn.classList.remove("button-hidden");
        if (addBtn) addBtn.classList.remove("button-hidden");
    });
}

function resetTracksAndLikes() {
    document.getElementById("confirm-reset-btn").disabled = true;
    document.getElementById("confirm-reset-btn").innerHTML = "Resetting...";

    fetch(BASE_API_URL + "includes/api_reset_tracks.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                localStorage.removeItem("musicmatch_liked_songs");
                likedSongs = [];
                if (typeof seenTracksRandom !== "undefined")
                    seenTracksRandom = [];
                if (typeof seenTracksShortTerm !== "undefined")
                    seenTracksShortTerm = [];
                if (typeof seenTracksMediumTerm !== "undefined")
                    seenTracksMediumTerm = [];
                if (typeof seenTracksLongTerm !== "undefined")
                    seenTracksLongTerm = [];
                updateLikedSongsList();
                currentTrackIndex = 0;
                if (tracks && tracks.length > 0) {
                    if (currentCard) {
                        currentCard.remove();
                        currentCard = null;
                    }
                    createCard(tracks[0]);
                    playCurrentTrack();
                }
                hideAllConfirmations();

                showNotification(
                    "All saved tracks have been reset!",
                    "success"
                );
            } else {
                showNotification(
                    "Error resetting tracks: " +
                        (data.message || "Unknown error"),
                    "error"
                );
            }

            document.getElementById("confirm-reset-btn").disabled = false;
            document.getElementById("confirm-reset-btn").innerHTML = "Reset";
        })
        .catch((error) => {
            console.error("Error resetting tracks:", error);
            showNotification(
                "Error resetting tracks: " + error.message,
                "error"
            );
            document.getElementById("confirm-reset-btn").disabled = false;
            document.getElementById("confirm-reset-btn").innerHTML = "Reset";
        });
}

document.addEventListener("DOMContentLoaded", function () {
    likedSongs = loadLikedSongs();

    if (likedSongs.length > 0) {
        updateLikedSongsList();

        saveLikedSongs(likedSongs);
    }
    setupResetButton();
    setInitialState();
    initializeCards();
    initializeEventListeners();
});
