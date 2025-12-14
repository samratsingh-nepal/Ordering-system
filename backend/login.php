<?php
// File: backend/login.php
require_once 'auth.php';

$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(["error" => "Username and password required"]);
        exit;
    }
    
    $result = $auth->login($data['username'], $data['password']);
    echo json_encode($result);
    
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['logout'])) {
    $result = $auth->logout();
    echo json_encode($result);
    
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
?>