<?php
require 'vendor/autoload.php';
session_start();

// Prüfen, ob ein Token vorhanden ist
if (!isset($_SESSION['spotify_access_token'])) {
    header('Location: index.php');
    die();
}

// API-Client initialisieren
$api = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken($_SESSION['spotify_access_token']);

// Benutzerdaten abrufen
try {
    $me = $api->me();
} catch (Exception $e) {
    die('Fehler beim Abrufen der Benutzerdaten: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Spotify Profil</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .profile { display: flex; align-items: center; }
        .profile-img { width: 100px; height: 100px; border-radius: 50%; margin-right: 20px; }
        .logout { margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Dein Spotify Profil</h1>
    
    <div class="profile">
        <?php if (isset($me->images[0]->url)): ?>
            <img src="<?php echo htmlspecialchars($me->images[0]->url); ?>" class="profile-img">
        <?php endif; ?>
        
        <div>
            <h2><?php echo htmlspecialchars($me->display_name); ?></h2>
            <p>E-Mail: <?php echo htmlspecialchars($me->email); ?></p>
            <p>Spotify ID: <?php echo htmlspecialchars($me->id); ?></p>
            <p>Land: <?php echo htmlspecialchars($me->country); ?></p>
        </div>
    </div>
    
    <div class="logout">
        <a href="logout.php">Abmelden</a>
    </div>
    
    <h3>Rohdaten:</h3>
    <pre><?php print_r($me); ?></pre>
</body>
</html>
