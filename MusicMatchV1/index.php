<?php
require 'vendor/autoload.php';
include "config.php";
session_start();

$isLoggedIn = isset($_SESSION['spotify_access_token']);

if ($isLoggedIn) {
    header('Location: dashboard.php');
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./style.css">
    <title>MusicMatch</title>
</head>
<body>
    <h1>Welcome to MusicMatch</h1>
    <p>Enhance your playlists by swiping.</p>
    <p>Login with Spotify to continue.</p>
    <a href="login.php" class="login-btn">Login with Spotify</a>
</body>
<?php
include "footer.php";
?>