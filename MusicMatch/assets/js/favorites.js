   document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('js-enabled');
        const createBtn = document.getElementById('create-top-tracks-playlist-btn');
        const confirmCreateBtn = document.getElementById('confirm-create-btn');
        const cancelCreateBtn = document.getElementById('cancel-create-btn');
        const createForm = document.getElementById('create-playlist-form');
        fixMobileNavigation();

        if (createBtn && createForm) {
            createBtn.addEventListener('click', function() {
                createForm.style.display = 'block';
                createBtn.style.display = 'none';
            });

            cancelCreateBtn.addEventListener('click', function() {
                createForm.style.display = 'none';
                createBtn.style.display = 'inline-flex';
            });

            confirmCreateBtn.addEventListener('click', function() {
                const playlistName = document.getElementById('playlist-name').value;

                if (!playlistName.trim()) {
                    showNotification("Please enter a playlist name", 'error');
                    return;
                }

                confirmCreateBtn.disabled = true;
                confirmCreateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';

                const trackUris = [];
                document.querySelectorAll('.card[data-uri]').forEach(track => {
                    const uri = track.getAttribute('data-uri');
                    if (uri) trackUris.push(uri);
                });

                if (trackUris.length === 0) {
                    showNotification("No tracks found to add to playlist", 'error');
                    confirmCreateBtn.disabled = false;
                    confirmCreateBtn.innerHTML = "Create Playlist";
                    return;
                }

                fetch("./includes/create_playlist.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            name: playlistName,
                            tracks: trackUris,
                            clear_liked_songs: false,
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(`Playlist "${playlistName}" created successfully!`, 'success');
                            createForm.style.display = 'none';
                            createBtn.style.display = 'inline-flex';
                        } else {
                            showNotification("Failed to create playlist: " + data.message, 'error');
                        }
                        confirmCreateBtn.disabled = false;
                        confirmCreateBtn.innerHTML = "Create Playlist";
                    })
                    .catch(error => {
                        console.error("Error creating playlist:", error);
                        showNotification("Failed to create playlist: " + error.message, 'error');
                        confirmCreateBtn.disabled = false;
                        confirmCreateBtn.innerHTML = "Create Playlist";
                    });
            });
        }

        document.querySelectorAll('.card').forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('visible');
            }, 50 * index);
        });

        animateNavigation();
    });

    function animateNavigation() {
        const navElements = document.querySelectorAll('.view-nav, .time-nav');
        navElements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(10px)';

            setTimeout(() => {
                el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 100 * (index + 1));
        });
    }

    function fixMobileNavigation() {
    if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
        const navLinks = document.querySelectorAll('.view-nav a, .time-nav a');
        
        navLinks.forEach(link => {
            link.addEventListener('touchstart', function(e) {
                if (!this.classList.contains('active')) {
                    e.preventDefault();
                    window.location.href = this.href;
                }
            });
        });
    }
}