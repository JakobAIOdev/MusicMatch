<?php
require_once './includes/session_handler.php';

if (isset($_SESSION['seen_track_ids'])) {
    $_SESSION['seen_track_ids'] = [];
}
$queryString = $_SERVER['QUERY_STRING'] ?? '';
header('Location: swiper.php' . (!empty($queryString) ? '?' . $queryString : ''));
exit;
?>