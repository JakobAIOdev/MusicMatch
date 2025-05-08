<?php
require 'vendor/autoload.php';
session_start();

/**
 * Holt eine bestimmte Anzahl von gespeicherten Tracks des Nutzers
 * 
 * @param SpotifyWebAPI\SpotifyWebAPI $api Die API-Instanz
 * @param int $maxTracks Maximale Anzahl der zu holenden Tracks (0 = alle)
 * @return array Array mit den Track-Informationen
 */
function getSavedTracks($api, $maxTracks = 10) {
    $limit = 50; // Maximale Anzahl von Tracks pro Anfrage (Spotify-Limit)
    $offset = 0;
    $allSavedTracks = [];
    $totalTracks = 0;
    
    do {
        try {
            $savedTracks = $api->getMySavedTracks([
                'limit' => $limit,
                'offset' => $offset
            ]);
            
            // Füge die Tracks zum Gesamtergebnis hinzu
            foreach ($savedTracks->items as $item) {
                $track = $item->track;
                $allSavedTracks[] = [
                    'id' => $track->id,
                    'name' => $track->name,
                    'artist' => $track->artists[0]->name,
                    'album' => $track->album->name,
                    'image' => isset($track->album->images[0]->url) ? $track->album->images[0]->url : null,
                    'preview_url' => $track->preview_url,
                    'external_url' => $track->external_urls->spotify,
                    'added_at' => $item->added_at
                ];
                
                $totalTracks++;
                
                // Wenn maxTracks > 0 und wir haben genug Tracks, breche die Schleife ab
                if ($maxTracks > 0 && $totalTracks >= $maxTracks) {
                    break 2; // Breche beide Schleifen ab
                }
            }
            
            $offset += $limit;
            
        } catch (Exception $e) {
            echo 'Fehler beim Abrufen der Tracks: ' . $e->getMessage();
            break;
        }
        
    } while ($offset < $savedTracks->total && ($maxTracks === 0 || $totalTracks < $maxTracks));
    
    return $allSavedTracks;
}

// Spotify API Credentials
$clientId = '499f3c04f86c48c6a24ae6e3987853b2';
$clientSecret = '956177b6040a46b699b143846123ec48';
$redirectUri = 'http://localhost:8000/callback.php';

// Erstelle Session und API-Objekte
$session = new SpotifyWebAPI\Session(
    $clientId,
    $clientSecret,
    $redirectUri
);

$api = new SpotifyWebAPI\SpotifyWebAPI();

// Überprüfe, ob der Nutzer bereits authentifiziert ist
if (isset($_SESSION['spotify_access_token'])) {
    $api->setAccessToken($_SESSION['spotify_access_token']);
    
    // Überprüfe, ob das Token abgelaufen ist
    if (isset($_SESSION['spotify_token_expires']) && time() > $_SESSION['spotify_token_expires']) {
        // Token erneuern
        $session->refreshAccessToken($_SESSION['spotify_refresh_token']);
        
        $accessToken = $session->getAccessToken();
        $refreshToken = $session->getRefreshToken();
        
        // Speichere die neuen Token-Daten in der Session
        $_SESSION['spotify_access_token'] = $accessToken;
        $_SESSION['spotify_refresh_token'] = $refreshToken;
        $_SESSION['spotify_token_expires'] = time() + $session->getTokenExpiration();
        
        $api->setAccessToken($accessToken);
    }
    
    // Anzahl der zu holenden Tracks (0 = alle)
    $tracksToFetch = isset($_GET['tracks']) ? intval($_GET['tracks']) : 0;
    
    // Hole die gespeicherten Tracks
    //$allSavedTracks = getSavedTracks($api, $tracksToFetch);
    $allSavedTracks = getSavedTracks($api, 20);
    
    // Ausgabe der Ergebnisse
    echo '<h1>Deine Lieblingssongs</h1>';
    
    if (!empty($allSavedTracks)) {
        echo '<p>Wir haben ' . count($allSavedTracks) . ' favorisierte Songs gefunden.</p>';
        
        echo '<h2>Deine Lieblingssongs</h2>';
        echo '<ul>';
        foreach ($allSavedTracks as $track) {
            echo '<li>';
            echo $track['name'] . ' von ' . $track['artist'] . ' (Album: ' . $track['album'] . ')';
            
            if ($track['preview_url']) {
                echo '<br><audio controls><source src="' . $track['preview_url'] . '" type="audio/mpeg"></audio>';
            }
            
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Keine favorisierten Songs gefunden. Speichere einige Songs in deiner Spotify-Bibliothek!</p>';
    }
} else {
    // Wenn der Nutzer nicht authentifiziert ist, leite zur Anmeldeseite weiter
    $options = [
        'scope' => [
            'user-library-read',
            'user-read-private',
            'user-read-email'
        ],
    ];
    
    header('Location: ' . $session->getAuthorizeUrl($options));
    die();
}
?>
