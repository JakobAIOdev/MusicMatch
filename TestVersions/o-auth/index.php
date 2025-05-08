<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Logout-Funktion
if (isset($_GET['logout'])) {
    // Session-Daten löschen
    session_unset();
    session_destroy();
    
    // Cookies löschen
    setcookie('spotify_auth_state', '', time() - 3600, '/');
    
    // Neue Session starten
    session_start();
}

// Error Handling
$authError = '';
if (isset($_GET['auth_error'])) {
    if ($_GET['auth_error'] === 'state_mismatch') {
        $authError = 'Sicherheitsüberprüfung fehlgeschlagen. Die Authentifizierung muss neu gestartet werden.';
    }
}

// Wenn der Benutzer bereits angemeldet ist, zur Profilseite weiterleiten
if (isAuthenticated()) {
    header('Location: profile.php');
    exit;
}

// Autorisierungs-URL generieren (verwendet jetzt eine konsistente State-Handhabung)
$authUrl = getAuthorizationUrl();

// Debug-Informationen sammeln
$sessionDebugInfo = [
    'session_id' => session_id(),
    'current_state' => $_SESSION['spotify_auth_state'] ?? 'not set',
    'pending_state' => $_SESSION['pending_spotify_auth_state'] ?? 'not set'
];
debug_log('Index page loaded', $sessionDebugInfo);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musik-Match - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
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
        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px 15px;
            margin: 20px 0;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 30px;
            text-align: left;
            font-size: 14px;
        }
        .actions {
            margin-top: 20px;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            margin-left: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1>Willkommen bei Musik-Match</h1>
    <p>Finde neue Musik, die zu deinem Geschmack passt!</p>
    
    <?php if ($authError): ?>
    <div class="error-message">
        <?php echo $authError; ?>
    </div>
    <?php endif; ?>
    
    <a href="<?php echo $authUrl; ?>" class="login-button">Mit Spotify anmelden</a>
    
    <?php if ($authError || isset($_GET['debug'])): ?>
    <div class="info-box">
        <h3>Session-Information:</h3>
        <p>Session ID: <?php echo session_id(); ?></p>
        <p>State in Session: <?php echo htmlspecialchars($_SESSION['spotify_auth_state'] ?? 'nicht gesetzt'); ?></p>
        <p>State in Cookie: <?php echo htmlspecialchars($_COOKIE['spotify_auth_state'] ?? 'nicht gesetzt'); ?></p>
        
        <?php if (isset($_SESSION['auth_error'])): ?>
        <h4>Fehlerdetails:</h4>
        <ul>
            <li>Empfangener State: <?php echo htmlspecialchars($_SESSION['auth_error']['received'] ?? 'nicht verfügbar'); ?></li>
            <li>Session State: <?php echo htmlspecialchars($_SESSION['auth_error']['session'] ?? 'nicht verfügbar'); ?></li>
            <li>Pending State: <?php echo htmlspecialchars($_SESSION['auth_error']['pending'] ?? 'nicht verfügbar'); ?></li>
            <li>Cookie State: <?php echo htmlspecialchars($_SESSION['auth_error']['cookie'] ?? 'nicht verfügbar'); ?></li>
        </ul>
        <?php endif; ?>
        
        <div class="actions">
            <a href="index.php?clear_session=1" class="btn-secondary">Session zurücksetzen</a>
            <a href="index.php" class="btn-secondary">Seite neu laden</a>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
