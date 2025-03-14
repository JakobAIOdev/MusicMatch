<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Debug-Daten sammeln
$debugData = [
    'session_id' => session_id(),
    'get_params' => $_GET,
    'session_state' => $_SESSION['spotify_auth_state'] ?? 'not set',
    'pending_state' => $_SESSION['pending_spotify_auth_state'] ?? 'not set',
    'cookie_state' => $_COOKIE['spotify_auth_state'] ?? 'not set'
];
debug_log('Callback received', $debugData);

// Überprüfen, ob ein Fehler zurückgegeben wurde
if (isset($_GET['error'])) {
    echo "Fehler bei der Authentifizierung: " . $_GET['error'];
    echo "<br><a href='index.php'>Zurück zur Startseite</a>";
    exit;
}

// Verbesserte Überprüfung des State-Parameters mit mehreren Fallback-Optionen
if (isset($_GET['state'])) {
    $receivedState = $_GET['state'];
    $validStates = [
        $_SESSION['spotify_auth_state'] ?? '',
        $_SESSION['pending_spotify_auth_state'] ?? '',
        $_COOKIE['spotify_auth_state'] ?? ''
    ];
    
    // Debug-Hilfsmittel (kann in Produktion entfernt werden)
    echo "<!-- Received: " . htmlspecialchars($receivedState) . " -->\n";
    echo "<!-- Session: " . htmlspecialchars($validStates[0]) . " -->\n";
    echo "<!-- Pending: " . htmlspecialchars($validStates[1]) . " -->\n";
    echo "<!-- Cookie: " . htmlspecialchars($validStates[2]) . " -->\n";
    
    // Prüfen, ob der empfangene State mit einem der gespeicherten übereinstimmt
    if (!in_array($receivedState, $validStates) || empty($receivedState)) {
        // State für Debug-Zwecke speichern
        $_SESSION['auth_error'] = [
            'received' => $receivedState,
            'session' => $_SESSION['spotify_auth_state'] ?? null,
            'pending' => $_SESSION['pending_spotify_auth_state'] ?? null,
            'cookie' => $_COOKIE['spotify_auth_state'] ?? null
        ];
        
        // Zur Startseite umleiten mit Fehlerparameter
        header('Location: index.php?auth_error=state_mismatch');
        exit;
    }
    
    // State-Parameter bereinigen, nachdem er verifiziert wurde
    unset($_SESSION['pending_spotify_auth_state']);
}

// Überprüfen, ob der Authorization Code vorhanden ist
if (isset($_GET['code'])) {
    // Token anfordern
    $tokenData = requestAccessToken($_GET['code']);
    
    if (isset($tokenData['access_token'])) {
        // Token in der Session speichern
        $_SESSION['access_token'] = $tokenData['access_token'];
        $_SESSION['refresh_token'] = $tokenData['refresh_token'];
        $_SESSION['expires_at'] = time() + $tokenData['expires_in'];
        
        // Zur Profilseite weiterleiten
        header('Location: profile.php');
        exit;
    } else {
        echo "Fehler beim Anfordern des Tokens: ";
        echo "<pre>" . print_r($tokenData, true) . "</pre>";
        echo "<br><a href='index.php'>Zurück zur Startseite</a>";
        exit;
    }
} else {
    echo "Kein Authorization Code erhalten.";
    echo "<br><a href='index.php'>Zurück zur Startseite</a>";
    exit;
}
