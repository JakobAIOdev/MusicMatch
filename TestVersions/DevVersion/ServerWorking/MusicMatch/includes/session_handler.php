<?php
function configureSession(){
    // Check if session is already active
    if (session_status() === PHP_SESSION_NONE) {
        // Set cookie parameters BEFORE starting the session
        session_set_cookie_params([
            'lifetime' => 3600, // 1 Stunde
            'path' => '/~fhs52920/MusicMatch/',
            'secure' => true,  // für HTTPS
            'httponly' => true, // keine JS-Zugriffe
            'samesite' => 'Lax' // CSRF-Schutz
        ]);
        session_start();
    }
    
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    }

    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800) {
        destroySession();
        header('Location: /auth/login.php?message=session_expired');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
    
    // reset session if it is older than 30 minutes against session fixation
    if (!isset($_SESSION['created']) || time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

function destroySession() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    
    session_destroy();
}

function isLoggedIn() {
    return isset($_SESSION['spotify_access_token']) && !empty($_SESSION['spotify_access_token']);
}

configureSession();

?>