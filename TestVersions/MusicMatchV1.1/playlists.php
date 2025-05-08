<?php
require 'vendor/autoload.php';
include "config.php";
session_start();

if (!isset($_SESSION['spotify_access_token'])) {
    header('Location: index.php');
    exit;
}

$api = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken($_SESSION['spotify_access_token']);

try {
    $userData = $api->me();
} catch (Exception $e) {
    die('Error fetching user data: ' . $e->getMessage());
}

try {
    $playlists = $api->getMyPlaylists([
        'limit' => 50
    ]);
} catch (Exception $e) {
    die('Error fetching playlists: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Spotify Playlists</title>
    <link rel="stylesheet" type="text/css" href="./style.css">
</head>
<body>
    <div class="site-header">
        <div class="site-logo">
            <h1>MusicMatch</h1>
        </div>
        <div class="nav-user-info">
            <a href="dashboard.php" class="back-to-dashboard">Dashboard</a>
            <a href="profile.php" class="profile-link">
                <img src="<?php echo htmlspecialchars($userData->images[0]->url ?? 'img/default-avatar.png'); ?>" alt="Profile Picture">
                <span><?php echo htmlspecialchars($userData->display_name); ?></span>
            </a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <h1>Your Spotify Playlists</h1>
    
    <p>Found <strong><?php echo count($playlists->items); ?></strong> playlists in your Spotify account</p>
    
    <div class="content-wrapper">
        <?php if (count($playlists->items) > 0): ?>
            <?php foreach ($playlists->items as $index => $playlist): ?>
                <div class="card">
                    <img 
                        src="<?php echo isset($playlist->images[0]) ? htmlspecialchars($playlist->images[0]->url) : 'https://via.placeholder.com/120?text=No+Image'; ?>" 
                        class="image" 
                        alt="<?php echo htmlspecialchars($playlist->name); ?>"
                    >
                    <div class="item-info">
                        <h3><?php echo htmlspecialchars($playlist->name); ?></h3>
                        <?php if (!empty($playlist->description)): ?>
                            <p>Description: <?php echo htmlspecialchars($playlist->description); ?></p>
                        <?php endif; ?>
                        <p>Tracks: <?php echo $playlist->tracks->total; ?></p>
                        <p>Owner: <?php echo htmlspecialchars($playlist->owner->display_name); ?></p>
                        <?php if ($playlist->public): ?>
                            <p>Visibility: Public</p>
                        <?php else: ?>
                            <p>Visibility: Private</p>
                        <?php endif; ?>
                        <a href="<?php echo $playlist->external_urls->spotify; ?>" target="_blank" class="spotify-link">Open on Spotify</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No playlists found. Create some playlists on Spotify to see them here.</p>
        <?php endif; ?>
    </div>
</body>

<?php include "footer.php";?>
