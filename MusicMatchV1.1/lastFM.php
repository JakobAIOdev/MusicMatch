<?php
session_start();
require_once 'config.php'; // Lade die API-Keys

$apiKey = $LastFmApiKey;
$apiSecret = $LastFmSharedSecret;

// Funktion zum Abrufen der empfohlenen Songs
function getRecommendedSongs($username) {
    global $apiKey;
    $url = "https://www.last.fm/player/station/user/{$username}/recommended?page=1&ajax=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MusicMatch/1.0)');
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        return ['error' => 'Curl error: ' . curl_error($ch)];
    }
    
    curl_close($ch);
    
    // Dekodiere die JSON-Antwort
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['playlist'])) {
        return ['error' => 'Keine Empfehlungen gefunden oder Fehler beim Dekodieren der Antwort'];
    }
    
    return [
        'success' => true,
        'playlist' => $data['playlist']
    ];
}

$error = '';
$recommendations = null;
$recommendedSongsArray = [];

// Login-Verarbeitung
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    
    if (empty($username)) {
        $error = "Bitte gib deinen Last.fm Benutzernamen ein.";
    } else {
        $_SESSION['lastfm_username'] = $username;
        $_SESSION['logged_in'] = true;
        
        // Empfehlungen abrufen
        $recommendations = getRecommendedSongs($username);
        
        // Speichere die empfohlenen Songs als Objekte in einem Array
        if (isset($recommendations['success']) && isset($recommendations['playlist'])) {
            foreach ($recommendations['playlist'] as $song) {
                $artistName = '';
                $imageUrl = '';
                
                // Verarbeite Künstlername
                if (isset($song['artist']['_name'])) {
                    $artistName = $song['artist']['_name'];
                } elseif (isset($song['artist']['name'])) {
                    $artistName = $song['artist']['name'];
                } elseif (isset($song['artist']) && is_string($song['artist'])) {
                    $artistName = $song['artist'];
                }
                
                // Verarbeite Bild-URL
                if (isset($song['image']) && !empty($song['image'])) {
                    if (is_string($song['image'])) {
                        $imageUrl = $song['image'];
                    } elseif (is_array($song['image'])) {
                        // Suche nach dem größten Bild
                        foreach ($song['image'] as $img) {
                            if (isset($img['size']) && $img['size'] === 'large' && isset($img['#text'])) {
                                $imageUrl = $img['#text'];
                                break;
                            }
                        }
                    }
                }
                
                $recommendedSongsArray[] = [
                    'title' => $song['name'] ?? 'Unbekannter Titel',
                    'artist' => $artistName,
                    'image' => $imageUrl
                ];
            }
        }
    }
}

// Logout-Funktion
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: lastFM.php');
    exit;
}

// Wenn der Benutzer bereits angemeldet ist, hole Empfehlungen
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && !isset($recommendations)) {
    $recommendations = getRecommendedSongs($_SESSION['lastfm_username']);
    
    // Speichere die empfohlenen Songs als Objekte in einem Array
    if (isset($recommendations['success']) && isset($recommendations['playlist'])) {
        foreach ($recommendations['playlist'] as $song) {
            $artistName = '';
            $imageUrl = '';
            
            // Verarbeite Künstlername
            if (isset($song['artist']['_name'])) {
                $artistName = $song['artist']['_name'];
            } elseif (isset($song['artist']['name'])) {
                $artistName = $song['artist']['name'];
            } elseif (isset($song['artist']) && is_string($song['artist'])) {
                $artistName = $song['artist'];
            }
            
            // Verarbeite Bild-URL
            if (isset($song['image']) && !empty($song['image'])) {
                if (is_string($song['image'])) {
                    $imageUrl = $song['image'];
                } elseif (is_array($song['image'])) {
                    // Suche nach dem größten Bild
                    foreach ($song['image'] as $img) {
                        if (isset($img['size']) && $img['size'] === 'large' && isset($img['#text'])) {
                            $imageUrl = $img['#text'];
                            break;
                        }
                    }
                }
            }
            
            $recommendedSongsArray[] = [
                'title' => $song['name'] ?? 'Unbekannter Titel',
                'artist' => $artistName,
                'image' => $imageUrl
            ];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./style.css">
    <title>Last.fm Musik-Empfehlungen</title>
</head>
<body>
    <div class="dashboard-container">
        <div class="site-header">
            <div class="site-logo">
                <h1>MusicMatch</h1>
            </div>
            <div class="nav-user-info">
                <a href="dashboard.php" class="back-to-dashboard">Dashboard</a>
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                    <a href="lastFM.php?logout=1" class="logout-btn">Abmelden</a>
                <?php endif; ?>
            </div>
        </div>

        <h1>Last.fm Musik-Empfehlungen</h1>
        
        <?php if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
            <!-- Login-Formular -->
            <div class="login-container">
                <h2>Anmelden mit Last.fm</h2>
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form action="lastFM.php" method="post">
                    <div class="form-group">
                        <label for="username">Last.fm Benutzername:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <button type="submit" name="login" class="login-btn lastfm-login-btn">Anmelden</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Benutzer ist angemeldet -->
            <div class="lastfm-user-info">
                <p>Angemeldet als: <strong><?php echo htmlspecialchars($_SESSION['lastfm_username']); ?></strong></p>
            </div>
            
            <h2>Deine Empfehlungen</h2>
            
            <?php if (isset($recommendations['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($recommendations['error']); ?>
                </div>
            <?php elseif (isset($recommendations['success']) && isset($recommendations['playlist'])): ?>
                <div class="content-wrapper">
                    <ul class="song-list">
                        <?php foreach ($recommendations['playlist'] as $song): ?>
                            <li class="song-item card">
                                <?php 
                                $imageUrl = '';
                                if (isset($song['image']) && !empty($song['image'])) {
                                    if (is_string($song['image'])) {
                                        $imageUrl = $song['image'];
                                    } elseif (is_array($song['image'])) {
                                        // Suche nach dem größten Bild
                                        foreach ($song['image'] as $img) {
                                            if (isset($img['size']) && $img['size'] === 'large' && isset($img['#text'])) {
                                                $imageUrl = $img['#text'];
                                                break;
                                            }
                                        }
                                    }
                                }
                                ?>
                                
                                <?php if (!empty($imageUrl)): ?>
                                    <img class="song-image image" src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Album Cover">
                                <?php else: ?>
                                    <div class="song-image image default-image">
                                        <span>🎵</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="song-info item-info">
                                    <h3 class="song-title"><?php echo htmlspecialchars($song['name'] ?? 'Unbekannter Titel'); ?></h3>
                                    <p class="song-artist">
                                        <?php 
                                        if (isset($song['artist']['_name'])) {
                                            echo htmlspecialchars($song['artist']['_name']);
                                        } elseif (isset($song['artist']['name'])) {
                                            echo htmlspecialchars($song['artist']['name']);
                                        } elseif (isset($song['artist']) && is_string($song['artist'])) {
                                            echo htmlspecialchars($song['artist']);
                                        } else {
                                            echo 'Unbekannter Künstler';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p>Keine Empfehlungen gefunden. Versuche es später noch einmal.</p>
            <?php endif; ?>
        <?php endif; ?>
        
        <script>
        // Hier können wir das Array mit den empfohlenen Songs im Browser-Speicher ablegen
        const recommendedSongs = <?php echo json_encode($recommendedSongsArray); ?>;
        console.log('Empfohlene Songs:', recommendedSongs);
        </script>
    </div>
</body>
<?php include "footer.php"; ?>
</html>
