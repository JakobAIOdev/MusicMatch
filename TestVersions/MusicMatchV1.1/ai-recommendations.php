<?php
use GeminiAPI\Client;
use GeminiAPI\Resources\Parts\TextPart;
use SpotifyWebAPI\SpotifyWebAPI;

require 'vendor/autoload.php';
include "config.php";
session_start();

if (!isset($_SESSION['spotify_access_token'])) {
    header('Location: index.php');
    exit;
}

// Function to generate fallback recommendations based on user's favorite songs
function generateFallbackRecommendations($favoriteSongs) {
    $recommendations = [];
    $artists = [];
    
    // Extract unique artists from favorite songs
    foreach ($favoriteSongs as $song) {
        if (!in_array($song['artist'], $artists)) {
            $artists[] = $song['artist'];
        }
    }
    
    // Simple recommendation algorithm: suggest songs with similar names/artists
    $songTemplates = [
        'Midnight' => ['Dream', 'Journey', 'Drive', 'City', 'Memories'],
        'Love' => ['Story', 'Again', 'Song', 'Life', 'Forever'],
        'Summer' => ['Nights', 'Days', 'Feeling', 'Breeze', 'Vibes'],
        'Lost' => ['In Time', 'Without You', 'In Music', 'Together', 'In Dreams'],
        'New' => ['Beginning', 'Day', 'Life', 'Chapter', 'Experience']
    ];
    
    // Generate recommendations combining artists with song templates
    $count = 0;
    foreach ($artists as $artist) {
        if ($count >= 10) break;
        
        $baseName = array_rand($songTemplates);
        $suffix = $songTemplates[$baseName][array_rand($songTemplates[$baseName])];
        $recommendations[] = "$baseName $suffix by $artist";
        $count++;
    }
    
    // If we need more recommendations, add some generic ones
    $genericArtists = ['The Weekend', 'Taylor Swift', 'Post Malone', 'Drake', 'Billie Eilish', 
                      'Dua Lipa', 'Ed Sheeran', 'Harry Styles', 'Ariana Grande', 'Bad Bunny'];
    
    while ($count < 10) {
        $baseName = array_rand($songTemplates);
        $suffix = $songTemplates[$baseName][array_rand($songTemplates[$baseName])];
        $artist = $genericArtists[array_rand($genericArtists)];
        $recommendations[] = "$baseName $suffix by $artist";
        $count++;
    }
    
    return $recommendations;
}

$api = new SpotifyWebAPI();
$api->setAccessToken($_SESSION['spotify_access_token']);

try {
    // Get user data for display
    $userData = $api->me();
    
    // Get only top tracks from the last 4 weeks
    $topTracks = $api->getMyTop('tracks', ['limit' => 50, 'time_range' => 'short_term']); // short_term is approximately last 4 weeks

    // Initialize array to store favorite songs
    $favoriteSongs = [];

    // Add top tracks to favorites (only tracks from the last 4 weeks)
    foreach ($topTracks->items as $track) {
        $songName = $track->name;
        $artistName = $track->artists[0]->name;
        
        // Create a unique key to avoid duplicates
        $key = $songName . ' - ' . $artistName;
        $favoriteSongs[$key] = [
            'name' => $songName,
            'artist' => $artistName
        ];
    }

    // Convert associative array to indexed array
    $favoriteSongs = array_values($favoriteSongs);
    
    $recommendedSongs = [];
    $errorMessage = '';
    $usingFallback = false;
    
    // Only make API request if we have favorite songs
    if (count($favoriteSongs) > 0) {
        try {
            $client = new Client($GeminiApiKey);
            $prompt = "Based on these favorite songs, recommend 10 similar tracks that the user might enjoy. " .
                      "Format your response as a numbered list with each line containing only 'SONG_NAME by ARTIST_NAME'. " .
                      "Focus on musical similarity and artist style. Don't include any additional text.";
            
            // Use direct model string instead of constant
            $response = $client->generativeModel('gemini-pro')->generateContent(
                new TextPart($prompt),
                new TextPart('Favorite songs: ' . implode(', ', array_map(function($song) {
                    return $song['name'] . ' by ' . $song['artist'];
                }, array_slice($favoriteSongs, 0, 15)))) // Limit to 15 songs to prevent huge requests
            );

            // Get the response text and clean it up
            $recommendations = $response->text();
            if ($recommendations) {
                $recommendedSongs = explode("\n", trim($recommendations));
                // Remove any numbers or bullets at the beginning of each line
                foreach ($recommendedSongs as $key => $song) {
                    $recommendedSongs[$key] = preg_replace('/^\d+[\.\)\-]\s*/', '', trim($song));
                }
            }
        } catch (Exception $e) {
            // Log the error for debugging
            error_log("Gemini API Error: " . $e->getMessage());
            
            // Use fallback recommendations instead
            $usingFallback = true;
            $recommendedSongs = generateFallbackRecommendations($favoriteSongs);
            
            // Add a note about using fallback
            $errorMessage = "AI recommendations are currently unavailable. Showing alternative suggestions based on your listening history.";
        }
    } else {
        $errorMessage = "No favorite songs found. Listen to more music and check back later!";
    }
} catch (Exception $e) {
    $errorMessage = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Music Recommendations</title>
    <link rel="stylesheet" type="text/css" href="./style.css">
    <style>
        .recommendations-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .recommendation-item {
            background-color: #2a2a2a;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .recommendation-number {
            font-size: 24px;
            font-weight: bold;
            margin-right: 15px;
            color: #1DB954;
            min-width: 30px;
        }
        .recommendation-info {
            flex-grow: 1;
        }
        .error-message {
            background-color: rgba(255, 0, 0, 0.1);
            color: #ff6b6b;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-message {
            background-color: rgba(30, 144, 255, 0.1);
            color: #1e90ff;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
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
                <span><?php echo htmlspecialchars($userData->display_name ?? 'User'); ?></span>
            </a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="recommendations-container">
        <h1>Music Recommendations</h1>
        <p>Based on your listening history from the last 4 weeks, we think you might enjoy these songs:</p>
        
        <?php if ($usingFallback): ?>
            <div class="info-message">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php elseif (!empty($errorMessage)): ?>
            <div class="error-message">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($recommendedSongs)): ?>
            <div class="recommendations-list">
                <?php foreach ($recommendedSongs as $index => $song): ?>
                    <div class="recommendation-item">
                        <div class="recommendation-number"><?php echo ($index + 1); ?></div>
                        <div class="recommendation-info">
                            <h3><?php echo htmlspecialchars($song); ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (empty($errorMessage)): ?>
            <p>No recommendations available at this time. Please try again later.</p>
        <?php endif; ?>
    </div>
    
    <?php include "footer.php"; ?>
</body>
</html>
