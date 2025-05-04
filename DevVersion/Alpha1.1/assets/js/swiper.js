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

//console.log(spotifyAccessToken, tracks);

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
    swipeContainer.innerHTML = "";
    createCard(tracks[currentTrackIndex]);
    currentCard = document.getElementById("current-card");
    playCurrentTrack();
}

function createCard(track) {
    // Clear any existing cards first
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
                <img src="./assets/img/icons/volume.svg" alt="Volume">
                <input type="range" id="volume" min="0" max="100" value="50">
            </div>
                
            <div class="swipe-indicator swipe-indicator-like">
                <img src="./assets/img/icons/like.svg" alt="Like">
            </div>
                
            <div class="swipe-indicator swipe-indicator-dislike">
                <img src="./assets/img/icons/dislike.svg" alt="Dislike">
            </div>
                
            <div class="player-controls-container">
                <button class="player-control-button" id="toggle-play-button">
                    <img src="./assets/img/icons/play.svg" alt="Play/Pause" id="play-icon">
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

    const playButton = document.getElementById("toggle-play-button");
    if (playButton) {
        playButton.addEventListener("click", togglePlay);
    }

    const volumeSlider = document.getElementById("volume");
    if (volumeSlider) {
        volumeSlider.addEventListener("input", handleVolumeChange);
    }

    const hammer = new Hammer(card);
    hammer.on("swipeleft", () => handleSwipe("left"));
    hammer.on("swiperight", () => handleSwipe("right"));

    hammer.on("pan", (e) => {
        if (isAnimating) return;

        const xPos = e.deltaX;
        card.style.transform = `translateX(${xPos}px) rotate(${
            xPos * 0.03
        }deg)`;

        if (xPos > 50) {
            card.classList.add("dragging-right");
            card.classList.remove("dragging-left");
        } else if (xPos < -50) {
            card.classList.add("dragging-left");
            card.classList.remove("dragging-right");
        } else {
            card.classList.remove("dragging-right", "dragging-left");
        }

        if (e.isFinal) {
            if (xPos > 100) {
                handleSwipe("right");
            } else if (xPos < -100) {
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
    }, 600); // Match animation duration in CSS
}

function likeCurrentTrack() {
    const track = tracks[currentTrackIndex];

    likedSongs.push(track);
    updateLikedSongsList();
    saveLikedSongs(likedSongs);
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
                <i class="fab fa-spotify"></i>
            </a>
        `;

        likedSongsList.appendChild(li);
    });

    document.querySelectorAll(".liked-song-play").forEach((button) => {
        button.addEventListener("click", (e) => {
            const index = e.currentTarget.getAttribute("data-index");
            playLikedSong(index);
        });
    });
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
    if (!deviceId) {
        console.error("No device ID available");
        return;
    }

    if (!tracks || currentTrackIndex >= tracks.length) {
        console.error("No tracks available or invalid track index");
        return;
    }

    const track = tracks[currentTrackIndex];
    previewEnded = false;

    const startPercentage = 0.3 + Math.random() * 0.1; // 30-40%
    currentStartPosition = Math.floor(track.duration_ms * startPercentage);

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
                    if (isPlaying) {
                        player.pause().then(() => {
                            isPlaying = false;
                            previewEnded = true;
                            updatePlayButton();
                            document.getElementById(
                                "progress-bar"
                            ).style.width = "100%";
                            document.getElementById(
                                "current-time"
                            ).textContent = "0:30";

                            if (progressUpdateTimer) {
                                clearInterval(progressUpdateTimer);
                                progressUpdateTimer = null;
                            }
                        });
                    }
                }, previewDuration);
            } else {
                response.json().then((data) => {
                    console.error("Error playing track:", data);
                });
            }
        })
        .catch((error) => {
            console.error("Playback error:", error);
            isPlaying = false;
            updatePlayButton();
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
window.onSpotifyWebPlaybackSDKReady = () => {
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
        if (state && !previewEnded) {
            isPlaying = !state.paused;
            updatePlayButton();

            const elapsedInPreview = Math.min(
                state.position - currentStartPosition,
                previewDuration
            );
            const clampedElapsed = Math.max(0, elapsedInPreview);
            const progress = (clampedElapsed / previewDuration) * 100;
            document.getElementById(
                "progress-bar"
            ).style.width = `${progress}%`;
            document.getElementById("current-time").textContent =
                formatTime(clampedElapsed);

            if (elapsedInPreview >= previewDuration && isPlaying) {
                player.pause().then(() => {
                    isPlaying = false;
                    previewEnded = true;
                    updatePlayButton();
                    document.getElementById("progress-bar").style.width =
                        "100%";
                    document.getElementById("current-time").textContent =
                        "0:30";

                    if (playbackTimer) {
                        clearTimeout(playbackTimer);
                        playbackTimer = null;
                    }
                });
            }
        }
    });

    player.addListener("ready", ({ device_id }) => {
        deviceId = device_id;
        console.log("Player ready with device ID:", device_id);
        initializeCards();
    });
    player.connect();
};
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
        player.getCurrentState().then((state) => {
            if (state && isPlaying && !previewEnded) {
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
                if (elapsedInPreview >= previewDuration) {
                    player.pause().then(() => {
                        isPlaying = false;
                        previewEnded = true;
                        updatePlayButton();

                        const progressBarEnd =
                            document.getElementById("progress-bar");
                        const currentTimeEnd =
                            document.getElementById("current-time");

                        if (progressBarEnd) {
                            progressBarEnd.style.width = "100%";
                        }

                        if (currentTimeEnd) {
                            currentTimeEnd.textContent = "0:30";
                        }

                        clearInterval(progressUpdateTimer);
                        progressUpdateTimer = null;

                        if (playbackTimer) {
                            clearTimeout(playbackTimer);
                            playbackTimer = null;
                        }
                    });
                }
            }
        });
    }, 100); // update every 100ms
}

const swipeMethod = document.getElementById("swipe-method");
const playlistInputGroup = document.getElementById("playlist-input-group");
const form = document.getElementById("swipe-form");

function setInitialState() {
    const inputGroup = document.querySelector(".input-group");

    if (swipeMethod.value === "playlist") {
        playlistInputGroup.classList.add("visible");
        inputGroup.classList.add("playlist-mode");
    } else {
        playlistInputGroup.classList.remove("visible");
        inputGroup.classList.remove("playlist-mode");
    }
}

function updateForm(e) {
    const inputGroup = document.querySelector(".input-group");

    if (swipeMethod.value === "playlist") {
        playlistInputGroup.classList.add("visible");
        inputGroup.classList.add("playlist-mode");
        if (e && e.preventDefault) e.preventDefault(); // Safer check
    } else {
        playlistInputGroup.classList.remove("visible");
        inputGroup.classList.remove("playlist-mode");
        form.submit();
    }
}

swipeMethod.addEventListener("change", updateForm);

function showPlaylistModal() {
    document.querySelector(".modal-body").innerHTML = `
        <div class="loading-spinner text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading your playlists...</p>
        </div>
    `;
    const playlistModal = new bootstrap.Modal(
        document.getElementById("playlist-modal")
    );
    playlistModal.show();

    fetchUserPlaylists();
}

function fetchUserPlaylists() {
    fetch("./get_playlists.php")
        .then((response) => {
            if (!response.ok) {
                throw new Error("Server returned status " + response.status);
            }
            return response.json();
        })
        .then((data) => {
            if (data.success && data.playlists && data.playlists.length > 0) {
                document.querySelector(".modal-body").innerHTML = `
                    <div class="mb-3">
                        <label for="playlist-select" class="form-label">Choose a playlist:</label>
                        <select class="form-select" id="playlist-select">
                            <option value="" disabled selected>Select a playlist...</option>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" id="confirm-playlist-btn" disabled>Add to Selected Playlist</button>
                    </div>
                `;

                const playlistSelect =
                    document.getElementById("playlist-select");

                data.playlists.forEach((playlist) => {
                    const option = document.createElement("option");
                    option.value = playlist.id;
                    option.textContent = `${playlist.name} (${playlist.tracks} tracks)`;
                    option.dataset.playlistName = playlist.name;
                    playlistSelect.appendChild(option);
                });

                playlistSelect.addEventListener("change", function () {
                    document.getElementById("confirm-playlist-btn").disabled =
                        !this.value;
                });

                document
                    .getElementById("confirm-playlist-btn")
                    .addEventListener("click", addTracksToSelectedPlaylist);
            } else {
                document.querySelector(".modal-body").innerHTML = `
                    <div class="alert alert-info">
                        <p>You don't have any playlists. Create one first!</p>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                `;
            }
        })
        .catch((error) => {
            document.querySelector(".modal-body").innerHTML = `
                <div class="alert alert-danger">
                    <p>Failed to load playlists: ${error.message}</p>
                </div>
                <div class="d-flex justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            `;
        });
}

