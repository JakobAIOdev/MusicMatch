<?php
// Spotify API Konfiguration
$clientId = "499f3c04f86c48c6a24ae6e3987853b2";
$clientSecret = "956177b6040a46b699b143846123ec48";

// Funktion zum Abrufen eines Access Tokens
function getSpotifyAccessToken($clientId, $clientSecret) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return false;
    }
    curl_close($ch);

    $data = json_decode($response, true);
    if (!isset($data['access_token'])) {
        return false;
    }

    return $data['access_token'];
}

// Funktion zum Suchen von Songs (ähnlich wie spotifyPreviewFinder im GitHub-Repo)
function spotifyPreviewFinder($songName, $accessToken, $limit = 5) {
    if (empty($songName)) {
        return ['success' => false, 'error' => 'Kein Suchbegriff angegeben'];
    }

    $encodedQuery = urlencode($songName);
    $url = "https://api.spotify.com/v1/search?q={$encodedQuery}&type=track&limit={$limit}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return ['success' => false, 'error' => 'Curl error: ' . curl_error($ch)];
    }
    curl_close($ch);

    $data = json_decode($response, true);
    if (isset($data['error'])) {
        return ['success' => false, 'error' => 'API-Fehler: ' . $data['error']['message']];
    }

    if (!isset($data['tracks']) || !isset($data['tracks']['items']) || empty($data['tracks']['items'])) {
        return ['success' => false, 'error' => 'Keine Ergebnisse gefunden'];
    }

    $results = [];
    foreach ($data['tracks']['items'] as $track) {
        // Extrahiere die Künstlernamen
        $artists = [];
        foreach ($track['artists'] as $artist) {
            $artists[] = $artist['name'];
        }
        
        // Album-Cover URL
        $albumCover = !empty($track['album']['images']) ? $track['album']['images'][0]['url'] : '';
        
        // Genau wie im GitHub-Repo, speichere die Preview-URL in einem Array
        $previewUrls = [];
        if (!empty($track['preview_url'])) {
            $previewUrls[] = $track['preview_url'];
        }
        
        $results[] = [
            'name' => $track['name'],
            'artist' => $artists[0], // Hauptkünstler
            'artists' => $artists,
            'artistsString' => implode(', ', $artists),
            'album' => $track['album']['name'],
            'albumCover' => $albumCover,
            'spotifyUrl' => $track['external_urls']['spotify'],
            'previewUrls' => $previewUrls // Array von Preview-URLs wie im GitHub-Repo
        ];
    }

    return [
        'success' => true,
        'results' => $results
    ];
}

