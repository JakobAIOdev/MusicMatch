<?php
require 'vendor/autoload.php';

$clientId = '499f3c04f86c48c6a24ae6e3987853b2';
$clientSecret = '956177b6040a46b699b143846123ec48';

$api = new SpotifyWebAPI\SpotifyWebAPI();

// Token abrufen
$session = new SpotifyWebAPI\Session($clientId, $clientSecret);
$session->requestCredentialsToken();
$accessToken = $session->getAccessToken();

$api->setAccessToken($accessToken);

function getPreviewUrl($trackId) {
    $url = "https://open.spotify.com/track/" . $trackId;
    $html = file_get_contents($url);
    
    if (preg_match('/"preview_url":"(https:\\\/\\\/p\.scdn\.co\\\/mp3-preview\\\/[^"]+)"/', $html, $matches)) {
        return str_replace('\/', '/', $matches[1]);
    }
    
    return null;
}

$searchResults = [];
$previewUrl = '';

if (isset($_GET['search'])) {
    $searchResults = $api->search($_GET['search'], 'track', ['limit' => 10]);
}

if (isset($_GET['trackId'])) {
    $previewUrl = getPreviewUrl($_GET['trackId']);
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Spotify Track Suche und Preview</title>
</head>
<body>
    <h1>Spotify Track Suche und Preview</h1>
    
    <form action="" method="get">
        <input type="text" name="search" placeholder="Track suchen">
        <button type="submit">Suchen</button>
    </form>

    <?php if (!empty($searchResults)): ?>
        <h2>Suchergebnisse:</h2>
        <ul>
            <?php foreach ($searchResults->tracks->items as $track): ?>
                <li>
                    <?= htmlspecialchars($track->name) ?> - <?= htmlspecialchars($track->artists[0]->name) ?>
                    <a href="?trackId=<?= $track->id ?>">Preview URL abrufen</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($previewUrl): ?>
        <h2>Preview URL:</h2>
        <p><?= htmlspecialchars($previewUrl) ?></p>
        <audio controls src="<?= htmlspecialchars($previewUrl) ?>"></audio>
    <?php elseif (isset($_GET['trackId'])): ?>
        <p>Keine Preview URL verfügbar für diesen Track.</p>
    <?php endif; ?>
</body>
</html>
