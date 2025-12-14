<?php
// File: backend/check-auth.php
require_once 'auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["error" => "Not authenticated"]);
    exit;
}

// Return user info
echo json_encode([
    'authenticated' => true,
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'user_type' => $_SESSION['user_type'],
    'outlet_id' => $_SESSION['outlet_id'],
    'outlet_name' => $_SESSION['outlet_name']
]);
?>