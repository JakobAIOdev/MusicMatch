<?php
require 'vendor/autoload.php';
include "config.php";
session_start();

// Prüfen, ob der Benutzer angemeldet ist
if (!isset($_SESSION['spotify_access_token'])) {
    header('Location: index.php');
    exit;
}

// Playlist-ID aus der URL holen
if (!isset($_GET['id'])) {
    header('Location: playlists.php');
    exit;
}
$playlistId = $_GET['id'];

// API-Client initialisieren
$api = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken($_SESSION['spotify_access_token']);

// Playlist-Details abrufen
try {
    $playlist = $api->getPlaylist($playlistId);
    $tracks = $api->getPlaylistTracks($playlistId, [
        'limit' => 50
    ]);
} catch (Exception $e) {
    die('Fehler beim Abrufen der Playlist: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($playlist->name); ?> - Spotify Playlist</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .playlist-header { display: flex; align-items: center; margin-bottom: 30px; }
        .playlist-img { width: 200px; height: 200px; margin-right: 20px; }
        .track-list { list-style-type: none; padding: 0; }
        .track-item { display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #eee; }
        .track-img { width: 50px; height: 50px; margin-right: 15px; }
        .track-number { margin-right: 15px; color: #888; }
        .nav-links { margin: 20px 0; }
    </style>
</head>
<body>
    <div class="playlist-header">
        <img 
            src="<?php echo isset($playlist->images[0]) ? htmlspecialchars($playlist->images[0]->url) : 'https://via.placeholder.com/200'; ?>" 
            class="playlist-img" 
            alt="<?php echo htmlspecialchars($playlist->name); ?>"
        >
        <div>
            <h1><?php echo htmlspecialchars($playlist->name); ?></h1>
            <p><?php echo htmlspecialchars($playlist->description); ?></p>
            <p>Erstellt von: <?php echo htmlspecialchars($playlist->owner->display_name); ?></p>
            <p><?php echo $playlist->tracks->total; ?> Tracks</p>
            <a href="<?php echo $playlist->external_urls->spotify; ?>" target="_blank">Auf Spotify öffnen</a>
        </div>
    </div>
    
    <h2>Tracks</h2>
    
    <?php if (count($tracks->items) > 0): ?>
        <ul class="track-list">
            <?php foreach ($tracks->items as $index => $item): ?>
                <?php $track = $item->track; ?>
                <li class="track-item">
                    <span class="track-number"><?php echo ($index + 1); ?></span>
                    <img 
                        src="<?php echo isset($track->album->images[0]) ? htmlspecialchars($track->album->images[0]->url) : 'https://via.placeholder.com/50'; ?>" 
                        class="track-img" 
                        alt="<?php echo htmlspecialchars($track->album->name); ?>"
                    >
                    <div>
                        <h3><?php echo htmlspecialchars($track->name); ?></h3>
                        <p><?php 
                            $artists = array_map(function($artist) {
                                return htmlspecialchars($artist->name);
                            }, $track->artists);
                            echo implode(', ', $artists);
                        ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Keine Tracks in dieser Playlist gefunden.</p>
    <?php endif; ?>
    
    <div class="nav-links">
        <a href="playlists.php">Zurück zu deinen Playlists</a>
    </div>
</body>
</html>
