<?php
    $isLocalEnvironment = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
    $_SERVER['HTTP_HOST'] === '127.0.0.1');
    
    $BASE_URL = $isLocalEnvironment ? '/' : ''; // Base URL for server environment
    $BASE_PATH = $isLocalEnvironment ? $_SERVER['DOCUMENT_ROOT'] . '/' : $_SERVER['DOCUMENT_ROOT'] . ''; // Base path for server environment
    $INCLUDE_PATH = $isLocalEnvironment ? dirname(__DIR__) . '/' : $_SERVER['DOCUMENT_ROOT'] . ''; // Include path for server environment
    
    $CALLBACK_URL = $isLocalEnvironment 
    ? "" // Callback URL for local environment 
    : ""; // Callback URL for server environment

    // Spotify API credentials
    $CLIENT_ID = "";
    $CLIENT_SECRET = "";
    

    define('SPOTIFY_DEV_MODE', true);
?>