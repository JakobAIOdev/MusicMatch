<?php
require_once '../includes/session_handler.php';
require_once '../vendor/autoload.php';
require_once '../includes/config.php';

if (isset($_GET['error'])) {
    $errorType = $_GET['error'];
    $errorDesc = isset($_GET['error_description']) ? $_GET['error_description'] : '';
    
    showAuthErrorPage("Authorization Failed", $errorDesc ?: $errorType);
    exit;
}

if (isset($_GET['message']) && isset($_GET['error']) && $_GET['error'] === 'api_error') {
    $errorMessage = $_GET['message'];
    
    if (strpos($errorMessage, 'the user may not be registered') !== false || 
        strpos($errorMessage, 'Check settings on developer.spotify.com/dashboard') !== false) {
        
        showAuthErrorPage("Developer Mode Restriction", "Your Spotify account is not authorized to use this application during development mode.");
        exit;
    }
    
    showAuthErrorPage("Authorization Failed", $errorMessage);
    exit;
}

$session = new SpotifyWebAPI\Session(
    $CLIENT_ID,
    $CLIENT_SECRET,
    $CALLBACK_URL
);

// Validate state to prevent CSRF attacks
if (!isset($_GET['state']) || !isset($_SESSION['spotify_auth_state']) || $_GET['state'] !== $_SESSION['spotify_auth_state']) {
    error_log('State mismatch in Spotify callback');
    showAuthErrorPage("Authentication Error", "Invalid state parameter. Please try logging in again.");
    exit;
}

if (isset($_GET['code'])) {
    try {
        $session->requestAccessToken($_GET['code']);
        
        $api = new SpotifyWebAPI\SpotifyWebAPI();
        $api->setAccessToken($session->getAccessToken());
        
        try {
            $userData = $api->me();
            
            $_SESSION['userData'] = [
                'id' => $userData->id,
                'display_name' => $userData->display_name,
                'email' => $userData->email,
                'images' => $userData->images,
                'subscription' => $userData->product,
            ];
            
            $_SESSION['spotify_access_token'] = $session->getAccessToken();
            $_SESSION['spotify_refresh_token'] = $session->getRefreshToken();
            $_SESSION['spotify_token_expires'] = time() + $session->getTokenExpiration();
            
            $redirectTo = '../index.php';
            if (isset($_SESSION['login_redirect'])) {
                $isLocalEnvironment = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                                    $_SERVER['HTTP_HOST'] === '127.0.0.1');

                if (strpos($_SESSION['login_redirect'], './') === 0) {
                    if (!$isLocalEnvironment) {
                        $_SESSION['login_redirect'] = '/~fhs52920/MusicMatch/' . substr($_SESSION['login_redirect'], 2);
                    } else {
                        $_SESSION['login_redirect'] = '/' . substr($_SESSION['login_redirect'], 2);
                    }
                }
                
                $redirectTo = $_SESSION['login_redirect'];
                unset($_SESSION['login_redirect']);
            }
            
            header('Location: ' . $redirectTo);
            exit;
        } catch (Exception $e) {
            error_log('Spotify API Error in callback: ' . $e->getMessage());
            showAuthErrorPage("API Error", $e->getMessage());
            exit;
        }
    } catch (Exception $e) {
        error_log('Spotify Session Error: ' . $e->getMessage());
        showAuthErrorPage("Authorization Error", $e->getMessage());
        exit;
    }
} else {
    error_log('No code received from Spotify');
    showAuthErrorPage("Authorization Failed", "No authorization code received from Spotify.");
    exit;
}

function showAuthErrorPage($title, $errorMessage) {
    global $BASE_URL;
    
    $pageTitle = $title;
    $additionalCSS = "<link rel='stylesheet' href='{$BASE_URL}assets/css/auth.css'>";
    include "../templates/header.php";
    ?>
    
    <section class="login-section">
        <div class="container">
            <div class="auth-card">
                <h1><?php echo htmlspecialchars($title); ?></h1>
                
                <?php if (defined('SPOTIFY_DEV_MODE') && SPOTIFY_DEV_MODE === true): ?>
                <div class="dev-mode-notice">
                    <h3>⚠️ Development Mode</h3>
                    <p>MusicMatch is currently in development mode and your Spotify account is not authorized for testing.</p>
                    <p>Only Spotify accounts that have been added to the developer dashboard can access this application during development.</p>
                </div>
                <?php endif; ?>
                
                <div class="error-details">
                    <p><?php echo htmlspecialchars($errorMessage); ?></p>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <a href="<?php echo $BASE_URL; ?>" class="btn btn-primary btn-block">Back to Home</a>
                </div>
                
                <div class="auth-info">
                    <p>Need access for testing? Contact: <a href="mailto:musicmatch@jakobaio.com">musicmatch@jakobaio.com</a></p>
                </div>
            </div>
        </div>
    </section>
    
    <?php
    include "../templates/footer.php";
}
?>