// Verarbeite Suchanfrage
$searchResults = null;
$error = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Hole Access Token
    $accessToken = getSpotifyAccessToken($clientId, $clientSecret);
    
    if (!$accessToken) {
        $error = "Fehler bei der Authentifizierung mit Spotify.";
    } else {
        // Suche Songs mit der Funktion aus dem GitHub-Repo
        $result = spotifyPreviewFinder($searchTerm, $accessToken, $limit);
        
        if (!$result['success']) {
            $error = $result['error'];
        } else {
            $searchResults = $result['results'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Preview Finder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #121212;
            color: #fff;
        }
        h1 {
            color: #1DB954;
            text-align: center;
            margin-bottom: 30px;
        }
        .search-form {
            display: flex;
            margin-bottom: 30px;
            justify-content: center;
            gap: 10px;
        }
        .search-form input {
            padding: 12px 15px;
            width: 60%;
            border: none;
            border-radius: 20px;
            background: #282828;
            color: white;
            font-size: 16px;
        }
        .search-form select {
            padding: 12px 15px;
            border: none;
            border-radius: 20px;
            background: #282828;
            color: white;
            cursor: pointer;
        }
        .search-form button {
            padding: 12px 20px;
            border: none;
            border-radius: 20px;
            background: #1DB954;
            color: white;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        .search-form button:hover {
            background: #1ed760;
        }
        .error {
            color: #ff4d4d;
            text-align: center;
            margin-bottom: 20px;
            background: rgba(255, 0, 0, 0.1);
            padding: 15px;
            border-radius: 5px;
        }
        .songs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .song-card {
            background: #282828;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .song-card:hover {
            transform: translateY(-5px);
        }
        .song-image {
            width: 100%;
            height: 0;
            padding-bottom: 100%;
            position: relative;
            background-size: cover;
            background-position: center;
        }
        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(29, 185, 84, 0.8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .song-image:hover .play-button {
            opacity: 1;
        }
        .play-icon {
            font-size: 24px;
            color: white;
        }
        .no-preview {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.7);
            color: #b3b3b3;
        }
        .song-info {
            padding: 15px;
        }
        .song-title {
            font-weight: bold;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .song-artist {
            color: #1DB954;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .song-album {
            color: #b3b3b3;
            font-size: 14px;
            margin-bottom: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .spotify-link {
            display: inline-block;
            background: #1DB954;
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }
        .spotify-link:hover {
            background: #1ed760;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #b3b3b3;
        }
    </style>
</head>
<body>
    <h1>Spotify Preview Finder</h1>
    
    <form class="search-form" method="GET">
        <input type="text" name="search" placeholder="Song oder Künstler suchen..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" required>
        <select name="limit">
            <option value="5" <?php echo (isset($_GET['limit']) && $_GET['limit'] == 5) ? 'selected' : ''; ?>>5 Ergebnisse</option>
            <option value="10" <?php echo (!isset($_GET['limit']) || $_GET['limit'] == 10) ? 'selected' : ''; ?>>10 Ergebnisse</option>
            <option value="20" <?php echo (isset($_GET['limit']) && $_GET['limit'] == 20) ? 'selected' : ''; ?>>20 Ergebnisse</option>
        </select>
        <button type="submit">Suchen</button>
    </form>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($searchResults): ?>
        <div class="songs-grid">
            <?php foreach ($searchResults as $song): ?>
                <div class="song-card">
                    <div class="song-image" style="background-image: url('<?php echo htmlspecialchars($song['albumCover']); ?>')">
                        <?php if (!empty($song['previewUrls'])): ?>
                            <div class="play-button" onclick="playPreview('<?php echo htmlspecialchars($song['previewUrls'][0]); ?>', this)">
                                <span class="play-icon">▶</span>
                            </div>
                        <?php else: ?>
                            <div class="no-preview">Kein Preview verfügbar</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="song-info">
                        <div class="song-title"><?php echo htmlspecialchars($song['name']); ?></div>
                        <div class="song-artist"><?php echo htmlspecialchars($song['artistsString']); ?></div>
                        <div class="song-album"><?php echo htmlspecialchars($song['album']); ?></div>
                        <a href="<?php echo htmlspecialchars($song['spotifyUrl']); ?>" target="_blank" class="spotify-link">Auf Spotify öffnen</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (isset($_GET['search'])): ?>
        <div class="no-results">
            <p>Keine Ergebnisse gefunden für "<?php echo htmlspecialchars($_GET['search']); ?>"</p>
        </div>
    <?php endif; ?>
    
    <audio controls id="audio-player">
        <source src="https://p.scdn.co/mp3-preview/fcce8cd6e7e4de19ff13a2e392664ddd8bbba86f" type="audio/mpeg">
    </audio>
    
    <script>
        let currentlyPlaying = null;
        
        function playPreview(previewUrl, button) {
            const audioPlayer = document.getElementById('audio-player');
            
            // Wenn der gleiche Song geklickt wird
            if (currentlyPlaying === button) {
                if (audioPlayer.paused) {
                    audioPlayer.play();
                    button.querySelector('.play-icon').textContent = '❚❚';
                } else {
                    audioPlayer.pause();
                    button.querySelector('.play-icon').textContent = '▶';
                }
                return;
            }
            
            // Wenn ein anderer Song geklickt wird
            if (currentlyPlaying) {
                currentlyPlaying.querySelector('.play-icon').textContent = '▶';
            }
            
            audioPlayer.src = previewUrl;
            audioPlayer.play();
            button.querySelector('.play-icon').textContent = '❚❚';
            currentlyPlaying = button;
            
            // Wenn der Song zu Ende ist
            audioPlayer.onended = function() {
                button.querySelector('.play-icon').textContent = '▶';
                currentlyPlaying = null;
            };
        }
    </script>
</body>
</html>
