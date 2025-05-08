<?php
require 'vendor/autoload.php';
include 'config.php';


session_start();

$isLoggedIn = isset($_SESSION['spotify_access_token']);

$userData = null;
if ($isLoggedIn) {
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($_SESSION['spotify_access_token']);
    
    try {
        $userData = $api->me();
    } catch (Exception $e) {
        // token expired
        $isLoggedIn = false;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Spotify API Test Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { display: flex; align-items: center; margin-bottom: 30px; }
        .profile-img { width: 50px; height: 50px; border-radius: 50%; margin-right: 15px; }
        .card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .card { border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .card h3 { margin-top: 0; }
        .card-icon { font-size: 2em; margin-bottom: 10px; }
        .login-btn { display: inline-block; background: #1DB954; color: white; padding: 10px 20px; text-decoration: none; border-radius: 30px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Spotify API Test Dashboard</h1>
    
    <?php if ($isLoggedIn && $userData): ?>
        <div class="header">
            <?php if (isset($userData->images[0])): ?>
                <img 
                    src="<?php echo htmlspecialchars($userData->images[0]->url); ?>" 
                    class="profile-img" 
                    alt="Profilbild"
                >
            <?php endif; ?>
            <div>
                <p>Angemeldet als <strong><?php echo htmlspecialchars($userData->display_name); ?></strong></p>
                <a href="logout.php">Abmelden</a>
            </div>
        </div>
        
        <div class="card-grid">
            <a href="profile.php" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <div class="card-icon">👤</div>
                    <h3>Dein Profil</h3>
                    <p>Zeige deine Spotify-Profilinformationen an</p>
                </div>
            </a>
            
            <a href="top_artists.php" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <div class="card-icon">🎤</div>
                    <h3>Top-Künstler</h3>
                    <p>Entdecke deine meistgehörten Künstler</p>
                </div>
            </a>
            
            <a href="top_tracks.php" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <div class="card-icon">🎵</div>
                    <h3>Top-Tracks</h3>
                    <p>Sieh dir deine Lieblingssongs an</p>
                </div>
            </a>
            
            <a href="playlists.php" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <div class="card-icon">📋</div>
                    <h3>Playlists</h3>
                    <p>Durchsuche deine Spotify-Playlists</p>
                </div>
            </a>
            
            <a href="recommendations.php" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <div class="card-icon">✨</div>
                    <h3>Empfehlungen</h3>
                    <p>Entdecke neue Musik basierend auf deinen Vorlieben</p>
                </div>
            </a>
            
            <a href="songinfos.php" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <div class="card-icon">🔍</div>
                    <h3>Song-Infos</h3>
                    <p>Inofs for a Song</p>
                </div>
            </a>

            <a href="recommendations.php" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <div class="card-icon">🔍</div>
                    <h3>Song-recommendations</h3>
                    <p>recommendations for Songs</p>
                </div>
            </a>

            <a href="recommendstions2.php" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <div class="card-icon">🔍</div>
                    <h3>Song-recommendations</h3>
                    <p>recommendations for Songs</p>
                </div>
            </a>
        </div>
    <?php else: ?>
        <p>Du bist nicht angemeldet. Melde dich mit deinem Spotify-Konto an, um die API-Funktionen zu testen.</p>
        <a href="login.php" class="login-btn">Mit Spotify anmelden</a>
    <?php endif; ?>
    
    <footer style="margin-top: 40px; border-top: 1px solid #eee; padding-top: 20px;">
        <p>Diese Anwendung ist ein Testprojekt für die Spotify API. Erstellt für MMP1 Musik-Match.</p>
    </footer>
</body>
</html>
