<?php
// config/database.php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->conn->exec("SET NAMES utf8mb4");
            $this->conn->exec("SET CHARACTER SET utf8mb4");

            // Self-healing migration for Notion features on notes table
            try {
                $check = $this->conn->query("SHOW COLUMNS FROM notes LIKE 'parent_id'")->fetch();
                if (!$check) {
                    $this->conn->exec("ALTER TABLE notes ADD COLUMN parent_id INT NULL DEFAULT NULL AFTER user_id");
                    $this->conn->exec("ALTER TABLE notes ADD COLUMN icon VARCHAR(100) DEFAULT NULL AFTER title");
                    $this->conn->exec("ALTER TABLE notes ADD COLUMN is_favorite TINYINT DEFAULT 0 AFTER is_pinned");
                    $this->conn->exec("ALTER TABLE notes ADD COLUMN is_archived TINYINT DEFAULT 0 AFTER is_favorite");
                    $this->conn->exec("ALTER TABLE notes ADD COLUMN is_trash TINYINT DEFAULT 0 AFTER is_archived");
                    $this->conn->exec("ALTER TABLE notes ADD FOREIGN KEY (parent_id) REFERENCES notes(id) ON DELETE SET NULL");
                    $this->conn->exec("CREATE INDEX idx_notes_parent_id ON notes(parent_id)");
                }
            } catch (PDOException $ex) {
                error_log("Database self-healing failed/skipped: " . $ex->getMessage());
            }
        } catch (PDOException $e) {
            // In a production environment, log error instead of printing
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    // Prevent cloning and deserialization
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize database singleton");
    }
}
