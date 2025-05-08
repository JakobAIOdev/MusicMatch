<?php
session_start();

// Überprüfe, ob der Nutzer authentifiziert ist
if (!isset($_SESSION['spotify_access_token'])) {
    header('Location: index.php');
    exit;
}

$access_token = $_SESSION['spotify_access_token'];

// Funktion zum Abrufen von Daten von der Spotify API
function getSpotifyData($url, $access_token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Hole Nutzerprofil
$user_profile = getSpotifyData('https://api.spotify.com/v1/me', $access_token);

// Hole Top-Tracks des Nutzers
$top_tracks = getSpotifyData('https://api.spotify.com/v1/me/top/tracks?limit=10', $access_token);

// Extrahiere Track-IDs für Empfehlungen
$seed_tracks = [];
if (isset($top_tracks['items']) && !empty($top_tracks['items'])) {
    foreach ($top_tracks['items'] as $index => $track) {
        if ($index < 5) { // Verwende maximal 5 Tracks als Seeds
            $seed_tracks[] = $track['id'];
        }
    }
}

// Hole Empfehlungen basierend auf Top-Tracks
$recommendations = [];
if (!empty($seed_tracks)) {
    // Verwende nur 2 Seed-Tracks, um sicherzustellen, dass wir Ergebnisse bekommen
    $seed_string = implode(',', array_slice($seed_tracks, 0, 2));
    $recommendations_url = "https://api.spotify.com/v1/recommendations?limit=10&seed_tracks={$seed_string}";
    $recommendations = getSpotifyData($recommendations_url, $access_token);
}

// Fallback: Wenn keine Empfehlungen gefunden wurden, versuche es mit einem einzelnen Track
if (empty($recommendations['tracks']) && !empty($seed_tracks)) {
    $recommendations_url = "https://api.spotify.com/v1/recommendations?limit=10&seed_tracks={$seed_tracks[0]}";

                        
    $recommendations = getSpotifyData($recommendations_url, $access_token);
}

// Erstelle eine Playlist mit den Empfehlungen, wenn der Nutzer auf "Playlist erstellen" klickt
$playlist_created = false;
$playlist_url = '';

if (isset($_POST['create_playlist']) && isset($recommendations['tracks']) && !empty($recommendations['tracks'])) {
    // Erstelle eine neue Playlist
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/users/' . $user_profile['id'] . '/playlists');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'name' => 'Meine Empfehlungen ' . date('d.m.Y'),
        'description' => 'Automatisch generierte Playlist basierend auf deinem Musikgeschmack',
        'public' => false
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $playlist = json_decode($response, true);
    
    if (isset($playlist['id'])) {
        // Füge Tracks zur Playlist hinzu
        $track_uris = [];
        foreach ($recommendations['tracks'] as $track) {
            $track_uris[] = $track['uri'];
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/playlists/' . $playlist['id'] . '/tracks');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'uris' => $track_uris
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (isset($result['snapshot_id'])) {
            $playlist_created = true;
            $playlist_url = $playlist['external_urls']['spotify'];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Deine Musikempfehlungen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #121212;
            color: #ffffff;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .user-info {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .user-info img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
        }
        .section {
            margin-bottom: 40px;
            background-color: #181818;
            padding: 20px;
            border-radius: 8px;
        }
        .track-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
            justify-content: center;
        }
        .track-card {
            width: 200px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            transition: transform 0.3s;
            background-color: #282828;
        }
        .track-card:hover {
            transform: translateY(-5px);
        }
        .track-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .track-info {
            padding: 15px;
        }
        .track-name {
            font-weight: bold;
            margin-bottom: 5px;
            color: #ffffff;
        }
        .track-artist {
            color: #b3b3b3;
            margin-bottom: 10px;
        }
        .buttons {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        .button {
            background-color: #1DB954;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: bold;
            cursor: pointer;
            margin: 0 10px;
        }
        .success-message {
            background-color: #1DB954;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
        }
        .logout {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: transparent;
            color: #b3b3b3;
            border: 1px solid #b3b3b3;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <a href="logout.php" class="logout">Logout</a>
    
    <div class="header">
        <h1>Deine Musikempfehlungen</h1>
        <?php if (isset($user_profile['display_name'])): ?>
            <div class="user-info">
                <?php if (isset($user_profile['images'][0]['url'])): ?>
                    <img src="<?php echo $user_profile['images'][0]['url']; ?>" alt="Profilbild">
                <?php endif; ?>
                <p>Hallo, <?php echo $user_profile['display_name']; ?>!</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Deine Top-Tracks</h2>
        <div class="track-container">
            <?php if (isset($top_tracks['items'])): ?>
                <?php foreach ($top_tracks['items'] as $track): ?>
                    <div class="track-card">
                        <?php if (isset($track['album']['images'][0]['url'])): ?>
                            <img src="<?php echo $track['album']['images'][0]['url']; ?>" alt="<?php echo $track['name']; ?>">
                        <?php endif; ?>
                        <div class="track-info">
                            <div class="track-name"><?php echo $track['name']; ?></div>
                            <div class="track-artist"><?php echo $track['artists'][0]['name']; ?></div>
                            <?php if (isset($track['preview_url']) && $track['preview_url']): ?>
                                <audio controls style="width: 100%;">
                                    <source src="<?php echo $track['preview_url']; ?>" type="audio/mpeg">
                                    Dein Browser unterstützt das Audio-Element nicht.
                                </audio>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Keine Top-Tracks gefunden.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="section">
        <h2>Empfohlene Songs für dich</h2>
        <div class="track-container">
            <?php if (isset($recommendations['tracks']) && !empty($recommendations['tracks'])): ?>
                <?php foreach ($recommendations['tracks'] as $track): ?>
                    <div class="track-card">
                        <?php if (isset($track['album']['images'][0]['url'])): ?>
                            <img src="<?php echo $track['album']['images'][0]['url']; ?>" alt="<?php echo $track['name']; ?>">
                        <?php endif; ?>
                        <div class="track-info">
                            <div class="track-name"><?php echo $track['name']; ?></div>
                            <div class="track-artist"><?php echo $track['artists'][0]['name']; ?></div>
                            <?php if (isset($track['preview_url']) && $track['preview_url']): ?>
                                <audio controls style="width: 100%;">
                                    <source src="<?php echo $track['preview_url']; ?>" type="audio/mpeg">
                                    Dein Browser unterstützt das Audio-Element nicht.
                                </audio>
                            <?php endif; ?>
                            <a href="<?php echo $track['external_urls']['spotify']; ?>" target="_blank" style="color: #1DB954; text-decoration: none; display: block; margin-top: 10px;">Auf Spotify öffnen</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Keine Empfehlungen gefunden. Versuche es später noch einmal.</p>
            <?php endif; ?>
        </div>
        
        <?php if (isset($recommendations['tracks']) && !empty($recommendations['tracks'])): ?>
            <div class="buttons">
                <form method="post">
                    <button type="submit" name="create_playlist" class="button">Playlist erstellen</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($playlist_created): ?>
        <div class="success-message">
            <p>Playlist wurde erfolgreich erstellt!</p>
            <a href="<?php echo $playlist_url; ?>" target="_blank" style="color: white; text-decoration: underline;">Auf Spotify öffnen</a>
        </div>
    <?php endif; ?>
</body>
</html>