function addTracksToSelectedPlaylist() {
    const playlistSelect = document.getElementById("playlist-select");
    const selectedOption = playlistSelect.options[playlistSelect.selectedIndex];
    const playlistId = playlistSelect.value;
    const playlistName = selectedOption.dataset.playlistName;

    if (!playlistId) {
        alert("Please select a playlist first");
        return;
    }

    const clearLikes = confirm(
        "Would you like to clear your liked songs after adding them to the playlist?"
    );

    document.getElementById("confirm-playlist-btn").disabled = true;
    document.getElementById("confirm-playlist-btn").innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';

    fetch("./create_playlist.php", {
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
                alert(
                    `Songs added to "${playlistName}" successfully!${
                        data.skipped_tracks > 0
                            ? " (" +
                              data.skipped_tracks +
                              " duplicates skipped)"
                            : ""
                    }`
                );

                const modalElement = document.getElementById("playlist-modal");
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }

                document.body.classList.remove("modal-open");
                document.body.style.overflow = "";
                document.body.style.paddingRight = "";

                const backdrop = document.querySelector(".modal-backdrop");
                if (backdrop) {
                    backdrop.remove();
                }
                if (data.cleared_likes) {
                    likedSongs = [];
                    localStorage.removeItem("musicmatch_liked_songs");
                    updateLikedSongsList();
                }
            } else {
                alert(
                    "Note: " +
                        (data.message ||
                            "Some tracks were already in the playlist")
                );

                try {
                    const modalInstance = bootstrap.Modal.getInstance(
                        document.getElementById("playlist-modal")
                    );
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    setTimeout(cleanupModal, 300);
                } catch (e) {
                    console.error("Error closing modal:", e);
                    cleanupModal();
                }
            }
        })
        .catch((error) => {
            console.error("Error adding songs:", error);
            alert("Failed to add songs. Please try again.");
            document.getElementById("confirm-playlist-btn").disabled = false;
            document.getElementById("confirm-playlist-btn").textContent =
                "Add to Selected Playlist";
        });
}

