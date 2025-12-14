<?php
// File: backend/db.php
session_start();
header('Content-Type: application/json');

class Database {
    private $host = "localhost";
    private $db_name = "da_aloo_orders";
    private $username = "YOUR_DB_USERNAME";
    private $password = "YOUR_DB_PASSWORD";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            http_response_code(500);
            echo json_encode(["error" => "Database connection failed"]);
            exit();
        }
        return $this->conn;
    }
}
?>