<?php

class Database {
    private $host;
    private $db;
    private $user;
    private $password;
    private $charset;
    private $conn;

    public function __construct() {
        // Prefer central config constants if available (bootstrap.php -> config/app.config.php)
        $this->host = defined('DB_HOST') ? DB_HOST : "localhost";
        $this->db = defined('DB_NAME') ? DB_NAME : "attendance-system";
        $this->user = defined('DB_USER') ? DB_USER : "root";
        $this->password = defined('DB_PASS') ? DB_PASS : "";
        $this->charset = defined('DB_CHARSET') ? DB_CHARSET : "utf8mb4";
    }

    public function connect() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->user, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }

        return $this->conn;
    }
}
