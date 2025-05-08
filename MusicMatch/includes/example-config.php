<?php
    $isLocalEnvironment = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
    $_SERVER['HTTP_HOST'] === '127.0.0.1');
    
    $BASE_URL = $isLocalEnvironment ? '/' : 'BaseURL';
    $BASE_PATH = $isLocalEnvironment ? $_SERVER['DOCUMENT_ROOT'] . '/' : $_SERVER['DOCUMENT_ROOT'] . 'BasePath';
    $INCLUDE_PATH = $isLocalEnvironment ? dirname(__DIR__) . '/' : $_SERVER['DOCUMENT_ROOT'] . 'IncludePath';
    
    $CALLBACK_URL = $isLocalEnvironment 
    ? "localhost-callback-url" 
    : "server-callback-url";

    // Spotify API credentials
    $CLIENT_ID = "";
    $CLIENT_SECRET = "";
    
    // Last.fm API Key
    $LastFmApiKey = '';
    $LastFmSharedSecret = '';
?>