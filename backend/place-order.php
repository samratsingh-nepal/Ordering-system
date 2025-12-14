<?php
// backend/place-order.php
session_start();
require_once 'db.php';

$database = new Database();
$db = $database->getConnection();

header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log file for debugging
$logFile = __DIR__ . '/order-errors.log';

function logError($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    return $logMessage;
}

// Get POST data
$rawData = file_get_contents("php://input");
logError("Raw POST data received: " . $rawData);

$data = json_decode($rawData, true);

// Check if JSON decode failed
if (json_last_error() !== JSON_ERROR_NONE) {
    $errorMsg = 'Invalid JSON: ' . json_last_error_msg();
    logError($errorMsg);
    http_response_code(400);
    echo json_encode(["error" => $errorMsg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $errorMsg = 'Method not allowed. Expected POST, got ' . $_SERVER['REQUEST_METHOD'];
    logError($errorMsg);
    http_response_code(405);
    echo json_encode(["error" => $errorMsg]);
    exit;
}

// Validate required fields
$required = ['customer_name', 'phone', 'address', 'latitude', 'longitude', 'items', 'total_amount'];
$missing = [];

foreach ($required as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    $errorMsg = 'Missing required fields: ' . implode(', ', $missing);
    logError($errorMsg);
    http_response_code(400);
    echo json_encode(["error" => $errorMsg]);
    exit;
}

logError("Order data validated. Customer: " . $data['customer_name'] . ", Phone: " . $data['phone']);

try {
    // Function to calculate distance
    function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371;
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
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to fetch outlets: " . implode(", ", $stmt->errorInfo()));
    }
    
    $outlets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logError("Found " . count($outlets) . " active outlets");

    if (empty($outlets)) {
        throw new Exception("No active outlets found in database");
    }

    // Find nearest outlet
    $nearestOutlet = null;
    $minDistance = PHP_INT_MAX;

    foreach ($outlets as $outlet) {
        if (!isset($outlet['latitude']) || !isset($outlet['longitude'])) {
            logError("Outlet missing coordinates: " . $outlet['name']);
            continue;
        }
        
        $distance = calculateDistance(
            floatval($data['latitude']),
            floatval($data['longitude']),
            floatval($outlet['latitude']),
            floatval($outlet['longitude'])
        );
        
        logError("Distance to " . $outlet['name'] . ": " . $distance . " km");
        
        if ($distance < $minDistance) {
            $minDistance = $distance;
            $nearestOutlet = $outlet;
        }
    }

    if (!$nearestOutlet) {
        throw new Exception("Could not determine nearest outlet");
    }

    logError("Nearest outlet: " . $nearestOutlet['name'] . " (ID: " . $nearestOutlet['id'] . ")");

    // Generate order number
    $orderNumber = 'DA' . date('Ymd') . strtoupper(substr(uniqid(), -6));

    // Convert items to JSON
    $itemsJson = json_encode($data['items']);
    if ($itemsJson === false) {
        throw new Exception("Failed to encode items to JSON: " . json_last_error_msg());
    }

    // Insert order
    $query = "INSERT INTO orders (
        order_number, customer_name, phone, address, 
        latitude, longitude, outlet_id,
        items, subtotal, total_amount, payment_type, status
    ) VALUES (
        :order_number, :customer_name, :phone, :address,
        :latitude, :longitude, :outlet_id,
        :items, :subtotal, :total_amount, :payment_type, 'pending'
    )";
    
    $stmt = $db->prepare($query);
    
    $payment_type = isset($data['payment_type']) ? $data['payment_type'] : 'cash';
    
    $params = [
        ':order_number' => $orderNumber,
        ':customer_name' => trim($data['customer_name']),
        ':phone' => trim($data['phone']),
        ':address' => trim($data['address']),
        ':latitude' => floatval($data['latitude']),
        ':longitude' => floatval($data['longitude']),
        ':outlet_id' => intval($nearestOutlet['id']),
        ':items' => $itemsJson,
        ':subtotal' => floatval($data['total_amount']),
        ':total_amount' => floatval($data['total_amount']),
        ':payment_type' => $payment_type
    ];
    
    logError("Inserting order with params: " . json_encode($params));
    
    if (!$stmt->execute($params)) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database insert failed: " . implode(", ", $errorInfo));
    }
    
    $orderId = $db->lastInsertId();
    logError("Order inserted successfully. ID: $orderId, Number: $orderNumber");

    // Return success response
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'assigned_outlet' => $nearestOutlet['name'],
        'outlet_phone' => $nearestOutlet['phone'] ?? 'Not available',
        'distance_km' => round($minDistance, 2),
        'message' => 'Order placed successfully'
    ]);
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    logError("EXCEPTION: " . $errorMsg);
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to place order',
        'debug' => $errorMsg, // Remove this in production
        'suggestion' => 'Please try again or call ' . ($nearestOutlet['phone'] ?? 'the outlet') . ' directly'
    ]);
}
?>
