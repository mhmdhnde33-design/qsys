<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // اقرأ متغيرات البيئة أو استخدم القيم الافتراضية
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('MYSQL_DATABASE') ?: 'queuing_system';
        $this->username = getenv('MYSQL_USER') ?: 'root';
        $this->password = getenv('MYSQL_PASSWORD') ?: '';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch(PDOException $exception) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $exception->getMessage()]);
            exit;
        }
        return $this->conn;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
