<?php
require_once '../includes/session_handler.php';

if (isset($_SESSION['liked_tracks'])) {
    unset($_SESSION['liked_tracks']);
}

if (isset($_SESSION['seen_tracks_random'])) {
    unset($_SESSION['seen_tracks_random']);
}
if (isset($_SESSION['seen_tracks_short_term'])) {
    unset($_SESSION['seen_tracks_short_term']);
}
if (isset($_SESSION['seen_tracks_medium_term'])) {
    unset($_SESSION['seen_tracks_medium_term']);
}
if (isset($_SESSION['seen_tracks_long_term'])) {
    unset($_SESSION['seen_tracks_long_term']);
}

session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'You have been logged out']);
    exit;
}

header('Location: ../index.php');
exit;