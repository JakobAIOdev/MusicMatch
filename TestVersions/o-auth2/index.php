<?php
require 'vendor/autoload.php';
include "config.php";
session_start();

// Prüfen, ob der Benutzer angemeldet ist
$isLoggedIn = isset($_SESSION['spotify_access_token']);

// Falls angemeldet, zum Dashboard weiterleiten
if ($isLoggedIn) {
    header('Location: dashboard.php');
    exit;
}

// Falls nicht angemeldet, Anmeldeseite anzeigen
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify API Test - Anmeldung</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }
        h1 {
            color: #1DB954;
        }
        .login-btn {
            display: inline-block;
            background-color: #1DB954;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 30px;
            font-weight: bold;
            margin-top: 20px;
        }
        .login-btn:hover {
            background-color: #1ED760;
        }
    </style>
</head>
<body>
    <h1>Willkommen beim Spotify API Test</h1>
    <p>Diese Anwendung demonstriert verschiedene Funktionen der Spotify API.</p>
    <p>Um fortzufahren, melden Sie sich bitte mit Ihrem Spotify-Konto an.</p>
    <a href="login.php" class="login-btn">Mit Spotify anmelden</a>
    
    <footer style="margin-top: 40px; border-top: 1px solid #eee; padding-top: 20px;">
        <p>Diese Anwendung ist ein Testprojekt für die Spotify API. Erstellt für MMP1 Musik-Match.</p>
    </footer>
</body>
</html>
