<?php
    require 'vendor/autoload.php';
    session_start();

    if(!isset($_SESSION['spotify_access_token'])) {
        header('Location: login.php');
        die();
    }

    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($_SESSION['spotify_access_token']);

    try{
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
    <link rel="stylesheet" href="./Style/style.css">
    <title>Spotify Profile</title>
</head>
<body class="profile-page">
    <h1>Your Spotify Profile</h1>
    <div class="profile">
        <?php if (isset($me->images[0]->url)): ?>
            <img src="<?php echo htmlspecialchars($me->images[0]->url); ?>" class="profile-img">
        <?php endif; ?>
        
        <div>
            <h2><?php echo htmlspecialchars($me->display_name); ?></h2>
            <p>E-Mail: <?php echo htmlspecialchars($me->email); ?></p>
            <p>Spotify ID: <?php echo htmlspecialchars($me->id); ?></p>
            <p>Land: <?php echo htmlspecialchars($me->country); ?></p>
        </div>
    </div>
</body>
</html>