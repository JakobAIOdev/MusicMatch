<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/api.php';

// Überprüfen, ob der Benutzer angemeldet ist
if (!isAuthenticated()) {
    header('Location: index.php');
    exit;
}

// Benutzerprofil abrufen
$profile = getUserProfile();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musik-Match - Profil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
        }
        nav a {
            margin-left: 15px;
            text-decoration: none;
            color: #666;
            font-weight: 500;
        }
        nav a.active, nav a:hover {
            color: #1DB954;
        }
        .profile-section {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .profile-card {
            display: flex;
            align-items: center;
            margin-top: 20px;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 30px;
        }
        .profile-image-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #1DB954;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 60px;
            margin-right: 30px;
        }
        .profile-info h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .profile-info p {
            margin-bottom: 5px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Musik-Match</h1>
            <nav>
                <a href="profile.php" class="active">Profil</a>
                <a href="discover.php">Entdecken</a>
                <a href="index.php?logout=1">Abmelden</a>
            </nav>
        </header>
        
        <main>
            <section class="profile-section">
                <h2>Dein Profil</h2>
                
                <?php if ($profile): ?>
                <div class="profile-card">
                    <?php if (isset($profile['images']) && count($profile['images']) > 0): ?>
                    <img class="profile-image" src="<?php echo $profile['images'][0]['url']; ?>" alt="Profilbild">
                    <?php else: ?>
                    <div class="profile-image-placeholder">
                        <span><?php echo substr($profile['display_name'], 0, 1); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="profile-info">
                        <h3><?php echo $profile['display_name']; ?></h3>
                        <p>Spotify ID: <?php echo $profile['id']; ?></p>
                        <?php if (isset($profile['email'])): ?>
                        <p>E-Mail: <?php echo $profile['email']; ?></p>
                        <?php endif; ?>
                        <p>Land: <?php echo isset($profile['country']) ? $profile['country'] : 'Nicht angegeben'; ?></p>
                        <p>Follower: <?php echo isset($profile['followers']['total']) ? $profile['followers']['total'] : '0'; ?></p>
                    </div>
                </div>
                <?php else: ?>
                <p>Fehler beim Laden des Profils. Bitte versuche es später erneut.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