function cleanupModal() {
    document.body.classList.remove("modal-open");
    document.body.style.overflow = "";
    document.body.style.paddingRight = "";

    document.querySelectorAll(".modal-backdrop").forEach((backdrop) => {
        backdrop.remove();
    });
}

function saveLikedSongs(songs) {
    // Save to localStorage
    localStorage.setItem("musicmatch_liked_songs", JSON.stringify(songs));

    // Save to session via AJAX
    fetch("./save_liked_track.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ songs: songs }),
    }).catch((error) => {
        console.error("Error saving liked tracks:", error);
    });
}

function initializeEvents() {
    document
        .getElementById("like-button")
        .addEventListener("click", () => handleSwipe("right"));
    document
        .getElementById("dislike-button")
        .addEventListener("click", () => handleSwipe("left"));
    document
        .getElementById("create-playlist-btn")
        .addEventListener("click", createPlaylist);
    document
        .getElementById("add-to-playlist-btn")
        .addEventListener("click", function () {
            if (likedSongs.length === 0) {
                alert("You need to like some songs first!");
                return;
            }
            showPlaylistModal();
        });

    document.addEventListener("keydown", function (e) {
        if (e.key === "ArrowLeft") handleSwipe("left");
        if (e.key === "ArrowRight") handleSwipe("right");
    });
    document.querySelectorAll(".modal").forEach((modal) => {
        modal.addEventListener("hidden.bs.modal", cleanupModal);
    });
}

function createPlaylist() {
    if (likedSongs.length === 0) {
        alert("You need to like some songs first!");
        return;
    }

    const playlistName = prompt(
        "Enter a name for your playlist:",
        "My MusicMatch Swiper Playlist"
    );
    if (!playlistName) return;

    const clearLikes = confirm(
        "Would you like to clear your liked songs after creating the playlist?"
    );

    fetch("./create_playlist.php", {
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
                alert(`Playlist "${playlistName}" created successfully!`);

                if (data.cleared_likes) {
                    likedSongs = [];
                    localStorage.removeItem("musicmatch_liked_songs");
                    updateLikedSongsList();
                }
            } else {
                alert("Failed to create playlist: " + data.message);
            }
        })
        .catch((error) => {
            console.error("Error creating playlist:", error);
            alert("Failed to create playlist. Please try again.");
        });
}

document.addEventListener("DOMContentLoaded", function () {
    if (initialLikedSongs && initialLikedSongs.length > 0) {
        likedSongs = [...initialLikedSongs];
        updateLikedSongsList();
    }

    setInitialState();
    initializeCards();
    initializeEvents();
});
