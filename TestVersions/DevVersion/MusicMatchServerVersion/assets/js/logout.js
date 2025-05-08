function performLogout() {
    localStorage.removeItem('musicmatch_liked_songs');
    
    const isLocalEnvironment = window.location.hostname.includes('localhost') || 
                              window.location.hostname === '127.0.0.1';
    const baseUrl = isLocalEnvironment ? 
                    window.location.origin + '/' : 
                    window.location.origin + '/~fhs52920/MusicMatch/';
    
    const apiUrl = baseUrl + 'includes/api_reset_tracks.php';
    const logoutUrl = baseUrl + 'auth/logout.php';
    
    if (typeof player !== 'undefined' && player) {
        try {
            player.disconnect();
        } catch (e) {
            console.log("Error disconnecting Spotify player:", e);
        }
    }
    localStorage.setItem('clear_liked_tracks_on_next_login', 'true');
    
    if (typeof showNotification === 'function') {
        showNotification("You have been logged out successfully", 'info');
        setTimeout(() => {
            proceedWithLogout();
        }, 1500);
    } else {
        proceedWithLogout();
    }
    
    function proceedWithLogout() {
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest' 
            },
            credentials: 'same-origin',
            signal: AbortSignal.timeout(5000)
        })
        .then(response => response.json())
        .then(data => {
            console.log("Session data cleared:", data);
            window.location.href = logoutUrl;
        })
        .catch(error => {
            console.error("Error clearing session data:", error);
            window.location.href = logoutUrl;
        });
    }
}