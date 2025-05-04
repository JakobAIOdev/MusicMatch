<?php
require_once '../includes/session_handler.php';

destroySession();

header('Location: ../index.php');
exit;