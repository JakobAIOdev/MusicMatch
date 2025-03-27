<?php
require_once 'vendor/autoload.php';
require_once 'config.php'; // Lade die API-Keys

session_start();

// Spotify API Konfiguration
$clientId = $CLIENT_ID;
$clientSecret = $CLIENT_SECRET;
$redirectUri = "http://localhost:8000/charts.php";

// Erstelle Session und API-Objekte
$session = new SpotifyWebAPI\Session(
    $clientId,
    $clientSecret,
    $redirectUri
);

$api = new SpotifyWebAPI\SpotifyWebAPI();

// Alternativen für die offiziellen Spotify-Playlists
// Verwende benutzererstellte Playlists statt der Spotify-eigenen

$chartPlaylists = [
    'global' => '6UeSakyzhiEt4NB3UAd6NQ', // Benutzer-erstellte Global Top 50
    'germany' => '7ue3jXbN1IXWNSwcQbAYKD', // Benutzer-erstellte Deutschland Top 50
    'usa' => '5R8dQlj4eXQ3IeXbFmDZsS', // Benutzer-erstellte USA Top 50
    'viral' => '2fmTTbBkXi8pewbUvG3CeZ'  // Benutzer-erstellte Viral 50
];

// Ausgewählte Chart
$selectedChart = $_GET['chart'] ?? 'global';
if (!isset($chartPlaylists[$selectedChart])) {
    $selectedChart = 'global';
}

// Authentifizierungsprozess
$error = '';
$chartData = null;
$songsArray = [];

// Wenn wir einen Code aus der Spotify-Authentifizierung erhalten haben
if (isset($_GET['code'])) {
    try {
        // Tausche den Code gegen ein Access Token ein
        $session->requestAccessToken($_GET['code']);
        
        // Speichere die Tokens in der Session
        $_SESSION['spotify_access_token'] = $session->getAccessToken();
        $_SESSION['spotify_refresh_token'] = $session->getRefreshToken();
        
        // Entferne den Code-Parameter aus der URL und leite weiter
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?chart=' . $selectedChart);
        exit;
    } catch (Exception $e) {
        $error = 'Authentifizierungsfehler: ' . $e->getMessage();
    }
}

