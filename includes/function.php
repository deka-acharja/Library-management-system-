<?php
function add_notification($message, $type = 'info') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['notifications'])) {
        $_SESSION['notifications'] = [];
    }

    $_SESSION['notifications'][] = [
        'message' => $message,
        'type' => $type,
        'time' => date('Y-m-d H:i:s')
    ];
}
