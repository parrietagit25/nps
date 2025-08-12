<?php
/**
 * Database Configuration for NPS Application
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $this->host = getenv('MYSQL_HOST') ?: 'mysql';
        $this->db_name = getenv('MYSQL_DATABASE') ?: 'nps_db';
        $this->username = getenv('MYSQL_USER') ?: 'nps_user';
        $this->password = getenv('MYSQL_PASSWORD') ?: 'nps_password';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4")
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            return null;
        }

        return $this->conn;
    }
}