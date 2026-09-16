<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
} 

function isAdmin(): bool {
    return !empty($_SESSION['is_admin']);
}

// Blocks the request with 401 JSON unless an admin session is active.
function requireAdmin(): void {
    if (!isAdmin()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Login required']);
        exit;
    }
}
