<?php
$pageTitle = "Music Swiper";
require_once './includes/session_handler.php';
include "./templates/header.php";
require_once './vendor/autoload.php';
require_once './includes/spotify_utils.php';
require_once './templates/components/premium_notice.php';

if (!isLoggedIn()) {
    header('Location: /auth/login.php');
    exit;
}

$api = getSpotifyApi();
$me = $api->me();

$hasPremium = ($me->product === 'premium');

if (!$hasPremium) {
    showPremiumNotice("Erweiterte Musikanalyse");
} else {

}

include "./templates/footer.php";
?>