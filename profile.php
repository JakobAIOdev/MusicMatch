<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = "Your Spotify Profile";
$additionalCSS = '<link rel="stylesheet" href="./assets/css/profile.css">';

require_once './includes/session_handler.php';
include "./templates/header.php";
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';

if (!isLoggedIn()) {
    header('Location: /auth/login.php');
    exit;
}


$api = getSpotifyApi();

$me = $api->me();

?>
<div class="profile-container">
    <h1>Your Spotify <span>Profile</span></h1>

    <div class="profile">
        <?php if (isset($me->images[0]->url)): ?>
            <img src="<?php echo htmlspecialchars($me->images[0]->url); ?>" class="profile-img" alt="Profile Picture">
        <?php else: ?>
            <div class="profile-img">
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
    <div class="action-buttons">
            <a href="<?php echo htmlspecialchars($me->external_urls->spotify);?>" class="btn spotify-btn">Open on Spotify</a>
        </div>
    </div>
</div>

<?php include "./templates/footer.php" ?>