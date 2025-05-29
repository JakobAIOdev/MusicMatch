document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('clear_liked_tracks_on_next_login') === 'true') {
        console.log("Clearing leftover liked tracks from previous session");
        localStorage.removeItem('musicmatch_liked_songs');
        localStorage.removeItem('clear_liked_tracks_on_next_login');
    }
});