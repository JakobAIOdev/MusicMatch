<?php
require 'vendor/autoload.php';
include "config.php";
session_start();

$isLoggedIn = isset($_SESSION['spotify_access_token']);

$userData = null;
if ($isLoggedIn) {
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($_SESSION['spotify_access_token']);

    try {
        $userData = $api->me();
    } catch (Exception $e) {
        // Token expired
        $isLoggedIn = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./style.css">
    <title>MusicMatch dashboard</title>
</head>
<body>
    <div class="dashboard-container">
        <div class="site-header">
            <div class="site-logo">
                <h1>MusicMatch</h1>
            </div>
            <div class="nav-user-info">
                <a href="profile.php" class="profile-link">
                    <img src="<?php echo htmlspecialchars($userData->images[0]->url ?? 'img/default-avatar.png'); ?>" alt="Profile Picture">
                    <span><?php echo htmlspecialchars($userData->display_name); ?></span>
                </a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <h1>Dashboard</h1>

        <div class="dashboard-grid">
            <a href="profile.php" class="dashboard-box">
                <div class="box-content">
                    <i class="icon-profile"></i>
                    <h2>My Profile</h2>
                    <p>View and edit your profile information</p>
                </div>
            </a>

            <a href="favorites.php" class="dashboard-box">
                <div class="box-content">
                    <i class="icon-matches"></i>
                    <h2>My Favorites</h2>
                    <p>See your most listend to Artists and Songs</p>
                </div>
            </a>

            <a href="playlists.php" class="dashboard-box">
                <div class="box-content">
                    <i class="icon-playlists"></i>
                    <h2>Playlists</h2>
                    <p>Browse and share playlists</p>
                </div>
            </a>

            <a href="discover.php" class="dashboard-box">
                <div class="box-content">
                    <i class="icon-discover"></i>
                    <h2>Discover</h2>
                    <p>Find new music based on your tastes</p>
                </div>
            </a>

            <a href="events.php" class="dashboard-box">
                <div class="box-content">
                    <i class="icon-events"></i>
                    <h2>Events</h2>
                    <p>Concerts and music events near you</p>
                </div>
            </a>

            <a href="settings.php" class="dashboard-box">
                <div class="box-content">
                    <i class="icon-settings"></i>
                    <h2>Settings</h2>
                    <p>Manage your account preferences</p>
                </div>
            </a>
        </div>
    </div>
</body>
<?php
include "footer.php";
?>