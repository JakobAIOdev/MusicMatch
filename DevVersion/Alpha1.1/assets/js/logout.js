function performLogout() {
    // Clear client-side data
    localStorage.removeItem('musicmatch_liked_songs');
    
    // Force absolute URL paths - extremely important for consistency
    const baseUrl = window.location.origin + '/';
    const apiUrl = baseUrl + 'includes/api_reset_tracks.php';
    const logoutUrl = baseUrl + 'auth/logout.php';
    
    // Disconnect Spotify player
    if (typeof player !== 'undefined' && player) {
        try {
            player.disconnect();
        } catch (e) {
            console.log("Error disconnecting Spotify player:", e);
        }
    }
    
    // Set a flag in localStorage to indicate we want tracks cleared
    // This serves as a backup if the AJAX call doesn't complete
    localStorage.setItem('clear_liked_tracks_on_next_login', 'true');
    
    // Clear server-side session data via AJAX with increased timeout
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest' // Add this for proper AJAX detection
        },
        // Add credentials to ensure cookies are sent
        credentials: 'same-origin',
        // Longer timeout
        signal: AbortSignal.timeout(5000)
    })
    .then(response => response.json())
    .then(data => {
        console.log("Session data cleared:", data);
        window.location.href = logoutUrl;
    })
    .catch(error => {
        console.error("Error clearing session data:", error);
        // Redirect anyway, the backup flag will help on next login
        window.location.href = logoutUrl;
    });
}