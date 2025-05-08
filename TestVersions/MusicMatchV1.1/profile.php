<?php
require 'vendor/autoload.php';
session_start();

if (!isset($_SESSION['spotify_access_token'])) {
    header('Location: index.php');
    die();
}

$api = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken($_SESSION['spotify_access_token']);

try {
    $me = $api->me();
} catch (Exception $e) {
    die('Fehler beim Abrufen der Benutzerdaten: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusicMatch Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="site-header">
        <div class="site-logo">
            <h1>MusicMatch</h1>
        </div>
        <div class="nav-user-info">
            <a href="dashboard.php" class="back-to-dashboard">Dashboard</a>
            <a href="profile.php" class="profile-link">
                <img src="<?php echo htmlspecialchars($me->images[0]->url ?? 'img/default-avatar.png'); ?>" alt="Profile Picture">
                <span><?php echo htmlspecialchars($me->display_name); ?></span>
            </a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

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

    <?php include "footer.php";?>

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