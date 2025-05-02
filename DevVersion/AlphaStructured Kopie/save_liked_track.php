<?php
require_once './includes/session_handler.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['songs']) && is_array($data['songs'])){
    $_SESSION['liked_tracks'] = $data['songs'];
} elseif (isset($data['id'])){
    if (!isset($_SESSION['liked_tracks'])) {
        $_SESSION['liked_tracks'] = [];
    }

    $exists = false;
    foreach ($_SESSION['liked_tracks'] as $track){
        if ($track['id'] === $data['id']) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $_SESSION['liked_tracks'][] = $data;
    }
}

echo json_encode(['success' => true]);