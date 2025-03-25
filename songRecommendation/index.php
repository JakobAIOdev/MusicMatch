<?php
// Starte die PHP-Session
session_start();

// Spotify API Credentials
$CLIENT_ID = "499f3c04f86c48c6a24ae6e3987853b2";
$CLIENT_SECRET = "956177b6040a46b699b143846123ec48";
$REDIRECT_URI = "http://localhost:8000/callback.php";

// Benötigte Berechtigungen
$scope = 'user-read-private user-read-email user-top-read playlist-modify-public playlist-modify-private';

// Generiere einen zufälligen State-Parameter für CSRF-Schutz
$state = bin2hex(random_bytes(16));
$_SESSION['spotify_auth_state'] = $state;

// Erstelle die Autorisierungs-URL
$authUrl = 'https://accounts.spotify.com/authorize?' . http_build_query([
    'response_type' => 'code',
    'client_id' => $CLIENT_ID,
    'scope' => $scope,
    'redirect_uri' => $REDIRECT_URI,
    'state' => $state,
    'show_dialog' => true // Erzwinge den Dialog, um Cache-Probleme zu vermeiden
]);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Spotify Music Matcher</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
            background-color: #121212;
            color: #ffffff;
        }
        .login-button {
            display: inline-block;
            background-color: #1DB954;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 30px;
            font-weight: bold;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <h1>Spotify Music Matcher</h1>
    <p>Entdecke neue Songs basierend auf deinem Musikgeschmack</p>
    <a href="<?php echo $authUrl; ?>" class="login-button">Mit Spotify anmelden</a>
</body>
</html>
