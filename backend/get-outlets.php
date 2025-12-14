<?php
// File: backend/get-outlets.php
require_once 'db.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, name, address, latitude, longitude, phone FROM outlets WHERE is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute();

$outlets = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'outlets' => $outlets
]);
?>