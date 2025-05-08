<?php
require 'vendor/autoload.php';
include 'config.php';
session_start();

// check if logged in
$isLoggedIn = isset($_SESSION['spotify_access_token']);

// if logged in -> dashboard redirect
if ($isLoggedIn) {
    header('Location: dashboard.php');
    exit;
}

// if not logged in -> index
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./Style/style.css">
    <title>MusicMatch</title>
</head>
<body>
    <h1>Welcome to MusicMatch</h1>
    <p>Enhance your playlist by swiping</p>
    <p>Log into your Spotify account to continue</p>
    <a href="login.php" class="login-btn">Login with Spotify</a>
    
    <footer>
        <p>This application is created as part of MMP1 Musik-Match.</p>
    </footer>
</body>
</html>