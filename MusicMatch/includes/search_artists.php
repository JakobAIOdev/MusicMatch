<?php
require_once '../includes/session_handler.php';
require_once '../vendor/autoload.php';
require_once '../includes/spotify_utils.php';
require_once '../includes/config.php';

if (!isset($_SESSION['spotify_access_token'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

if (!isset($_GET['q']) || empty($_GET['q'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Query parameter is required']);
    exit;
}

$api = getSpotifyAPI();
$query = $_GET['q'];

try {
    $result = $api->search($query, 'artist', ['limit' => 5]);
    
    $artists = [];
    if (!empty($result->artists->items)) {
        foreach ($result->artists->items as $artist) {
            $artists[] = [
                'id' => $artist->id,
                'name' => $artist->name,
                'image' => !empty($artist->images) ? $artist->images[0]->url : null,
                'popularity' => $artist->popularity
            ];
        }
    }
    
    echo json_encode(['artists' => $artists]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}