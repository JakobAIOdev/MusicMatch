<?php
// Start session at the very beginning
session_start();

$pageTitle = "Your Spotify Profile";
$additionalCSS = '<link rel="stylesheet" href="./styles/profile.css">';

require_once 'vendor/autoload.php';
include_once "config.php";

// Refresh token if needed
if (isset($_SESSION['spotify_access_token']) && isset($_SESSION['spotify_token_expires']) && $_SESSION['spotify_token_expires'] < time() && isset($_SESSION['spotify_refresh_token'])) {
    try {
        $session = new SpotifyWebAPI\Session(
            $CLIENT_ID,
            $CLIENT_SECRET,
            $CALLBACK_URL
        );
        $session->refreshAccessToken($_SESSION['spotify_refresh_token']);
        $_SESSION['spotify_access_token'] = $session->getAccessToken();
        $_SESSION['spotify_token_expires'] = time() + $session->getTokenExpiration();
    } catch (Exception $e) {
        error_log('Failed to refresh token: ' . $e->getMessage());
    }
}
if (!isset($_SESSION['spotify_access_token'])) {
    header('Location: index.php');
    die();
}

if (isset($_SESSION['spotify_access_token'])) {
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($_SESSION['spotify_access_token']);
    try {
        $me = $api->me();
        if (!isset($_SESSION['userData']) || empty($_SESSION['userData'])) {
            $_SESSION['userData'] = [
                'id' => $me->id,
                'display_name' => $me->display_name,
                'email' => $me->email,
                'images' => $me->images
            ];
        }
        error_log('User validated in index: ' . $_SESSION['userData']['display_name']);
    } catch (Exception $e) {
        error_log('API Error in index: ' . $e->getMessage());
        unset($_SESSION['userData']);
        unset($_SESSION['spotify_access_token']);
    }
}

include "header.php";
?>
    <div class="profile-container">
        <h1>Your Spotify Profile</h1>
        
        <div class="profile">
            <?php if (isset($me->images[0]->url)): ?>
                <img src="<?php echo htmlspecialchars($me->images[0]->url); ?>" class="profile-img" alt="Profile Picture">
            <?php else: ?>
                <div class="profile-img" style="background-color: #ddd; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 3em;">👤</span>
                </div>
            <?php endif; ?>
            
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($me->display_name); ?></h2>
                <p><strong>E-Mail:</strong> <?php echo htmlspecialchars($me->email); ?></p>
                <p><strong>Spotify ID:</strong> <?php echo htmlspecialchars($me->id); ?></p>
                <p><strong>Country:</strong> <?php echo htmlspecialchars($me->country); ?></p>
                <?php if (isset($me->product)): ?>
                    <p><strong>Subscription:</strong> <?php echo ucfirst(htmlspecialchars($me->product)); ?></p>
                <?php endif; ?>
                <?php if (isset($me->followers->total)): ?>
                    <p><strong>Followers:</strong> <?php echo htmlspecialchars($me->followers->total); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="token-container">
            <h3>Current access token:</h3>
            <div class="token-text" id="access-token"><?php echo htmlspecialchars($_SESSION['spotify_access_token']); ?></div>
            <button class="copy-button" onclick="copyToken()">Copy token</button>
        </div>
        
        <div class="action-buttons">
            <a href="index.php" class="btn dashboard-btn">Go to Dashboard</a>
        </div>
    </div>

    <script>
        function copyToken() {
            const tokenText = document.getElementById('access-token');
            const selection = window.getSelection();
            const range = document.createRange();
            range.selectNodeContents(tokenText);
            selection.removeAllRanges();
            selection.addRange(range);
            document.execCommand('copy');
            selection.removeAllRanges();
            
            const button = document.querySelector('.copy-button');
            const originalText = button.textContent;
            button.textContent = 'Copied!';
            setTimeout(() => {
                button.textContent = originalText;
            }, 2000);
        }
    </script>
</body>
</html>

<?php include "footer.php" ?>