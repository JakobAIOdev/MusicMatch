<?php
session_start();

// Lösche alle Spotify-bezogenen Session-Variablen
unset($_SESSION['spotify_access_token']);
unset($_SESSION['spotify_refresh_token']);
unset($_SESSION['spotify_token_expires']);
unset($_SESSION['spotify_auth_state']);

// Zurück zur Startseite
header('Location: index.php');
exit;
?>      