// Wenn wir bereits ein Access Token haben
if (isset($_SESSION['spotify_access_token'])) {
    $api->setAccessToken($_SESSION['spotify_access_token']);
    
    // Versuche, die Playlist abzurufen
    try {
        $chartData = $api->getPlaylist($chartPlaylists[$selectedChart], [
            'fields' => 'name,description,tracks.items(track(id,name,album(name,images),artists(name)))'
        ]);
        
        // Extrahiere Songs aus den Chartdaten für JavaScript
        if (isset($chartData->tracks->items)) {
            foreach ($chartData->tracks->items as $index => $item) {
                $track = $item->track;
                if (!$track) continue;
                
                $artists = [];
                foreach ($track->artists as $artist) {
                    $artists[] = $artist->name;
                }
                
                $imageUrl = '';
                if (isset($track->album->images) && !empty($track->album->images)) {
                    if (count($track->album->images) > 1) {
                        $imageUrl = $track->album->images[1]->url;
                    } else {
                        $imageUrl = $track->album->images[0]->url;
                    }
                }
                
                $songsArray[] = [
                    'rank' => $index + 1,
                    'id' => $track->id,
                    'title' => $track->name,
                    'artist' => implode(', ', $artists),
                    'album' => $track->album->name,
                    'image' => $imageUrl
                ];
            }
        }
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        // Wenn das Token abgelaufen ist, versuche es zu erneuern
        if ($e->getCode() == 401 && isset($_SESSION['spotify_refresh_token'])) {
            try {
                $session->refreshAccessToken($_SESSION['spotify_refresh_token']);
                $_SESSION['spotify_access_token'] = $session->getAccessToken();
                
                $api->setAccessToken($_SESSION['spotify_access_token']);
                
                // Versuche es erneut
                $chartData = $api->getPlaylist($chartPlaylists[$selectedChart], [
                    'fields' => 'name,description,tracks.items(track(id,name,album(name,images),artists(name)))'
                ]);
                
                // Extrahiere Songs aus den Chartdaten für JavaScript
                if (isset($chartData->tracks->items)) {
                    foreach ($chartData->tracks->items as $index => $item) {
                        $track = $item->track;
                        if (!$track) continue;
                        
                        $artists = [];
                        foreach ($track->artists as $artist) {
                            $artists[] = $artist->name;
                        }
                        
                        $imageUrl = '';
                        if (isset($track->album->images) && !empty($track->album->images)) {
                            if (count($track->album->images) > 1) {
                                $imageUrl = $track->album->images[1]->url;
                            } else {
                                $imageUrl = $track->album->images[0]->url;
                            }
                        }
                        
                        $songsArray[] = [
                            'rank' => $index + 1,
                            'id' => $track->id,
                            'title' => $track->name,
                            'artist' => implode(', ', $artists),
                            'album' => $track->album->name,
                            'image' => $imageUrl
                        ];
                    }
                }
            } catch (Exception $e) {
                $error = 'Token-Erneuerungsfehler: ' . $e->getMessage();
            }
        } else {
            $error = 'API-Fehler: ' . $e->getMessage();
        }
    } catch (Exception $e) {
        $error = 'Allgemeiner Fehler: ' . $e->getMessage();
    }
} else {
    // Wenn wir kein Token haben, leite zur Spotify-Authentifizierung weiter
    $options = [
        'scope' => [
            'playlist-read-private',
            'user-read-private',
        ],
    ];
    
    header('Location: ' . $session->getAuthorizeUrl($options));
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Charts</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f8f8;
            color: #333;
        }
        h1, h2 {
            color: #1DB954; /* Spotify Grün */
        }
        .chart-selector {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .chart-btn {
            background: #1DB954;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .chart-btn:hover {
            background: #1ed760;
        }
        .chart-btn.active {
            background: #191414; /* Spotify Schwarz */
        }
        .error {
            color: #d51007;
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .chart-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .chart-title {
            margin: 0;
            font-size: 24px;
        }
        .chart-description {
            color: #666;
            margin-top: 5px;
        }
        .song-list {
            list-style: none;
            padding: 0;
        }
        .song-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        .song-item:hover {
            background-color: #f5f5f5;
        }
        .song-rank {
            font-size: 18px;
            font-weight: bold;
            width: 40px;
            text-align: center;
            color: #999;
        }
        .song-image {
            width: 60px;
            height: 60px;
            border-radius: 4px;
            margin-right: 15px;
        }
        .song-info {
            flex-grow: 1;
        }
        .song-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .song-artist {
            color: #666;
            font-size: 14px;
        }
        .song-album {
            color: #999;
            font-size: 12px;
            margin-top: 3px;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        .logout-btn {
            background: #666;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            float: right;
        }
    </style>
</head>
<body>
    <h1>Spotify Charts</h1>
    
    <?php if (isset($_SESSION['spotify_access_token'])): ?>
        <a href="?logout=1" class="logout-btn">Abmelden</a>
    <?php endif; ?>
    
    <div class="chart-selector">
        <a href="?chart=global" class="chart-btn <?php echo $selectedChart === 'global' ? 'active' : ''; ?>">Global Top 50</a>
        <a href="?chart=germany" class="chart-btn <?php echo $selectedChart === 'germany' ? 'active' : ''; ?>">Deutschland Top 50</a>
        <a href="?chart=usa" class="chart-btn <?php echo $selectedChart === 'usa' ? 'active' : ''; ?>">USA Top 50</a>
        <a href="?chart=viral" class="chart-btn <?php echo $selectedChart === 'viral' ? 'active' : ''; ?>">Viral 50</a>
    </div>
    
    <?php if ($error): ?>
        <div class="error">
            <strong>Fehler:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php elseif ($chartData): ?>
        <div class="chart-container">
            <div class="chart-header">
                <div>
                    <h2 class="chart-title"><?php echo htmlspecialchars($chartData->name); ?></h2>
                    <?php if (isset($chartData->description) && !empty($chartData->description)): ?>
                        <p class="chart-description"><?php echo htmlspecialchars($chartData->description); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <ul class="song-list">
                <?php 
                if (isset($chartData->tracks->items) && is_array($chartData->tracks->items)):
                    foreach ($chartData->tracks->items as $index => $item): 
                        $track = $item->track;
                        if (!$track) continue; // Überspringe, falls kein Track vorhanden
                ?>
                    <li class="song-item">
                        <div class="song-rank"><?php echo $index + 1; ?></div>
                        
                        <?php 
                        $imageUrl = '';
                        if (isset($track->album->images) && !empty($track->album->images)) {
                            // Wähle ein mittelgroßes Bild, falls verfügbar
                            if (count($track->album->images) > 1) {
                                $imageUrl = $track->album->images[1]->url;
                            } else {
                                $imageUrl = $track->album->images[0]->url;
                            }
                        }
                        ?>
                        
                        <?php if (!empty($imageUrl)): ?>
                            <img class="song-image" src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Album Cover">
                        <?php else: ?>
                            <div class="song-image" style="background-color: #ddd; display: flex; align-items: center; justify-content: center;">
                                <span>🎵</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="song-info">
                            <div class="song-title"><?php echo htmlspecialchars($track->name); ?></div>
                            <div class="song-artist">
                                <?php 
                                $artists = [];
                                foreach ($track->artists as $artist) {
                                    $artists[] = $artist->name;
                                }
                                echo htmlspecialchars(implode(', ', $artists));
                                ?>
                            </div>
                            <div class="song-album"><?php echo htmlspecialchars($track->album->name); ?></div>
                        </div>
                    </li>
                <?php 
                    endforeach;
                else:
                ?>
                    <li class="loading">Keine Songs in dieser Playlist gefunden.</li>
                <?php endif; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="loading">Daten werden geladen...</div>
    <?php endif; ?>
    
    <script>
    // JavaScript-Array mit allen Songs für mögliche weitere Verwendung
    const chartSongs = <?php echo json_encode($songsArray); ?>;
    
    console.log('Chart Songs:', chartSongs);
    </script>
</body>
</html>
