<?php
// File: backend/api/get-orders.php
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

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$outlet_id = isset($_GET['outlet_id']) ? intval($_GET['outlet_id']) : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

$query = "SELECT o.*, ot.name as outlet_name FROM orders o 
          LEFT JOIN outlets ot ON o.outlet_id = ot.id 
          WHERE 1=1";
          
$params = [];

if ($auth->isOutlet()) {
    $query .= " AND o.outlet_id = :outlet_id";
    $params[':outlet_id'] = $_SESSION['outlet_id'];
} elseif ($outlet_id) {
    $query .= " AND o.outlet_id = :outlet_id";
    $params[':outlet_id'] = $outlet_id;
}

if ($status) {
    $query .= " AND o.status = :status";
    $params[':status'] = $status;
}

$query .= " ORDER BY o.created_at DESC LIMIT :limit";

$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'orders' => $orders
]);
?>