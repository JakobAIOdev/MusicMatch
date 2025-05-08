<?php
    require 'vendor/autoload.php';
    include 'config.php';
    session_start();

    // Prüfen, ob ein Token vorhanden ist
    if (!isset($_SESSION['spotify_access_token'])) {
        header('Location: index.php');
        die();
    }

    // API-Client initialisieren
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($_SESSION['spotify_access_token']);

    // search song via text input and get song information
    $searchQuery = '';
    $searchResults = null;
    $selectedTrack = null;
    
    // Process search form submission
    if (isset($_POST['search']) && !empty($_POST['query'])) {
        $searchQuery = $_POST['query'];
        try {
            // Search for tracks with the query
            $searchResults = $api->search($searchQuery, 'track', ['limit' => 10]);
        } catch (Exception $e) {
            echo 'Fehler bei der Suche: ' . $e->getMessage();
        }
    }
    
    // If a specific track ID is provided
    if (isset($_GET['track_id']) && !empty($_GET['track_id'])) {
        try {
            $selectedTrack = $api->getTrack($_GET['track_id']);
        } catch (Exception $e) {
            echo 'Fehler beim Abrufen des Tracks: ' . $e->getMessage();
        }
    }

    // Remove unreachable code with hardcoded TRACK_ID
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusicMatch - Songinformationen</title>
    <link rel="stylesheet" href="./Style/style.css">
</head>
<body>
    <div class="container">
        <h1>MusicMatch - Songinformationen</h1>
        
        <!-- Suchformular -->
        <div class="search-section">
            <form method="POST" action="">
                <input type="text" name="query" placeholder="Song suchen..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit" name="search">Suchen</button>
            </form>
        </div>
        
        <!-- Suchergebnisse anzeigen -->
        <?php if ($searchResults && !empty($searchResults->tracks->items)): ?>
            <div class="search-results">
                <h2>Suchergebnisse</h2>
                <ul>
                    <?php foreach ($searchResults->tracks->items as $track): ?>
                        <li>
                            <a href="?track_id=<?php echo $track->id; ?>">
                                <?php echo htmlspecialchars($track->name); ?> - 
                                <?php 
                                    $artists = [];
                                    foreach ($track->artists as $artist) {
                                        $artists[] = htmlspecialchars($artist->name);
                                    }
                                    echo implode(', ', $artists);
                                ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Ausgewählten Track anzeigen -->
        <?php if ($selectedTrack): ?>
            <div class="track-details">
                <h2><?php echo htmlspecialchars($selectedTrack->name); ?></h2>
                <p>
                    Künstler: 
                    <?php 
                        $artists = [];
                        foreach ($selectedTrack->artists as $artist) {
                            $artists[] = htmlspecialchars($artist->name);
                        }
                        echo implode(', ', $artists);
                    ?>
                </p>
                <p>Album: <?php echo htmlspecialchars($selectedTrack->album->name); ?></p>
                <?php if (isset($selectedTrack->album->images[0])): ?>
                    <img src="<?php echo htmlspecialchars($selectedTrack->album->images[0]->url); ?>" alt="Album Cover" class="album-cover">
                <?php endif; ?>
                
                <p>Dauer: <?php echo round($selectedTrack->duration_ms / 1000 / 60, 2); ?> Minuten</p>
                <p>Popularität: <?php echo $selectedTrack->popularity; ?>/100</p>
                
                <a href="<?php echo htmlspecialchars($selectedTrack->external_urls->spotify); ?>" target="_blank" class="spotify-link">Auf Spotify anhören</a>
            </div>
        <?php endif; ?>
        
        <div class="navigation">
            <a href="./Pages/dashboard.php" class="btn">Zurück zum Dashboard</a>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </div>
</body>
</html>