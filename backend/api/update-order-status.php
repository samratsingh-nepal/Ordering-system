<?php
// File: backend/api/update-order-status.php
require_once '../auth.php';
require_once '../db.php';

$auth = new Auth();
$database = new Database();
$db = $database->getConnection();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["error" => "Not authenticated"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['order_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

$order_id = $data['order_id'];
$status = $data['status'];

// Check if outlet has permission to update this order
if ($auth->isOutlet()) {
    $checkQuery = "SELECT id FROM orders WHERE id = :order_id AND outlet_id = :outlet_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":order_id", $order_id);
    $checkStmt->bindParam(":outlet_id", $_SESSION['outlet_id']);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        http_response_code(403);
        echo json_encode(["error" => "Not authorized to update this order"]);
        exit;
    }
}

$query = "UPDATE orders SET status = :status WHERE id = :order_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":status", $status);
$stmt->bindParam(":order_id", $order_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Order status updated'
    ]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to update order status"]);
}
?>