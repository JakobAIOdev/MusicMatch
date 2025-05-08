<?php
session_start();
require_once 'config.php';

// Check if user is logged in with valid Spotify tokens
if (!isset($_SESSION['access_token'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get action and track ID
$action = $_POST['action'] ?? '';
$track_id = $_POST['track_id'] ?? '';

// Validate inputs
if (empty($action) || empty($track_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

// Check if token has expired
if (isset($_SESSION['token_expiry']) && $_SESSION['token_expiry'] <= time()) {
    // Refresh token if expired
    if (!isset($_SESSION['refresh_token'])) {
        echo json_encode(['success' => false, 'message' => 'Session expired, please log in again']);
        exit();
    }
    
    $refresh_token = $_SESSION['refresh_token'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'refresh_token',
        'refresh_token' => $refresh_token
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($CLIENT_ID . ':' . $CLIENT_SECRET)
    ]);
    
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if (isset($response['access_token'])) {
        $_SESSION['access_token'] = $response['access_token'];
        $_SESSION['token_expiry'] = time() + $response['expires_in'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to refresh session']);
        exit();
    }
}

$access_token = $_SESSION['access_token'];

// Handle the different actions
switch ($action) {
    case 'save':
        // Add track to user's library
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.spotify.com/v1/me/tracks?ids={$track_id}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$access_token}"
        ]);
        
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 || $http_code == 201) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => "API error: HTTP {$http_code}"]);
        }
        break;
        
    case 'remove':
        // Remove track from user's library
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.spotify.com/v1/me/tracks?ids={$track_id}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$access_token}"
        ]);
        
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => "API error: HTTP {$http_code}"]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
