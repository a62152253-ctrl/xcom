<?php
// Replace database instance to use SQLite
class Database {
    private static $instance = null;
    private $conn = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        if ($this->conn === null) {
            $db_file = __DIR__ . '/mock_db.sqlite';
            $this->conn = new PDO('sqlite:' . $db_file);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Setup minimum schema
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, email TEXT, status TEXT);
                CREATE TABLE IF NOT EXISTS projects (id INTEGER PRIMARY KEY, name TEXT);
                CREATE TABLE IF NOT EXISTS project_members (id INTEGER PRIMARY KEY, project_id INTEGER, user_id INTEGER);
                CREATE TABLE IF NOT EXISTS notifications (id INTEGER PRIMARY KEY, user_id INTEGER, is_read INTEGER);
            ");

            // Insert mock user if not exists
            $stmt = $this->conn->query("SELECT COUNT(*) FROM users");
            if ($stmt->fetchColumn() == 0) {
                $this->conn->exec("INSERT INTO users (id, email, status) VALUES (1, 'test@example.com', 'Active')");
            }
        }
        return $this->conn;
    }
}
