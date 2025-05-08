<?php
function configureSession(){
    if (session_status() === PHP_SESSION_NONE) {
        $isLocalEnvironment = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
                               ($_SERVER['HTTP_HOST'] ?? '') === '127.0.0.1');
        
        session_set_cookie_params([
            'lifetime' => 3600, // 1 hour
            'path' => '/',
            'domain' => '', // empty for localhost
            'secure' => !$isLocalEnvironment,
            'httponly' => true, // no js access
            'samesite' => 'Lax' // CSRF-secure
        ]);
        
        session_start();
        error_log('SESSION DEBUG: Started session with ID: ' . session_id());
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