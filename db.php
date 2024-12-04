<?php
class Database {
    private $host = "localhost";
    private $db_name = "timetable_planner";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            $this->sendResponse(500, 'Connection error: ' . $exception->getMessage());
        }
        return $this->conn;
    }

    // Unified response function
    public function sendResponse($status_code, $message, $data = null) {
        header("Content-Type: application/json");
        http_response_code($status_code);
        echo json_encode([
            'success' => $status_code === 200,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}
?>
