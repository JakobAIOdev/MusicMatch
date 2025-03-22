<?php
require 'vendor/autoload.php';
include "config.php";
session_start();

// Prüfen, ob der Benutzer angemeldet ist
if (!isset($_SESSION['spotify_access_token'])) {
    header('Location: index.php');
    exit;
}

// API-Client initialisieren
$api = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken($_SESSION['spotify_access_token']);

// Playlists abrufen
try {
    $playlists = $api->getMyPlaylists([
        'limit' => 20
    ]);
} catch (Exception $e) {
    die('Fehler beim Abrufen der Playlists: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Deine Spotify Playlists</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .playlist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .playlist-card { border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow: hidden; }
        .playlist-img { width: 100%; height: 200px; object-fit: cover; }
        .playlist-info { padding: 10px; }
        .nav-links { margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Deine Spotify Playlists</h1>
    
    <?php if (count($playlists->items) > 0): ?>
        <div class="playlist-grid">
            <?php foreach ($playlists->items as $playlist): ?>
                <div class="playlist-card">
                    <img 
                        src="<?php echo isset($playlist->images[0]) ? htmlspecialchars($playlist->images[0]->url) : 'https://via.placeholder.com/200'; ?>" 
                        class="playlist-img" 
                        alt="<?php echo htmlspecialchars($playlist->name); ?>"
                    >
                    <div class="playlist-info">
                        <h3><?php echo htmlspecialchars($playlist->name); ?></h3>
                        <p><?php echo $playlist->tracks->total; ?> Tracks</p>
                        <a href="playlist_detail.php?id=<?php echo $playlist->id; ?>">Details anzeigen</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Keine Playlists gefunden.</p>
    <?php endif; ?>
    
    <div class="nav-links">
        <a href="profile.php">Zurück zum Profil</a>
    </div>
</body>
</html>
