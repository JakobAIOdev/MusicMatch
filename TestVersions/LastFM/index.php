<?php
session_start();

// API-Schlüssel und Shared Secret
$apiKey = '541be5acacce7e2b53dd6ed0b68955aa';
$sharedSecret = '6b09476829dd58e697669c2061ed421d';

// Last.fm API-Endpunkt
$apiUrl = 'http://ws.audioscrobbler.com/2.0/';

// Authentifizierungs-URL
$authUrl = "https://www.last.fm/api/auth/?api_key=$apiKey";

// Callback-URL für die Authentifizierung
$callbackUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
    "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";

// OAuth-Callback verarbeiten
if (isset($_GET['token'])) {
    try {
        // Session-Schlüssel abrufen
        $signature = md5("api_key{$apiKey}methodauth.getSessiontoken{$_GET['token']}{$sharedSecret}");
        $url = "{$apiUrl}?method=auth.getSession&api_key={$apiKey}&token={$_GET['token']}&api_sig={$signature}&format=json";
        
        $response = file_get_contents($url);
        $sessionData = json_decode($response, true);
        
        if (isset($sessionData['session']['key'])) {
            $_SESSION['lastfm_session'] = $sessionData['session']['key'];
            $_SESSION['lastfm_user'] = $sessionData['session']['name'];
        } else {
            $error = "Fehler bei der Authentifizierung: " . json_encode($sessionData);
        }
        
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Funktion zum Abrufen von Last.fm-Daten
function callLastFmApi($method, $params = []) {
    global $apiKey, $apiUrl;
    
    $params['method'] = $method;
    $params['api_key'] = $apiKey;
    $params['format'] = 'json';
    
    $url = $apiUrl . '?' . http_build_query($params);
    $response = file_get_contents($url);
    
    return json_decode($response, true);
}

// HTML-Ausgabe starten
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Last.fm API Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

<h1>Last.fm API Test</h1>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="mb-4">
    <h3>Authentifizierung</h3>
    <?php if (!isset($_SESSION['lastfm_session'])): ?>
        <a href="<?= $authUrl ?>&cb=<?= urlencode($callbackUrl) ?>" class="btn btn-primary">Mit Last.fm verbinden</a>
    <?php else: ?>
        <div class="alert alert-success">
            Erfolgreich verbunden als: <strong><?= htmlspecialchars($_SESSION['lastfm_user']) ?></strong>
            <a href="?logout=1" class="btn btn-sm btn-outline-danger ms-3">Abmelden</a>
        </div>
    <?php endif; ?>
</div>

<div class="mb-4">
    <h3>Künstlerinformationen</h3>
    <?php
    try {
        $artistInfo = callLastFmApi('artist.getInfo', ['artist' => 'Radiohead']);
        
        if (isset($artistInfo['artist'])) {
            $artist = $artistInfo['artist'];
            echo '<div class="card mb-3">';
            if (isset($artist['image'][3]['#text'])) {
                echo '<img src="' . htmlspecialchars($artist['image'][3]['#text']) . '" class="card-img-top" style="max-width: 300px">';
            }
            echo '<div class="card-body">';
            echo '<h5 class="card-title">' . htmlspecialchars($artist['name']) . '</h5>';
            if (isset($artist['bio']['summary'])) {
                echo '<p class="card-text">' . $artist['bio']['summary'] . '</p>';
            }
            echo '</div></div>';
        } else {
            echo '<div class="alert alert-warning">Keine Künstlerinformationen gefunden.</div>';
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Fehler beim Abrufen der Künstlerinfo: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
</div>

<div class="mb-4">
    <h3>Ähnliche Tracks</h3>
    <?php
    try {
        $similarTracks = callLastFmApi('track.getSimilar', [
            'artist' => 'Radiohead',
            'track' => 'Karma Police',
            'limit' => 5
        ]);
        
        if (isset($similarTracks['similartracks']['track'])) {
            $tracks = $similarTracks['similartracks']['track'];
            
            echo '<div class="list-group">';
            foreach ($tracks as $track) {
                echo '<div class="list-group-item">';
                echo '<div class="d-flex w-100 justify-content-between">';
                echo '<h5 class="mb-1">' . htmlspecialchars($track['name']) . '</h5>';
                echo '<small>' . htmlspecialchars($track['match']) . '</small>';
                echo '</div>';
                echo '<p class="mb-1">Künstler: ' . htmlspecialchars($track['artist']['name']) . '</p>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning">Keine ähnlichen Tracks gefunden.</div>';
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Fehler beim Abrufen ähnlicher Tracks: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
</div>

<?php if (isset($_SESSION['lastfm_user'])): ?>
<div class="mb-4">
    <h3>Deine Top-Künstler</h3>
    <?php
    try {
        $topArtists = callLastFmApi('user.getTopArtists', [
            'user' => $_SESSION['lastfm_user'],
            'period' => 'overall',
            'limit' => 5
        ]);
        
        if (isset($topArtists['topartists']['artist'])) {
            $artists = $topArtists['topartists']['artist'];
            
            echo '<div class="row">';
            foreach ($artists as $artist) {
                echo '<div class="col-md-4 mb-3">';
                echo '<div class="card h-100">';
                if (isset($artist['image'][2]['#text'])) {
                    echo '<img src="' . htmlspecialchars($artist['image'][2]['#text']) . '" class="card-img-top">';
                }
                echo '<div class="card-body">';
                echo '<h5 class="card-title">' . htmlspecialchars($artist['name']) . '</h5>';
                echo '<p class="card-text">Wiedergaben: ' . htmlspecialchars($artist['playcount']) . '</p>';
                echo '</div></div></div>';
            }
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning">Keine Top-Künstler gefunden.</div>';
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Fehler beim Abrufen der Top-Künstler: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
</div>
<?php endif; ?>

</body>
</html>
