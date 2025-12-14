<?php
// File: backend/place-order.php
require_once 'db.php';

$database = new Database();
$db = $database->getConnection();

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$required = ['customer_name', 'phone', 'address', 'latitude', 'longitude', 'items', 'total_amount'];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required field: $field"]);
        exit;
    }
}

// Function to calculate distance
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // in kilometers
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + 
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}

// Get all active outlets
$query = "SELECT * FROM outlets WHERE is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$outlets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Find nearest outlet
$nearestOutlet = null;
$minDistance = PHP_INT_MAX;

foreach ($outlets as $outlet) {
    $distance = calculateDistance(
        $data['latitude'],
        $data['longitude'],
        $outlet['latitude'],
        $outlet['longitude']
    );
    
    if ($distance < $minDistance) {
        $minDistance = $distance;
        $nearestOutlet = $outlet;
    }
}

if (!$nearestOutlet) {
    http_response_code(500);
    echo json_encode(["error" => "No outlet available"]);
    exit;
}

// Generate order number
$orderNumber = 'DA' . date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

// Insert order
$query = "INSERT INTO orders (
    order_number, customer_name, phone, address, 
    latitude, longitude, outlet_id, assigned_outlet_id,
    items, subtotal, total_amount, payment_type, status
) VALUES (
    :order_number, :customer_name, :phone, :address,
    :latitude, :longitude, :outlet_id, :assigned_outlet_id,
    :items, :subtotal, :total_amount, :payment_type, 'pending'
)";

$stmt = $db->prepare($query);
$stmt->bindParam(":order_number", $orderNumber);
$stmt->bindParam(":customer_name", $data['customer_name']);
$stmt->bindParam(":phone", $data['phone']);
$stmt->bindParam(":address", $data['address']);
$stmt->bindParam(":latitude", $data['latitude']);
$stmt->bindParam(":longitude", $data['longitude']);
$stmt->bindParam(":outlet_id", $nearestOutlet['id']);
$stmt->bindParam(":assigned_outlet_id", $nearestOutlet['id']);
$stmt->bindParam(":items", json_encode($data['items']));
$stmt->bindParam(":subtotal", $data['total_amount']);
$stmt->bindParam(":total_amount", $data['total_amount']);
$payment_type = isset($data['payment_type']) ? $data['payment_type'] : 'cash';
$stmt->bindParam(":payment_type", $payment_type);

if ($stmt->execute()) {
    $orderId = $db->lastInsertId();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'assigned_outlet' => $nearestOutlet['name'],
        'outlet_phone' => $nearestOutlet['phone'],
        'message' => 'Order placed successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to place order"]);
}
?>