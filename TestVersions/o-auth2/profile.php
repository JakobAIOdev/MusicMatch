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
        .token-container { 
            margin-top: 20px; 
            padding: 10px; 
            background-color: #f5f5f5; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
        }
        .token-text {
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
        }
        .copy-button {
            margin-top: 10px;
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
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
    
    <div class="token-container">
        <h3>Dein aktueller Access Token:</h3>
        <div class="token-text" id="access-token"><?php echo htmlspecialchars($_SESSION['spotify_access_token']); ?></div>
        <button class="copy-button" onclick="copyToken()">Token kopieren</button>
    </div>
    
    <div class="logout">
        <a href="logout.php">Abmelden</a>
    </div>
    
    <h3>Rohdaten:</h3>
    <pre><?php print_r($me); ?></pre>

    <script>
        function copyToken() {
            const tokenElement = document.getElementById('access-token');
            const tokenText = tokenElement.innerText;
            
            navigator.clipboard.writeText(tokenText)
                .then(() => {
                    alert('Access Token wurde in die Zwischenablage kopiert!');
                })
                .catch(err => {
                    console.error('Fehler beim Kopieren: ', err);
                    alert('Fehler beim Kopieren des Tokens.');
                });
        }
    </script>
</body>
</html>
