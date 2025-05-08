<?php
// Start session at the very beginning
session_start();

$pageTitle = "Discover Your Perfect Music Match";
$additionalCSS = '<link rel="stylesheet" href="./styles/landing-page.css">';

require_once 'vendor/autoload.php';
include_once "config.php";


if(isset($_SESSION['spotify_access_token'])) {
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($_SESSION['spotify_access_token']);
    try {
        $me = $api->me();
        if (!isset($_SESSION['userData']) || empty($_SESSION['userData'])) {
            $_SESSION['userData'] = [
                'id' => $me->id,
                'display_name' => $me->display_name,
                'email' => $me->email,
                'images' => $me->images
            ];
        }
        error_log('User validated in index: ' . $_SESSION['userData']['display_name']);
    } catch (Exception $e) {
        error_log('API Error in index: ' . $e->getMessage());
        unset($_SESSION['userData']);
        unset($_SESSION['spotify_access_token']);
    }
}