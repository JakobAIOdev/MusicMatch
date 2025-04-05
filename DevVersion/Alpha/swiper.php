<?php
$pageTitle = "Discover Your Perfect Music Match";
$additionalCSS = '<link rel="stylesheet" href="./styles/landing-page.css">';

require_once 'vendor/autoload.php';
include_once "config.php";
require_once 'spotify_utils.php';

// Get API instance with fresh token
$api = getSpotifyAPI();

if ($api) {
    try {
        // Get user profile to check subscription status
        $currentUser = $api->me();
        
        // Check if user has Premium subscription
        if ($currentUser->product !== 'premium') {
            // User doesn't have Premium
            include "header.php";
            echo '<div class="container mt-5 text-center">';
            echo '<h2>Premium Required</h2>';
            echo '<p>This feature requires a Spotify Premium subscription.</p>';
            echo '<p>Please upgrade your Spotify account to access this feature.</p>';
            echo '<a href="https://www.spotify.com/premium/" class="btn btn-success mt-3" target="_blank">Upgrade to Premium</a>';
            echo '</div>';
            include "footer.php";
            exit;
        }
        
        // If user has Premium, proceed with normal operation
        $userPlaylists = $api->getUserPlaylists($_SESSION['userData']['id']);
        // Process data...
    } catch (Exception $e) {
        error_log('Spotify API Error: ' . $e->getMessage());
        header('Location: login.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}

include "header.php";
?>

<?php
include "footer.php";
?>