<?php
class Database {
    private $host = "localhost";
    private $db_name = "jwt_auth"; // Make sure this matches your DB name in phpMyAdmin
    private $username = "root";
    private $password = ""; // XAMPP default is empty
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
        } catch(PDOException $exception) {
            // This will help us see if the DB is the problem
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>